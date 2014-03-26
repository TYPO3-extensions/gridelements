<?php
namespace GridElementsTeam\Gridelements\Hooks;

/**
 * Class/Function which manipulates the rendering of item example content and replaces it with a grid of child elements.
 *
 * @author		Jo Hasenau <info@cybercraft.de>
 * @package		TYPO3
 * @subpackage	tx_gridelements
 */
class DrawItem implements \TYPO3\CMS\Backend\View\PageLayoutViewDrawItemHookInterface {

	/**
	 * @var language
	 */
	var $lang;

	/**
	 * @var \TYPO3\CMS\Core\Database\QueryGenerator
	 */
	protected $tree;

	public function __construct() {
		$this->lang = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Lang\LanguageService');
		$this->lang->init($GLOBALS['BE_USER']->uc['lang']);
	}

	/**
	 * Processes the item to be rendered before the actual example content gets rendered
	 * Deactivates the original example content output
	 *
	 * @param \TYPO3\CMS\Backend\View\PageLayoutView     $parentObject: The parent object that triggered this hook
	 * @param boolean           $drawItem: A switch to tell the parent object, if the item still must be drawn
	 * @param string            $headerContent: The content of the item header
	 * @param string            $itemContent: The content of the item itself
	 * @param array             $row: The current data row for this item
	 * @return	void
	 */
	public function preProcess(\TYPO3\CMS\Backend\View\PageLayoutView &$parentObject, &$drawItem, &$headerContent, &$itemContent, array &$row) {
		if ($row['CType']) {
			$showHidden = $parentObject->tt_contentConfig['showHidden'] ? '' : \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields('tt_content');
			$deleteClause = \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('tt_content');

			if($GLOBALS['BE_USER']->uc['hideContentPreview']) {
				$drawItem = FALSE;
			}

			switch ($row['CType']) {
				case 'gridelements_pi1':
					$drawItem = FALSE;
					$itemContent .= $this->renderCTypeGridelements($parentObject, $row, $showHidden, $deleteClause);
					$refIndexObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\Database\ReferenceIndex');
					/* @var $refIndexObj t3lib_refindex */
					$refIndexObj->updateRefIndexTable('tt_content', $row['uid']);
					break;
				case 'shortcut':
					$drawItem = FALSE;
					$itemContent .= $this->renderCTypeShortcut($parentObject, $row, $showHidden, $deleteClause);
					break;
			}
		}
		$headerContent = '<div id="ce' . $row['uid'] . '" class="t3-ctype-' . $row['CType'] . '">' . $headerContent . '</div>';
	}

	/**
	 * renders the HTML output for elements of the CType gridelements_pi1
	 *
	 * @param \TYPO3\CMS\Backend\View\PageLayoutView     $parentObject: The parent object that triggered this hook
	 * @param array             $row: The current data row for this item
	 * @param string            $showHidden: query String containing enable fields
	 * @param string            $deleteClause: query String to check for deleted items
	 * @return string           $itemContent: The HTML output for elements of the CType gridelements_pi1
	 */
	public function renderCTypeGridelements(\TYPO3\CMS\Backend\View\PageLayoutView $parentObject, &$row, &$showHidden, &$deleteClause) {
		$head = array();
		$gridContent = array();
		$editUidList = array();
		$colPosValues = array();
		$singleColumn = FALSE;

		// get the layout record for the selected backend layout if any
		$gridContainerId = $row['uid'];
		/** @var $layoutSetup \GridElementsTeam\Gridelements\Backend\LayoutSetup */
		$layoutSetup = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('GridElementsTeam\Gridelements\Backend\LayoutSetup');
		$gridElement = $layoutSetup->init($row['pid'])->cacheCurrentParent($gridContainerId, TRUE);
		$layoutUid = $gridElement['tx_gridelements_backend_layout'];
		$layout = $layoutSetup->getLayoutSetup($layoutUid);
		$parserRows = $layout['config']['rows.'];

		// if there is anything to parse, lets check for existing columns in the layout

		if (is_array($parserRows) && count($parserRows) > 0) {
			$this->setMultipleColPosValues($parserRows, $colPosValues);
		} else {
			$singleColumn = TRUE;
			$this->setSingleColPosItems($parentObject, $colPosValues, $gridElement, $showHidden, $deleteClause);
		}

		// if there are any columns, lets build the content for them
		if (count($colPosValues) > 0) {
			$this->renderGridColumns($parentObject, $colPosValues, $gridContent, $gridElement, $editUidList, $singleColumn, $head, $showHidden, $deleteClause);
		}

		// if we got a selected backend layout, we have to create the layout table now
		if ($layoutUid && isset($layout['config'])) {
			$itemContent = $this->renderGridLayoutTable($layout, $gridElement, $head, $gridContent);
		} else {
			$itemContent = '<div class="t3-gridContainer">';
			$itemContent .= '<table border="0" cellspacing="1" cellpadding="4" width="100%" height="100%" class="t3-page-columns t3-gridTable">';
			$itemContent .= '<tr><td valign="top" class="t3-gridCell t3-page-column t3-page-column-0">' . $gridContent[0] . '</td></tr>';
			$itemContent .= '</table></div>';
		}
		return $itemContent;
	}

	/**
	 * renders the HTML output for elements of the CType shortcut
	 *
	 * @param \TYPO3\CMS\Backend\View\PageLayoutView     $parentObject: The parent object that triggered this hook
	 * @param array             $row: The current data row for this item
	 * @param string            $showHidden: query String containing enable fields
	 * @param string            $deleteClause: query String to check for deleted items
	 * @return string           $shortcutContent: The HTML output for elements of the CType shortcut
	 */
	public function renderCTypeShortcut(\TYPO3\CMS\Backend\View\PageLayoutView $parentObject, &$row, &$showHidden, &$deleteClause) {
		$shortcutContent = '';
		if ($row['records']) {
			$shortcutItems = explode(',', $row['records']);
			$collectedItems = array();
			foreach ($shortcutItems as $shortcutItem) {
				$shortcutItem = trim($shortcutItem);
				if (strpos($shortcutItem, 'pages_') !== FALSE) {
					$this->collectContentDataFromPages($shortcutItem, $collectedItems, $row['recursive'], $showHidden, $deleteClause);
				} else if (strpos($shortcutItem, '_') === FALSE || strpos($shortcutItem, 'tt_content_') !== FALSE) {
					$this->collectContentData($shortcutItem, $collectedItems, $showHidden, $deleteClause);
				}
			}
			if (count($collectedItems)) {
				foreach ($collectedItems as $itemRow) {
					if ($itemRow){
						$className = $itemRow['tx_gridelements_reference_container'] ? 'reference container_reference' : 'reference';
						$shortcutContent .= '<div class="' . $className . '">';
						$shortcutContent .= $this->renderSingleElementHTML($parentObject, $itemRow);
						// NOTE: this is the end tag for <div class="t3-page-ce-body">
						// because of bad (historic) conception, starting tag has to be placed inside tt_content_drawHeader()
						$shortcutContent .= '<div class="reference-overlay"></div></div></div><br />';
					}
				}
			}
		}
		return $shortcutContent;
	}

	/**
	 * Sets column positions based on a selected gridelement layout
	 *
	 * @param array $parserRows: The parsed rows of the gridelement layout
	 * @param array $colPosValues: The column positions that have been found for that layout
	 * @return void
	 */
	public function setMultipleColPosValues($parserRows, &$colPosValues) {
		if (is_array($parserRows)) {
			foreach ($parserRows as $parserRow) {
				if (is_array($parserRow['columns.']) && count($parserRow['columns.']) > 0) {
					foreach ($parserRow['columns.'] as $parserColumns) {
						$name = $this->lang->sL($parserColumns['name'], TRUE);
						if ($parserColumns['colPos'] !== '') {
							$colPosValues[(int)$parserColumns['colPos']] = array(
								'name' => $name,
								'allowed' => $parserColumns['allowed']
							);
						} else {
							$colPosValues[32768] = array(
								'name' => $this->lang->getLL('notAssigned'),
								'allowed' => ''
							);
						}
					}
				}
			}
		}
	}

	/**
	 * Directly returns the items for a single column if the rendering mode is set to single columns only
	 *
	 * @param \TYPO3\CMS\Backend\View\PageLayoutView $parentObject: The parent object that triggered this hook
	 * @param array $colPosValues: The column positions that have been found for that layout
	 * @param array $row: The current data row for the container item
	 * @param string $showHidden: query String containing enable fields
	 * @param string $deleteClause: query String to check for deleted items
	 * @return array collected items for this column
	 */
	public function setSingleColPosItems(\TYPO3\CMS\Backend\View\PageLayoutView $parentObject, &$colPosValues, &$row, $showHidden, $deleteClause) {
		// Due to the pid being "NOT USED" in makeQueryArray we have to set pidSelect here
		$originalPidSelect = $parentObject->pidSelect;
		$parentObject->pidSelect = 'pid = ' . $row['pid'];
		$specificUid = \GridElementsTeam\Gridelements\Helper\Helper::getInstance()->getSpecificUid($row);

		$queryParts = $parentObject->makeQueryArray(
			'tt_content',
			$row['pid'],
			'AND colPos = -1 AND tx_gridelements_container=' .
				$specificUid .
				$showHidden .
				$deleteClause .
				$parentObject->showLanguage
		);

		// Due to the pid being "NOT USED" in makeQueryArray we have to reset pidSelect here
		$parentObject->pidSelect = $originalPidSelect;

		$result = $GLOBALS['TYPO3_DB']->exec_SELECT_queryArray($queryParts);
		$colPosValues[] = array(0, '');
		return $parentObject->getResult($result);
	}

	/**
	 * renders the columns of a grid layout
	 *
	 * @param \TYPO3\CMS\Backend\View\PageLayoutView		$parentObject: The parent object that triggered this hook
	 * @param array				$colPosValues: The column positions we want to get the content for
	 * @param array				$gridContent: The rendered content data of the grid columns
	 * @param array				$row: The current data row for the container item
	 * @param array				$editUidList: determines if we will get edit icons or not
	 * @param boolean			$singleColumn: Determines if we are in single column mode or not
	 * @param array				$head: An array of headers for each of the columns
	 * @param string			$showHidden: query String containing enable fields
	 * @param string			$deleteClause: query String to check for deleted items
	 * @return void
	 */
	public function renderGridColumns(\TYPO3\CMS\Backend\View\PageLayoutView $parentObject, &$colPosValues, &$gridContent, &$row, &$editUidList, &$singleColumn, &$head, $showHidden, $deleteClause) {
		foreach ($colPosValues as $colPos => $values) {
			// first we have to create the column content separately for each column
			// so we can check for the first and the last element to provide proper sorting
			if ($singleColumn === FALSE) {
				$items = $this->collectItemsForColumn($parentObject, $colPos, $row, $showHidden, $deleteClause);
			} else {
				$items = array();
			}
			// if there are any items, we can create the HTML for them just like in the original TCEform
			$this->renderSingleGridColumn($parentObject, $items, $colPos, $gridContent, $row, $editUidList);
			// we will need a header for each of the columns to activate mass editing for elements of that column
			$this->setColumnHeader($parentObject, $head, $colPos, $values['name'], $editUidList);
		}
	}

	/**
	 * Collects tt_content data from a single tt_content element
	 *
	 * @param \TYPO3\CMS\Backend\View\PageLayoutView		$parentObject: The paren object that triggered this hook
	 * @param int			   	$colPos: The column position to collect the items for
	 * @param array			 	$row: The current data row for the container item
	 * @param string			$showHidden: query String containing enable fields
	 * @param string			$deleteClause: query String to check for deleted items
	 * @return array			collected items for the given column
	 */
	public function collectItemsForColumn(\TYPO3\CMS\Backend\View\PageLayoutView $parentObject, &$colPos, &$row, &$showHidden, &$deleteClause) {
		// Due to the pid being "NOT USED" in makeQueryArray we have to set pidSelect here
		$originalPidSelect = $parentObject->pidSelect;
		$parentObject->pidSelect = 'pid = ' . $row['pid'];

		if(!$parentObject->tt_contentConfig['languageMode']) {
			$showLanguage = ' AND sys_language_uid=' . $parentObject->tt_contentConfig['sys_language_uid'];
		} else if($row['sys_language_uid'] > 0) {
			$showLanguage = ' AND sys_language_uid=' . $row['sys_language_uid'];
		} else {
			$showLanguage = '';
		}

		$specificUid = \GridElementsTeam\Gridelements\Helper\Helper::getInstance()->getSpecificUid($row);
		$queryParts = $parentObject->makeQueryArray(
			'tt_content',
			$row['pid'],
			'AND colPos = -1 AND tx_gridelements_container=' .
				$specificUid .
				' AND tx_gridelements_columns=' .
				$colPos .
				$showHidden .
				$deleteClause .
				$showLanguage
		);

		// Due to the pid being "NOT USED" in makeQueryArray we have to reset pidSelect here
		$parentObject->pidSelect = $originalPidSelect;

		$result = $GLOBALS['TYPO3_DB']->exec_SELECT_queryArray($queryParts);
		return $parentObject->getResult($result);
	}

	/**
	 * renders a single column of a grid layout and sets the edit uid list
	 *
	 * @param \TYPO3\CMS\Backend\View\PageLayoutView $parentObject: The parent object that triggered this hook
	 * @param array $items: The content data of the column to be rendered
	 * @param int $colPos: The column position we want to get the content for
	 * @param array $gridContent: The rendered content data of the grid column
	 * @param $row
	 * @param array $editUidList: determines if we will get edit icons or not
	 * @return void
	 */
	public function renderSingleGridColumn(\TYPO3\CMS\Backend\View\PageLayoutView $parentObject, &$items, &$colPos, &$gridContent, $row, &$editUidList) {

		$specificUid = \GridElementsTeam\Gridelements\Helper\Helper::getInstance()->getSpecificUid($row);

		if ($colPos < 32768) {
			$newParams = $parentObject->newContentElementOnClick(
				$row['pid'],
				'-1' .
					'&tx_gridelements_container=' . $specificUid .
					'&tx_gridelements_columns=' . $colPos,
				$parentObject->lP
			);
		}

		$gridContent[$colPos] .= '
			<div class="t3-page-ce-wrapper">
				<div class="t3-page-ce-dropzone">
					<div class="t3-page-ce-wrapper-new-ce">
						<a href="#" onclick="' . htmlspecialchars($newParams) . '" title="' . $GLOBALS['LANG']->getLL('newInColumn', TRUE) . '">' .
								\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-new') .
						'</a>
					</div>
			</div>';

		if(count($items) > 0) {
			foreach ($items as $itemRow) {
				if (is_array($itemRow)) {
					$statusHidden = $parentObject->isDisabled('tt_content', $itemRow)
						? ' t3-page-ce-hidden'
						: '';
					$gridContent[$colPos] .= '
				<div class="t3-page-ce' . $statusHidden . '"><div class="t3-page-ce-dragitem">' .
						$this->renderSingleElementHTML($parentObject, $itemRow) . '</div></div>';
					// New content element:
					if ($parentObject->option_newWizard) {
						$onClick = 'window.location.href=\'db_new_content_el.php?id=' . $itemRow['pid'] . '&sys_language_uid=' . $itemRow['sys_language_uid'] . '&colPos=' . $itemRow['colPos'] . '&uid_pid=' . -$itemRow['uid'] . '&returnUrl=' . rawurlencode(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI')) . '\';';
					} else {
						$params = '&edit[tt_content][' . -$itemRow['uid'] . ']=new';
						$onClick = \TYPO3\CMS\Backend\Utility\BackendUtility::editOnClick($params, $this->backPath);
					}
					$gridContent[$colPos] .= '
				<div class="t3-page-ce-dropzone"><div class="t3-page-ce-new-ce">
					<a href="#" onclick="' . htmlspecialchars($onClick) . '" title="' . $GLOBALS['LANG']->getLL('newRecordHere', 1) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-new') . '</a>
				</div></div></div>
					';
					$editUidList[$colPos] .= $editUidList[$colPos]
						? ',' . $itemRow['uid']
						: $itemRow['uid'];
				}
			}
		}

		$gridContent[$colPos] .= '</div>';
	}

	/**
	 * Sets the headers for a grid before content and headers are put together
	 *
	 * @param \TYPO3\CMS\Backend\View\PageLayoutView $parentObject: The parent object that triggered this hook
	 * @param array $head: The collected item data rows
	 * @param int $colPos: The column position we want to get a header for
	 * @param string $name: The name of the header
	 * @param array $editUidList: determines if we will get edit icons or not
	 * @internal param array $row : The current data row for the container item
	 * @return void
	 */
	public function setColumnHeader(\TYPO3\CMS\Backend\View\PageLayoutView $parentObject, &$head, &$colPos, &$name, &$editUidList) {
		$head[$colPos] = $this->tt_content_drawColHeader(
			$name,
			($parentObject->doEdit && $editUidList[$colPos])
				? '&edit[tt_content][' . $editUidList[$colPos] . ']=edit' .
				$parentObject->pageTitleParamForAltDoc
				: '',
			$parentObject);
	}

	/**
	 * Draw header for a content element column:
	 *
	 * @param string $colName Column name
	 * @param string $editParams Edit params (Syntax: &edit[...] for alt_doc.php)
	 * @param \TYPO3\CMS\Backend\View\PageLayoutView $parentObject
	 * @return string HTML table
	 */
	function tt_content_drawColHeader($colName, $editParams, &$parentObject) {

		$icons = '';
		// Create command links:
		if ($parentObject->tt_contentConfig['showCommands']) {
			// Edit whole of column:
			if ($editParams) {
				$icons .= '<a href="#" onclick="' . htmlspecialchars(\TYPO3\CMS\Backend\Utility\BackendUtility::editOnClick($editParams, $parentObject->backPath)) . '" title="' . $GLOBALS['LANG']->getLL('editColumn', TRUE) . '">' .
					\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-open') .
					'</a>';
			}
			$icons .= '<a href="#" class="toggle-content toggle-up" title="' . $this->lang->sL('LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tx_gridelements_togglecontent') . '">' .
				\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-move-to-top') .
				'</a>';
			$icons .= '<a href="#" class="toggle-content toggle-down" title="' . $this->lang->sL('LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tx_gridelements_togglecontent') . '">' .
				\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-move-to-bottom') .
				'</a>';
		}
		if (strlen($icons)) {
			$icons = '<div class="t3-page-colHeader-icons">' . $icons . '</div>';
		}

		// Create header row:
		$out = '<div class="t3-page-colHeader t3-row-header">
					' . $icons . '
					<div class="t3-page-colHeader-label">' . htmlspecialchars($colName) . '</div>
				</div>';
		return $out;
	}

	/**
	 * Renders the grid layout table after the HTML content for the single elements has been rendered
	 *
	 * @param array     $layoutSetup: The setup of the layout that is selected for the grid we are going to render
	 * @param array     $row: The current data row for the container item
	 * @param array     $head: The data for the column headers of the grid we are going to render
	 * @param array     $gridContent: The content data of the grid we are going to render
	 * @return string
	 */
	public function renderGridLayoutTable($layoutSetup, $row, $head, $gridContent) {
		$specificUid = \GridElementsTeam\Gridelements\Helper\Helper::getInstance()->getSpecificUid($row);

		$grid = '<div class="t3-gridContainer' .
			($layoutSetup['frame']
				? ' t3-gridContainer-framed t3-gridContainer-' . $layoutSetup['frame']
				: ''
			) .
			($layoutSetup['top_level_layout']
				? ' t3-gridTLContainer'
				: ''
			) .
			'">';
		if ($layoutSetup['frame']) {
			$grid .= '<h4 class="t3-gridContainer-title-' . $layoutSetup['frame'] . '">' .
				$this->lang->sL($layoutSetup['title'], TRUE) .
				'</h4>';
		}
		$grid .= '<table border="0" cellspacing="1" cellpadding="4" width="100%" height="100%" class="t3-page-columns t3-gridTable">';
		// add colgroups
		$colCount = (int)$layoutSetup['config']['colCount'];
		$rowCount = (int)$layoutSetup['config']['rowCount'];
		$grid .= '<colgroup>';
		for ($i = 0; $i < $colCount; $i++) {
			$grid .= '<col style="width:' . (100 / $colCount) . '%"></col>';
		}
		$grid .= '</colgroup>';
		// cycle through rows
		for ($layoutRow = 1; $layoutRow <= $rowCount; $layoutRow++) {
			$rowConfig = $layoutSetup['config']['rows.'][$layoutRow . '.'];
			if (!isset($rowConfig)) {
				continue;
			}
			$grid .= '<tr>';
			for ($col = 1; $col <= $colCount; $col++) {
				$columnConfig = $rowConfig['columns.'][$col . '.'];
				if (!isset($columnConfig)) {
					continue;
				}
				// which column should be displayed inside this cell
				$columnKey = $columnConfig['colPos'] != '' ? (int)$columnConfig['colPos'] : 32768;
				// allowed CTypes
				if(!empty($columnConfig['allowed'])) {
					$allowedCTypes = array_flip(explode(',', $columnConfig['allowed']));
					if(!isset($allowedCTypes['*'])) {
						foreach($allowedCTypes as $key => &$ctype){
							$ctype = 't3-allow-' . $key;
						}
					}
				}
				// render the grid cell
				$colSpan = (int)$columnConfig['colspan'];
				$rowSpan = (int)$columnConfig['rowspan'];
				$grid .= '<td valign="top"' .
					(isset($columnConfig['colspan'])
						? ' colspan="' . $colSpan . '"'
						: '') .
					(isset($columnConfig['rowspan'])
						? ' rowspan="' . $rowSpan . '"'
						: '') .
					'id="column-' . $specificUid . 'x' . $columnKey . '" class="t3-gridCell t3-page-column t3-page-column-' . $columnKey .
					(!isset($columnConfig['colPos']) || $columnConfig['colPos'] == ''
						? ' t3-gridCell-unassigned'
						: '') .
					(isset($columnConfig['colspan']) && $columnConfig['colPos'] != ''
						? ' t3-gridCell-width' . $colSpan
						: '') .
					(isset($columnConfig['rowspan']) && $columnConfig['colPos'] != ''
						? ' t3-gridCell-height' . $rowSpan
						: '') .
					' ' . (count($allowedCTypes) ? join(' ', $allowedCTypes) : 't3-allow-all') .
					'">';

				$grid .= ($GLOBALS['BE_USER']->uc['hideColumnHeaders'] ? '' : $head[$columnKey]) . $gridContent[$columnKey];
				$grid .= '</td>';
			}
			$grid .= '</tr>';
		}
		$grid .= '</table></div>';
		return $grid;
	}

	/**
	 * Collects tt_content data from a single page or a page tree starting at a given page
	 *
	 * @param string    $shortcutItem: The single page to be used as the tree root
	 * @param array     $collectedItems: The collected item data rows ordered by parent position, column position and sorting
	 * @param int       $recursive: The number of levels for the recursion
	 * @param string    $showHidden: query String containing enable fields
	 * @param string    $deleteClause: query String to check for deleted items
	 * @return void
	 */
	public function collectContentDataFromPages($shortcutItem, &$collectedItems, $recursive = 0, &$showHidden, &$deleteClause) {
		$itemList = str_replace('pages_', '', $shortcutItem);
		if ($recursive) {
			if (!$this->tree instanceof \TYPO3\CMS\Core\Database\QueryGenerator) {
				$this->tree = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('\TYPO3\CMS\Core\Database\QueryGenerator');
			}
			$itemList = $this->tree->getTreeList($itemList, (int)$recursive, 0, 1);
		}
		$itemRows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			'tt_content',
			'pid IN (' . $itemList . ') AND colPos >= 0 ' .
				$showHidden .
				$deleteClause,
			'',
			'FIND_IN_SET(pid, \'' . $itemList . '\'),colPos,sorting'
		);
		foreach ($itemRows as $itemRow) {
			if($GLOBALS['BE_USER']->workspace > 0) {
				\TYPO3\CMS\Backend\Utility\BackendUtility::workspaceOL('tt_content', $itemRow, $GLOBALS['BE_USER']->workspace);
			}
			$itemRow['tx_gridelements_reference_container'] = $itemRow['pid'];
			$collectedItems[] = $itemRow;
		}
	}

	/**
	 * Collects tt_content data from a single tt_content element
	 *
	 * @param string    $shortcutItem: The tt_content element to fetch the data from
	 * @param array     $collectedItems: The collected item data row
	 * @param string    $showHidden: query String containing enable fields
	 * @param string    $deleteClause: query String to check for deleted items
	 * @return void
	 */
	public function collectContentData($shortcutItem, &$collectedItems, &$showHidden, &$deleteClause) {
		$shortcutItem = str_replace('tt_content_', '', $shortcutItem);
		$itemRow = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
			'*',
			'tt_content',
			'uid=' .
				$shortcutItem .
				$showHidden .
				$deleteClause
		);
		if($GLOBALS['BE_USER']->workspace > 0) {
			\TYPO3\CMS\Backend\Utility\BackendUtility::workspaceOL('tt_content', $itemRow, $GLOBALS['BE_USER']->workspace);
		}
		$collectedItems[] = $itemRow;
	}

	/**
	 * Renders the HTML code for a single tt_content element
	 *
	 * @param \TYPO3\CMS\Backend\View\PageLayoutView     $parentObject: The parent object that triggered this hook
	 * @param array             $itemRow: The data row to be rendered as HTML
	 * @return string
	 */
	public function renderSingleElementHTML(\TYPO3\CMS\Backend\View\PageLayoutView $parentObject, $itemRow) {
		$singleElementHTML = $parentObject->tt_content_drawHeader(
			$itemRow,
			$parentObject->tt_contentConfig['showInfo']
				? 15
				: 5,
			$parentObject->defLangBinding && $parentObject->lP > 0,
			TRUE);
		$isRTE = $parentObject->RTE && $parentObject->isRTEforField('tt_content', $itemRow, 'bodytext');
		$singleElementHTML .= '<div ' .
			(!empty($itemRow['_ORIG_uid'])
				? ' class="ver-element"'
				: '') .
			'><div class="t3-page-ce-body-inner t3-page-ce-body-inner-' . $itemRow['CType'] . '">' .
			$parentObject->tt_content_drawItem($itemRow, $isRTE) .
			'</div></div>';
		return $singleElementHTML;
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/Classes/Hooks/DrawItem.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/Classes/Hooks/DrawItem.php']);
}


