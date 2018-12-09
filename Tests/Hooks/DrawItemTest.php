<?php

use GridElementsTeam\Gridelements\Hooks\DrawItem;
use TYPO3\CMS\Backend\Controller\PageLayoutController;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

class DrawItemTest extends UnitTestCase
{

    /**
     * test get language service
     *
     * @test
     */
    public function testGetLanguageService()
    {
        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageService::class);
        $drawItem = GeneralUtility::makeInstance(DrawItem::class);
        $result = $drawItem->getLanguageService();
        $this->assertEquals($GLOBALS['LANG'], $result);
    }

    /**
     * test get icon factory
     *
     * @test
     */
    public function testGetIconFactory()
    {
        $drawItem = GeneralUtility::makeInstance(DrawItem::class);
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $result = $drawItem->getIconFactory();
        $this->assertEquals($iconFactory, $result);
    }

    /**
     * test get icon factory
     *
     * @test
     */
    public function testGetPageLayoutController()
    {
        $drawItem = GeneralUtility::makeInstance(DrawItem::class);
        $GLOBALS['SOBE'] = GeneralUtility::makeInstance(PageLayoutController::class);
        $result = $drawItem->getPageLayoutController();
        $this->assertEquals($GLOBALS['SOBE'], $result);
    }

    /**
     * test get backend user
     *
     * @test
     */
    public function testGetBackendUser()
    {
        $drawItem = GeneralUtility::makeInstance(DrawItem::class);
        $GLOBALS['BE_USER'] = GeneralUtility::makeInstance(BackendUserAuthentication::class);
        $result = $drawItem->getBackendUser();
        $this->assertEquals($GLOBALS['BE_USER'], $result);
    }

    /**
     * test cleanup collapsed states in UC
     *
     * @test
     */
    public function testCleanupCollapsedStatesInUC()
    {
    }

    /**
     * test pre process
     *
     * @test
     */
    public function testPreProcess()
    {
    }

    /**
     * test render content type gridelements
     *
     * @test
     */
    public function testRenderCTypeGridelements()
    {
    }

    /**
     * test set multiple colpos values
     *
     * @test
     */
    public function testSetMultipleColPosValues()
    {
    }

    /**
     * test set single colpos items
     *
     * @test
     */
    public function testSetSingleColPosItems()
    {
    }

    /**
     * test render grid columns
     *
     * @test
     */
    public function testRenderGridColumns()
    {
    }

    /**
     * test collect items for column
     *
     * @test
     */
    public function testCollectItemsForColumn()
    {
    }

    /**
     * test render single grid column
     *
     * @test
     */
    public function testRenderSingleGridColumn()
    {
    }

    /**
     * test set column header
     *
     * @test
     */
    public function testSetColumnHeader()
    {
    }

    /**
     * test tt_content draw column header
     *
     * @test
     */
    public function testTtContentDrawColHeader()
    {
    }

    /**
     * test render grid layout table
     *
     * @test
     */
    public function testRenderGridLayoutTable()
    {
    }

    /**
     * test collect content data from pages
     *
     * @test
     */
    public function testCollectContentDataFromPages()
    {
    }

    /**
     * test collect content data
     *
     * @test
     */
    public function testCollectContentData()
    {
    }

    /**
     * test render single element HTML
     *
     * @test
     */
    public function testRenderSingleElementHTML()
    {
    }

    /**
     * test check if translation exists in language
     *
     * @test
     */
    public function tesCheckIfTranslationsExistInLanguage()
    {
    }
}
