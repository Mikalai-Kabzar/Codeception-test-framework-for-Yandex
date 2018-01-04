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

    const STRING_LIMIT = 20000;
    const COMMENT = '$this->getScenario()->comment($description);';
    const PICTURE_CONTENT_TYPE = 'png';
    const WEBDRIVER_MODULE_NAME = 'WebDriver';
    const EXAMPLE_JSON_WORD = 'example';
    const COMMENT_STEPS_DESCRIPTION = 'comment';
    private $isCommentStep = false;
    private $firstSuite = false;
    private $launchName;
    private $launchDescription;
    private $rootItemID;
    private $testItemID;
    private $stepItemID;
    private $failedStepItemID;
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

    /**
     * Configure http client.
     */
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

    /**
     * @param SuiteEvent $e
     */
    public function afterSuite(SuiteEvent $e)
    {
        self::$httpService->finishRootItem();
    }

    /**
     * @param TestEvent $e
     */
    public function beforeTestExecution(TestEvent $e)
    {

    }

    /**
     * @param TestEvent $e
     */
    public function beforeTest(TestEvent $e)
    {
        $testName = $e->getTest()->getMetadata()->getName();
        $exampleParamsString = '';
        $params = $e->getTest()->getMetadata()->getCurrent();
        if (array_key_exists(self::EXAMPLE_JSON_WORD, $params)) {
            $exampleParams = $e->getTest()->getMetadata()->getCurrent()[self::EXAMPLE_JSON_WORD];
            foreach ($exampleParams as $key => $value) {
                $exampleParamsString = $exampleParamsString . $value . '; ';
            }
            if (!empty($exampleParamsString)) {
                $exampleParamsString = substr($exampleParamsString, 0, -2);
                $exampleParamsString = ' (' . $exampleParamsString . ')';
            }
        }
        $this->testName = $testName . $exampleParamsString;
        $this->testDescription = $exampleParamsString;
        $response = self::$httpService->startChildItem($this->rootItemID, $this->testDescription, $this->testName, ItemTypesEnum::TEST, []);
        $this->testItemID = self::getID($response);
    }

    /**
     * @param TestEvent $e
     */
    public function afterTest(TestEvent $e)
    {

    }

    /**
     * @param TestEvent $e
     */
    public function afterTestExecution(TestEvent $e)
    {

    }

    /**
     * @param FailEvent $e
     */
    public function afterTestFail(FailEvent $e)
    {
        $this->setFailedLaunch();
        $trace = $e->getFail()->getTraceAsString();
        $message = $e->getFail()->getMessage();
        self::$httpService->addLogMessage($this->failedStepItemID, $message, LogLevelsEnum::ERROR);
        self::$httpService->addLogMessage($this->failedStepItemID, $trace, LogLevelsEnum::ERROR);
        self::$httpService->finishItem($this->testItemID, ItemStatusesEnum::FAILED, $this->testDescription);
    }

    /**
     * @param FailEvent $e
     */
    public function afterTestFailAdditional(FailEvent $e)
    {
        $this->setFailedLaunch();
    }

    /**
     * @param FailEvent $e
     */
    public function afterTestError(FailEvent $e)
    {

        self::$httpService->finishItem($this->testItemID, ItemStatusesEnum::STOPPED, $this->testDescription);
        $this->setFailedLaunch();
    }

    /**
     * @param FailEvent $e
     */
    public function afterTestIncomplete(FailEvent $e)
    {
        self::$httpService->finishItem($this->testItemID, ItemStatusesEnum::CANCELLED, $this->testDescription);
        $this->setFailedLaunch();
    }

    /**
     * @param FailEvent $e
     */
    public function afterTestSkipped(FailEvent $e)
    {
        $this->beforeTest($e);
        $trace = $e->getFail()->getTraceAsString();
        $message = $e->getFail()->getMessage();
        self::$httpService->addLogMessage($this->testItemID, $message, LogLevelsEnum::ERROR);
        self::$httpService->addLogMessage($this->testItemID, $trace, LogLevelsEnum::ERROR);
        self::$httpService->finishItem($this->testItemID, ItemStatusesEnum::SKIPPED, $message);
        $this->setFailedLaunch();
    }

    /**
     * @param TestEvent $e
     */
    public function afterTestSuccess(TestEvent $e)
    {
        self::$httpService->finishItem($this->testItemID, ItemStatusesEnum::PASSED, $this->testDescription);
    }

    /**
     * @param StepEvent $e
     */
    public function beforeStep(StepEvent $e)
    {
        $pairs = explode(':', $e->getStep()->getLine());
        $fileAddress = $pairs[0];
        $lineNumber = $pairs[1];
        $fileLines = file($fileAddress);
        $stepName = $fileLines[$lineNumber - 1];
        $stepAsString = $e->getStep()->toString(self::STRING_LIMIT);
        if (strpos($stepName, self::COMMENT) !== false) {
            $stepName = $stepAsString;
            $this->isCommentStep = true;
        } else {
            $this->isCommentStep = false;
        }
        $response = self::$httpService->startChildItem($this->testItemID, '', $stepName, ItemTypesEnum::STEP, []);
        $this->stepItemID = self::getID($response);
        self::$httpService->setStepItemID($this->stepItemID);
    }

    /**
     * @param StepEvent $e
     */
    public function afterStep(StepEvent $e)
    {
        $stepToString = $e->getStep()->toString(self::STRING_LIMIT);
        $isFailedStep = $e->getStep()->hasFailed();
        $module = null;
        if ($this->hasModule(self::WEBDRIVER_MODULE_NAME)) {
            $module = $this->getModule(self::WEBDRIVER_MODULE_NAME);
        }
        if ($isFailedStep and $module !== null) {
            $screenShot = $module->webDriver->takeScreenshot();
            self::$httpService->addLogMessageWithPicture($this->stepItemID, $stepToString, LogLevelsEnum::ERROR,
                $screenShot, self::PICTURE_CONTENT_TYPE);
        }
        $status = self::getStatusByBool($isFailedStep);
        if ($this->isCommentStep) {
            $description = self::COMMENT_STEPS_DESCRIPTION;
        } else {
            $description = $e->getStep()->toString(self::STRING_LIMIT);
        }
        self::$httpService->finishItem($this->stepItemID, $status, $description);
        self::$httpService->setStepItemIDToEmpty();
        $this->failedStepItemID = $this->stepItemID;

    }

    /**
     * @param FailEvent $e
     */
    public function afterStepFail(FailEvent $e)
    {

    }

    /**
     * @param PrintResultEvent $e
     */
    public function afterTesting(PrintResultEvent $e)
    {
        $status = self::getStatusByBool($this->isFailedLaunch);
        self::$httpService->finishTestRun($status);
    }

    /**
     * Get status for HTTP request from boolean variable
     * @param bool $isFailed
     * @return string
     */
    private static function getStatusByBool(bool $isFailed)
    {
        if ($isFailed) {
            $status = ItemStatusesEnum::FAILED;
        } else {
            $status = ItemStatusesEnum::PASSED;
        }
        return $status;
    }

    /**
     *Set isFailedLaunch to true
     */
    private function setFailedLaunch()
    {
        $this->isFailedLaunch = true;
    }

    /**
     * Get ID from response
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
