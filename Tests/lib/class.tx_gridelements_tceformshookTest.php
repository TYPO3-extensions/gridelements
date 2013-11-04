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

class tx_gridelements_tceformshookTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * test get single field before render
	 *
	 * @test
	 */
	public function testGetSingleFieldBeforeRender() {
		$hook = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_gridelements_TCEformsHook');

		$expectedTable = $table = 'tt_content';
		$expectedField = $field = 'bodytext';
		$expectedRow = $row = array(
			'uid' => 12,
			'pid' => 10,
			'CType' => 'gridelements_pi1',
			'tx_gridelements_backend_layout' => '0'
		);
		$expectedPA = $PA = NULL;
		$hook->getSingleField_beforeRender($table, $field, $row, $PA);
		$this->assertEquals($expectedTable, $table);
		$this->assertEquals($expectedField, $field);
		$this->assertEquals($expectedRow, $row);
		$this->assertEquals($expectedPA, $PA);

		$expectedRow = $row = array(
			'uid' => 12,
			'pid' => 10,
			'CType' => 'gridelements_pi1',
			'tx_gridelements_backend_layout' => '2'
		);
		$expectedPA = $PA = NULL;
		$hook->getSingleField_beforeRender($table, $field, $row, $PA);
		$this->assertEquals($expectedTable, $table);
		$this->assertEquals($expectedField, $field);
		$this->assertEquals($expectedRow, $row);
		$this->assertEquals($expectedPA, $PA);

		$expectedField = $field = 'pi_flexform';
		$expectedRow = $row = array(
			'uid' => 12,
			'pid' => 10,
			'CType' => 'gridelements_pi1',
			'tx_gridelements_backend_layout' => '2'
		);
		$PA = NULL;
		$expectedPA['fieldConf']['config']['ds']['*,gridelements_pi1'] = 'Hello world';
		$layoutSetup = $this->getMock('\GridElementsTeam\Gridelements\Backend\LayoutSetup', array('getFlexformConfiguration'));
		$layoutSetup
			->expects($this->once())
			->method('getFlexformConfiguration')
			->will($this->returnValue('Hello world'));
		$hook->injectLayoutSetup($layoutSetup);
		$hook->getSingleField_beforeRender($table, $field, $row, $PA);
		$this->assertEquals($expectedTable, $table);
		$this->assertEquals($expectedField, $field);
		$this->assertEquals($expectedRow, $row);
		$this->assertEquals($expectedPA, $PA);
	}
}
