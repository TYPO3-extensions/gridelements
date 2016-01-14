<?php

use GridElementsTeam\Gridelements\Hooks\DataHandler;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DataHandlerTest extends UnitTestCase
{

    /**
     * test get database connection
     *
     * @test
     */
    public function testGetDatabaseConnection()
    {
        $GLOBALS['TYPO3_DB'] = GeneralUtility::makeInstance(DatabaseConnection::class);
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $result = $dataHandler->getDatabaseConnection();
        $this->assertEquals($GLOBALS['TYPO3_DB'], $result);
    }

    /**
     * test process data map preprocess field array
     *
     * @test
     */
    public function testProcessDatamap_preProcessFieldArray()
    {
    }

    /**
     * test process data map after database operations
     *
     * @test
     */
    public function testProcessDatamap_afterDatabaseOperations()
    {
    }

    /**
     * test process cmd map
     *
     * @test
     */
    public function testProcessCmdmap()
    {
    }

}
