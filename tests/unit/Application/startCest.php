<?php namespace Sharedsway\Sharedsway\Test\Application;
use Sharedsway\Sharedsway\Test\UnitTester;

class startCest
{
    public function _before(UnitTester $I)
    {
    }

    // tests
    public function startTest(UnitTester $I)
    {
        ob_start();
        (new \Sharedsway\Sharedsway\Application())->start();
        $content = ob_get_contents();
        ob_end_clean();

        $I->assertEquals('hello world' . PHP_EOL, $content);
    }
}
