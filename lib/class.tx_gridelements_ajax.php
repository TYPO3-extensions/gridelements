<?php
// load models and views

/**
 * AJAX request disatcher
 *
 * @author      Dirk Hoffmann <dirk-hoffmann@telekom.de>
 * @package     TYPO3
 * @subpackage  tx_gridelements
 */
class tx_gridelements_ajax {

	/**
	 * The content for the ajax output
	 *
	 * @var	string
	 */
	protected $content;

	/**
	 * Hold all valid params
	 *
	 * @var	array
	 */
	protected $validParams = array(
		'cmd',
		'table',	// table name
		'uid',		// uid of the record
		'level'		// the current level
	);

	/**
	 * Hold values of valid GP params
	 *
	 * @var	array
	 */
	protected $params = array();

	/**
	 * Initialize method
	 *
	 * @param	array		$params not used yet
	 * @param	TYPO3AJAX	$ajaxObj the parent ajax object
	 * @return void
	 */
	public function init($params, TYPO3AJAX &$ajaxObj) {

		// fill local params because that's not done in typo3/ajax.php yet ($params is always empty)
		foreach ($this->validParams as $validParam){
			$gpValue = t3lib_div::_GP($validParam);
			if ($gpValue !== NULL){
				$this->paramValues[$validParam] = $gpValue;
			}
		}

		// set ajaxObj to render JSON
		$ajaxObj->setContentFormat('jsonbody');

		$this->dispatch($ajaxObj);
	}

	/**
	 * Creates the content depending on the 'cmd' parameter and fills $ajaxObj
	 *
	 * @param	TYPO3AJAX $ajaxObj
	 * @return	void
	 **/
	protected function dispatch(TYPO3AJAX &$ajaxObj) {
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
	 *
	 * @param	TYPO3AJAX $ajaxObj the parent ajax object
	 * @return	void
	 */
	public function getListRows(TYPO3AJAX &$ajaxObj) {
		$uid = (int) $this->getParamValue('uid');
		if ($uid > 0) {
			$table = (string) $this->getParamValue('table');
			$table = $table ? $table : 'tt_content';

			$level = (int) $this->getParamValue('level');

			require_once(PATH_typo3 . 'class.db_list.inc');
			require_once(PATH_typo3 . 'class.db_list_extra.inc');

			$this->initializeTemplateContainer();

			$elementChilds = tx_gridelements_helper::getInstance()->getChildren(
				$table, $uid, t3lib_div::_GP('sortField'), (int) t3lib_div::_GP('sortRev')
			);

			$row = t3lib_BEfunc::getRecord($table, $uid);
			$recordList = $this->getRecordList($table, $uid, $row);

			if ($recordList instanceof localRecordList) {
				$level++;
				foreach ($elementChilds as $elementChild) {
					$listRows[] = $recordList->renderListRow(
						$elementChild->getTable(),
						t3lib_BEfunc::getRecord($elementChild->getTable(), $elementChild->getId()),
						0,
						$GLOBALS['TCA'][$table]['ctrl']['label'],
						$GLOBALS['TCA'][$table]['ctrl']['thumbnail'],
						1, // indent
						$level
					);
				}
			}

			$ajaxObj->addContent('list', $listRows);
		}

	}

	/**
	 * initialize and return localRecordList
	 *
	 * @return	localRecordList
	 */
	private function getRecordList($table, $uid, $row) {
		$dblist = null;

		$permsClause = $GLOBALS['BE_USER']->getPagePermsClause(1);

		// todo
		// GPvars:
#		$this->pointer = t3lib_div::_GP('pointer');
#		$this->imagemode = t3lib_div::_GP('imagemode');
#		$this->search_field = t3lib_div::_GP('search_field');
#		$this->search_levels = t3lib_div::_GP('search_levels');
#		$this->showLimit = t3lib_div::_GP('showLimit');
#		$this->returnUrl = t3lib_div::sanitizeLocalUrl(t3lib_div::_GP('returnUrl'));

#		$this->clear_cache = t3lib_div::_GP('clear_cache');
#		$this->cmd = t3lib_div::_GP('cmd');
#		$this->cmd_table = t3lib_div::_GP('cmd_table');
		$cmd_table = t3lib_div::_GP('cmd_table');

		// Loading current page record and checking access:
		$pageinfo = t3lib_BEfunc::readPageAccess($row['pid'], $permsClause);
		$access = is_array($pageinfo) ? 1 : 0;

		if ($access)	{
			// TODO: Menu settings: Apply predefined values for hidden checkboxes
			// Set predefined value for DisplayBigControlPanel:
			// Set predefined value for Clipboard:
			// Set predefined value for LocalizationView:

			// Initialize the dblist object:
			/** @var $dblist localRecordList */
			$dblist = t3lib_div::makeInstance('localRecordList');
			$dblist->calcPerms = $GLOBALS['BE_USER']->calcPerms($pageinfo);
			$dblist->thumbs = $GLOBALS['BE_USER']->uc['thumbnailsByDefault'];

			$modName = 'web_list';
			$MOD_MENU = array('bigControlPanel' => '', 'clipBoard' => '', 'localization' => '');
			// Loading module configuration:
			$modTSconfig = t3lib_BEfunc::getModTSconfig($uid,'mod.' . $modName);


			// todo: bring GP settings from outer list to the ajax request
			$MOD_SETTINGS = t3lib_BEfunc::getModuleData($MOD_MENU, t3lib_div::_GP('SET'), $modName);

			$dblist->allFields = ($MOD_SETTINGS['bigControlPanel'] || $table) ? 1 : 0;
			$dblist->localizationView = $MOD_SETTINGS['localization'];
			$dblist->showClipboard = 1;

			$dblist->disableSingleTableView = $modTSconfig['properties']['disableSingleTableView'];
			$dblist->listOnlyInSingleTableMode = $modTSconfig['properties']['listOnlyInSingleTableView'];
			$dblist->hideTables = $modTSconfig['properties']['hideTables'];
			$dblist->hideTranslations = $modTSconfig['properties']['hideTranslations'];
			$dblist->tableTSconfigOverTCA = $modTSconfig['properties']['table.'];
			$dblist->clickTitleMode = $modTSconfig['properties']['clickTitleMode'];
			$dblist->alternateBgColors=$modTSconfig['properties']['alternateBgColors']?1:0;
			$dblist->allowedNewTables = t3lib_div::trimExplode(',', $modTSconfig['properties']['allowedNewTables'], 1);
			$dblist->deniedNewTables = t3lib_div::trimExplode(',', $modTSconfig['properties']['deniedNewTables'], 1);
			$dblist->newWizards=$modTSconfig['properties']['newWizards']?1:0;

			$dblist->pageRow = $pageinfo;
			$dblist->counter++;
			$dblist->MOD_MENU = $MOD_MENU;
			$dblist->modTSconfig = $modTSconfig;

			// Clipboard is initialized:
			$dblist->clipObj = t3lib_div::makeInstance('t3lib_clipboard');		// Start clipboard
			$dblist->clipObj->initializeClipboard();	// Initialize - reads the clipboard content from the user session

			// todo
			// Clipboard actions are handled:
			$CB = t3lib_div::_GET('CB');	// CB is the clipboard command array
			if ($this->cmd=='setCB') {
				// CBH is all the fields selected for the clipboard, CBC is the checkbox fields which were checked. By merging we get a full array of checked/unchecked elements
				// This is set to the 'el' array of the CB after being parsed so only the table in question is registered.
				$CB['el'] = $dblist->clipObj->cleanUpCBC(array_merge((array)t3lib_div::_POST('CBH'),(array)t3lib_div::_POST('CBC')),$cmd_table);
			}
			if (!$MOD_SETTINGS['clipBoard']) {
				$CB['setP']='normal';
			}

			// If the clipboard is NOT shown, set the pad to 'normal'.
			$dblist->clipObj->setCmd($CB);		// Execute commands.
			$dblist->clipObj->cleanCurrent();	// Clean up pad
			$dblist->clipObj->endClipboard();	// Save the clipboard content

			// This flag will prevent the clipboard panel in being shown.
			// It is set, if the clickmenu-layer is active AND the extended view is not enabled.
			$dblist->dontShowClipControlPanels = $GLOBALS['CLIENT']['FORMSTYLE'] && !$MOD_SETTINGS['bigControlPanel']
				&& $dblist->clipObj->current=='normal' && !$GLOBALS['BE_USER']->uc['disableCMlayers']
				&& !$modTSconfig['properties']['showClipControlPanelsDespiteOfCMlayers'];



			// If there is access to the page, then render the list contents and set up the document template object:
			// todo: there is no browsing in child records
			//$this->pointer = t3lib_utility_Math::forceIntegerInRange($this->pointer,0,100000);
			$pointer = 0;
			$search_field = '';
			$search_levels = '';
			$showLimit = 10;

			//$dblist->start($this->id,$this->table,$this->pointer,$this->search_field,$this->search_levels,$this->showLimit);
			$dblist->start($row['pid'], $table, $pointer, $search_field, $search_levels, $showLimit);
			$dblist->setDispFields();

			// Render the list of tables:
			$dblist->generateList();
		}

#		t3lib_utility_Debug::debug($modTSconfig);
#		die;

//		$recordList->clipObj->current = 'normal';

		return $dblist;
	}

	/**
	 * Initializes an anonymous template container.
	 * The created container can be compared to alt_doc.php in backend-only disposal.
	 *
	 * @return	void
	 */
	public function initializeTemplateContainer() {
		$GLOBALS['SOBE'] = new stdClass();

		// Create an instance of the document template object
		require_once(PATH_typo3 . 'template.php');
		$GLOBALS['SOBE']->doc = t3lib_div::makeInstance('template');
	}


	/**
	 * Returns the param with given key
	 *
	 * @param	string	$param
	 * @return	mixed
	 */
	public function getParamValue($param) {
		return $this->paramValues[$param];
	}
}
