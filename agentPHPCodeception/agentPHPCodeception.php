<?php


use Codeception\Exception\ModuleRequireException as ModuleRequireException;
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

    const stringLimit = 20000;
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
        $response = self::$httpService->createRootItem($suiteBaseName, $suiteBaseName . ' tests', []);
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
        $exampleParamsString = '';
        try {
            $exampleParams = $e->getTest()->getMetadata()->getCurrent()['example'];
            foreach ($exampleParams as $key => $value) {
                $exampleParamsString = $exampleParamsString.$value.'; ';
            }
            if (!empty($exampleParamsString)) {
                $exampleParamsString = substr($exampleParamsString, 0, -2);
                $exampleParamsString = ' ('.$exampleParamsString.')';
            }
        } catch (Exception $exception) {
        }

        $this->testName = $testName .$exampleParamsString;
        $this->testDescription = $exampleParamsString;
        $response = self::$httpService->startChildItem($this->rootItemID, $this->testDescription, $this->testName, ItemTypesEnum::TEST, []);
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

        $pairs = explode(':', $e->getStep()->getLine());
        $fileAddress = $pairs[0];
        $lineNumber = $pairs[1];
        $fileLines = file($fileAddress);
        $stepName = $fileLines[$lineNumber - 1];
        $action = $e->getStep()->getAction();
        $stepAsString = $e->getStep()->toString(self::stringLimit);
        if ($action = $stepAsString) {

            $stepName = $stepAsString;
        }

        //$argumentsAsString = $e->getStep()->getArgumentsAsString();
        $argumentsAsString = $e->getStep()->getHumanizedArguments();
        //echo $argumentsAsString;
        $actionName = $e->getStep()->getAction();
        $actionName = $e->getStep()->getHumanizedActionWithoutArguments();

        if (empty($argumentsAsString)) {
            $description = $actionName;
        } else {
            $description = $actionName . '(' . $argumentsAsString . ')';
        }
       //$description = $e->getStep()->getLine();
        $response = self::$httpService->startChildItem($this->testItemID, $description, $stepName, ItemTypesEnum::STEP, []);
        $this->stepItemID = self::getID($response);

    }

    public function afterStep(StepEvent $e)
    {
        $argumentsAsString = $e->getStep()->getArgumentsAsString();
        $logDir = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $this->getLogDir());

        $stepToString = $e->getStep()->toString(self::stringLimit);
        $isFailedStep = $e->getStep()->hasFailed();


        if ($isFailedStep) {
            self::$httpService->addLogMessage($this->stepItemID, $stepToString, LogLevelsEnum::ERROR);


            try {
                $this->getModule('WebDriver')->_saveScreenshot(codecept_output_dir() . 'screenshot_1-123.png');
                $screenshotBinary = $this->getModule('WebDriver')->webDriver->takeScreenshot();
                //var_dump($screenshotBinary);
                //self::$httpService->addLogMessage($this->stepItemID, '_'.$screenshotBinary.'_', LogLevelsEnum::ERROR);
                //self::$httpService->addLogMessageWithPicture($this->stepItemID,$stepToString,LogLevelsEnum::ERROR,$screenshotBinary,'bmp');
              } catch (ModuleRequireException $error) {
            }
            //self::$httpService->addLogMessageWithPicture($this->stepItemID,$stepToString,LogLevelsEnum::ERROR,,'bmp');
        }


        $status = self::getStatusByBool($isFailedStep);


        $action = $e->getStep()->getAction();
        $stepAsString = $e->getStep()->toString(self::stringLimit);
        if ($action = $stepAsString) {
            $description = '';
        } else {
            $description = $e->getStep()->toString(self::stringLimit);
        }




        self::$httpService->finishItem($this->stepItemID, $status, $description);
    }

    public function afterStepFail(FailEvent $e)
    {

    }

    public function afterTesting(PrintResultEvent $e)
    {
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
