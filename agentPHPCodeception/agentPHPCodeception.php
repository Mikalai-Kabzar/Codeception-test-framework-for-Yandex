<?php


use ReportPortalBasic\Enum\ItemStatusesEnum as ItemStatusesEnum;
use ReportPortalBasic\Enum\ItemTypesEnum as ItemTypesEnum;
use ReportPortalBasic\Service\ReportPortalHTTPService;
use GuzzleHttp\Psr7\Response as Response;

use \Codeception\Events as Events;
use \Codeception\Event\SuiteEvent as SuiteEvent;
use \Codeception\Event\TestEvent as TestEvent;
use \Codeception\Event\FailEvent as FailEvent;
use \Codeception\Event\StepEvent as StepEvent;
use \Codeception\Event\PrintResultEvent as PrintResultEvent;

class agentPHPCodeception extends \Codeception\Platform\Extension
{
    private $firstSuite = false;
    private $UUID;
    private $projectName;
    private $host;
    private $timeZone;
    private $launchName;
    private $launchDescription;
    private $launchID;
    private $rootItemID;
    private $testItemID;
    private $stepItemID;
    private $testName;
    private $testDescription;
    private $isFailedLaunch = false;

    /**
     *
     * @var ReportPortalHTTPService
     */
    protected static $httpService;

    // list events to listen to
    public static $events = array(
        Events::SUITE_BEFORE => 'beforeSuite',
        Events::TEST_START => 'beforeTestExecution',
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
        Events::SUITE_AFTER => 'afterSuite'
    );

    private function configureClient()
    {
        $UUID = $this->config['UUID'];
        $projectName = $this->config['projectName'];
        $host = $this->config['host'];
        $timeZone = $this->config['timeZone'];
        $this->launchName = $this->config['launchName'];
        $this->launchDescription = $this->config['launchDescription'];

        $isHTTPErrorsAllowed = false;
        $baseURI = sprintf(ReportPortalHTTPService::BASE_URI_TEMPLATE, $host);

        ReportPortalHTTPService::configureClient($UUID, $baseURI, $host, $timeZone, $projectName, $isHTTPErrorsAllowed);
        self::$httpService = new ReportPortalHTTPService();
    }

    /**
     * @param SuiteEvent $e
     */
    public function beforeSuite(SuiteEvent $e)
    {
        if ($this->firstSuite == false) {
            $this->configureClient();
            self::$httpService->launchTestRun($this->launchName, $this->launchDescription, ReportPortalHTTPService::DEFAULT_LAUNCH_MODE, []);
            $this->firstSuite = true;
        }
        $response = self::$httpService->createRootItem($e->getSuite()->getBaseName(), 'root item description', []);
        $this->rootItemID = self::getID($response);

    }

    public function afterSuite(SuiteEvent $e)
    {
        self::$httpService->finishRootItem();
    }

    public function beforeTestExecution(TestEvent $e)
    {

    }

    public function beforeTest(TestEvent $e)
    {
        $testName = $e->getTest()->getMetadata()->getName();
        $testParam = implode($e->getTest()->getMetadata()->getDependencies());

        $this->testName = $testName . '_' . $testParam;
        $this->testDescription = 'Description of ' . $this->testName;
        $response = self::$httpService->startChildItem($this->rootItemID, 'Description of ' . $this->testName, $this->testName, ItemTypesEnum::TEST, []);
        $this->testItemID = self::getID($response);
    }

    public function afterTest(TestEvent $e)
    {

    }

    public function afterTestExecution(TestEvent $e)
    {

    }

    public function afterTestFail(FailEvent $e)
    {
        $this->setFailedLaunch();
        self::$httpService->finishItem($this->testItemID, ItemStatusesEnum::FAILED, $this->testDescription);
    }

    public function afterTestFailAdditional(FailEvent $e)
    {
        $this->setFailedLaunch();
    }

    public function afterTestError(FailEvent $e)
    {
        $this->setFailedLaunch();
    }

    public function afterTestIncomplete(FailEvent $e)
    {
        $this->setFailedLaunch();
    }

    public function afterTestSkipped(FailEvent $e)
    {
        $this->setFailedLaunch();
    }

    public function afterTestSuccess(TestEvent $e)
    {
        self::$httpService->finishItem($this->testItemID, ItemStatusesEnum::PASSED, $this->testDescription);
    }

    public function beforeStep(StepEvent $e)
    {
        $stepName = $e->getStep()->getAction() . '(' . $e->getStep()->getArgumentsAsString() . ')';
        $response = self::$httpService->startChildItem($this->testItemID, 'Description of step ' . $stepName, $stepName, ItemTypesEnum::STEP, []);
        $this->stepItemID = self::getID($response);

    }

    public function afterStep(StepEvent $e)
    {
        $stepStatus = $e->getStep()->toString(200);
        $status = self::getStatusByBool($e->getStep()->hasFailed());
        self::$httpService->finishItem($this->stepItemID, $status, 'Description of step ' . $stepStatus);
    }

    public function afterStepFail(FailEvent $e)
    {

    }

    public function afterTesting(PrintResultEvent $e){
        $status = self::getStatusByBool($this->isFailedLaunch);
        self::$httpService->finishTestRun($status);
    }

    private static function getStatusByBool(bool $isFailed)
    {
        if ($isFailed) {
            $status = ItemStatusesEnum::FAILED;
        } else {
            $status = ItemStatusesEnum::PASSED;
        }
        return $status;
    }

    private function setFailedLaunch()
    {
        $this->isFailedLaunch = true;
    }

    /**
     * @param Response $response
     * @return string
     */
    private static function getID(Response $response)
    {
        $array = json_decode($response->getBody(), true);
        return $array['id'];
    }

}

?>
