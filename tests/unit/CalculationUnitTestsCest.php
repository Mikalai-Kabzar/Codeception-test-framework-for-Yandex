<?php
use \Codeception\Example as Example;
class CalculationUnitTestsCest
{

    function _before(UnitTester $I){
        $I->amGoingTo('Break the system');
        $I->comment('Some before comment');
    }

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

    /**
     * @dataProvider providerAdd1
     */
    function checkEqualNegative2(UnitTester $I, Example $example)
    {
        $I->assertEquals($example['expected'], $example['actual'], 'It is a negative test for numbers.');
    }

    /**
     * @dataProvider providerAdd2
     * @param UnitTester $I
     * @param \Codeception\Example $example
     */
    function checkEqualNegative3(UnitTester $I, Example $example)
    {
        $I->assertEquals($example[0], $example[1], 'It is a negative test for numbers.');
    }

    /**
     * @return array
     */
    private function providerAdd1()
    {
        return array(
            ['expected' => 1, 'actual' => 2],
            ['expected' => 10, 'actual' => 10],
            ['expected' => -3, 'actual' => -3]
        );
    }

    private function providerAdd2()
    {
        return array(
            [1, 2],
            [101, 10],
            ['123', '321'],
            [101, 10],
            ['Mikalai', 'Mikalai'],
            [ -3, -13]
        );
    }

}
