<?php

namespace Test\DEVizzent\CodeceptionMockServerHelper\Integration;

use Codeception\Lib\ModuleContainer;
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
        $config = [
            'url' => getenv('MOCKSERVER_URL'),
            'expectationsPath' => __DIR__ . '/../../docker/mockserver/expectations',
        ];
        $moduleContainer = $this->createMock(ModuleContainer::class);
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

    public function testExpectationWasNotCalled(): void
    {
        $this->sot->seeMockRequestWasNotCalled('get-post-2');
    }

    public function testExpectationWasNotCalledButItWasThrowException(): void
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessageMatches(
            '#^Request not found exactly 0 times, expected:((.|\n)*) but was:((.|\n)*)'
            . 'Failed asserting that 406 matches expected 202\.$#'
        );
        $this->client->request('GET', 'https://jsonplaceholder.typicode.com/posts/2');
        $this->sot->seeMockRequestWasNotCalled('get-post-2');
    }

    public function testExpectationWasCalledNotExistExpectationThrowException(): void
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage(
            'No expectation found with id not-existing-expectation' . PHP_EOL
            . 'Failed asserting that 400 matches expected 202.'
        );
        $this->sot->seeMockRequestWasNotCalled('not-existing-expectation');
    }
}
