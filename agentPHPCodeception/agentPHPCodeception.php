<?php


use ReportPortalBasic\Enum\ItemStatusesEnum as ItemStatusesEnum;
use ReportPortalBasic\Enum\ItemTypesEnum as ItemTypesEnum;
use ReportPortalBasic\Enum\LogLevelsEnum as LogLevelsEnum;
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
        Events::TEST_BEFORE => 'beforeTest',
        Events::STEP_BEFORE => 'beforeStep',
        'step.fail' => 'afterStepFail',
        Events::STEP_AFTER => 'afterStep',
        Events::TEST_AFTER => 'afterTest',
        Events::TEST_END => 'afterTestExecution',
        Events::TEST_FAIL => 'afterTestFail',
        Events::TEST_ERROR => 'afterTestError',
        Events::TEST_INCOMPLETE => 'afterTestIncomplete',
        Events::TEST_SKIPPED => 'afterTestSkipped',
        Events::TEST_SUCCESS => 'afterTestSuccess',
        Events::TEST_FAIL_PRINT => 'afterTestFailAdditional',
        Events::RESULT_PRINT_AFTER => 'afterTesting',
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
        $suiteBaseName = $e->getSuite()->getBaseName();
        $response = self::$httpService->createRootItem($suiteBaseName, $suiteBaseName.' tests', []);
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
        $argumentsAsString = $e->getStep()->getArgumentsAsString();
        echo $argumentsAsString;
        $actionName = $e->getStep()->getAction();
        if (empty($argumentsAsString)){
            $stepName = $actionName;    
        } else {
            $stepName = $actionName.'('.$argumentsAsString.')'; 
        }
        $response = self::$httpService->startChildItem($this->testItemID, $argumentsAsString, $stepName, ItemTypesEnum::STEP, []);
        $this->stepItemID = self::getID($response);

    }

    public function afterStep(StepEvent $e)
    {
        $stringLimit = 20000;
        $argumentsAsString = $e->getStep()->getArgumentsAsString();
        $logDir = str_replace(['/','\\'],DIRECTORY_SEPARATOR,$this->getLogDir());

        $stepToString = $e->getStep()->toString($stringLimit);
        $isFailedStep = $e->getStep()->hasFailed();
        if ($isFailedStep){
            self::$httpService->addLogMessage($this->stepItemID,$stepToString,LogLevelsEnum::ERROR);
            self::$httpService->addLogMessage($this->stepItemID,$this->getLogDir(),LogLevelsEnum::TRACE);
            self::$httpService->addLogMessage($this->stepItemID,$logDir,LogLevelsEnum::TRACE);
        }
        $status = self::getStatusByBool($isFailedStep); 
        self::$httpService->finishItem($this->stepItemID, $status, $argumentsAsString);
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
