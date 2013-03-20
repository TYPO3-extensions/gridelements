<?php
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
class tx_gridelements_tcemain_abstract {

	protected $table;
	protected $pageUid;

	/**
	 * @var \TYPO3\CMS\Core\DataHandling\DataHandler
	 */
	protected $tceMain;

	/**
	 * @var \TYPO3\CMS\Backend\Form\FormEngine
	 */
	protected $tceForms;

	/**
	 * @var tx_gridelements_layoutsetup
	 */
	protected $layoutSetup;

	/**
	 * @var \TYPO3\CMS\Backend\Utility\BackendUtility
	 */
	protected $beFunc;

	/**
	 * inject tce forms
	 *
	 * @param \TYPO3\CMS\Backend\Form\FormEngine $tceForms
	 * @return void
	 */
	public function injectTceForms(\TYPO3\CMS\Backend\Form\FormEngine $tceForms) {
		$this->tceForms = $tceForms;
	}

	/**
	 * inject layout setup
	 *
	 * @param tx_gridelements_layoutsetup $layoutSetup
	 * @return void
	 */
	public function injectLayoutSetup(tx_gridelements_layoutsetup $layoutSetup) {
		$this->layoutSetup = $layoutSetup;
	}

	/**
	 * inject beFunc
	 *
	 * @param wrapperForT3libBeFunc $beFunc
	 * @return void
	 */
	public function injectBeFunc(wrapperForT3libBeFunc $beFunc) {
		$this->beFunc = $beFunc;
	}

	/**
	 * initializes this class
	 *
	 * @param   string $table: The name of the table the data should be saved to
	 * @param   integer $pageUid: The uid of the page we are currently working on
	 * @param   \TYPO3\CMS\Core\DataHandling\DataHandler $tceMain
	 * @return void
	 */
	public function init($table, $pageUid, \TYPO3\CMS\Core\DataHandling\DataHandler $tceMain) {
		$this->setTable($table);
		$this->setPageUid($pageUid);
		$this->setTceMain($tceMain);
		if (!$this->layoutSetup instanceof tx_gridelements_layoutsetup) {
			$this->injectLayoutSetup(
				\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_gridelements_layoutsetup')->init($pageUid)
			);
		}
		if (!$this->beFunc instanceof wrapperForT3libBeFunc) {
			$this->injectBeFunc(\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('wrapperForT3libBeFunc'));
		}
		if (!$this->tceForms instanceof \TYPO3\CMS\Backend\Form\FormEngine) {
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
	 * setter for tceMain object
	 *
	 * @param \TYPO3\CMS\Core\DataHandling\DataHandler $tceMain
	 * @return void
	 */
	public function setTceMain(\TYPO3\CMS\Core\DataHandling\DataHandler $tceMain) {
		$this->tceMain = $tceMain;
	}

	/**
	 * getter for tceMain
	 *
	 * @return \TYPO3\CMS\Core\DataHandling\DataHandler tceMain
	 */
	public function getTceMain() {
		return $this->tceMain;
	}

	/**
	 * Function to handle record actions between different grid containers
	 *
	 * @param array $containerUpdateArray
	 * @internal param int $uid : The uid of the grid container that needs an update
	 * @return void
	 */
	public function doGridContainerUpdate($containerUpdateArray = array()) {
		if(count($containerUpdateArray > 0)) {
			foreach ($containerUpdateArray as $containerUid => $newElement) {
				$fieldArray = array(
					'tx_gridelements_children' => 'tx_gridelements_children + ' . $newElement
				);
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tt_content', 'uid=' . $containerUid, $fieldArray, 'tx_gridelements_children');
				$this->getTceMain()->updateRefIndex('tt_content', $containerUid);
			}
		}
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/lib/tcemain/class.tx_gridelements_itemsprocfunc_abstract.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/lib/class.tx_gridelements_itemsprocfunc_abstract.php']);
}
?>