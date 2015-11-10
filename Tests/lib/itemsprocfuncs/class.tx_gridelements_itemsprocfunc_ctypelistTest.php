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
class tx_gridelements_itemsprocfunc_ctypelistTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	var $tempT3libDb;


	public function setUp() {
		$this->tempT3libDb = $GLOBALS['TYPO3_DB'];
	}

	public function tearDown() {
		$GLOBALS['TYPO3_DB'] = $this->tempT3libDb;
	}


	/**
	 * test items proc func
	 *
	 * @test
	 */
	public function testItemsProcFunc() {
		$params['row']['pid'] = 12;
		$params['row']['colPos'] = 2;
		$params['row']['tx_gridelements_container'] = 23;
		$params['row']['tx_gridelements_columns'] = 34;
		$params['items'][0][0] = 'left';
		$params['items'][0][1] = '1';
		$params['items'][0][2] = '';
		$params['items'][1][0] = 'normal';
		$params['items'][1][1] = '0';
		$params['items'][1][2] = '';
		$params['items'][2][0] = 'right';
		$params['items'][2][1] = '2';
		$params['items'][2][2] = '';
		$colPosList = $this->getMock('GridElementsTeam\\Gridelements\\Backend\\ItemsProcFuncs\\CTypeList', array('checkForAllowedCTypes'));
		$colPosList
				->expects($this->any())
				->method('checkForAllowedCTypes')
				->with(
						$this->equalTo($params['items']),
						$this->equalTo(12),
						$this->equalTo(2),
						$this->equalTo(23),
						$this->equalTo(34)
				);
		$colPosList->itemsProcFunc($params);

		$params['row']['pid'] = -12;
		$t3libDb = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array('exec_SELECTgetSingleRow'));
		$t3libDb
				->expects($this->once())
				->method('exec_SELECTgetSingleRow')
				->with(
						$this->equalTo('pid, CType, colPos, tx_gridelements_container, tx_gridelements_columns'),
						$this->equalTo('tt_content'),
						$this->equalTo('uid=12')
				)
				->will($this->returnValue(NULL));
		$GLOBALS['TYPO3_DB'] = $t3libDb;
		$colPosList = $this->getMock('GridElementsTeam\\Gridelements\\Backend\\ItemsProcFuncs\\CTypeList', array('checkForAllowedCTypes'));
		$colPosList
				->expects($this->never())
				->method('checkForAllowedCTypes');
		$colPosList->itemsProcFunc($params);

		$params['row']['pid'] = -12;
		$returnValue['pid'] = 123;
		$returnValue['colPos'] = 20;
		$returnValue['tx_gridelements_container'] = 45;
		$returnValue['tx_gridelements_columns'] = 67;
		$t3libDb = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array('exec_SELECTgetSingleRow'));
		$t3libDb
				->expects($this->once())
				->method('exec_SELECTgetSingleRow')
				->with(
						$this->equalTo('pid, CType, colPos, tx_gridelements_container, tx_gridelements_columns'),
						$this->equalTo('tt_content'),
						$this->equalTo('uid=12')
				)
				->will($this->returnValue($returnValue));
		$GLOBALS['TYPO3_DB'] = $t3libDb;
		$colPosList = $this->getMock('GridElementsTeam\\Gridelements\\Backend\\ItemsProcFuncs\\CTypeList', array('checkForAllowedCTypes'));
		$colPosList
				->expects($this->once())
				->method('checkForAllowedCTypes')
				->with(
						$this->equalTo($params['items']),
						$this->equalTo(123),
						$this->equalTo(20),
						$this->equalTo(45),
						$this->equalTo(67)
				);
		$colPosList->itemsProcFunc($params);
	}

	/**
	 * test check for allowed CTypesitems proc func
	 *
	 * @test
	 */
	public function testCheckForAllowedCTypes() {
		$cTypeList = $this->getMock('GridElementsTeam\\Gridelements\\Backend\\ItemsProcFuncs\\CTypeList', array('getSelectedBackendLayout'));

		$items = array();
		$pid = 12;
		$pageColumn = 1;
		$gridContainerId = 2;
		$gridColumn = 3;
		$cTypeList
				->expects($this->once())
				->method('getSelectedBackendLayout')
				->with($this->equalTo(12))
				->will($this->returnValue(array()));
		$cTypeList->checkForAllowedCTypes($items, $pid, $pageColumn, $gridContainerId, $gridColumn);
		$this->assertEquals(array(), $items);

		$items[0][0] = 'left';
		$items[0][1] = '1';
		$items[0][2] = '';
		$items[1][0] = 'normal';
		$items[1][1] = '0';
		$items[1][2] = '';
		$items[2][0] = 'right';
		$items[2][1] = '2';
		$items[2][2] = '';
		$pid = 12;
		$pageColumn = -5;
		$gridContainerId = 2;
		$gridColumn = 3;
		$layoutSetup = $this->getMock('GridElementsTeam\\Gridelements\\Backend\\LayoutSetup', array('getLayoutSetup'));
		$layoutSetup
				->expects($this->once())
				->method('getLayoutSetup')
				->will($this->returnValue(array()));
		$cTypeList->injectLayoutSetup($layoutSetup);
		$testItems = $items;
		$cTypeList->checkForAllowedCTypes($testItems, $pid, $pageColumn, $gridContainerId, $gridColumn);
		$this->assertEquals(array(), $testItems);

		$pid = 12;
		$pageColumn = -5;
		$gridContainerId = 2;
		$gridColumn = 3;
		$returnValue['columns'][3] = '1,2';
		$layoutSetup = $this->getMock('GridElementsTeam\\Gridelements\\Backend\\LayoutSetup', array('getLayoutSetup'));
		$layoutSetup
				->expects($this->once())
				->method('getLayoutSetup')
				->will($this->returnValue($returnValue));
		$cTypeList->injectLayoutSetup($layoutSetup);
		$expectedItems = $testItems = $items;
		unset($expectedItems[1]);
		$cTypeList->checkForAllowedCTypes($testItems, $pid, $pageColumn, $gridContainerId, $gridColumn);
		$this->assertEquals($expectedItems, $testItems);

		$pid = 12;
		$pageColumn = -5;
		$gridContainerId = 2;
		$gridColumn = 3;
		$returnValue['columns'][3] = '1,*';
		$layoutSetup = $this->getMock('GridElementsTeam\\Gridelements\\Backend\\LayoutSetup', array('getLayoutSetup'));
		$layoutSetup
				->expects($this->once())
				->method('getLayoutSetup')
				->will($this->returnValue($returnValue));
		$cTypeList->injectLayoutSetup($layoutSetup);
		$expectedItems = $testItems = $items;
		$cTypeList->checkForAllowedCTypes($testItems, $pid, $pageColumn, $gridContainerId, $gridColumn);
		$this->assertEquals($expectedItems, $testItems);
	}
}
