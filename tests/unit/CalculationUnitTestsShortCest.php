<?php

class CalculationUnitTestsShortCest
{

    function _before(UnitTester $I)
    {
        $I->amGoingTo('Break the system');
        $I->comment('Some before comment');
    }

    function checkEqualPositive123(UnitTester $I)
    {
        $I->assertEquals('16', '16');
    }

    function checkEqualPositive234(UnitTester $I)
    {
        $I->assertEquals('51', '51');
    }

    /**
     * @param UnitTester $I

     */
    function checkEqualNegative1(UnitTester $I)
    {
        //$I->assertEquals('1', '1');
        $I->assertEquals('24', '24');
    }

    /**
     * @param UnitTester $I
     * @depends checkEqualPositive234
     */
    function checkEqualNegative2(UnitTester $I)
    {
        $I->assertEquals('13', '13');
        $I->assertEquals('23', '23');

    }

    /**
     */
    function checkEqualNegative3(UnitTester $I)
    {
        $I->assertEquals('32', '32');
        $I->assertEquals(42, 42);
        $I->assertEquals(52, 2);
    }

    /**
     * @depends checkEqualNegative3
     * @param UnitTester $I
     */
    function checkEqualNegative4(UnitTester $I)
    {
        $I->assertEquals('11', '11');
        $I->assertEquals(41, 41);
    }

}
