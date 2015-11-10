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
class tx_gridelements_db_list_extra_hookTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * test make clip
	 *
	 * @test
	 */
	public function testMakeClip() {
		$dbList = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('GridElementsTeam\\Gridelements\\Hooks\\DatabaseRecordList');

		$table = 'pages';
		$row = array(
				'uid' => 12,
				'pid' => 5
		);
		$cells = 'uid,pid';
		$parentObject = new stdClass();

		$result = $dbList->makeClip($table, $row, $cells, $parentObject);
		$this->assertEquals('uid,pid', $result);
	}

	/**
	 * test make control
	 *
	 * @test
	 */
	public function testMakeControl() {
		$dbList = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('GridElementsTeam\\Gridelements\\Hooks\\DatabaseRecordList');

		$table = 'pages';
		$row = array(
				'uid' => 12,
				'pid' => 5
		);
		$cells = 'uid,pid';
		$parentObject = new stdClass();

		$result = $dbList->makeControl($table, $row, $cells, $parentObject);
		$this->assertEquals('uid,pid', $result);
	}

	/**
	 * test render list header
	 *
	 * @test
	 */
	public function testRenderListHeader() {
		$dbList = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('GridElementsTeam\\Gridelements\\Hooks\\DatabaseRecordList');

		$table = 'pages';
		$currentIdList = '12,23,34';
		$headerColumns = '1,2';
		$parentObject = new stdClass();

		$result = $dbList->renderListHeader($table, $currentIdList, $headerColumns, $parentObject);
		$this->assertEquals('1,2', $result);
	}

	/**
	 * test render list header actions
	 *
	 * @test
	 */
	public function testRenderListHeaderActions() {
		$dbList = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('GridElementsTeam\\Gridelements\\Hooks\\DatabaseRecordList');

		$table = 'pages';
		$currentIdList = '12,23,34';
		$cells = 'uid,pid';
		$parentObject = new stdClass();

		$result = $dbList->renderListHeaderActions($table, $currentIdList, $cells, $parentObject);
		$this->assertEquals('uid,pid', $result);
	}
}
