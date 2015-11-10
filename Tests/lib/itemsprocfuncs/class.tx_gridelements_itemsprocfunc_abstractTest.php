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
class tx_gridelements_itemsprocfunc_abstract extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

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
	 * test get selected backend layout
	 *
	 * @test
	 */
	public function testGetSelectedBackendLayoutWithNoBackendLayoutDefined() {
		// create a little rootline
		$rootline = array(
				'2' => array(
						'uid' => '23'
				),
				'1' => array(
						'uid' => '20'
				),
				'0' => array(
						'uid' => '17'
				)
		);

		// create the first 3 db results for each rootline entry
		$t3lib_db = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array('exec_SELECTgetSingleRow'));

		$t3lib_db
				->expects($this->at(0))
				->method('exec_SELECTgetSingleRow')
				->will($this->returnValue(array(
						'uid' => '23',
						'backend_layout' => '',
						'backend_layout_next_level' => ''
				)));
		$t3lib_db
				->expects($this->at(1))
				->method('exec_SELECTgetSingleRow')
				->will($this->returnValue(array(
						'uid' => '20',
						'backend_layout' => '',
						'backend_layout_next_level' => ''
				)));
		$t3lib_db
				->expects($this->at(2))
				->method('exec_SELECTgetSingleRow')
				->will($this->returnValue(array(
						'uid' => '17',
						'backend_layout' => '',
						'backend_layout_next_level' => ''
				)));

		$GLOBALS['TYPO3_DB'] = $t3lib_db;

		$colPosList = $this->getMock('tx_gridelements_itemsprocfunc_abstract', array('getRootline'));
		$colPosList
				->expects($this->once())
				->method('getRootline')
				->will($this->returnValue($rootline));

		$result = $colPosList->getSelectedBackendLayout();
		$this->assertEquals(NULL, $result);
	}

	/**
	 * test get selected backend layout
	 *
	 * @test
	 */
	public function testGetSelectedBackendLayoutWithBackendLayoutSetInParentPage() {
		// create a little rootline
		$rootline = array(
				'2' => array(
						'uid' => '23'
				),
				'1' => array(
						'uid' => '20'
				),
				'0' => array(
						'uid' => '17'
				)
		);

		$colPosList = $this->getMock('tx_gridelements_itemsprocfunc_abstract', array('getRootline'));
		$colPosList
				->expects($this->any())
				->method('getRootline')
				->will($this->returnValue($rootline));

		// create the first 3 db results for each rootline entry
		$t3lib_db = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array('exec_SELECTgetSingleRow'));
		$t3lib_db
				->expects($this->at(0))
				->method('exec_SELECTgetSingleRow')
				->will($this->returnValue(array(
						'uid' => '23',
						'backend_layout' => '',
						'backend_layout_next_level' => ''
				)));
		$t3lib_db
				->expects($this->at(1))
				->method('exec_SELECTgetSingleRow')
				->will($this->returnValue(array(
						'uid' => '20',
						'backend_layout' => '2',
						'backend_layout_next_level' => ''
				)));
		$t3lib_db
				->expects($this->at(2))
				->method('exec_SELECTgetSingleRow')
				->will($this->returnValue(array(
						'uid' => '17',
						'backend_layout' => '',
						'backend_layout_next_level' => ''
				)));

		$GLOBALS['TYPO3_DB'] = $t3lib_db;

		$result = $colPosList->getSelectedBackendLayout();
		$this->assertEquals(NULL, $result);
	}

	/**
	 * test get selected backend layout
	 *
	 * @test
	 */
	public function testGetSelectedBackendLayoutWithBackendLayoutNextInRootPage() {
		// create a little rootline
		$rootline = array(
				'2' => array(
						'uid' => '23'
				),
				'1' => array(
						'uid' => '20'
				),
				'0' => array(
						'uid' => '17'
				)
		);

		$colPosList = $this->getMock('tx_gridelements_itemsprocfunc_abstract', array('getRootline'));
		$colPosList
				->expects($this->any())
				->method('getRootline')
				->will($this->returnValue($rootline));

		// create the first 3 db results for each rootline entry
		$t3lib_db = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array('exec_SELECTgetSingleRow'));
		$t3lib_db
				->expects($this->at(0))
				->method('exec_SELECTgetSingleRow')
				->will($this->returnValue(array(
						'uid' => '23',
						'backend_layout' => '',
						'backend_layout_next_level' => ''
				)));
		$t3lib_db
				->expects($this->at(1))
				->method('exec_SELECTgetSingleRow')
				->will($this->returnValue(array(
						'uid' => '20',
						'backend_layout' => '2',
						'backend_layout_next_level' => ''
				)));
		$t3lib_db
				->expects($this->at(2))
				->method('exec_SELECTgetSingleRow')
				->will($this->returnValue(array(
						'uid' => '17',
						'backend_layout' => '1',
						'backend_layout_next_level' => '3'
				)));
		$t3lib_db
				->expects($this->at(3))
				->method('exec_SELECTgetSingleRow')
				->will($this->returnValue(NULL));

		$GLOBALS['TYPO3_DB'] = $t3lib_db;

		$result = $colPosList->getSelectedBackendLayout();
		$this->assertEquals(NULL, $result);
	}
}
