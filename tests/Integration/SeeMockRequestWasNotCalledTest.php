<?php

namespace Integration;

use DEVizzent\CodeceptionMockServerHelper\MockServerHelper;
use GuzzleHttp\Client;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

class SeeMockRequestWasNotCalledTest extends TestCase
{
    private MockServerHelper $sot;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sot = new MockServerHelper();
        $this->client = new Client(['proxy' => 'http://mockserver:1080', 'verify' => false]);
    }

    public function testExpectationWasNotCalled(): void
    {
        $this->sot->seeMockRequestWasNotCalled('get-post-2');
    }

    public function testExpectationWasCalledThrowException(): void
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage(
            'No expectation found with id not-existing-expectation' . PHP_EOL
            . 'Failed asserting that 400 matches expected 202.'
        );
        $this->sot->seeMockRequestWasNotCalled('not-existing-expectation');
    }
}