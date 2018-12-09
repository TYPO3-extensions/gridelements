<?php

use GridElementsTeam\Gridelements\Backend\ItemsProcFuncs\ColPosList;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

class ColPosListTest extends UnitTestCase
{

    /**
     * test get database connection
     *
     * @test
     */
    public function testGetLanguageService()
    {
        $itemsProcFunc = GeneralUtility::makeInstance(ColPosList::class);
        $languageService = GeneralUtility::makeInstance(LanguageService::class);
        $itemsProcFunc->setLanguageService($languageService);
        $result = $itemsProcFunc->getLanguageService();
        $this->assertEquals($languageService, $result);
    }

    /**
     * test get backend user
     *
     * @test
     */
    public function testGetBackendUser()
    {
        $itemsProcFunc = GeneralUtility::makeInstance(ColPosList::class);
        $backendUserAuthentication = GeneralUtility::makeInstance(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserAuthentication;
        $result = $itemsProcFunc->getBackendUser();
        $this->assertEquals($backendUserAuthentication, $result);
    }

    /**
     * test get selected backend layout
     *
     * @test
     */
    public function testGetSelectedBackendLayout()
    {
    }

    /**
     * test items proc func
     *
     * @test
     */
    public function testItemsProcFunc()
    {
    }

    /**
     * test add colpos list layout items
     *
     * @test
     */
    public function testAddColPosListLayoutItems()
    {
    }
}
