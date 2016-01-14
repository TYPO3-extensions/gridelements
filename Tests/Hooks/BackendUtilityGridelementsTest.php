<?php

use GridElementsTeam\Gridelements\Hooks\BackendUtilityGridelements;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class BackendUtilityGridelementsTest extends UnitTestCase
{

    /**
     * test get database connection
     *
     * @test
     */
    public function testGetDatabaseConnection()
    {
        $backendUtilityGridelements = GeneralUtility::makeInstance(BackendUtilityGridelements::class);
        $databaseConnection = GeneralUtility::makeInstance(DatabaseConnection::class);
        $backendUtilityGridelements->setDatabaseConnection($databaseConnection);
        $result = $backendUtilityGridelements->getDatabaseConnection();
        $this->assertEquals($databaseConnection, $result);
    }

    /**
     * test get get flexform data structure post process data structure
     *
     * @test
     */
    public function testGetFlexFormDS_postProcessDS()
    {
    }


}