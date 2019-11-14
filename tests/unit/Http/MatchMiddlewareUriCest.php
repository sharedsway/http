<?php namespace Sharedsway\Http\Test\Http;

use Codeception\Example;
use Sharedsway\Http\Http;
use Sharedsway\Http\Test\UnitTester;

class MatchMiddlewareUriCest
{
    protected $method;

    public function _before(UnitTester $I)
    {


        $this->method = function ($middleUri, $realUri) {
            $ref    = new \ReflectionClass(Http::class);
            $method = $ref->getMethod('_matchMiddlewareUri');

            $method->setAccessible(true);

            return $method->invokeArgs($ref, [$middleUri, $realUri]);
        };


    }

    // tests

    /**
     * @param UnitTester $I
     * @param Example $example
     * @dataProvider getSource
     */
    public function tryToTest(UnitTester $I, Example $example)
    {
        $middleUri = $example[0];
        $realUri   = $example[1];
        $expect    = $example[2];


        $I->assertEquals($expect, call_user_func($this->method, $middleUri, $realUri));
    }

    protected function getSource()
    {
        return [
            ['*', '', true],
            ['*', 'hello', true],
            ['*', '/hello/world', true],

            ['/', '/', true],
            ['/', '/s', false],
            ['/', '//', true],
            ['/hello', '/', false],
            ['/hello', '/hell', false],
            ['/hello', '/hello', true],
            ['/hello', '/Hello', false],
            ['/hello', '/hello/', false],

            ['/hello*', '/hello', true],
            ['/hello*', '/hello/', true],
            ['/hello*', '/hello/world', true],
            ['/hello/*', '/hello/', true],
            ['/hello/*', '/hello/world', true],
        ];
    }
}

