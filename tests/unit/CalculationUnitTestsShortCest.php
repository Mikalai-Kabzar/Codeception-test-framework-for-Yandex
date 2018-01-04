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
        $I->assertEquals('1', '1');
    }

    function checkEqualPositive234(UnitTester $I)
    {
        $I->assertEquals('1', '1');
    }

    /**
     * @param UnitTester $I
     * @depends checkEqualPositive234
     */
    function checkEqualNegative1(UnitTester $I)
    {
        //$I->assertEquals('1', '1');
        $I->assertEquals('2', '2');
    }

    /**
     * @param UnitTester $I 
     * @depends checkEqualPositive234
     */
    function checkEqualNegative2(UnitTester $I)
    {
        $I->assertEquals('1', '1');
        $I->assertEquals('2', '2');

    }

    /**
     * @param UnitTester $I
     * @depends checkEqualPositive1
     */
    function checkEqualNegative3(UnitTester $I)
    {
        $I->assertEquals('3', '3');
        $I->assertEquals(4, 4);
        $I->assertEquals(4, 5);
    }

    /**
     * @param UnitTester $I
     * @depends checkEqualPositive3
     */
    function checkEqualNegative4(UnitTester $I)
    {
        $I->assertEquals('1', '1');
        $I->assertEquals(4, 4);
    }

}
