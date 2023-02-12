<?php

namespace DEVizzent\CodeceptionMockServerHelper;

use Codeception\Lib\ModuleContainer;
use Codeception\Module;
use Codeception\TestInterface;
use DEVizzent\CodeceptionMockServerHelper\Config\CleanUpBefore;
use DEVizzent\CodeceptionMockServerHelper\Config\NotMatchedRequest;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\ExpectationFailedException;

class MockServerHelper extends Module
{
    private const CONFIG_NOT_MATCHED_REQUEST = 'notMatchedRequest';
    private const CONFIG_URL = 'url';
    private const CONFIG_CLEANUP_BEFORE = 'cleanupBefore';
    public const NOT_MATCHED_REQUEST_ID = 'not-matched-request';
    private Client $mockserverClient;
    private CleanUpBefore $cleanUpBefore;
    private NotMatchedRequest $notMatchedRequest;
    /** @param array<string, string>|null $config */
    public function __construct(ModuleContainer $moduleContainer, ?array $config = null)
    {
        $this->requiredFields = [self::CONFIG_URL];
        $this->cleanUpBefore = new CleanUpBefore(CleanUpBefore::TEST);
        $this->notMatchedRequest = new NotMatchedRequest(NotMatchedRequest::ENABLED);
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
        $this->mockserverClient = new Client([
            'base_uri'  => $this->config[self::CONFIG_URL]
        ]);
        if ($this->notMatchedRequest->isEnabled()) {
            $expectationJson = file_get_contents(__DIR__ . '/not-matched-request.json');
            Assert::assertIsString($expectationJson);
            $this->createMockRequest($expectationJson);
        }
    }

    public function _beforeSuite(array $settings = []): void
    {
        parent::_beforeSuite($settings);
        if ($this->cleanUpBefore->isSuite()) {
            $this->clearMockServerLogs();
        }
    }

    public function _before(TestInterface $test): void
    {
        parent::_before($test);
        if ($this->cleanUpBefore->isTest()) {
            $this->clearMockServerLogs();
        }
    }

    public function seeMockRequestWasCalled(string $expectationId, ?int $times = null): void
    {
        $body = json_encode([
            'expectationId' => ['id' => $expectationId],
            'times' => ['atLeast' => $times ?? 1, 'atMost' => $times ?? 1000]
        ]);
        Assert::assertNotFalse($body);
        $request = new Request('PUT', '/mockserver/verify', [], $body);
        $response = $this->mockserverClient->sendRequest($request);
        Assert::assertEquals(
            202,
            $response->getStatusCode(),
            $response->getBody()->getContents()
        );
    }

    public function seeMockRequestWasNotCalled(string $expectationId): void
    {
        $this->seeMockRequestWasCalled($expectationId, 0);
    }

    public function seeAllRequestWereMatched(): void
    {
        if (!$this->notMatchedRequest->isEnabled()) {
            throw new ExpectationFailedException(
                '\'seeAllRequestWereMatched\' can\'t be used without enable notMatchedRequest in config.'
            );
        }
        try {
            $this->seeMockRequestWasCalled(self::NOT_MATCHED_REQUEST_ID, 0);
        } catch (ExpectationFailedException $exception) {
            $message = 'REQUEST NOT MATCHED' . strstr($exception->getMessage(), ' was:');
            throw new ExpectationFailedException($message);
        }
    }

    public function createMockRequest(string $json): void
    {
        $request = new Request(
            'PUT',
            '/mockserver/expectation',
            ['Content-Type' => 'application/json'],
            $json
        );
        $response = $this->mockserverClient->sendRequest($request);
        Assert::assertEquals(
            201,
            $response->getStatusCode(),
            $response->getBody()->getContents()
        );
    }

    public function clearMockServerLogs(): void
    {
        $request = new Request('PUT', '/mockserver/clear?type=log');
        $response = $this->mockserverClient->sendRequest($request);
        Assert::assertEquals(
            200,
            $response->getStatusCode(),
            $response->getBody()->getContents()
        );
    }

    public function deactivateNotMatchedRequest(): void
    {
        $body = json_encode([
            'id' => self::NOT_MATCHED_REQUEST_ID
        ]);
        Assert::assertIsString($body);
        $request = new Request('PUT', '/mockserver/clear?type=expectations', [], $body);
        $response = $this->mockserverClient->sendRequest($request);
        Assert::assertEquals(
            200,
            $response->getStatusCode(),
            $response->getBody()->getContents()
        );
    }
}
