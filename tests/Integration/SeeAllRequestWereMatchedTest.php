<?php

namespace Test\DEVizzent\CodeceptionMockServerHelper\Integration;

use Codeception\Lib\ModuleContainer;
use DEVizzent\CodeceptionMockServerHelper\Config\NotMatchedRequest;
use DEVizzent\CodeceptionMockServerHelper\MockServerHelper;
use GuzzleHttp\Client;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

class SeeAllRequestWereMatchedTest extends TestCase
{
    private MockServerHelper $sot;
    private Client $client;

    protected function initialize(string $notMatchedRequest): void
    {
        $moduleContainer = $this->createMock(ModuleContainer::class);
        $config = [
            'url' => getenv('MOCKSERVER_URL'),
            'notMatchedRequest' => $notMatchedRequest,
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

    public function testAllRequestWereMatchedWhenConfigDisabledThrowException(): void
    {
        $this->initialize(NotMatchedRequest::DISABLED);
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage(
            '\'seeAllRequestWereMatched\' can\'t be used without enable notMatchedRequest in config.'
        );
        $this->sot->seeAllRequestWereMatched();
    }

    public function testAllRequestWereMatched(): void
    {
        $this->initialize(NotMatchedRequest::ENABLED);
        $this->client->request('GET', 'https://jsonplaceholder.typicode.com/posts/1');
        $this->client->request('GET', 'https://jsonplaceholder.typicode.com/posts/2');
        $this->sot->seeAllRequestWereMatched();
    }

    public function testNotAllRequestWereMatchedThrowException(): void
    {
        $this->initialize(NotMatchedRequest::ENABLED);
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessageMatches('/REQUEST NOT MATCHED was:.*/');
        $this->client->request('GET', 'https://jsonplaceholder.typicode.com/posts/1');
        $this->client->request(
            'GET',
            'https://jsonplaceholder.typicode.com/posts/3',
            ['http_errors' => false]
        );
        $this->sot->seeAllRequestWereMatched();
    }
}
