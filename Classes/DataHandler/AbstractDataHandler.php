<?php
namespace GridElementsTeam\Gridelements\DataHandler;

	/***************************************************************
	 *  Copyright notice
	 *
	 *  (c) 2013 Jo Hasenau <info@cybercraft.de>
	 *  (c) 2013 Stefan froemken <froemken@gmail.com>
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

/**
 * Class/Function which offers TCE main hook functions.
 *
 * @author		Jo Hasenau <info@cybercraft.de>
 * @package		TYPO3
 * @subpackage	tx_gridelements
 */
abstract class AbstractDataHandler {

	protected $table;
	protected $pageUid;

	/**
	 * @var \TYPO3\CMS\Core\DataHandling\DataHandler
	 */
	protected $dataHandler;

	/**
	 * @var \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected $databaseConnection;

	/**
	 * @var \TYPO3\CMS\Backend\Form\FormEngine
	 */
	protected $formEngine;

	/**
	 * @var \GridElementsTeam\Gridelements\Backend\LayoutSetup
	 */
	protected $layoutSetup;

	/**
	 * @var \TYPO3\CMS\Backend\Utility\BackendUtility
	 */
	protected $beFunc;

	/**
	 * inject tce forms
	 *
	 * @param \TYPO3\CMS\Backend\Form\FormEngine $formEngine
	 * @return void
	 */
	public function injectTceForms(\TYPO3\CMS\Backend\Form\FormEngine $formEngine) {
		$this->formEngine = $formEngine;
	}

	/**
	 * inject layout setup
	 *
	 * @param \GridElementsTeam\Gridelements\Backend\LayoutSetup $layoutSetup
	 * @return void
	 */
	public function injectLayoutSetup(\GridElementsTeam\Gridelements\Backend\LayoutSetup $layoutSetup) {
		$this->layoutSetup = $layoutSetup;
	}

	/**
	 * inject beFunc
	 *
	 * @param \GridElementsTeam\Gridelements\Backend\WrapperForT3libBeFunc $beFunc
	 * @return void
	 */
	public function injectBeFunc(\GridElementsTeam\Gridelements\Backend\WrapperForT3libBeFunc $beFunc) {
		$this->beFunc = $beFunc;
	}

	/**
	 * initializes this class
	 *
	 * @param   string $table : The name of the table the data should be saved to
	 * @param   integer $pageUid : The uid of the page we are currently working on
	 * @param   \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler
	 * @return  void
	 */
	public function init($table, $pageUid, \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler) {
		$this->setTable($table);
		$this->setPageUid($pageUid);
		$this->setTceMain($dataHandler);
		$this->setDatabaseConnection($GLOBALS['TYPO3_DB']);
		if (!$this->layoutSetup instanceof \GridElementsTeam\Gridelements\Backend\LayoutSetup) {
			$this->injectLayoutSetup(
				\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('GridElementsTeam\Gridelements\Backend\LayoutSetup')->init($pageUid)
			);
		}
		if (!$this->beFunc instanceof \GridElementsTeam\Gridelements\Backend\WrapperForT3libBeFunc) {
			$this->injectBeFunc(\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('GridElementsTeam\Gridelements\Backend\WrapperForT3libBeFunc'));
		}
		if (!$this->formEngine instanceof \TYPO3\CMS\Backend\Form\FormEngine) {
			$this->injectTceForms(\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Backend\Form\FormEngine'));
		}
	}

	/**
	 * setter for table
	 *
	 * @param string $table
	 * @return void
	 */
	public function setTable($table) {
		$this->table = $table;
	}

	/**
	 * getter for table
	 *
	 * @return string table
	 */
	public function getTable() {
		return $this->table;
	}

	/**
	 * setter for pageUid
	 *
	 * @param integer $pageUid
	 * @return void
	 */
	public function setPageUid($pageUid) {
		$this->pageUid = $pageUid;
	}

	/**
	 * getter for pageUid
	 *
	 * @return integer pageUid
	 */
	public function getPageUid() {
		return $this->pageUid;
	}

	/**
	 * setter for dataHandler object
	 *
	 * @param \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler
	 * @return void
	 */
	public function setTceMain(\TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler) {
		$this->dataHandler = $dataHandler;
	}

	/**
	 * getter for dataHandler
	 *
	 * @return \TYPO3\CMS\Core\DataHandling\DataHandler dataHandler
	 */
	public function getTceMain() {
		return $this->dataHandler;
	}

	/**
	 * setter for databaseConnection object
	 *
	 * @param \TYPO3\CMS\Core\Database\DatabaseConnection $databaseConnection
	 * @return void
	 */
	public function setDatabaseConnection(\TYPO3\CMS\Core\Database\DatabaseConnection $databaseConnection) {
		$this->databaseConnection = $databaseConnection;
	}

	/**
	 * getter for databaseConnection
	 *
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection databaseConnection
	 */
	public function getDatabaseConnection() {
		return $this->databaseConnection;
	}

	/**
	 * Function to handle record actions between different grid containers
	 *
	 * @param array $containerUpdateArray
	 * @return void
	 */
	public function doGridContainerUpdate($containerUpdateArray = array()) {
		if(is_array($containerUpdateArray) && count($containerUpdateArray > 0)) {
			foreach ($containerUpdateArray as $containerUid => $newElement) {
				$fieldArray = array(
					'tx_gridelements_children' => 'tx_gridelements_children + ' . $newElement
				);
				$this->databaseConnection->exec_UPDATEquery('tt_content', 'uid=' . $containerUid, $fieldArray, 'tx_gridelements_children');
				$this->getTceMain()->updateRefIndex('tt_content', $containerUid);
			}
		}
	}

	/**
	 * Function to handle record actions for children of translated grid containers
	 *
	 * @param array $containerUpdateArray
	 * @return void
	 */
	public function checkAndUpdateTranslatedChildren($containerUpdateArray = array()) {
		if(is_array($containerUpdateArray) && count($containerUpdateArray > 0)) {
			foreach ($containerUpdateArray as $containerUid => $newElement) {
				$translatedContainers = $this->databaseConnection->exec_SELECTgetRows('uid,sys_language_uid', 'tt_content', 'l18n_parent = ' . $containerUid . \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields('tt_content'));
				if(count($translatedContainers) > 0) {
					foreach($translatedContainers as $languageArray) {
						$targetContainer = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL('tt_content', $languageArray['uid']);
						$fieldArray['tx_gridelements_container'] = $targetContainer['uid'];
						$where = 'tx_gridelements_container = ' . $containerUid . ' AND sys_language_uid = ' . $targetContainer['sys_language_uid'];
						$this->databaseConnection->exec_UPDATEquery('tt_content', $where, $fieldArray, 'tx_gridelements_container');
						$this->getTceMain()->updateRefIndex('tt_content', $targetContainer['uid']);
					}
				}
			}
		}
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/Classes/DataHandler/AbstractDataHandler.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/Classes/DataHandler/AbstractDataHandler.php']);
}
