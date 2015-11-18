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
class tx_gridelements_drawitemhookTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * test pre process with CType empty
	 *
	 * @test
	 */
	public function testPreProcessCTypeEmpty() {
		$drawItemHook = $this->getMock('GridElementsTeam\\Gridelements\\Hooks\\DrawItem', array('renderCTypeGridelements', 'renderCTypeShortcut'));
		$drawItemHook
				->expects($this->never())
				->method('renderCTypeGridelements')
				->will($this->returnValue('gridelements'));
		$drawItemHook
				->expects($this->never())
				->method('renderCTypeShortcut')
				->will($this->returnValue('shortcut'));

		$parentObject = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\View\\PageLayoutView');
		$drawItem = TRUE;
		$headerContent = 'header';
		$itemContent = '<li>Entry</li>';
		$row = array(
				'uid' => 12,
				'pid' => 13
		);
		$drawItemHook->preProcess($parentObject, $drawItem, $headerContent, $itemContent, $row);
		$this->assertEquals('<li>Entry</li>', $itemContent);
		$this->assertEquals(TRUE, $drawItem);
	}

	/**
	 * test pre process with CType grid elements
	 *
	 * @test
	 */
	public function testPreProcessCTypeGridElements() {
		$drawItemHook = $this->getMock('GridElementsTeam\\Gridelements\\Hooks\\DrawItem', array('renderCTypeGridelements', 'renderCTypeShortcut'));
		$drawItemHook
				->expects($this->once())
				->method('renderCTypeGridelements')
				->will($this->returnValue('gridelements'));
		$drawItemHook
				->expects($this->never())
				->method('renderCTypeShortcut')
				->will($this->returnValue('shortcut'));

		$parentObject = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Backend\View\PageLayoutView');
		$drawItem = TRUE;
		$headerContent = 'header';
		$itemContent = '<li>Entry</li>';
		$row = array(
				'uid' => 12,
				'pid' => 13,
				'CType' => 'gridelements_pi1'
		);
		$drawItemHook->preProcess($parentObject, $drawItem, $headerContent, $itemContent, $row);
		$this->assertEquals('<li>Entry</li>gridelements', $itemContent);
		$this->assertEquals(FALSE, $drawItem);
	}

	/**
	 * test pre process with shortcut
	 *
	 * @test
	 */
	public function testPreProcessCTypeShortcut() {
		$drawItemHook = $this->getMock('GridElementsTeam\\Gridelements\\Hooks\\DrawItem', array('renderCTypeGridelements', 'renderCTypeShortcut'));
		$drawItemHook
				->expects($this->never())
				->method('renderCTypeGridelements')
				->will($this->returnValue('gridelements'));
		$drawItemHook
				->expects($this->once())
				->method('renderCTypeShortcut')
				->will($this->returnValue('shortcut'));

		$parentObject = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Backend\View\PageLayoutView');
		$drawItem = TRUE;
		$headerContent = 'header';
		$itemContent = '<li>Entry</li>';
		$row = array(
				'uid' => 12,
				'pid' => 13,
				'CType' => 'shortcut'
		);
		$drawItemHook->preProcess($parentObject, $drawItem, $headerContent, $itemContent, $row);
		$this->assertEquals('<li>Entry</li>shortcut', $itemContent);
		$this->assertEquals(FALSE, $drawItem);
	}

	/**
	 * test set multiple col pos values
	 *
	 * @test
	 */
	public function testSetMultipleColPosValues() {
		$drawItemHook = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('GridElementsTeam\\Gridelements\\Hooks\\DrawItem');

		$parserRows = NULL;
		$colPosValues = NULL;
		$drawItemHook->setMultipleColPosValues($parserRows, $colPosValues);
		$this->assertEquals(NULL, $parserRows);
		$this->assertEquals(NULL, $colPosValues);

		$parserRows = 'Hello world';
		$colPosValues = NULL;
		$drawItemHook->setMultipleColPosValues($parserRows, $colPosValues);
		$this->assertEquals('Hello world', $parserRows);
		$this->assertEquals(NULL, $colPosValues);

		$parserRows = array();
		$expectedRows = array();
		$parserRows['1.']['columns.'] = array();
		$expectedRows['1.']['columns.'] = array();

		$colPosValues = NULL;
		$drawItemHook->setMultipleColPosValues($parserRows, $colPosValues);
		$this->assertEquals($expectedRows, $parserRows);
		$this->assertEquals(NULL, $colPosValues);

		$parserRows['1.']['columns.']['1.']['name'] = 'Header';
		$parserRows['1.']['columns.']['1.']['colspan'] = '2';
		$parserRows['1.']['columns.']['1.']['colPos'] = '1';
		$parserRows['2.']['columns.']['1.']['name'] = 'Text';
		$parserRows['2.']['columns.']['1.']['colPos'] = '10';
		$parserRows['2.']['columns.']['2.']['name'] = 'Image';
		$parserRows['2.']['columns.']['2.']['colPos'] = '2';
		$expectedRows = $parserRows;

		$colPosValues = NULL;
		$expectedColPosValues = array();
		$expectedColPosValues[1]['name'] = 'Überschrift';
		$expectedColPosValues[1]['allowed'] = '';
		$expectedColPosValues[10]['name'] = 'Text';
		$expectedColPosValues[10]['allowed'] = '';
		$expectedColPosValues[2]['name'] = 'Bild';
		$expectedColPosValues[2]['allowed'] = '';

		$drawItemHook->lang = $this->getMock('language');
		$drawItemHook->lang
				->expects($this->any())
				->method('sL')
				->will($this->onConsecutiveCalls('Überschrift', 'Text', 'Bild'));
		$drawItemHook->setMultipleColPosValues($parserRows, $colPosValues);
		$this->assertEquals($expectedRows, $parserRows);
		$this->assertEquals($expectedColPosValues, $colPosValues);

		$parserRows['1.']['columns.']['1.']['name'] = 'Header';
		$parserRows['1.']['columns.']['1.']['colspan'] = '2';
		$parserRows['1.']['columns.']['1.']['colPos'] = '1';
		$parserRows['2.']['columns.']['1.']['name'] = 'Text';
		$parserRows['2.']['columns.']['1.']['colPos'] = '';
		$parserRows['2.']['columns.']['2.']['name'] = 'Image';
		$parserRows['2.']['columns.']['2.']['colPos'] = '2';
		$expectedRows = $parserRows;

		$colPosValues = NULL;
		$expectedColPosValues = array();
		$expectedColPosValues[1]['name'] = 'Überschrift';
		$expectedColPosValues[1]['allowed'] = '';
		$expectedColPosValues[32768]['name'] = 'Nicht zugewiesen';
		$expectedColPosValues[32768]['allowed'] = '';
		$expectedColPosValues[2]['name'] = 'Bild';
		$expectedColPosValues[2]['allowed'] = '';

		$drawItemHook->lang = $this->getMock('language');
		$drawItemHook->lang
				->expects($this->any())
				->method('sL')
				->will($this->onConsecutiveCalls('Überschrift', 'Text', 'Bild'));
		$drawItemHook->lang
				->expects($this->any())
				->method('getLL')
				->will($this->returnValue('Nicht zugewiesen'));
		$drawItemHook->setMultipleColPosValues($parserRows, $colPosValues);
		$this->assertEquals($expectedRows, $parserRows);
		$this->assertEquals($expectedColPosValues, $colPosValues);

		$parserRows['1.']['columns.']['1.']['name'] = 'Header';
		$parserRows['1.']['columns.']['1.']['colspan'] = '2';
		$parserRows['1.']['columns.']['1.']['colPos'] = '1';
		$parserRows['2.']['columns.']['1.']['name'] = 'Text';
		$parserRows['2.']['columns.']['1.']['colPos'] = '';
		$parserRows['2.']['columns.']['2.']['name'] = 'Image';
		$parserRows['2.']['columns.']['2.']['colPos'] = '2';
		$expectedRows = $parserRows;

		$colPosValues = NULL;
		$expectedColPosValues = array();
		$expectedColPosValues[1]['name'] = 'Überschrift';
		$expectedColPosValues[1]['allowed'] = '';
		$expectedColPosValues[32768]['name'] = 'Nicht zugewiesen';
		$expectedColPosValues[32768]['allowed'] = '';
		$expectedColPosValues[2]['name'] = 'Bild';
		$expectedColPosValues[2]['allowed'] = '';

		$drawItemHook->lang = $this->getMock('language');
		$drawItemHook->lang
				->expects($this->any())
				->method('sL')
				->will($this->onConsecutiveCalls('Überschrift', '', 'Bild'));
		$drawItemHook->lang
				->expects($this->any())
				->method('getLL')
				->will($this->returnValue('Nicht zugewiesen'));
		$drawItemHook->setMultipleColPosValues($parserRows, $colPosValues);
		$this->assertEquals($expectedRows, $parserRows);
		$this->assertEquals($expectedColPosValues, $colPosValues);

		$parserRows['1.']['columns.']['1.']['name'] = 'Header';
		$parserRows['1.']['columns.']['1.']['colspan'] = '2';
		$parserRows['1.']['columns.']['1.']['colPos'] = '1';
		$parserRows['2.']['columns.']['1.']['name'] = 'Text';
		$parserRows['2.']['columns.']['1.']['colPos'] = '';
		$parserRows['2.']['columns.']['2.']['name'] = 'Image';
		$parserRows['2.']['columns.']['2.']['colPos'] = '';
		$expectedRows = $parserRows;

		$colPosValues = NULL;
		$expectedColPosValues = array();
		$expectedColPosValues[1]['name'] = 'Überschrift';
		$expectedColPosValues[1]['allowed'] = '';
		$expectedColPosValues[32768]['name'] = 'Nicht zugewiesen';
		$expectedColPosValues[32768]['allowed'] = '';

		$drawItemHook->lang = $this->getMock('language');
		$drawItemHook->lang
				->expects($this->any())
				->method('sL')
				->will($this->onConsecutiveCalls('Überschrift', '', 'Bild'));
		$drawItemHook->lang
				->expects($this->any())
				->method('getLL')
				->will($this->returnValue('Nicht zugewiesen'));
		$drawItemHook->setMultipleColPosValues($parserRows, $colPosValues);
		$this->assertEquals($expectedRows, $parserRows);
		$this->assertEquals($expectedColPosValues, $colPosValues);
	}
}
