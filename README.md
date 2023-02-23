# Codecetion-MockServer Helper
This library is a Module for [Codeception](https://codeception.com/) which allows your tests interact with 
[mock-server](https://www.mock-server.com/)(v5) in an easy and intuitive way.

MockServer allows you to simulate http services in your testing/local environment and this helper allows you check all 
the request you have sent to mock server and manage the expectations of mockserver. 

## How to use it

```php
//You can create expectations before call your application in a test
$I->createMockRequest('{"id": "elastic-get-entity-1", "httpRequest": {...}, "httpResponse": {...}}')
$I->createMockRequest('{"id": "elastic-get-entity-2", "httpRequest": {...}, "httpResponse": {...}}')
$I->createMockRequestFromJsonFile('/root/path/elastic-get-entity-3.json')
$I->sendGet('/applition/endpoint/1');
//After execute our application we can check our mocked HTTP communication
$I->seeMockRequestWasCalled('elastic-get-entity-1');
$I->seeMockRequestWasNotCalled('elastic-get-entity-2');
$I->seeAllRequestWereMatched();
$I->removeMockRequest("elastic-get-entity-2");
$I->removeAllMockRequest();
```

## Installation

Install the latest version with

```bash
$ composer require devizzent/codeception-mockserver-helper --dev
```

## Codeception Configuration
`This helper isn't a codeception module, it  can't be configured in the global codeception.yml 
 only in the *.suite.yml configuration.`

Go to your codeception **suite** configuration and add this module
```yaml
modules:
  enabled:
    - DEVizzent\CodeceptionMockServerHelper\MockServerHelper:
        ##Mandatory field, mock server url
        url: 'http://mockserver:1080' 
        ## Optional field, [test, suite, never] allowed. Default: test
        cleanupBefore: 'test'
        ## Optional field, [enabled, disabled] allowed. Default: enabled
        notMatchedRequest: 'enabled' 
        ## Optional field, path of the expectations folder or file to load before test
        expectationsPath: '/absolute/expectations/path' 
```

### cleanupBefore

This variable set the moment when we want to clean the mockserver logs in order to don't affect the other tests or suites.
We recomend set this value in the default value.
 - 'test' will remove all logs before start any test making sure that you are not affected by request did in the previous
test execution.
 - 'suite' will remove all logs before start a suite of tests.
 - 'never' will not remove your mock-server logs. It can generate confusion and failures in your tests, because if 
you check something was called X times, the number of times increase every time you run your tests. If you only check that
a request was called, it could be called in the previous execution, but by error not in the current one.

### notMatchedRequest

When this option is enabled, it creates an expectation with lowest priority which match all the request haven't matched
our expectations, returning a 500 error with a message `Request not matched by MockServer`.

It allows us to validate all request our application do, are expected and we haven't change our communication with 
external services.

### expectationsPath

Get the file or files in the path, and send the content to create expectations on mockserver. 

## About

### Requirements

Codecetion-MockServer Helper needs at least php 7.4 or higher and codeception 4 or higher.

### How to contribute

Create an issue describing the bug or the new feature, create your fork of this project and send your PR.

For using the dev environment you only need Docker, Docker-compose and Makefile.
```text
Usage:
  make <target>

Targets:
  help                       Display this help
  install                    Install required software and initialize your local configuration
  up                         Start application containers and required services
  debug                      Start application containers and required services in debug mode
  down                       Stop application containers and required services
  test                       Execute all phpunit test
  composer-update            Run composer update
  composer-install           Run composer update

```

### Author

Vicent Valls - <vizzent@gmail.com> - <https://twitter.com/ViMalaBarraka><https://www.youtube.com/@DEVizzent><br />
See also the list of [contributors](https://github.com/DEVizzent/codeception-mockserver-helper/graphs/contributors) who participated in this project.

### License

Codecetion-MockServer Helper is licensed under the MIT License - see the [LICENSE](LICENSE) file for details