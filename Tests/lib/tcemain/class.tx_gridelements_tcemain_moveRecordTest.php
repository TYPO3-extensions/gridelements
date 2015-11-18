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
class tx_gridelements_tcemain_moveRecordTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * test create update array for splitted elements
	 * some elements of tt_content are splitted by an "x" in its uid
	 *
	 * @test
	 */
	public function testCreateUpdateArrayForSplittedElements() {
		$recordUid = 123;
		$destPid = 0;
		$targetUid = 456;
		$target[0] = -12;
		$target[1] = 34;
		$containerUpdateArray = array();
		$expectedUpdateArray['colPos'] = -1;
		$expectedUpdateArray['sorting'] = 0;
		$expectedUpdateArray['tx_gridelements_container'] = 456;
		$expectedUpdateArray['tx_gridelements_columns'] = 34;
		$expectedUpdateArray['pid'] = null;
		$tceMain = $this->getMock('TYPO3\\CMS\\Core\\DataHandling\\DataHandler', array('updateDB'));
		$tceMain
				->method('updateDB')
				->with(
						$this->equalTo('tt_content'),
						$this->equalTo(123),
						$this->equalTo($expectedUpdateArray)
				)
				->will($this->returnValue(''));
		$moveRecord = $this->getMock('GridElementsTeam\\Gridelements\\DataHandler\\MoveRecord', array('doGridContainerUpdate'));
		$moveRecord
				->method('doGridContainerUpdate')
				->with(
						$this->equalTo(array(0 => 456)),
						$this->equalTo($tceMain)
				)
				->will($this->returnValue(NULL));
		$moveRecord->setTceMain($tceMain);
		$result = $moveRecord->createUpdateArrayForSplitElements($recordUid, $destPid, $targetUid, $target, $containerUpdateArray);
		$this->assertEquals($expectedUpdateArray, $result);
		$this->assertEquals(-123, $destPid);

		$containerUpdateArray[0] = 123;
		$tceMain = $this->getMock('TYPO3\\CMS\\Core\\DataHandling\\DataHandler', array('updateDB'));
		$tceMain
				->method('updateDB')
				->with(
						$this->equalTo('tt_content'),
						$this->equalTo(123),
						$this->equalTo($expectedUpdateArray)
				)
				->will($this->returnValue(''));
		$moveRecord = $this->getMock('GridElementsTeam\\Gridelements\\DataHandler\\MoveRecord', array('doGridContainerUpdate'));
		$moveRecord
				->method('doGridContainerUpdate')
				->with(
						$this->equalTo(array(
								0 => 123,
								1 => 456
						)),
						$this->equalTo($tceMain)
				)
				->will($this->returnValue(NULL));
		$moveRecord->setTceMain($tceMain);
		$result = $moveRecord->createUpdateArrayForSplitElements($recordUid, $destPid, $targetUid, $target, $containerUpdateArray, $tceMain);
		$this->assertEquals($expectedUpdateArray, $result);
		$this->assertEquals(-123, $destPid);

		$target[0] = 12;
		$expectedUpdateArray['pid'] = 12;
		$expectedUpdateArray['colPos'] = 34;
		$expectedUpdateArray['sorting'] = 256;
		$expectedUpdateArray['tx_gridelements_container'] = 0;
		$expectedUpdateArray['tx_gridelements_columns'] = 0;
		$tceMain = $this->getMock('TYPO3\\CMS\\Core\\DataHandling\\DataHandler', array('updateDB'));
		$tceMain
				->method('updateDB')
				->with(
						$this->equalTo('tt_content'),
						$this->equalTo(123),
						$this->equalTo($expectedUpdateArray)
				)
				->will($this->returnValue(''));
		$moveRecord = $this->getMock('GridElementsTeam\\Gridelements\\DataHandler\\MoveRecord', array('doGridContainerUpdate'));
		$moveRecord
				->method('doGridContainerUpdate')
				->with(
						$this->equalTo(array(0 => 123)),
						$this->equalTo($tceMain)
				)
				->will($this->returnValue(NULL));
		$moveRecord->setTceMain($tceMain);
		$result = $moveRecord->createUpdateArrayForSplitElements($recordUid, $destPid, $targetUid, $target, $containerUpdateArray, $tceMain);
		$this->assertEquals($expectedUpdateArray, $result);
		$this->assertEquals(-123, $destPid);

		$target[0] = 12;
		$recordUid = 456;
		$targetUid = 456;
		$expectedUpdateArray = array();
		$expectedUpdateArray['colPos'] = 34;
		$expectedUpdateArray['sorting'] = 256;
		$expectedUpdateArray['tx_gridelements_container'] = 0;
		$expectedUpdateArray['tx_gridelements_columns'] = 0;
		$tceMain = $this->getMock('TYPO3\\CMS\\Core\\DataHandling\\DataHandler', array('updateDB'));
		$tceMain
				->method('updateDB')
				->with(
						$this->equalTo('tt_content'),
						$this->equalTo(456),
						$this->equalTo($expectedUpdateArray)
				)
				->will($this->returnValue(''));
		$moveRecord = $this->getMock('GridElementsTeam\\Gridelements\\DataHandler\\MoveRecord', array('doGridContainerUpdate'));
		$moveRecord
				->method('doGridContainerUpdate')
				->with(
						$this->equalTo(array(0 => 123)),
						$this->equalTo($tceMain)
				)
				->will($this->returnValue(NULL));
		$moveRecord->setTceMain($tceMain);
		$result = $moveRecord->createUpdateArrayForSplitElements($recordUid, $destPid, $targetUid, $target, $containerUpdateArray, $tceMain);
		$this->assertEquals($expectedUpdateArray, $result);
		$this->assertEquals(-456, $destPid);
	}

	/**
	 * test create update array for container move
	 *
	 * @test
	 */
	public function testCreateUpdateArrayForContainerMove() {
		$originalElement = array();
		$expectedUpdateArray = array();
		$expectedUpdateArray['colPos'] = 0;
		$expectedUpdateArray['sorting'] = 0;
		$expectedUpdateArray['tx_gridelements_container'] = 0;
		$expectedUpdateArray['tx_gridelements_columns'] = 0;
		$tceMain = $this->getMock('TYPO3\\CMS\\Core\\DataHandling\\DataHandler', array('updateDB'));
		$tceMain
				->method('updateDB')
				->with(
						$this->equalTo('tt_content'),
						$this->equalTo(123),
						$this->equalTo($expectedUpdateArray)
				)
				->will($this->returnValue(''));
		$tceMain->moveChildren = FALSE;
		$moveRecord = $this->getMock('GridElementsTeam\\Gridelements\\DataHandler\\MoveRecord', array('doGridContainerUpdate'));
		$moveRecord
				->method('doGridContainerUpdate')
				->with(
						$this->equalTo(array()),
						$this->equalTo($tceMain)
				)
				->will($this->returnValue(NULL));
		$moveRecord->setTceMain($tceMain);
		$result = $moveRecord->createUpdateArrayForContainerMove($originalElement);
		$this->assertEquals($expectedUpdateArray, $result);
		$this->assertEquals(FALSE, $tceMain->moveChildren);

		$originalElement['CType'] = 'gridelements_pi1';
		$expectedUpdateArray = array();
		$expectedUpdateArray['colPos'] = 0;
		$expectedUpdateArray['sorting'] = 0;
		$expectedUpdateArray['tx_gridelements_container'] = 0;
		$expectedUpdateArray['tx_gridelements_columns'] = 0;
		$tceMain = $this->getMock('TYPO3\\CMS\\Core\\DataHandling\\DataHandler', array('updateDB'));
		$tceMain
				->method('updateDB')
				->with(
						$this->equalTo('tt_content'),
						$this->equalTo(123),
						$this->equalTo($expectedUpdateArray)
				)
				->will($this->returnValue(''));
		$tceMain->moveChildren = FALSE;
		$moveRecord = $this->getMock('GridElementsTeam\\Gridelements\\DataHandler\\MoveRecord', array('doGridContainerUpdate'));
		$moveRecord
				->method('doGridContainerUpdate')
				->with(
						$this->equalTo(array(0 => 123)),
						$this->equalTo($tceMain)
				)
				->will($this->returnValue(NULL));
		$moveRecord->setTceMain($tceMain);
		$result = $moveRecord->createUpdateArrayForContainerMove($originalElement);
		$this->assertEquals($expectedUpdateArray, $result);
		$this->assertEquals(TRUE, $tceMain->moveChildren);
	}
}
