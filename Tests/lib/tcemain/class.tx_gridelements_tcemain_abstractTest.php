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
		$hook = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_gridelements_tcemain_abstract');
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
		$hook = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_gridelements_tcemain_abstract');
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
		$hook = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_gridelements_tcemain_abstract');
		$tceMain = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('t3lib_TCEmain');
		$hook->setTceMain($tceMain);
		$result = $hook->getTceMain();
		$this->assertEquals($tceMain, $result);
	}

	/**
	 * test process datamap after database operations
	 *
	 * @test
	 */
	public function testDoGridContainerUpdate() {
		$hook = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_gridelements_tcemain_abstract');
		$t3lib_db = $this->getMock('t3lib_db', array('exec_UPDATEquery'));
		$t3lib_db
			->expects($this->never())
			->method('exec_UPDATEquery')
			->will($this->returnValue(TRUE));
		$GLOBALS['TYPO3_DB'] = $t3lib_db;

		$containerUpdateArray = array();
		$hook->doGridContainerUpdate($containerUpdateArray);

		$hook = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_gridelements_tcemain_abstract');
		$t3lib_db = $this->getMock('t3lib_db', array('exec_UPDATEquery'));
		$t3lib_db
			->expects($this->exactly(4))
			->method('exec_UPDATEquery')
			->will($this->returnValue(TRUE));
		$GLOBALS['TYPO3_DB'] = $t3lib_db;

		$containerUpdateArray = array(1, 2, 3, 4);
		$hook->doGridContainerUpdate($containerUpdateArray);
	}
}
