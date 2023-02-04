<?php

namespace DEVizzent\CodeceptionMockServerHelper;

use Codeception\Module;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\Assert;

class MockServerHelper extends Module
{
    private Client $mockserverClient;

    public function __construct()
    {
        $this->mockserverClient = new Client(['base_uri'  => 'http://mockserver:1080']);
    }

    public function seeMockRequestWasCalled(string $expectationId, ?int $times = null): void
    {
        $body = [
            'expectationId' => ['id' => $expectationId],
            'times' => ['atLeast' => $times ?? 1, 'atMost' => $times ?? 1000]
        ];
        $request = new Request('PUT', '/mockserver/verify', [], json_encode($body));
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

    public function resetMockServerLogs()
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