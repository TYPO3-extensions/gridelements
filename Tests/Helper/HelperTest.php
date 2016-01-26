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
        $GLOBALS['TYPO3_DB'] = GeneralUtility::makeInstance(DatabaseConnection::class);
        $helper = Helper::getInstance();
        $helper->setDatabaseConnection($GLOBALS['TYPO3_DB']);
        $result = $helper->getDatabaseConnection();
        $this->assertEquals($GLOBALS['TYPO3_DB'], $result);
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
        $GLOBALS['TYPO3_DB'] = GeneralUtility::makeInstance(DatabaseConnection::class);
        $helper = Helper::getInstance();
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
        $GLOBALS['TYPO3_DB'] = GeneralUtility::makeInstance(DatabaseConnection::class);
        $helper = Helper::getInstance();
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
        $GLOBALS['TYPO3_DB'] = GeneralUtility::makeInstance(DatabaseConnection::class);
        $helper = Helper::getInstance();
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
        $GLOBALS['TYPO3_DB'] = GeneralUtility::makeInstance(DatabaseConnection::class);
        $helper = Helper::getInstance();
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
