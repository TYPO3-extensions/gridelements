<?php

use GridElementsTeam\Gridelements\DataHandler\AfterDatabaseOperations;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class AfterDatabaseOperationsTest extends UnitTestCase
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
        $hook = GeneralUtility::makeInstance(AfterDatabaseOperations::class);
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
        $hook = GeneralUtility::makeInstance(AfterDatabaseOperations::class);
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
        $hook = GeneralUtility::makeInstance(AfterDatabaseOperations::class);
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $hook->setTceMain($dataHandler);
        $result = $hook->getTceMain();
        $this->assertEquals($dataHandler, $result);
    }

    /**
     * test do grid container update
     *
     * @test
     */
    public function testDoGridContainerUpdate()
    {
    }

    /**
     * test check and update translated children
     *
     * @test
     */
    public function testCheckAndUpdateTranslatedChildren()
    {
    }

    /**
     * test execute after database operations
     *
     * @test
     */
    public function testExecute_afterDatabaseOperations()
    {
    }

    /**
     * test save cleaned up field array
     *
     * @test
     */
    public function testSaveCleanedUpFieldArray()
    {
    }

    /**
     * test set unused elements
     *
     * @test
     */
    public function testSetUnusedElements()
    {
    }

    /**
     * test get subpages recursively
     *
     * @test
     */
    public function testGetSubpagesRecursively()
    {
    }

    /**
     * test get available columns
     *
     * @test
     */
    public function testGetAvailableColumns()
    {
    }
}
