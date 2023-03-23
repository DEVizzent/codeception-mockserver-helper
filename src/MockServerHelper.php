<?php

namespace DEVizzent\CodeceptionMockServerHelper;

use Codeception\Lib\ModuleContainer;
use Codeception\Module;
use Codeception\TestInterface;
use DEVizzent\CodeceptionMockServerHelper\Client\MockServer;
use DEVizzent\CodeceptionMockServerHelper\Config\CleanUpBefore;
use DEVizzent\CodeceptionMockServerHelper\Config\ExpectationsPath;
use DEVizzent\CodeceptionMockServerHelper\Config\NotMatchedRequest;
use GuzzleHttp\Client;
use Jfcherng\Diff\DiffHelper;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\ExpectationFailedException;

class MockServerHelper extends Module
{
    private const CONFIG_NOT_MATCHED_REQUEST = 'notMatchedRequest';
    private const CONFIG_URL = 'url';
    private const CONFIG_CLEANUP_BEFORE = 'cleanupBefore';
    private const CONFIG_EXPECTATIONS_PATH = 'expectationsPath';
    public const NOT_MATCHED_REQUEST_ID = 'not-matched-request';
    private MockServer $mockserver;
    private CleanUpBefore $cleanUpBefore;
    private NotMatchedRequest $notMatchedRequest;
    private ExpectationsPath $expectationPath;

    /** @param array<string, string>|null $config */
    public function __construct(ModuleContainer $moduleContainer, ?array $config = null)
    {
        $this->requiredFields = [self::CONFIG_URL];
        $this->cleanUpBefore = new CleanUpBefore(CleanUpBefore::TEST);
        $this->notMatchedRequest = new NotMatchedRequest(NotMatchedRequest::ENABLED);
        $this->expectationPath = new ExpectationsPath();
        parent::__construct($moduleContainer, $config);
    }


    public function _initialize(): void
    {
        parent::_initialize();
        if (is_string($this->config[self::CONFIG_NOT_MATCHED_REQUEST] ?? null)) {
            $this->notMatchedRequest = new NotMatchedRequest($this->config[self::CONFIG_NOT_MATCHED_REQUEST]);
        }
        if (is_string($this->config[self::CONFIG_CLEANUP_BEFORE] ?? null)) {
            $this->cleanUpBefore = new CleanUpBefore($this->config[self::CONFIG_CLEANUP_BEFORE]);
        }
        if (is_string($this->config[self::CONFIG_EXPECTATIONS_PATH] ?? null)) {
            $this->expectationPath = new ExpectationsPath($this->config[self::CONFIG_EXPECTATIONS_PATH]);
        }
        $this->mockserver = new MockServer(new Client([
            'base_uri' => $this->config[self::CONFIG_URL]
        ]));
        if ($this->notMatchedRequest->isEnabled()) {
            $this->createMockRequestFromJsonFile(__DIR__ . '/not-matched-request.json');
            return;
        }

        try {
            $this->deactivateNotMatchedRequest();
        } catch (AssertionFailedError $exception) {
            return;
        }
    }

    public function _beforeSuite($settings = []): void
    {
        parent::_beforeSuite($settings);
        if ($this->cleanUpBefore->isSuite()) {
            $this->mockserver->clearLogs();
        }
        foreach ($this->expectationPath->getExpectationsFiles() as $expectationFile) {
            $this->createMockRequestFromJsonFile($expectationFile);
        }
    }

    public function _before(TestInterface $test): void
    {
        parent::_before($test);
        if ($this->cleanUpBefore->isTest()) {
            $this->mockserver->clearLogs();
        }
    }

    public function seeMockRequestWasCalled(string $expectationId, ?int $times = null): void
    {
        try {
            $this->mockserver->verify($expectationId, $times);
        } catch (AssertionFailedError $exception) {
            //throw $exception;
            preg_match('#(.|\n)* expected:<(?<expected>\{(.|\n)*\})> but was:<(.|\n)*>#', $exception->getMessage(), $matches);
            if (empty($matches)) {
                throw $exception;
            }
            $expected = json_decode($matches['expected'], true);
            $notMatchedRequests = $this->mockserver->getNotMatchedRequests();
            $currentSimilityRatio = 0;
            $expectedFormatted = $this->formatMockServerRequest($expected);
            foreach ($notMatchedRequests as $notMatchedRequest) {
                $diff = DiffHelper::calculate($expectedFormatted, $this->formatMockServerRequest($notMatchedRequest));
                $statistics = DiffHelper::getStatistics();
                $similityRatio = $statistics['unmodified'] - $statistics['inserted'] - $statistics['deleted'];
                if ($currentSimilityRatio < $similityRatio) {
                    $currentSimilityRatio = $similityRatio;
                    $bestDiff = $diff;
                }
            }
            throw new AssertionFailedError('Impossible match request: '. PHP_EOL . $bestDiff);
        }
    }

    public function seeMockRequestWasNotCalled(string $expectationId): void
    {
        $this->mockserver->verify($expectationId, 0);
    }

    public function seeAllRequestWereMatched(): void
    {
        if (!$this->notMatchedRequest->isEnabled()) {
            throw new ExpectationFailedException(
                '\'seeAllRequestWereMatched\' can\'t be used without enable notMatchedRequest in config.'
            );
        }
        try {
            $this->mockserver->verify(self::NOT_MATCHED_REQUEST_ID, 0);
        } catch (ExpectationFailedException $exception) {
            $message = 'REQUEST NOT MATCHED' . strstr($exception->getMessage(), ' was:');
            throw new ExpectationFailedException($message);
        }
    }

    public function createMockRequest(string $json): void
    {
        $this->mockserver->create($json);
    }

    public function removeMockRequest(string $mockRequestId): void
    {
        $this->mockserver->removeById($mockRequestId);
    }

    public function removeAllMockRequest(): void
    {
        $this->mockserver->removeAllExpectations();
    }

    public function clearMockServerLogs(): void
    {
        $this->mockserver->clearLogs();
    }

    public function deactivateNotMatchedRequest(): void
    {
        $this->mockserver->removeById(self::NOT_MATCHED_REQUEST_ID);
    }

    public function createMockRequestFromJsonFile(string $expectationFile): void
    {
        $expectationJson = file_get_contents($expectationFile);
        Assert::assertIsString($expectationJson);
        $this->createMockRequest($expectationJson);
    }

    public function formatMockServerRequest(array $mockServerRequest): string
    {
        ksort($mockServerRequest);
        return json_encode($mockServerRequest, JSON_PRETTY_PRINT);
    }
}
