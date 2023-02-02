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

    public function seeMockRequestWasCalled(string $expectationId, int $times = 1): void
    {
        $body = [
            'expectationId' => ['id' => $expectationId],
            'times' => ['atLeast' => $times, 'atMost' => $times]
        ];
        $request = new Request('PUT', '/mockserver/verify', [], json_encode($body));
        $response = $this->mockserverClient->sendRequest($request);
        Assert::assertEquals(
            202,
            $response->getStatusCode(),
            $response->getBody()->getContents()
        );
    }

}