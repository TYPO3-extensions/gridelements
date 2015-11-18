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
class tx_gridelements_itemsprocfunc_colposlistTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected $tempT3libDb;

	/**
	 * @var language
	 */
	protected $lang;


	public function setUp() {
		$this->tempT3libDb = $GLOBALS['TYPO3_DB'];
		$this->lang = $GLOBALS['LANG'];
	}

	public function tearDown() {
		$GLOBALS['TYPO3_DB'] = $this->tempT3libDb;
		$GLOBALS['LANG'] = $this->lang;
	}


	/**
	 * test items proc func
	 *
	 * @test
	 */
	public function testItemsProcFunc() {
		$params['row']['pid'] = 12;
		$params['row']['CType'] = 'gridelements';
		$params['items'][0][0] = 'left';
		$params['items'][0][1] = '1';
		$params['items'][0][2] = '';
		$params['items'][1][0] = 'normal';
		$params['items'][1][1] = '0';
		$params['items'][1][2] = '';
		$params['items'][2][0] = 'right';
		$params['items'][2][1] = '2';
		$params['items'][2][2] = '';
		$expectedParams['row']['pid'] = 12;
		$expectedParams['row']['CType'] = 'gridelements';
		$expectedParams['items'] = 'Hello world';
		$colPosList = $this->getMock('GridElementsTeam\\Gridelements\\Backend\\ItemsProcFuncs\\ColPosList', array('addColPosListLayoutItems'));
		$colPosList
				->expects($this->once())
				->method('addColPosListLayoutItems')
				->with(
						$this->equalTo(12),
						$this->equalTo($params['items']),
						$this->equalTo('gridelements')
				)
				->will($this->returnValue('Hello world'));
		$testParams = $params;
		$colPosList->itemsProcFunc($testParams);
		$this->assertEquals($expectedParams, $testParams);

		$params['row']['pid'] = -15;
		$dbReturnValue['pid'] = -12;
		$dbReturnValue['CType'] = 'gridelements';
		$t3libDb = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array('exec_SELECTgetSingleRow'));
		$t3libDb
				->expects($this->once())
				->method('exec_SELECTgetSingleRow')
				->with(
						$this->equalTo('pid, CType'),
						$this->equalTo('tt_content'),
						$this->equalTo('uid=15')
				)
				->will($this->returnValue($dbReturnValue));
		$GLOBALS['TYPO3_DB'] = $t3libDb;
		$testParams = $expectedParams = $params;
		$colPosList->itemsProcFunc($testParams);
		$this->assertEquals($expectedParams, $testParams);

		$params['row']['pid'] = -15;
		$dbReturnValue['pid'] = 12;
		$dbReturnValue['CType'] = 'gridelements';
		$t3libDb = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array('exec_SELECTgetSingleRow'));
		$t3libDb
				->expects($this->once())
				->method('exec_SELECTgetSingleRow')
				->with(
						$this->equalTo('pid, CType'),
						$this->equalTo('tt_content'),
						$this->equalTo('uid=15')
				)
				->will($this->returnValue($dbReturnValue));
		$GLOBALS['TYPO3_DB'] = $t3libDb;
		$colPosList = $this->getMock('GridElementsTeam\\Gridelements\\Backend\\ItemsProcFuncs\\ColPosList', array('addColPosListLayoutItems'));
		$colPosList
				->expects($this->once())
				->method('addColPosListLayoutItems')
				->with(
						$this->equalTo(12),
						$this->equalTo($params['items']),
						$this->equalTo('gridelements')
				)
				->will($this->returnValue('Hello world'));
		$expectedParams['row']['pid'] = -15;
		$expectedParams['row']['CType'] = 'gridelements';
		$expectedParams['items'] = 'Hello world';
		$testParams = $params;
		$colPosList->itemsProcFunc($testParams);
		$this->assertEquals($expectedParams, $testParams);
	}

	/**
	 * test add col pos list layout items
	 * method is protected: can't be tested
	 */
	public function testAddColPosListLayoutItems() {
	}
}
