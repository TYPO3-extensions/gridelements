<?php

use GridElementsTeam\Gridelements\Helper\Helper;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class HelperTest extends UnitTestCase
{

    /**
     * test get database connection
     *
     * @test
     */
    public function testGetDatabaseConnection()
    {
        $helper = GeneralUtility::makeInstance(Helper::class);
        $databaseConnection = GeneralUtility::makeInstance(DatabaseConnection::class);
        $helper->setDatabaseConnection($databaseConnection);
        $result = $helper->getDatabaseConnection();
        $this->assertEquals($databaseConnection, $result);
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
     * test get PID from negative UID
     *
     * @test
     */
    public function testGetPidFromNegativeUid()
    {
    }

    /**
     * test get specific ids
     *
     * @test
     */
    public function testGetSpecificIdsWithoutWorkspaceAndOriginalId()
    {
        $helper = GeneralUtility::makeInstance(Helper::class);
        $record = array(
            'uid' => '1',
            'pid' => '2',
            't3ver_oid' => '0'
        );
        $expected = array(
            'uid' => 1,
            'pid' => 2
        );
        $result = $helper->getSpecificIds($record);
        $this->assertEquals($expected, $result);
    }

    /**
     * test get specific ids
     *
     * @test
     */
    public function testGetSpecificIdsWithoutWorkspaceButWithOriginalId()
    {
        $helper = GeneralUtility::makeInstance(Helper::class);
        $record = array(
            'uid' => '1',
            'pid' => '2',
            't3ver_oid' => '3'
        );
        $expected = array(
            'uid' => 1,
            'pid' => 2
        );
        $result = $helper->getSpecificIds($record);
        $this->assertEquals($expected, $result);
    }

    /**
     * test get specific ids
     *
     * @test
     */
    public function testGetSpecificIdsWithWorkspaceAndWithOriginalId()
    {
        $helper = GeneralUtility::makeInstance(Helper::class);
        $GLOBALS['BE_USER'] = $this->getMock(BackendUserAuthentication::class);
        $GLOBALS['BE_USER']->workspace = 1;
        $record = array(
            'uid' => '1',
            'pid' => '2',
            't3ver_oid' => '3'
        );
        $expected = array(
            'uid' => 3,
            'pid' => -1
        );
        $result = $helper->getSpecificIds($record);
        $this->assertEquals($expected, $result);
    }

    /**
     * test get specific ids
     *
     * @test
     */
    public function testGetSpecificIdsWithWorkspaceButWithoutOriginalId()
    {
        $helper = GeneralUtility::makeInstance(Helper::class);
        $GLOBALS['BE_USER'] = $this->getMock(BackendUserAuthentication::class);
        $GLOBALS['BE_USER']->workspace = 1;
        $record = array(
            'uid' => '1',
            'pid' => '2',
            't3ver_oid' => '0'
        );
        $expected = array(
            'uid' => 1,
            'pid' => 2
        );
        $result = $helper->getSpecificIds($record);
        $this->assertEquals($expected, $result);
    }

}
