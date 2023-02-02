<?php

namespace Integration;

use DEVizzent\CodeceptionMockServerHelper\MockServerHelper;
use PHPUnit\Framework\TestCase;

class SeeMockRequestWasCalledTest extends TestCase
{
    private MockServerHelper $sot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sot = new MockServerHelper();
    }

    public function testExpectationNotWasCalledThrowException(): void
    {
        $this->sot->seeMockRequestWasCalled('some-expectation');
    }
}