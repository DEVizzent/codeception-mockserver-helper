<?php

namespace DEVizzent\CodeceptionMockServerHelper;

use Codeception\Lib\ModuleContainer;
use Codeception\Module;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\Assert;

class MockServerHelper extends Module
{
    private Client $mockserverClient;

    /**
     * @param array<string, string> $config
     */
    public function __construct(ModuleContainer $moduleContainer, ?array $config = null)
    {
        parent::__construct($moduleContainer, $config);
        $this->mockserverClient = new Client(['base_uri'  => 'http://mockserver:1080']);
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

    public function resetMockServerLogs(): void
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