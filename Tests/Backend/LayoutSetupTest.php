<?php

use GridElementsTeam\Gridelements\Backend\LayoutSetup;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

class LayoutSetupTest extends UnitTestCase
{

    /**
     * test get database connection
     *
     * @test
     */
    public function testGetLanguageService()
    {
        $layoutSetup = GeneralUtility::makeInstance(LayoutSetup::class);
        $languageService = GeneralUtility::makeInstance(LanguageService::class);
        $layoutSetup->setLanguageService($languageService);
        $result = $layoutSetup->getLanguageService();
        $this->assertEquals($languageService, $result);
    }

    /**
     * test get backend user
     *
     * @test
     */
    public function testGetBackendUser()
    {
        $layoutSetup = GeneralUtility::makeInstance(LayoutSetup::class);
        $backendUserAuthentication = GeneralUtility::makeInstance(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserAuthentication;
        $result = $layoutSetup->getBackendUser();
        $this->assertEquals($backendUserAuthentication, $result);
    }

    /**
     * test get layout setup
     *
     * @test
     */
    public function testGetLayoutSetup()
    {
    }

    /**
     * test get TypoScript setup
     *
     * @test
     */
    public function testGetTypoScriptSetup()
    {
    }

    /**
     * test get Flexform configuration path and file name
     *
     * @test
     */
    public function testGetFlexformConfigurationPathAndFileName()
    {
    }

    /**
     * test cache current parent
     *
     * @test
     */
    public function testCacheCurrentParent()
    {
    }

    /**
     * test get layout columns
     *
     * @test
     */
    public function testGetLayoutColumns()
    {
    }

    /**
     * test get layout select items
     *
     * @test
     */
    public function testGetLayoutSelectItems()
    {
    }

    /**
     * test get layout columns select items
     *
     * @test
     */
    public function testGetLayoutColumnsSelectItems()
    {
    }

    /**
     * test get layout wizard items
     *
     * @test
     */
    public function testGetLayoutWizardItems()
    {
    }

    /**
     * test get flexform configuration
     *
     * @test
     */
    public function testGetFlexformConfiguration()
    {
    }

    /**
     * test load layout setup
     *
     * @test
     */
    public function testLoadLayoutSetup()
    {
    }
}
