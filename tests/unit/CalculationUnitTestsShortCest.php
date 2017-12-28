<?php

class CalculationUnitTestsShortCest
{
    function checkEqualPositive1(UnitTester $I)
    {
        $I->assertEquals('1', '1');
    }

    function checkEqualPositive2(UnitTester $I)
    {
        $I->assertEquals('1', '1');
    }

    function checkEqualNegative1(UnitTester $I)
    {
        $I->assertEquals('1', '2', 'It is a negative test for string.');
    }
}
