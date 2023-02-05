<?php

namespace DEVizzent\CodeceptionMockServerHelper;

use Codeception\Module;
use Codeception\TestInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\Assert;

class MockServerHelper extends Module
{
    protected array $config = [
        'url'          => 'http://mockserver:1080',
        'cleanupBefore' => 'test'
    ];

    private Client $mockserverClient;

    /**
     * @param array<string, string> $config
     */
    public function _initialize()
    {
        $this->mockserverClient = new Client(['base_uri'  => $this->config['url']]);
    }

    public function _beforeSuite(array $settings = [])
    {
        if ('suite' === $this->config['cleanupBefore']) {
            $this->clearMockServerLogs();
        }
    }

    public function _before(TestInterface $test)
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
}