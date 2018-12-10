<?php

use GridElementsTeam\Gridelements\Helper\Helper;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Tests\UnitTestCase;

class HelperTest extends UnitTestCase
{

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
        $helper = Helper::getInstance();
        $record = [
            'uid' => '1',
            'pid' => '2',
            't3ver_oid' => '0'
        ];
        $expected = [
            'uid' => 1,
            'pid' => 2
        ];
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
        $helper = Helper::getInstance();
        $record = [
            'uid' => '1',
            'pid' => '2',
            't3ver_oid' => '3'
        ];
        $expected = [
            'uid' => 1,
            'pid' => 2
        ];
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
        $helper = Helper::getInstance();
        $GLOBALS['BE_USER'] = $this->getMock(BackendUserAuthentication::class);
        $GLOBALS['BE_USER']->workspace = 1;
        $record = [
            'uid' => '1',
            'pid' => '2',
            't3ver_oid' => '3'
        ];
        $expected = [
            'uid' => 3,
            'pid' => -1
        ];
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
        $helper = Helper::getInstance();
        $GLOBALS['BE_USER'] = $this->getMock(BackendUserAuthentication::class);
        $GLOBALS['BE_USER']->workspace = 1;
        $record = [
            'uid' => '1',
            'pid' => '2',
            't3ver_oid' => '0'
        ];
        $expected = [
            'uid' => 1,
            'pid' => 2
        ];
        $result = $helper->getSpecificIds($record);
        $this->assertEquals($expected, $result);
    }
}
