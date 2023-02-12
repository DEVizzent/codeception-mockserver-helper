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
        $moduleContainer = $this->createMock(ModuleContainer::class);
        $this->sot = new MockServerHelper($moduleContainer, ['url' => 'http://mockserver:1080']);
        $this->sot->_initialize();
        $this->client = new Client(['proxy' => 'http://mockserver:1080', 'verify' => false]);
        $this->sot->clearMockServerLogs();
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
