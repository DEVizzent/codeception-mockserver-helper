<?php

namespace Test\DEVizzent\CodeceptionMockServerHelper\Integration;

use Codeception\Lib\ModuleContainer;
use DEVizzent\CodeceptionMockServerHelper\MockServerHelper;
use GuzzleHttp\Client;
use PHPUnit\Framework\AssertionFailedError;
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
        $config = [
            'url' => getenv('MOCKSERVER_URL'),
            'expectationsPath' => __DIR__ . '/../../docker/mockserver/expectations',
        ];
        $this->sot = new MockServerHelper($moduleContainer, $config);
        $this->sot->_initialize();
        $this->sot->_beforeSuite();
        $this->client = new Client(['proxy' => getenv('MOCKSERVER_URL'), 'verify' => false]);
        $this->sot->clearMockServerLogs();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->sot->removeAllMockRequest();
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

    public function testExpectationWasCalledButWasNotWithGoodRecomendation(): void
    {
        $this->client->request('GET', 'https://jsonplaceholder.typicode.com/posts/2', ['http_errors' => false]);
        $this->client->request('GET', 'https://jsonplaceholder.typicode.com/posts/2', ['http_errors' => false]);
        $this->client->request(
            'GET',
            'https://jsonplaceholder.typicode.com/posts/5',
            ['http_errors' => false, 'headers' => ['randomHeader' => 'value']]
        );
        $this->client->request('GET', 'https://jsonplaceholder.typicode.com/users/3/albums', ['http_errors' => false]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessageMatches('#.*"path": "\\\/users\\\/3\\\/albums".*#');
        $this->sot->seeMockRequestWasCalled('get-post-1');
    }

    public function testExpectationWasCalledButWasNotWithGoodRecomendation2(): void
    {
        $this->client->request('GET', 'https://jsonplaceholder.typicode.com/posts/2', ['http_errors' => false]);
        $this->client->request('GET', 'https://jsonplaceholder.typicode.com/posts/2', ['http_errors' => false]);
        $this->client->request('GET', 'https://jsonplaceholder.typicode.com/posts/5', ['http_errors' => false]);
        $this->client->request(
            'GET',
            'https://jsonplaceholder.typicode.com/users/3/albums',
            ['http_errors' => false, 'headers' => ['randomHeader' => 'value']]
        );

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessageMatches('#.*"path": "\\\/posts\\\/5".*#');
        $this->sot->seeMockRequestWasCalled('get-post-1');
    }
}
