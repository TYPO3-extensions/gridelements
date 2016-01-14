<?php

use GridElementsTeam\Gridelements\Hooks\DatabaseRecordList;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

class DatabaseRecordListTest extends UnitTestCase
{

    /**
     * test get language service
     *
     * @test
     */
    public function testGetLanguageService()
    {
        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageService::class);
        $databaseRecordList = GeneralUtility::makeInstance(DatabaseRecordList::class);
        $result = $databaseRecordList->getLanguageService();
        $this->assertEquals($GLOBALS['LANG'], $result);
    }

    /**
     * test get icon factory
     *
     * @test
     */
    public function testGetIconFactory()
    {
        $databaseRecordList = GeneralUtility::makeInstance(DatabaseRecordList::class);
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $result = $databaseRecordList->getIconFactory();
        $this->assertEquals($iconFactory, $result);
    }

    /**
     * test make clip
     *
     * @test
     */
    public function testMakeClip()
    {
    }

    /**
     * test make control
     *
     * @test
     */
    public function testMakeControl()
    {
    }

    /**
     * test render list header
     *
     * @test
     */
    public function testRenderListHeader()
    {
    }

    /**
     * test render list header actions
     *
     * @test
     */
    public function testRenderListHeaderActions()
    {
    }

    /**
     * test check children
     *
     * @test
     */
    public function testCheckChildren()
    {
    }

    /**
     * test content collapse icon
     *
     * @test
     */
    public function testContentCollapseIcon()
    {
    }

}