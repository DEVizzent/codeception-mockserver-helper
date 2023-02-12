<?php

namespace Test\DEVizzent\CodeceptionMockServerHelper\Integration;

use Codeception\Lib\ModuleContainer;
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
        $moduleContainer = $this->createMock(ModuleContainer::class);
        $this->sot = new MockServerHelper($moduleContainer, ['url' => 'http://mockserver:1080']);
        $this->sot->_initialize();
        $this->client = new Client(['proxy' => 'http://mockserver:1080', 'verify' => false]);
        $this->sot->clearMockServerLogs();
    }

    public function testExpectationWasCalled(): void
    {
        $this->client->request('GET', 'https://jsonplaceholder.typicode.com/posts/1');
        $this->sot->seeMockRequestWasCalled('get-post-1');
    }

    public function testExpectationWasCalledExact(): void
    {
        $this->client->request('GET', 'https://jsonplaceholder.typicode.com/posts/1');
        $this->client->request('GET', 'https://jsonplaceholder.typicode.com/posts/1');
        $this->sot->seeMockRequestWasCalled('get-post-1', 2);
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
