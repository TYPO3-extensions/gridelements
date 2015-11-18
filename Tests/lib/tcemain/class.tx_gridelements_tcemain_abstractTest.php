<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Stefan Froemken <froemken@gmail.com>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
class tx_gridelements_tcemain_abstractTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * test get table
	 *
	 * @test
	 */
	public function testGetTable() {
		$hook = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('GridElementsTeam\\Gridelements\\DataHandler\\AfterDatabaseOperations');
		$table = 'tt_content';
		$hook->setTable($table);
		$result = $hook->getTable();
		$this->assertEquals($table, $result);
	}

	/**
	 * test get page uid
	 *
	 * @test
	 */
	public function testGetPageUid() {
		$hook = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('GridElementsTeam\\Gridelements\\DataHandler\\AfterDatabaseOperations');
		$pageUid = 123;
		$hook->setPageUid($pageUid);
		$result = $hook->getPageUid();
		$this->assertEquals($pageUid, $result);
	}

	/**
	 * test get tce main
	 *
	 * @test
	 */
	public function testGetTceMain() {
		$hook = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('GridElementsTeam\\Gridelements\\DataHandler\\AfterDatabaseOperations');
		$tceMain = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
		$hook->setTceMain($tceMain);
		$result = $hook->getTceMain();
		$this->assertEquals($tceMain, $result);
	}

}
