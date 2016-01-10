<?php

use GridElementsTeam\Gridelements\Backend\ClickMenuOptions;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

class ClickMenuOptionsTest extends UnitTestCase
{

    /**
     * test get database connection
     *
     * @test
     */
    public function testGetLanguageService()
    {
        $clickMenuOptions = GeneralUtility::makeInstance(ClickMenuOptions::class);
        $languageService = GeneralUtility::makeInstance(LanguageService::class);
        $clickMenuOptions->setLanguageService($languageService);
        $result = $clickMenuOptions->getLanguageService();
        $this->assertEquals($languageService, $result);
    }

    /**
     * test get backend user
     *
     * @test
     */
    public function testGetBackendUser()
    {
        $clickMenuOptions = GeneralUtility::makeInstance(ClickMenuOptions::class);
        $backendUserAuthentication = GeneralUtility::makeInstance(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserAuthentication;
        $result = $clickMenuOptions->getBackendUser();
        $this->assertEquals($backendUserAuthentication, $result);
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
     * test DB paste
     *
     * @test
     */
    public function testDB_paste()
    {
    }

}
