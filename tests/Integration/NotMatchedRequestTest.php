<?php

namespace Test\DEVizzent\CodeceptionMockServerHelper\Integration;

use Codeception\Lib\ModuleContainer;
use DEVizzent\CodeceptionMockServerHelper\MockServerHelper;
use GuzzleHttp\Client;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

class NotMatchedRequestTest extends TestCase
{
    const NOT_MATCHED_URI = 'https://jsonplaceholder.typicode.com/posts/3';
    private MockServerHelper $sot;
    private Client $client;

    private function initialize(string $notMatchedRequestConfig): void
    {
        $moduleContainer = $this->createMock(ModuleContainer::class);
        $this->sot = new MockServerHelper(
            $moduleContainer,
            ['url' => 'http://mockserver:1080', 'not-matched-request' => $notMatchedRequestConfig]
        );
        $this->sot->_initialize();
        $this->client = new Client(['proxy' => 'http://mockserver:1080', 'verify' => false]);
        $this->sot->clearMockServerLogs();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->sot->deactivateNotMatchedRequest();
    }

    public function testActivateNotMatchedRequestCreateExpectation()
    {
        $this->initialize('activated');
        $this->sot->seeMockRequestWasNotCalled('not-matched-request');
    }

    public function testActivateNotMatchedRequestWasCreatedAndCalled()
    {
        $this->initialize('activated');
        $this->client->request('GET', self::NOT_MATCHED_URI, ['http_errors' => false]);
        $this->sot->seeMockRequestWasCalled('not-matched-request', 1);
    }

    public function testActivateNotMatchedRequestWasCreatedAndDeactivated()
    {
        $this->initialize('activated');
        $this->sot->deactivateNotMatchedRequest();
        $this->client->request('GET', self::NOT_MATCHED_URI, ['http_errors' => false]);
        $this->sot->seeMockRequestWasNotCalled('not-matched-request');
    }


}