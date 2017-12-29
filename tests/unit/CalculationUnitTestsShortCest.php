<?php

class CalculationUnitTestsShortCest
{
    function checkEqualPositive123(UnitTester $I)
    {
        $I->assertEquals('1', '1');
    }

    function checkEqualPositive234(UnitTester $I)
    {
        $I->assertEquals('1', '1');
    }

    function checkEqualNegative123456(UnitTester $I)
    {
        $I->assertEquals('1', '2', 'It is a negative test for string.');
    }
}
