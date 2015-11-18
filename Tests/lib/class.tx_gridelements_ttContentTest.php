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
class tx_gridelements_ttContentTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

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
	 * test columns items proc func
	 *
	 * @test
	 */
	public function testColumnsItemsProcFunc() {
		$ttContent = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('GridElementsTeam\\Gridelements\\Backend\\TtContent');

		$layoutSetup = $this->getMock('\GridElementsTeam\Gridelements\Backend\LayoutSetup');
		$ttContent->injectLayoutSetup($layoutSetup);
		$params['row']['tx_gridelements_container'] = 'Hello world';
		$expectedParams = $params;
		$ttContent->columnsItemsProcFunc($params);
		$this->assertEquals($expectedParams, $params);

		$t3libDb = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array('exec_SELECTgetSingleRow'));
		$t3libDb
				->expects($this->once())
				->method('exec_SELECTgetSingleRow')
				->with(
						$this->equalTo('*'),
						$this->equalTo('tt_content'),
						$this->equalTo('uid=123 AND tt_content.deleted=0')
				)->will(
						$this->returnValue(array('tx_gridelements_backend_layout' => '2'))
				);
		$GLOBALS['TYPO3_DB'] = $t3libDb;
		$layoutSetup = $this->getMock('GridElementsTeam\\Gridelements\\Backend\\LayoutSetup', array('getLayoutColumnsSelectItems'));
		$layoutSetup
				->expects($this->once())
				->method('getLayoutColumnsSelectItems')
				->with($this->equalTo('2'))
				->will($this->returnValue('Hello world'));
		$ttContent->injectLayoutSetup($layoutSetup);
		$params['row']['tx_gridelements_container'] = '123';
		$expectedParams = $params;
		$expectedParams['items'] = 'Hello world';
		$ttContent->columnsItemsProcFunc($params);
		$this->assertEquals($expectedParams, $params);

		$t3libDb = $this->getMock('t3lib_db', array('exec_SELECTgetSingleRow'));
		$t3libDb
				->method('exec_SELECTgetSingleRow')
				->with(
						$this->equalTo('*'),
						$this->equalTo('tt_content'),
						$this->equalTo('uid=123 AND tt_content.deleted=0')
				)->will(
						$this->returnValue(array('tx_gridelements_backend_layout' => '2'))
				);
		$GLOBALS['TYPO3_DB'] = $t3libDb;
		$returnValue[0][0] = 'left';
		$returnValue[0][1] = '1';
		$returnValue[0][2] = '';
		$returnValue[0][3] = 'text';
		$returnValue[1][0] = 'normal';
		$returnValue[1][1] = '0';
		$returnValue[1][2] = '';
		$returnValue[1][3] = '';
		$returnValue[2][0] = 'right';
		$returnValue[2][1] = '2';
		$returnValue[2][2] = '';
		$returnValue[2][3] = 'image,gridelements,form';
		$layoutSetup = $this->getMock('GridElementsTeam\\Gridelements\\Backend\\LayoutSetup', array('getLayoutColumnsSelectItems'));
		$layoutSetup
				->expects($this->once())
				->method('getLayoutColumnsSelectItems')
				->with($this->equalTo('2'))
				->will($this->returnValue($returnValue));
		$ttContent->injectLayoutSetup($layoutSetup);
		$params = array();
		$params['row']['tx_gridelements_container'] = '123';
		$params['row']['CType'] = 'gridelements';
		$expectedParams = $params;
		$expectedParams['items'][1][0] = 'normal';
		$expectedParams['items'][1][1] = '0';
		$expectedParams['items'][1][2] = '';
		$expectedParams['items'][1][3] = '';
		$expectedParams['items'][2][0] = 'right';
		$expectedParams['items'][2][1] = '2';
		$expectedParams['items'][2][2] = '';
		$expectedParams['items'][2][3] = 'image,gridelements,form';
		$ttContent->columnsItemsProcFunc($params);
		$this->assertEquals($expectedParams, $params);
	}

	/**
	 * test container items proc func
	 *
	 * @test
	 */
	public function testContainerItemsProcFunc() {
		$ttContent = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('GridElementsTeam\\Gridelements\\Backend\\TtContent');

		$layoutSetup = $this->getMock('GridElementsTeam\\Gridelements\\Backend\\LayoutSetup');
		$ttContent->injectLayoutSetup($layoutSetup);
		$params = array();
		$ttContent->containerItemsProcFunc($params);
		$this->assertEquals(array(), $params);


		/*$params['row']['CType'] = 'gridelements_pi1';
		$params['items']['0']['0'] = 'Header';
		$params['items']['0']['1'] = 'Text';
		$params['items']['0']['2'] = 'Image';
		$params['items']['1']['0'] = 'Col right';
		$params['items']['1']['1'] = 'Col left';
		$expectedParams = array();
		$expectedParams['row']['CType'] = 'gridelements_pi1';
		$expectedParams['items']['0']['0'] = 'Header';
		$expectedParams['items']['0']['1'] = 'Text';
		$expectedParams['items']['0']['2'] = 'Image';
		$expectedParams['items']['Col left']['0'] = 'Col right';
		$expectedParams['items']['Col left']['1'] = 'Col left';
		$ttContent->containerItemsProcFunc($params);
		$this->assertEquals($expectedParams, $params);

		$params['row']['uid'] = '123';
		$params['row']['CType'] = 'gridelements_pi1';
		$params['items']['0']['0'] = 'Header';
		$params['items']['0']['1'] = 'Text';
		$params['items']['0']['2'] = 'Image';
		$params['items']['1']['0'] = 'Col right';
		$params['items']['1']['1'] = 'Col left';
		$expectedParams = array();
		$expectedParams['row']['uid'] = '123';
		$expectedParams['row']['CType'] = 'gridelements_pi1';
		$expectedParams['items']['0']['0'] = 'Header';
		$expectedParams['items']['0']['1'] = 'Text';
		$expectedParams['items']['0']['2'] = 'Image';
		$expectedParams['items']['Col left']['0'] = 'Col right';
		$expectedParams['items']['Col left']['1'] = 'Col left';
		$possibleContainers['Col left']['0'] = 'Col right';
		$possibleContainers['Col left']['1'] = 'Col left';
		$ttContent = $this->getMock('tx_gridelements_tt_content', array('lookForChildContainersRecursively'));
		$ttContent
			->expects($this->once())
			->method('lookForChildContainersRecursively')
			->with(
				$this->equalTo(123),
				$this->equalTo($possibleContainers)
			);
		$ttContent->containerItemsProcFunc($params);
		$this->assertEquals($expectedParams, $params);*/
	}

	/**
	 * test layout items proc func
	 *
	 * @test
	 */
	public function testLayoutItemsProcFunc() {
		$ttContent = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('GridElementsTeam\\Gridelements\\Backend\\TtContent');

		$returnValue['0']['0'] = 'Hello world';
		$returnValue['0']['1'] = 123;
		$returnValue['0']['2'] = '';

		$layoutSetup = $this->getMock('\GridElementsTeam\Gridelements\Backend\LayoutSetup', array('getLayoutSelectItems'));
		$layoutSetup
				->expects($this->once())
				->method('getLayoutSelectItems')
				->with($this->equalTo(2))
				->will($this->returnValue($returnValue));
		$ttContent->injectLayoutSetup($layoutSetup);
		$params['items'] = array();
		$params['row']['pid'] = 10;
		$params['row']['colPos'] = 2;
		$expectedParams['row']['pid'] = 10;
		$expectedParams['row']['colPos'] = 2;
		$expectedParams['items']['0']['0'] = 'Hello world';
		$expectedParams['items']['0']['1'] = 123;
		$expectedParams['items']['0']['2'] = '';
		$ttContent->layoutItemsProcFunc($params);
		$this->assertEquals($expectedParams, $params);

		$returnValue['0']['0'] = 'Hello world';
		$returnValue['0']['1'] = 123;
		$returnValue['0']['2'] = '';

		$layoutSetup = $this->getMock('\GridElementsTeam\Gridelements\Backend\LayoutSetup', array('getLayoutSelectItems'));
		$layoutSetup
				->expects($this->once())
				->method('getLayoutSelectItems')
				->with($this->equalTo(2))
				->will($this->returnValue($returnValue));
		$ttContent->injectLayoutSetup($layoutSetup);
		$params['items']['0']['0'] = 'Hello world';
		$params['items']['0']['1'] = 123;
		$params['items']['0']['2'] = '';
		$params['items']['1']['0'] = 'TYPO3';
		$params['items']['1']['1'] = 234;
		$params['items']['1']['2'] = '5';
		$params['row']['pid'] = 10;
		$params['row']['colPos'] = 2;
		$expectedParams['row']['pid'] = 10;
		$expectedParams['row']['colPos'] = 2;
		$expectedParams['items']['0']['0'] = 'Hello world';
		$expectedParams['items']['0']['1'] = 123;
		$expectedParams['items']['0']['2'] = '';
		$ttContent->layoutItemsProcFunc($params);
		$this->assertEquals($expectedParams, $params);
	}
}
