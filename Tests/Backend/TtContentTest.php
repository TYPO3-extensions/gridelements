<?php

use GridElementsTeam\Gridelements\Backend\TtContent;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class TtContentTest extends UnitTestCase
{

    /**
     * test get database connection
     *
     * @test
     */
    public function testGetDatabaseConnection()
    {
        $ttContent = GeneralUtility::makeInstance(TtContent::class);
        $databaseConnection = GeneralUtility::makeInstance(DatabaseConnection::class);
        $ttContent->setDatabaseConnection($databaseConnection);
        $result = $ttContent->getDatabaseConnection();
        $this->assertEquals($databaseConnection, $result);
    }

    /**
     * test columns items proc func
     *
     * @test
     */
    public function testColumnsItemsProcFunc()
    {
    }

    /**
     * test container items proc func
     *
     * @test
     */
    public function testContainerItemsProcFunc()
    {
    }

    /**
     * test removes items from list of selectable containers
     *
     * @test
     */
    public function testRemovesItemsFromListOfSelectableContainers()
    {
    }

    /**
     * test delete unallowed container
     *
     * @test
     */
    public function testDeleteUnallowedContainer()
    {
    }

    /**
     * test layout items proc func
     *
     * @test
     */
    public function testLayoutItemsProcFunc()
    {
    }

    /**
     * test look for child containers recursively
     *
     * @test
     */
    public function testLookForChildContainersRecursively()
    {
    }

}
