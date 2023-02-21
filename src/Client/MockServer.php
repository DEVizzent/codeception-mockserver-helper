<?php

namespace DEVizzent\CodeceptionMockServerHelper\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\Assert;

class MockServer
{
    private Client $mockserverClient;

    /**
     * @param Client $mockserverClient
     */
    public function __construct(Client $mockserverClient)
    {
        $this->mockserverClient = $mockserverClient;
    }

    public function verify(string $expectationId, ?int $times = null): void
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
    public function create(string $json): void
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
    public function removeById(string $mockRequestId): void
    {
        $body = json_encode([
            'id' => $mockRequestId
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

    public function clearLogs(): void
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
