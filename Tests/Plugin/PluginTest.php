<?php

use GridElementsTeam\Gridelements\Plugin\Gridelements;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PluginTest extends UnitTestCase
{

    /**
     * test get page renderer
     *
     * @test
     */
    public function testGetPageRenderer()
    {
        $gridelements = GeneralUtility::makeInstance(Gridelements::class);
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $result = $gridelements->getPageRenderer();
        $this->assertEquals($pageRenderer, $result);
    }

    /**
     * test main
     *
     * @test
     */
    public function testMain()
    {
    }

    /**
     * test get children
     *
     * @test
     */
    public function testGetChildren()
    {
    }

    /**
     * test get plugin flexform data
     *
     * @test
     */
    public function testGetPluginFlexFormData()
    {
    }

    /**
     * test render children into parent columns
     *
     * @test
     */
    public function testRenderChildrenIntoParentColumns()
    {
    }

    /**
     * test get used columns
     *
     * @test
     */
    public function testGetUsedColumns()
    {
    }

    /**
     * test copy current parent grid
     *
     * @test
     */
    public function testCopyCurrentParentGrid()
    {
    }

    /**
     * test reset current parent grid
     *
     * @test
     */
    public function testResetCurrentParentGrid()
    {
    }

    /**
     * test get parent grid data
     *
     * @test
     */
    public function testGetParentGridData()
    {
    }

    /**
     * test set parent grid data
     *
     * @test
     */
    public function testSetParentGridData()
    {
    }

    /**
     * test render children into parent column
     *
     * @test
     */
    public function testRenderChildrenIntoParentColumn()
    {
    }

    /**
     * test render columns into parent grid
     *
     * @test
     */
    public function testRenderColumnsIntoParentGrid()
    {
    }

    /**
     * test user get tree list
     *
     * @test
     */
    public function testUserGetTreeList()
    {
    }

    /**
     * test init plugin flexform
     *
     * @test
     */
    public function testInitPluginFlexform()
    {
    }

    /**
     * test get flexform value
     *
     * @test
     */
    public function testGetFlexformValue()
    {
    }

    /**
     * test get flexform value from sheet array
     *
     * @test
     */
    public function testGetFlexformValueFromSheetArray()
    {
    }

    /**
     * test get flexform sections recursively
     *
     * @test
     */
    public function testGetFlexformSectionsRecursively()
    {
    }
}
