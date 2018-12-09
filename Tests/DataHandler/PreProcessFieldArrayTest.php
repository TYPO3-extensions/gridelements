<?php

use GridElementsTeam\Gridelements\DataHandler\PreProcessFieldArray;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PreProcessFieldArrayTest extends UnitTestCase
{

    /**
     * test inject layout setup
     *
     * @test
     */
    public function testInjectLayoutSetup()
    {
    }

    /**
     * test init
     *
     * @test
     */
    public function testInit()
    {
    }

    /**
     * test get table
     *
     * @test
     */
    public function testGetTable()
    {
        $hook = GeneralUtility::makeInstance(PreProcessFieldArray::class);
        $table = 'tt_content';
        $hook->setTable($table);
        $result = $hook->getTable();
        $this->assertEquals($table, $result);
    }

    /**
     * test get page uid
     *
     * @test
     */
    public function testGetPageUid()
    {
        $hook = GeneralUtility::makeInstance(PreProcessFieldArray::class);
        $pageUid = 123;
        $hook->setPageUid($pageUid);
        $result = $hook->getPageUid();
        $this->assertEquals($pageUid, $result);
    }

    /**
     * test get tce main
     *
     * @test
     */
    public function testGetTceMain()
    {
        $hook = GeneralUtility::makeInstance(PreProcessFieldArray::class);
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $hook->setTceMain($dataHandler);
        $result = $hook->getTceMain();
        $this->assertEquals($dataHandler, $result);
    }

    /**
     * test execute preprocess field array
     *
     * @test
     */
    public function testExecute_preProcessFieldArray()
    {
    }

    /**
     * test process field array for tt_content
     *
     * @test
     */
    public function testProcessFieldArrayForTtContent()
    {
    }

    /**
     * test set default field values
     *
     * @test
     */
    public function testSetDefaultFieldValues()
    {
    }

    /**
     * test extract default data from data structure
     *
     * @test
     */
    public function testExtractDefaultDataFromDataStructure()
    {
    }

    /**
     * test set unused field entries
     *
     * @test
     */
    public function testSetFieldEntries()
    {
    }

    /**
     * test set field entries for grid containers
     *
     * @test
     */
    public function testSetFieldEntriesForGridContainers()
    {
    }

    /**
     * test check for root column
     *
     * @test
     */
    public function testCheckForRootColumn()
    {
    }

    /**
     * test get backend user
     *
     * @test
     */
    public function testGetBackendUser()
    {
        $hook = GeneralUtility::makeInstance(PreProcessFieldArray::class);
        $backendUserAuthentication = GeneralUtility::makeInstance(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserAuthentication;
        $result = $hook->getBackendUser();
        $this->assertEquals($backendUserAuthentication, $result);
    }
}
