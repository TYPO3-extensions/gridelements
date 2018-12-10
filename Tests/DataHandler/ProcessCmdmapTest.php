<?php

use GridElementsTeam\Gridelements\DataHandler\ProcessCmdmap;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ProcessCmdmapTest extends UnitTestCase
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
        $hook = GeneralUtility::makeInstance(ProcessCmdmap::class);
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
        $hook = GeneralUtility::makeInstance(ProcessCmdmap::class);
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
        $hook = GeneralUtility::makeInstance(ProcessCmdmap::class);
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $hook->setTceMain($dataHandler);
        $result = $hook->getTceMain();
        $this->assertEquals($dataHandler, $result);
    }

    /**
     * test execute process cmd map
     *
     * @test
     */
    public function testExecute_processCmdmap()
    {
    }
}
