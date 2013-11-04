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

class tx_gridelements_tcemain_preProcessFieldArrayTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var t3lib_db
	 */
	var $tempT3libDb;





	public function setUp() {
		$this->tempT3libDb = $GLOBALS['TYPO3_DB'];
	}

	public function tearDown() {
		$GLOBALS['TYPO3_DB'] = $this->tempT3libDb;
	}





	/**
	 * test get subpages recursively
	 *
	 * @test
	 */
	public function testGetSubpagesRecursively() {
		$preProcess = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_gridelements_tcemain_preProcessFieldArray');

		$t3lib_db = $this->getMock('t3lib_db', array('exec_SELECTgetRows'));
		$t3lib_db
			->expects($this->at(0))
			->method('exec_SELECTgetRows')
			->will($this->returnValue(NULL));
		$GLOBALS['TYPO3_DB'] = $t3lib_db;
		$subpages = array();
		$preProcess->getSubpagesRecursively(30, $subpages);
		$this->assertEquals(array(), $subpages);

		$returnValue[0]['uid'] = 31;
		$returnValue[0]['backend_layout'] = 0;
		$returnValue[0]['backend_layout_next_level'] = 1;
		$returnValue[1]['uid'] = 32;
		$returnValue[1]['backend_layout'] = 0;
		$returnValue[1]['backend_layout_next_level'] = 2;
		$t3lib_db = $this->getMock('t3lib_db', array('exec_SELECTgetRows'));
		$t3lib_db
			->expects($this->once())
			->method('exec_SELECTgetRows')
			->will($this->returnValue($returnValue));
		$GLOBALS['TYPO3_DB'] = $t3lib_db;
		$subpages = array();
		$expectedSubpages = $returnValue;
		$preProcess->getSubpagesRecursively(30, $subpages);
		$this->assertEquals($expectedSubpages, $subpages);

		$t3lib_db = $this->getMock('t3lib_db', array('exec_SELECTgetRows'));
		$t3lib_db
			->expects($this->once())
			->method('exec_SELECTgetRows')
			->will($this->returnValue($returnValue));
		$GLOBALS['TYPO3_DB'] = $t3lib_db;
		$subpages = array();
		$subpages[0]['uid'] = 35;
		$subpages[0]['backend_layout'] = 0;
		$subpages[0]['backend_layout_next_level'] = 5;
		$expectedSubpages[0]['uid'] = 35;
		$expectedSubpages[0]['backend_layout'] = 0;
		$expectedSubpages[0]['backend_layout_next_level'] = 5;
		$expectedSubpages[1]['uid'] = 31;
		$expectedSubpages[1]['backend_layout'] = 0;
		$expectedSubpages[1]['backend_layout_next_level'] = 1;
		$expectedSubpages[2]['uid'] = 32;
		$expectedSubpages[2]['backend_layout'] = 0;
		$expectedSubpages[2]['backend_layout_next_level'] = 2;
		$preProcess->getSubpagesRecursively(30, $subpages);
		$this->assertEquals($expectedSubpages, $subpages);
	}

	/**
	 * test get available columns
	 *
	 * @test
	 */
	public function testGetAvailableColumns() {
		$preProcess = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_gridelements_tcemain_preProcessFieldArray');

		$layout = '';
		$table = '';
		$id = 0;
		$result = $preProcess->getAvailableColumns($layout, $table, $id);
		$this->assertEquals(array(), $result);

		$layout = 2;
		$table = 'tt_content';
		$tcaColumns['CSV'] = 'Hello world';
		$expectedTcaColumns = 'Hello world';
		$layoutSetup = $this->getMock('\GridElementsTeam\Gridelements\Backend\LayoutSetup', array('getLayoutColumns'));
		$layoutSetup
			->expects($this->once())
			->method('getLayoutColumns')
			->with($this->equalTo($layout))
			->will($this->returnValue($tcaColumns));
		$preProcess->injectLayoutSetup($layoutSetup);
		$result = $preProcess->getAvailableColumns($layout, $table, $id);
		$this->assertEquals($expectedTcaColumns, $result);
	}
}
