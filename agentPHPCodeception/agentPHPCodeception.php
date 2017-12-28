<?php

use \Codeception\Event\SuiteEvent as SuiteEvent;
use \Codeception\Event\TestEvent as TestEvent;
use \Codeception\Event\FailEvent as FailEvent;
use \Codeception\Event\StepEvent as StepEvent;
use \Codeception\Event\PrintResultEvent as PrintResultEvent;

class agentPHPCodeception extends \Codeception\Platform\Extension
{
    // list events to listen to
    public static $events = array(
        'suite.before' => 'beforeSuite',
        'test.start' => 'beforeTestExecution',
        'test.before' => 'beforeTest',
        'step.before' => 'beforeStep',
        'step.fail' => 'afterStepFail',
        'step.after' => 'afterStep',
        'test.after' => 'afterTest',
        'test.end' => 'afterTestExecution',
        'test.fail' => 'afterTestFail',
        'test.error' => 'afterTestError',
        'test.incomplete' => 'afterTestIncomplete',
        'test.skipped' => 'afterTestSkipped',
        'test.success' => 'afterTestSuccess',
        'test.fail.print' => 'afterTestFailAdditional',
        'result.print.after' => 'afterTesting',
        'suite.after' => 'afterSuite'
    );


    /**
     * @param SuiteEvent $e
     */
    public function beforeSuite(SuiteEvent $e)
    {

    }

    public function afterSuite(SuiteEvent $e)
    {

    }

    public function beforeTestExecution(TestEvent $e)
    {

    }

    public function beforeTest(TestEvent $e)
    {

    }

    public function afterTest(TestEvent $e)
    {

    }

    public function afterTestExecution(TestEvent $e)
    {

    }

    public function afterTestFail(FailEvent $e)
    {

    }

    public function afterTestFailAdditional(FailEvent $e)
    {

    }

    public function afterTestError(FailEvent $e)
    {

    }

    public function afterTestIncomplete(FailEvent $e)
    {

    }

    public function afterTestSkipped(FailEvent $e)
    {

    }

    public function afterTestSuccess(TestEvent $e)
    {

    }

    public function beforeStep(StepEvent $e)
    {

    }

    public function afterStep(StepEvent $e)
    {

    }

    public function afterStepFail(StepEvent $e)
    {

    }

    public function afterTesting(PrintResultEvent $e)
    {

    }

}

?>
