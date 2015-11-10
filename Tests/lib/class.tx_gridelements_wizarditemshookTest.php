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
class tx_gridelements_wizarditemshookTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	var $tempT3libDb;

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
	 * test removing empty headers from wizard
	 */
	public function testRemoveEmptyHeadersFromWizard() {
		$itemsHook = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('GridElementsTeam\\Gridelements\\Hooks\\WizardItems');

		$wizardItems['test']['header'] = 'Header without element';
		$wizardItems['common']['header'] = 'Content elements';
		$wizardItems['common_text']['title'] = 'Text';
		$wizardItems['common_text']['description'] = 'Typical Text Element';
		$wizardItems['common_image']['title'] = 'Images';
		$wizardItems['common_image']['description'] = 'Amount of images';
		$wizardItems['forms']['header'] = 'Forms';
		$wizardItems['forms_login']['title'] = 'Login';
		$wizardItems['forms_login']['description'] = 'Inserts a login/logout formular';
		$expectedItems = $tempWizardItems = $wizardItems;
		unset($expectedItems['test']);
		$itemsHook->removeEmptyHeadersFromWizard($tempWizardItems);
		$this->assertEquals($expectedItems, $tempWizardItems);
	}

	/**
	 * test remove disallowed wizard items
	 */
	public function testRemoveDisallowedWizardItems() {
		$itemsHook = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('GridElementsTeam\\Gridelements\\Hooks\\WizardItems');

		$allowed['text'] = 1;
		$allowed['login'] = 1;
		$wizardItems['common']['header'] = 'Content elements';
		$wizardItems['common_text']['title'] = 'Text';
		$wizardItems['common_text']['description'] = 'Typical Text Element';
		$wizardItems['common_text']['tt_content_defValues']['CType'] = 'text';
		$wizardItems['common_image']['title'] = 'Images';
		$wizardItems['common_image']['description'] = 'Amount of images';
		$wizardItems['common_image']['tt_content_defValues']['CType'] = 'image';
		$wizardItems['forms']['header'] = 'Forms';
		$wizardItems['forms_login']['title'] = 'Login';
		$wizardItems['forms_login']['description'] = 'Inserts a login/logout formular';
		$wizardItems['forms_login']['tt_content_defValues']['CType'] = 'login';
		$expectedItems = $tempWizardItems = $wizardItems;
		unset($expectedItems['common_image']);
		$itemsHook->removeDisallowedWizardItems($allowed, $tempWizardItems);
		$this->assertEquals($expectedItems, $tempWizardItems);
	}

	/**
	 * test add grid items to wizard
	 */
	public function testAddGridItemsToWizard() {
		$itemsHook = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('GridElementsTeam\\Gridelements\\Hooks\\WizardItems');
		$language = $this->getMock('language', array('sL'));
		$language
				->expects($this->once())
				->method('sL')
				->with($this->equalTo('LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tx_gridelements_backend_layout_wizard_label'))
				->will($this->returnValue('Rasterelemente'));
		$GLOBALS['LANG'] = $language;

		$newGridItem[0]['title'] = 'News';
		$newGridItem[0]['tll'] = '0';
		$newGridItem[0]['uid'] = 1;
		$newGridItem[0]['description'] = 'Inserts a news element';
		$wizardItems['common']['header'] = 'Content elements';
		$wizardItems['common_text']['title'] = 'Text';
		$wizardItems['common_text']['description'] = 'Typical Text Element';
		$wizardItems['common_image']['title'] = 'Images';
		$wizardItems['common_image']['description'] = 'Amount of images';
		$wizardItems['forms']['header'] = 'Forms';
		$wizardItems['forms_login']['title'] = 'Login';
		$wizardItems['forms_login']['description'] = 'Inserts a login/logout formular';
		$wizardItems['gridelements']['header'] = 'Rasterelemente';
		$wizardItems['gridelements_grid_1']['icon'] = '../typo3conf/ext/gridelements/Resources/Public/Backend/Images/new_content_el.gif';
		$wizardItems['gridelements_grid_1']['params'] = '&defVals[tt_content][CType]=gridelements_pi1&defVals[tt_content][tx_gridelements_backend_layout]=1';
		$wizardItems['gridelements_grid_1']['title'] = 'News';
		$wizardItems['gridelements_grid_1']['description'] = 'Inserts a news element';
		$wizardItems['gridelements_grid_1']['tt_content_defValues']['CType'] = 'gridelements_pi1';
		$wizardItems['gridelements_grid_1']['tt_content_defValues']['tx_gridelements_backend_layout'] = 1;
		$expectedItems = $tempWizardItems = $wizardItems;
		$itemsHook->addGridItemsToWizard($newGridItem, $tempWizardItems);
		$this->assertEquals($expectedItems, $tempWizardItems);

		$language = $this->getMock('language', array('sL'));
		$language
				->expects($this->once())
				->method('sL')
				->with($this->equalTo('LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tx_gridelements_backend_layout_wizard_label'))
				->will($this->returnValue('Rasterelemente'));
		$GLOBALS['LANG'] = $language;
		$newGridItem[0]['tll'] = '1';
		$wizardItems['gridelements_grid_1']['params'] = '&defVals[tt_content][CType]=gridelements_pi1&defVals[tt_content][tx_gridelements_backend_layout]=1&isTopLevelLayout';
		$expectedItems = $tempWizardItems = $wizardItems;
		$itemsHook->addGridItemsToWizard($newGridItem, $tempWizardItems);
		$this->assertEquals($expectedItems, $tempWizardItems);
	}

	/**
	 * test init wizard items
	 *
	 * @test
	 */
	public function testAddGridValuesToWizardItems() {
		$ttContent = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('GridElementsTeam\\Gridelements\\Hooks\\WizardItems');

		$wizardItems = array();
		$container = 0;
		$column = 0;
		$ttContent->addGridValuesToWizardItems($wizardItems, $container, $column);
		$this->assertEquals(array(), $wizardItems);

		$wizardItems['common']['header'] = 'Content elements';
		$wizardItems['common_text']['title'] = 'Text';
		$wizardItems['common_text']['description'] = 'Typical Text Element';
		$wizardItems['common_image']['title'] = 'Images';
		$wizardItems['common_image']['description'] = 'Amount of images';
		$wizardItems['forms']['header'] = 'Forms';
		$wizardItems['forms_login']['title'] = 'Login';
		$wizardItems['forms_login']['description'] = 'Inserts a login/logout formular';
		$expectedWizardItems = $wizardItemsForTesting = $wizardItems;
		$ttContent->addGridValuesToWizardItems($wizardItemsForTesting, $container, $column);
		$this->assertEquals($expectedWizardItems, $wizardItemsForTesting);

		$container = 1;
		$column = 0;
		$expectedWizardItems['common_text']['tt_content_defValues']['tx_gridelements_container'] = 1;
		$expectedWizardItems['common_text']['params'] .= '&defVals[tt_content][tx_gridelements_container]=1';
		$expectedWizardItems['common_image']['tt_content_defValues']['tx_gridelements_container'] = 1;
		$expectedWizardItems['common_image']['params'] .= '&defVals[tt_content][tx_gridelements_container]=1';
		$expectedWizardItems['forms_login']['tt_content_defValues']['tx_gridelements_container'] = 1;
		$expectedWizardItems['forms_login']['params'] .= '&defVals[tt_content][tx_gridelements_container]=1';
		$wizardItemsForTesting = $wizardItems;
		$ttContent->addGridValuesToWizardItems($wizardItemsForTesting, $container, $column);
		$this->assertEquals($expectedWizardItems, $wizardItemsForTesting);

		$container = 1;
		$column = 2;
		$expectedWizardItems['common_text']['tt_content_defValues']['tx_gridelements_columns'] = 2;
		$expectedWizardItems['common_text']['params'] .= '&defVals[tt_content][tx_gridelements_columns]=2';
		$expectedWizardItems['common_image']['tt_content_defValues']['tx_gridelements_columns'] = 2;
		$expectedWizardItems['common_image']['params'] .= '&defVals[tt_content][tx_gridelements_columns]=2';
		$expectedWizardItems['forms_login']['tt_content_defValues']['tx_gridelements_columns'] = 2;
		$expectedWizardItems['forms_login']['params'] .= '&defVals[tt_content][tx_gridelements_columns]=2';
		$wizardItemsForTesting = $wizardItems;
		$ttContent->addGridValuesToWizardItems($wizardItemsForTesting, $container, $column);
		$this->assertEquals($expectedWizardItems, $wizardItemsForTesting);
	}
}
