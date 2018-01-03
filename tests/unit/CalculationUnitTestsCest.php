<?php
use \Codeception\Example as Example;
class CalculationUnitTestsCest
{

    function _before(UnitTester $I){
        $I->amGoingTo('Break the system');
        $I->comment('Super puper mega before comment');
    }

    function checkEqualPositive1EqualTo1(UnitTester $I)
    {
        $I->assertEquals('1', '1','test on 1 equal to 1');
    }

    function checkEqualPositive1EqualTo1SecondTest(UnitTester $I)
    {
        $I->assertEquals('1', '1');
    }

    function checkEqualNegative1EqualTo2(UnitTester $I)
    {
        $I->assertEquals('1', '2', 'It is a negative test for string.');
    }

    /**
     * @dataProvider dataProviderFullArray
     */
    function checkEqualNegativeTestWithDataProvider(UnitTester $I, Example $example)
    {
        $I->assertEquals($example['expected'], $example['actual'], 'It is a negative test for numbers.');
    }

    /**
     * @dataProvider dataProviderLightArray
     * @param UnitTester $I
     * @param \Codeception\Example $example
     */
    function checkEqualNegativeWIthDataProviderAndExamples(UnitTester $I, Example $example)
    {
        $I->assertEquals($example[0], $example[1], 'It is a negative test for numbers.');
    }

    /**
     * @return array
     */
    private function dataProviderFullArray()
    {
        return array(
            ['expected' => 1, 'actual' => 2],
            ['expected' => 10, 'actual' => 10],
            ['expected' => -3, 'actual' => -3]
        );
    }

    private function dataProviderLightArray()
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
