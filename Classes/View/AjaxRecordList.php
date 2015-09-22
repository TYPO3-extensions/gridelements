<?php
namespace GridElementsTeam\Gridelements\View;

/***************************************************************
 *  Copyright notice
 *  (c) 2011 Dirk Hoffmann <dirk-hoffmann@telekom.de>
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use GridElementsTeam\Gridelements\Helper\Helper;
use GridElementsTeam\Gridelements\Xclass\DatabaseRecordList;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\AjaxRequestHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * AJAX request dispatcher for record list
 * @author Dirk Hoffmann <dirk-hoffmann@telekom.de>
 * @package TYPO3
 * @subpackage tx_gridelements
 */
class AjaxRecordList {

	/**
	 * The content for the ajax output
	 * @var    string
	 */
	protected $content;

	/**
	 * Hold all valid params
	 * @var    array
	 */
	protected $validParams = array('cmd', 'table', // table name
		'uid', // uid of the record
		'level'// the current level
	);

	/**
	 * Hold values of valid GP params
	 * @var    array
	 */
	protected $paramValues = array();

	/**
	 * @var string
	 */
	protected $cmd = '';

	/**
	 * Initialize method
	 * @param array $params not used yet
	 * @param AjaxRequestHandler $ajaxObj the parent ajax object
	 * @return void
	 */
	public function init($params, AjaxRequestHandler $ajaxObj) {

		// fill local params because that's not done in typo3/ajax.php yet ($params is always empty)
		foreach ($this->validParams as $validParam) {
			$gpValue = GeneralUtility::_GP($validParam);
			if ($gpValue !== null) {
				$this->paramValues[$validParam] = $gpValue;
			}
		}

		// set ajaxObj to render JSON
		$ajaxObj->setContentFormat('jsonbody');

		$this->dispatch($ajaxObj);
	}

	/**
	 * Creates the content depending on the 'cmd' parameter and fills $ajaxObj
	 * @param AjaxRequestHandler $ajaxObj
	 * @return void
	 **/
	protected function dispatch(AjaxRequestHandler &$ajaxObj) {
		if (!is_string($this->paramValues['cmd'])) {
			$ajaxObj->addContent('error', array('message' => 'cmd is not a string'));
		} else {
			switch ($this->paramValues['cmd']) {
				case 'getListRows':
					$this->getListRows($ajaxObj);
					break;
			}
		}
	}

	/**
	 * get list rows
	 * @param AjaxRequestHandler $ajaxObj the parent ajax object
	 * @return void
	 */
	public function getListRows(AjaxRequestHandler &$ajaxObj) {
		$uid = (int)$this->getParamValue('uid');
		if ($uid > 0) {
			$table = (string)$this->getParamValue('table');
			$table = $table ? $table : 'tt_content';

			$level = (int)$this->getParamValue('level');
			$this->initializeTemplateContainer();

			$elementChildren = Helper::getInstance()->getChildren($table, $uid, GeneralUtility::_GP('sortField'), (int)GeneralUtility::_GP('sortRev'));

			$row = BackendUtility::getRecord($table, $uid);
			$recordList = $this->getRecordList($table, $uid, $row);

			$listRows = [];
			if ($recordList instanceof DatabaseRecordList) {
				$level++;
				foreach ($elementChildren as $elementChild) {
					// @todo broken, $elementChild is NOT an object, but an array
					//$listRows[] = $recordList->renderListRow($elementChild->getTable(), BackendUtility::getRecord($elementChild->getTable(), $elementChild->getId()), 0, $GLOBALS['TCA'][$table]['ctrl']['label'], $GLOBALS['TCA'][$table]['ctrl']['thumbnail'], 1, $level);
				}
			}

			$ajaxObj->addContent('list', $listRows);
		}

	}

	/**
	 * initialize and return localRecordList
	 * @param string $table
	 * @param int $uid
	 * @param array $row
	 * @return DatabaseRecordList
	 */
	private function getRecordList($table, $uid, $row) {
		$dataBaseList = null;
		$beUser = $this->getBackendUser();

		$permsClause = $beUser->getPagePermsClause(1);

		// todo
		// GPvars:
		// $this->pointer = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('pointer');
		// $this->imagemode = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('imagemode');
		// $this->search_field = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('search_field');
		// $this->search_levels = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('search_levels');
		// $this->showLimit = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('showLimit');
		// $this->returnUrl = \TYPO3\CMS\Core\Utility\GeneralUtility::sanitizeLocalUrl(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('returnUrl'));

		// $this->clear_cache = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('clear_cache');
		// $this->cmd = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('cmd');
		// $this->cmd_table = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('cmd_table');
		$cmd_table = GeneralUtility::_GP('cmd_table');

		// Loading current page record and checking access:
		$pageInfo = BackendUtility::readPageAccess($row['pid'], $permsClause);
		$access = is_array($pageInfo) ? 1 : 0;

		if ($access) {
			// TODO: Menu settings: Apply predefined values for hidden checkboxes
			// Set predefined value for DisplayBigControlPanel:
			// Set predefined value for Clipboard:
			// Set predefined value for LocalizationView:

			// Initialize the dataBaseList object:
			/** @var $dataBaseList DatabaseRecordList */
			$dataBaseList = GeneralUtility::makeInstance('TYPO3\\CMS\\Recordlist\\RecordList\\DatabaseRecordList');
			$dataBaseList->calcPerms = $beUser->calcPerms($pageInfo);
			$dataBaseList->thumbs = $beUser->uc['thumbnailsByDefault'];

			$modName = 'web_list';
			$MOD_MENU = array('bigControlPanel' => '', 'clipBoard' => '', 'localization' => '');
			// Loading module configuration:
			$modTSconfig = BackendUtility::getModTSconfig($uid, 'mod.' . $modName);

			// todo: bring GP settings from outer list to the ajax request
			$MOD_SETTINGS = BackendUtility::getModuleData($MOD_MENU, GeneralUtility::_GP('SET'), $modName);

			$dataBaseList->allFields = ($MOD_SETTINGS['bigControlPanel'] || $table) ? 1 : 0;
			$dataBaseList->localizationView = $MOD_SETTINGS['localization'];
			$dataBaseList->showClipboard = 1;

			$dataBaseList->disableSingleTableView = $modTSconfig['properties']['disableSingleTableView'];
			$dataBaseList->listOnlyInSingleTableMode = $modTSconfig['properties']['listOnlyInSingleTableView'];
			$dataBaseList->hideTables = $modTSconfig['properties']['hideTables'];
			$dataBaseList->hideTranslations = $modTSconfig['properties']['hideTranslations'];
			$dataBaseList->tableTSconfigOverTCA = $modTSconfig['properties']['table.'];
			$dataBaseList->clickTitleMode = $modTSconfig['properties']['clickTitleMode'];
			$dataBaseList->allowedNewTables = GeneralUtility::trimExplode(',', $modTSconfig['properties']['allowedNewTables'], 1);
			$dataBaseList->deniedNewTables = GeneralUtility::trimExplode(',', $modTSconfig['properties']['deniedNewTables'], 1);
			$dataBaseList->newWizards = $modTSconfig['properties']['newWizards'] ? 1 : 0;

			$dataBaseList->pageRow = $pageInfo;
			$dataBaseList->counter++;
			$dataBaseList->MOD_MENU = $MOD_MENU;
			$dataBaseList->modTSconfig = $modTSconfig;

			// Clipboard is initialized:
			$dataBaseList->clipObj = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Clipboard\\Clipboard'); // Start clipboard
			$dataBaseList->clipObj->initializeClipboard(); // Initialize - reads the clipboard content from the user session

			// todo
			// Clipboard actions are handled:
			$CB = GeneralUtility::_GET('CB'); // CB is the clipboard command array
			if ($this->cmd == 'setCB') {
				// CBH is all the fields selected for the clipboard, CBC is the checkbox fields which were checked. By merging we get a full array of checked/unchecked elements
				// This is set to the 'el' array of the CB after being parsed so only the table in question is registered.
				$CB['el'] = $dataBaseList->clipObj->cleanUpCBC(array_merge((array)GeneralUtility::_POST('CBH'), (array)GeneralUtility::_POST('CBC')), $cmd_table);
			}
			if (!$MOD_SETTINGS['clipBoard']) {
				$CB['setP'] = 'normal';
			}

			// If the clipboard is NOT shown, set the pad to 'normal'.
			$dataBaseList->clipObj->setCmd($CB); // Execute commands.
			$dataBaseList->clipObj->cleanCurrent(); // Clean up pad
			$dataBaseList->clipObj->endClipboard(); // Save the clipboard content

			// This flag will prevent the clipboard panel in being shown.
			// It is set, if the clickmenu-layer is active AND the extended view is not enabled.
			$dataBaseList->dontShowClipControlPanels = $GLOBALS['CLIENT']['FORMSTYLE'] && !$MOD_SETTINGS['bigControlPanel'] && $dataBaseList->clipObj->current == 'normal' && !$beUser->uc['disableCMlayers'] && !$modTSconfig['properties']['showClipControlPanelsDespiteOfCMlayers'];

			// If there is access to the page, then render the list contents and set up the document template object:
			// todo: there is no browsing in child records
			//$this->pointer = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($this->pointer,0,100000);
			$pointer = 0;
			$search_field = '';
			$search_levels = '';
			$showLimit = 10;

			//$dataBaseList->start($this->id,$this->table,$this->pointer,$this->search_field,$this->search_levels,$this->showLimit);
			$dataBaseList->start($row['pid'], $table, $pointer, $search_field, $search_levels, $showLimit);
			$dataBaseList->setDispFields();

			// Render the list of tables:
			$dataBaseList->generateList();
		}

		return $dataBaseList;
	}

	/**
	 * Initializes an anonymous template container.
	 * The created container can be compared to alt_doc.php in backend-only disposal.
	 * @return void
	 */
	public function initializeTemplateContainer() {
		$GLOBALS['SOBE'] = new \stdClass();
		$GLOBALS['SOBE']->doc = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
	}

	/**
	 * Returns the param with given key
	 * @param string $param
	 * @return mixed
	 */
	public function getParamValue($param) {
		return $this->paramValues[$param];
	}

	/**
	 * @return BackendUserAuthentication
	 */
	protected function getBackendUser() {
		return $GLOBALS['BE_USER'];
	}
}
