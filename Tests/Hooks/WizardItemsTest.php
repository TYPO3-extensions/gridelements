<?php

use GridElementsTeam\Gridelements\Hooks\WizardItems;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

class WizardItemmsTest extends UnitTestCase
{

    /**
     * test get data base
     *
     * @test
     */
    public function testGetDatabase()
    {
    }

    /**
     * test get language service
     *
     * @test
     */
    public function testGetLanguageService()
    {
        $wizardItems = GeneralUtility::makeInstance(WizardItems::class);
        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageService::class);
        $result = $wizardItems->getLanguageService();
        $this->assertEquals($GLOBALS['LANG'], $result);
    }

    /**
     * test manipulate wizard items
     *
     * @test
     */
    public function testManipulateWizardItems()
    {
    }

    /**
     * test remove empty headers from wizard
     *
     * @test
     */
    public function testRemoveEmptyHeadersFromWizard()
    {
    }

    /**
     * test remove disallowed wizard items
     *
     * @test
     */
    public function testRemoveDisallowedWizardItems()
    {
    }

    /**
     * test get exclude layouts
     *
     * @test
     */
    public function testGetExcludeLayouts()
    {
    }

    /**
     * test add grid items to wizard
     *
     * @test
     */
    public function testAddGridItemsToWizard()
    {
    }

    /**
     * test add grid items to wizard
     *
     * @test
     */
    public function testAddGridValuesToWizardItems()
    {
    }
}
