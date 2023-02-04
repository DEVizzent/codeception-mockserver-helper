<?php

namespace Integration;

use DEVizzent\CodeceptionMockServerHelper\MockServerHelper;
use GuzzleHttp\Client;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

class SeeMockRequestWasCalledTest extends TestCase
{
    private MockServerHelper $sot;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sot = new MockServerHelper();
        $this->client = new Client(['proxy' => 'http://mockserver:1080', 'verify' => false]);
    }

    public function testExpectationWasCalledNotThrowException(): void
    {
        $this->client->request('GET', 'https://jsonplaceholder.typicode.com/posts/1', []);
        $this->sot->seeMockRequestWasCalled('get-post-1');
    }

    public function testExpectationNotWasCalledThrowException2(): void
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage(
            'No expectation found with id not-existing-expectation' . PHP_EOL
            . 'Failed asserting that 400 matches expected 202.'
        );
        $this->sot->seeMockRequestWasCalled('not-existing-expectation');
    }
}