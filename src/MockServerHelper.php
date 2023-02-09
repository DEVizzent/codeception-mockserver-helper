<?php

namespace DEVizzent\CodeceptionMockServerHelper;

use Codeception\Lib\ModuleContainer;
use Codeception\Module;
use Codeception\TestInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\Assert;

class MockServerHelper extends Module
{
    const CONFIG_NOT_MATCHED_REQUEST = 'not-matched-request';
    const NOT_MATCHED_REQUEST_ACTIVATED = 'activated';
    const CONFIG_URL = 'url';
    private Client $mockserverClient;
    public function __construct(ModuleContainer $moduleContainer, ?array $config = null)
    {
        $this->requiredFields = [self::CONFIG_URL];
        $this->config['cleanupBefore'] = 'test';
        $this->config[self::CONFIG_NOT_MATCHED_REQUEST] = self::NOT_MATCHED_REQUEST_ACTIVATED;
        parent::__construct($moduleContainer, $config);
    }


    public function _initialize(): void
    {
        $this->mockserverClient = new Client([
            'base_uri'  => $this->config[self::CONFIG_URL]
        ]);
        if (self::NOT_MATCHED_REQUEST_ACTIVATED === $this->config[self::CONFIG_NOT_MATCHED_REQUEST]) {
            $this->createMockRequest(file_get_contents(__DIR__.'/not-matched-request.json'));
        }
    }

    public function _beforeSuite(array $settings = []): void
    {
        if ('suite' === $this->config['cleanupBefore']) {
            $this->clearMockServerLogs();
        }
    }

    public function _before(TestInterface $test):void
    {
        if ('test' === $this->config['cleanupBefore']) {
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
            'id' => self::CONFIG_NOT_MATCHED_REQUEST
        ]);
        $request = new Request('PUT', '/mockserver/clear?type=expectations', [], $body);
        $response = $this->mockserverClient->sendRequest($request);
        Assert::assertEquals(
            200,
            $response->getStatusCode(),
            $response->getBody()->getContents()
        );

    }
}