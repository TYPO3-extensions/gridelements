<?php
namespace GridElementsTeam\Gridelements\Hooks;

use GridElementsTeam\Gridelements\Helper\Helper;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Backend\View\PageLayoutView;
use TYPO3\CMS\Backend\View\PageLayoutViewDrawItemHookInterface;
use TYPO3\CMS\Core\Database\QueryGenerator;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class/Function which manipulates the rendering of item example content and replaces it with a grid of child elements.
 *
 * @author         Jo Hasenau <info@cybercraft.de>
 * @package        TYPO3
 * @subpackage     tx_gridelements
 */
class DrawItem implements PageLayoutViewDrawItemHookInterface {

	/**
	 * @var \TYPO3\CMS\Lang\LanguageService
	 */
	var $lang;

	/**
	 * @var QueryGenerator
	 */
	protected $tree;

	public function __construct() {
		$this->lang = GeneralUtility::makeInstance('TYPO3\\CMS\\Lang\\LanguageService');
		$this->lang->init($GLOBALS['BE_USER']->uc['lang']);
	}

	/**
	 * Processes the item to be rendered before the actual example content gets rendered
	 * Deactivates the original example content output
	 *
	 * @param PageLayoutView $parentObject : The parent object that triggered this hook
	 * @param boolean $drawItem : A switch to tell the parent object, if the item still must be drawn
	 * @param string $headerContent : The content of the item header
	 * @param string $itemContent : The content of the item itself
	 * @param array $row : The current data row for this item
	 *
	 * @return    void
	 */
	public function preProcess(PageLayoutView &$parentObject, &$drawItem, &$headerContent, &$itemContent, array &$row) {
		if ($row['CType']) {
			$showHidden = $parentObject->tt_contentConfig['showHidden'] ? '' : BackendUtility::BEenableFields('tt_content');
			$deleteClause = BackendUtility::deleteClause('tt_content');

			if ($GLOBALS['BE_USER']->uc['hideContentPreview']) {
				$drawItem = FALSE;
			}

			switch ($row['CType']) {
				case 'gridelements_pi1':
					$drawItem = FALSE;
					$itemContent .= $this->renderCTypeGridelements($parentObject, $row, $showHidden, $deleteClause);
					$refIndexObj = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\ReferenceIndex');
					/* @var $refIndexObj \TYPO3\CMS\Core\Database\ReferenceIndex */
					$refIndexObj->updateRefIndexTable('tt_content', $row['uid']);
					break;
				case 'shortcut':
					$drawItem = FALSE;
					$itemContent .= $this->renderCTypeShortcut($parentObject, $row, $showHidden, $deleteClause);
					break;
			}
		}
		$gridType = $row['tx_gridelements_backend_layout'] ? ' t3-gridtype-' . $row['tx_gridelements_backend_layout'] : '';
		$headerContent = '<div id="ce' . $row['uid'] . '" class="t3-ctype-' . $row['CType'] . $gridType . '">' . $headerContent . '</div>';
	}

	/**
	 * renders the HTML output for elements of the CType gridelements_pi1
	 *
	 * @param PageLayoutView $parentObject : The parent object that triggered this hook
	 * @param array $row : The current data row for this item
	 * @param string $showHidden : query String containing enable fields
	 * @param string $deleteClause : query String to check for deleted items
	 *
	 * @return string           $itemContent: The HTML output for elements of the CType gridelements_pi1
	 */
	public function renderCTypeGridelements(PageLayoutView $parentObject, &$row, &$showHidden, &$deleteClause) {
		$head = array();
		$gridContent = array();
		$editUidList = array();
		$colPosValues = array();
		$singleColumn = FALSE;

		// get the layout record for the selected backend layout if any
		$gridContainerId = $row['uid'];
		/** @var $layoutSetup \GridElementsTeam\Gridelements\Backend\LayoutSetup */
		$layoutSetup = GeneralUtility::makeInstance('GridElementsTeam\\Gridelements\\Backend\\LayoutSetup');
		if ($row['pid'] < 0) {
			$originalRecord = BackendUtility::getRecord('tt_content', $row['t3ver_oid']);
		} else {
			$originalRecord = $row;
		}
		$gridElement = $layoutSetup->init($originalRecord['pid'])
				->cacheCurrentParent($gridContainerId, TRUE);
		$layoutUid = $gridElement['tx_gridelements_backend_layout'];
		$layout = $layoutSetup->getLayoutSetup($layoutUid);
		if (isset($layout['config']) && isset($layout['config']['rows.'])) {
			$parserRows = $layout['config']['rows.'];
		}

		// if there is anything to parse, lets check for existing columns in the layout

		if (is_array($parserRows) && count($parserRows) > 0) {
			$this->setMultipleColPosValues($parserRows, $colPosValues);
		} else {
			$singleColumn = TRUE;
			$this->setSingleColPosItems($parentObject, $colPosValues, $gridElement, $showHidden, $deleteClause);
		}

		// if there are any columns, lets build the content for them
		$outerTtContentDataArray = $parentObject->tt_contentData['nextThree'];
		if (count($colPosValues) > 0) {
			$this->renderGridColumns($parentObject, $colPosValues, $gridContent, $gridElement, $editUidList, $singleColumn, $head, $showHidden, $deleteClause);
		}
		$parentObject->tt_contentData['nextThree'] = $outerTtContentDataArray;

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
	 * @param PageLayoutView $parentObject : The parent object that triggered this hook
	 * @param array $row : The current data row for this item
	 * @param string $showHidden : query String containing enable fields
	 * @param string $deleteClause : query String to check for deleted items
	 *
	 * @return string           $shortcutContent: The HTML output for elements of the CType shortcut
	 */
	public function renderCTypeShortcut(PageLayoutView $parentObject, &$row, &$showHidden, &$deleteClause) {
		$shortcutContent = '';
		if ($row['records']) {
			$shortcutItems = explode(',', $row['records']);
			$collectedItems = array();
			foreach ($shortcutItems as $shortcutItem) {
				$shortcutItem = trim($shortcutItem);
				if (strpos($shortcutItem, 'pages_') !== FALSE) {
					$this->collectContentDataFromPages($shortcutItem, $collectedItems, $row['recursive'], $showHidden, $deleteClause, $row['uid']);
				} else if (strpos($shortcutItem, '_') === FALSE || strpos($shortcutItem, 'tt_content_') !== FALSE) {
					$this->collectContentData($shortcutItem, $collectedItems, $showHidden, $deleteClause, $row['uid']);
				}
			}
			if (count($collectedItems)) {
				foreach ($collectedItems as $itemRow) {
					if ($itemRow) {
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
	 * @param array $parserRows : The parsed rows of the gridelement layout
	 * @param array $colPosValues : The column positions that have been found for that layout
	 *
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
	 * @param PageLayoutView $parentObject : The parent object that triggered this hook
	 * @param array $colPosValues : The column positions that have been found for that layout
	 * @param array $row : The current data row for the container item
	 * @param string $showHidden : query String containing enable fields
	 * @param string $deleteClause : query String to check for deleted items
	 *
	 * @return array collected items for this column
	 */
	public function setSingleColPosItems(PageLayoutView $parentObject, &$colPosValues, &$row, $showHidden, $deleteClause) {
		// Due to the pid being "NOT USED" in makeQueryArray we have to set pidSelect here
		$originalPidSelect = $parentObject->pidSelect;
		$specificIds = Helper::getInstance()
				->getSpecificIds($row);
		$parentObject->pidSelect = 'pid = ' . $specificIds['pid'];

		$queryParts = $parentObject->makeQueryArray('tt_content', $row['pid'], 'AND colPos = -1 AND tx_gridelements_container IN (' . $row['uid'] . ',' . $specificIds['uid'] . ') ' . $showHidden . $deleteClause . $parentObject->showLanguage);

		// Due to the pid being "NOT USED" in makeQueryArray we have to reset pidSelect here
		$parentObject->pidSelect = $originalPidSelect;

		$result = $GLOBALS['TYPO3_DB']->exec_SELECT_queryArray($queryParts);
		$colPosValues[] = array(
				0,
				''
		);
		return $parentObject->getResult($result);
	}

	/**
	 * renders the columns of a grid layout
	 *
	 * @param PageLayoutView $parentObject : The parent object that triggered this hook
	 * @param array $colPosValues : The column positions we want to get the content for
	 * @param array $gridContent : The rendered content data of the grid columns
	 * @param array $row : The current data row for the container item
	 * @param array $editUidList : determines if we will get edit icons or not
	 * @param boolean $singleColumn : Determines if we are in single column mode or not
	 * @param array $head : An array of headers for each of the columns
	 * @param string $showHidden : query String containing enable fields
	 * @param string $deleteClause : query String to check for deleted items
	 *
	 * @return void
	 */
	public function renderGridColumns(PageLayoutView $parentObject, &$colPosValues, &$gridContent, &$row, &$editUidList, &$singleColumn, &$head, $showHidden, $deleteClause) {
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
	 * @param PageLayoutView $parentObject : The paren object that triggered this hook
	 * @param int $colPos : The column position to collect the items for
	 * @param array $row : The current data row for the container item
	 * @param string $showHidden : query String containing enable fields
	 * @param string $deleteClause : query String to check for deleted items
	 *
	 * @return array            collected items for the given column
	 */
	public function collectItemsForColumn(PageLayoutView $parentObject, &$colPos, &$row, &$showHidden, &$deleteClause) {
		// Due to the pid being "NOT USED" in makeQueryArray we have to set pidSelect here
		$originalPidSelect = $parentObject->pidSelect;
		$helper = Helper::getInstance();
		$specificIds = $helper->getSpecificIds($row);

		$parentObject->pidSelect = 'pid = ' . $row['pid'];

		if (!$parentObject->tt_contentConfig['languageMode']) {
			$showLanguage = ' AND (sys_language_uid = -1 OR sys_language_uid=' . $parentObject->tt_contentConfig['sys_language_uid'] . ')';
		} else if ($row['sys_language_uid'] > 0) {
			$showLanguage = ' AND sys_language_uid=' . $row['sys_language_uid'];
		} else {
			$showLanguage = '';
		}

		if ($helper->getBackendUser()->workspace > 0 && $row['t3ver_wsid'] > 0) {
			$where = 'AND t3ver_wsid = ' . $row['t3ver_wsid'];
		}
		$where .= ' AND colPos = -1 AND tx_gridelements_container IN (' . $row['uid'] . ',' . $specificIds['uid'] . ') AND tx_gridelements_columns=' . $colPos . $showHidden . $deleteClause . $showLanguage;

		$queryParts = $parentObject->makeQueryArray('tt_content', $row['pid'], $where);

		// Due to the pid being "NOT USED" in makeQueryArray we have to reset pidSelect here
		$parentObject->pidSelect = $originalPidSelect;

		$result = $GLOBALS['TYPO3_DB']->exec_SELECT_queryArray($queryParts);
		return $parentObject->getResult($result);
	}

	/**
	 * renders a single column of a grid layout and sets the edit uid list
	 *
	 * @param PageLayoutView $parentObject : The parent object that triggered this hook
	 * @param array $items : The content data of the column to be rendered
	 * @param int $colPos : The column position we want to get the content for
	 * @param array $gridContent : The rendered content data of the grid column
	 * @param                $row
	 * @param array $editUidList : determines if we will get edit icons or not
	 *
	 * @return void
	 */
	public function renderSingleGridColumn(PageLayoutView $parentObject, &$items, &$colPos, &$gridContent, $row, &$editUidList) {

		$specificIds = Helper::getInstance()
				->getSpecificIds($row);

		if ($colPos < 32768) {
			if ($row{'sys_language_uid'}) {
				$language = (int)$row['sys_language_uid'];
			}
			$newParams = $parentObject->newContentElementOnClick($parentObject->id, '-1' . '&tx_gridelements_container=' . $specificIds['uid'] . '&tx_gridelements_columns=' . $colPos, $language);
		}

		$gridContent[$colPos] .= '
			<div class="t3-page-ce-wrapper">
				<div class="t3-page-ce-dropzone">
					<div class="t3-page-ce-wrapper-new-ce">
						<a href="#" onclick="' . htmlspecialchars($newParams) . '" title="' . $GLOBALS['LANG']->getLL('newInColumn', TRUE) . '">' . IconUtility::getSpriteIcon('actions-document-new') . '</a>
					</div>
			</div>';

		if (count($items) > 0) {
			foreach ($items as $itemRow) {
				if (is_array($itemRow)) {
					$statusHidden = $parentObject->isDisabled('tt_content', $itemRow) ? ' t3-page-ce-hidden' : '';
					$gridContent[$colPos] .= '
				<div class="t3-page-ce' . $statusHidden . '"><div class="t3-page-ce-dragitem">' . $this->renderSingleElementHTML($parentObject, $itemRow) . '</div></div>';
					// New content element:
					if ($parentObject->option_newWizard) {
						$onClick = 'window.location.href=\'db_new_content_el.php?id=' . $itemRow['pid'] . '&sys_language_uid=' . $itemRow['sys_language_uid'] . '&colPos=' . $itemRow['colPos'] . '&uid_pid=' . -$itemRow['uid'] . '&returnUrl=' . rawurlencode(GeneralUtility::getIndpEnv('REQUEST_URI')) . '\';';
					} else {
						$params = '&edit[tt_content][' . -$itemRow['uid'] . ']=new';
						$onClick = BackendUtility::editOnClick($params, $parentObject->backPath);
					}
					$gridContent[$colPos] .= '
				<div class="t3-page-ce-dropzone"><div class="t3-page-ce-new-ce">
					<a href="#" onclick="' . htmlspecialchars($onClick) . '" title="' . $GLOBALS['LANG']->getLL('newRecordHere', 1) . '">' . IconUtility::getSpriteIcon('actions-document-new') . '</a>
				</div></div></div>
					';
					$editUidList[$colPos] .= $editUidList[$colPos] ? ',' . $itemRow['uid'] : $itemRow['uid'];
				}
			}
		}

		$gridContent[$colPos] .= '</div>';
	}

	/**
	 * Sets the headers for a grid before content and headers are put together
	 *
	 * @param PageLayoutView $parentObject : The parent object that triggered this hook
	 * @param array $head : The collected item data rows
	 * @param int $colPos : The column position we want to get a header for
	 * @param string $name : The name of the header
	 * @param array $editUidList : determines if we will get edit icons or not
	 *
	 * @internal param array $row : The current data row for the container item
	 * @return void
	 */
	public function setColumnHeader(PageLayoutView $parentObject, &$head, &$colPos, &$name, &$editUidList) {
		$head[$colPos] = $this->tt_content_drawColHeader($name, ($parentObject->doEdit && $editUidList[$colPos]) ? '&edit[tt_content][' . $editUidList[$colPos] . ']=edit' . $parentObject->pageTitleParamForAltDoc : '', $parentObject);
	}

	/**
	 * Draw header for a content element column:
	 *
	 * @param string $colName Column name
	 * @param string $editParams Edit params (Syntax: &edit[...] for alt_doc.php)
	 * @param PageLayoutView $parentObject
	 *
	 * @return string HTML table
	 */
	function tt_content_drawColHeader($colName, $editParams, &$parentObject) {

		$icons = '';
		// Create command links:
		if ($parentObject->tt_contentConfig['showCommands']) {
			// Edit whole of column:
			if ($editParams) {
				$icons .= '<a href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick($editParams, $parentObject->backPath)) . '" title="' . $GLOBALS['LANG']->getLL('editColumn', TRUE) . '">' . IconUtility::getSpriteIcon('actions-document-open') . '</a>';
			}
			$icons .= '<a href="#" class="toggle-content toggle-up" title="' . $this->lang->sL('LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tx_gridelements_togglecontent') . '">' . IconUtility::getSpriteIcon('actions-move-to-top') . '</a>';
			$icons .= '<a href="#" class="toggle-content toggle-down" title="' . $this->lang->sL('LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tx_gridelements_togglecontent') . '">' . IconUtility::getSpriteIcon('actions-move-to-bottom') . '</a>';
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
	 * @param array $layoutSetup : The setup of the layout that is selected for the grid we are going to render
	 * @param array $row : The current data row for the container item
	 * @param array $head : The data for the column headers of the grid we are going to render
	 * @param array $gridContent : The content data of the grid we are going to render
	 *
	 * @return string
	 */
	public function renderGridLayoutTable($layoutSetup, $row, $head, $gridContent) {
		$specificIds = Helper::getInstance()
				->getSpecificIds($row);

		$grid = '<div class="t3-gridContainer' . ($layoutSetup['frame'] ? ' t3-gridContainer-framed t3-gridContainer-' . $layoutSetup['frame'] : '') . ($layoutSetup['top_level_layout'] ? ' t3-gridTLContainer' : '') . '">';
		if ($layoutSetup['frame']) {
			$grid .= '<h4 class="t3-gridContainer-title-' . $layoutSetup['frame'] . '">' . $this->lang->sL($layoutSetup['title'], TRUE) . '</h4>';
		}
		if ($GLOBALS['BE_USER']->uc['showGridInformation'] === 1) {
			$grid .= '<span class="t3-help-link" href="#" data-title="' . htmlspecialchars($this->lang->sL($layoutSetup['title'])) . '" data-description="' . htmlspecialchars($this->lang->sL($layoutSetup['description'])) . '"><abbr class="t3-help-teaser">' . $this->lang->sL($layoutSetup['title'], TRUE) . '</abbr></span>';
		}
		$grid .= '<table border="0" cellspacing="1" cellpadding="4" width="100%" height="100%" class="t3-page-columns t3-gridTable">';
		// add colgroups
		$colCount = 0;
		$rowCount = 0;
		if (isset($layoutSetup['config'])) {
			if (isset($layoutSetup['config']['colCount'])) {
				$colCount = (int)$layoutSetup['config']['colCount'];
			}
			if (isset($layoutSetup['config']['rowCount'])) {
				$rowCount = (int)$layoutSetup['config']['rowCount'];
			}
		}
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
				$columnKey = $columnConfig['colPos'] !== '' ? (int)$columnConfig['colPos'] : 32768;
				// allowed CTypes
				if (!empty($columnConfig['allowed'])) {
					$allowedCTypes = array_flip(GeneralUtility::trimExplode(',', $columnConfig['allowed']));
					if (!isset($allowedCTypes['*'])) {
						foreach ($allowedCTypes as $ctype => &$ctypeClass) {
							$ctypeClass = 't3-allow-' . $ctype;
						}
					} else {
						unset($allowedCTypes);
					}
				}
				if (!empty($columnConfig['allowedGridTypes'])) {
					$allowedGridTypes = array_flip(GeneralUtility::trimExplode(',', $columnConfig['allowedGridTypes']));
					if (!isset($allowedGridTypes['*'])) {
						foreach ($allowedGridTypes as $gridType => &$gridTypeClass) {
							$gridTypeClass = 't3-allow-gridtype-' . $gridType;
						}
						$allowedCTypes['gridelements_pi1'] = 't3-allow-gridelements_pi1';
					} else {
						if (isset($allowedCTypes)) {
							$allowedCTypes['gridelements_pi1'] = 't3-allow-gridelements_pi1';
						}
						unset($allowedGridTypes);
					}
				}
				// render the grid cell
				$colSpan = (int)$columnConfig['colspan'];
				$rowSpan = (int)$columnConfig['rowspan'];
				$grid .= '<td valign="top"' .
						(isset($columnConfig['colspan']) ? ' colspan="' . $colSpan . '"' : '') .
						(isset($columnConfig['rowspan']) ? ' rowspan="' . $rowSpan . '"' : '') .
						'id="column-' . $specificIds['uid'] . 'x' . $columnKey . '" class="t3-gridCell t3-page-column t3-page-column-' . $columnKey .
						(!isset($columnConfig['colPos']) || $columnConfig['colPos'] === '' ? ' t3-gridCell-unassigned' : '') .
						(isset($columnConfig['colspan']) && $columnConfig['colPos'] !== '' ? ' t3-gridCell-width' . $colSpan : '') .
						(isset($columnConfig['rowspan']) && $columnConfig['colPos'] !== '' ? ' t3-gridCell-height' . $rowSpan : '') . ' ' .
						($layoutSetup['horizontal'] ? ' t3-gridCell-horizontal' : '') .
						(count($allowedCTypes) ? ' ' . join(' ', $allowedCTypes) : ' t3-allow-all') .
						(count($allowedGridTypes) ? ' ' . join(' ', $allowedGridTypes) : '') . '">';

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
	 * @param string $shortcutItem : The single page to be used as the tree root
	 * @param array $collectedItems : The collected item data rows ordered by parent position, column position and sorting
	 * @param int $recursive : The number of levels for the recursion
	 * @param string $showHidden : query String containing enable fields
	 * @param string $deleteClause : query String to check for deleted items
	 * @param int $parentUid : uid of the referencing tt_content record
	 *
	 * @return void
	 */
	public function collectContentDataFromPages($shortcutItem, &$collectedItems, $recursive = 0, &$showHidden, &$deleteClause, $parentUid) {
		$itemList = str_replace('pages_', '', $shortcutItem);
		if ($recursive) {
			if (!$this->tree instanceof QueryGenerator) {
				$this->tree = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\QueryGenerator');
			}
			$itemList = $this->tree->getTreeList($itemList, (int)$recursive, 0, 1);
		}
		$itemRows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'tt_content', 'uid != ' . (int)$parentUid . ' AND pid IN (' . $itemList . ') AND colPos >= 0 ' . $showHidden . $deleteClause, '', 'FIND_IN_SET(pid, \'' . $itemList . '\'),colPos,sorting');
		foreach ($itemRows as $itemRow) {
			if ($GLOBALS['BE_USER']->workspace > 0) {
				BackendUtility::workspaceOL('tt_content', $itemRow, $GLOBALS['BE_USER']->workspace);
			}
			$itemRow['tx_gridelements_reference_container'] = $itemRow['pid'];
			$collectedItems[] = $itemRow;
		}
	}

	/**
	 * Collects tt_content data from a single tt_content element
	 * @param string $shortcutItem : The tt_content element to fetch the data from
	 * @param array $collectedItems : The collected item data row
	 * @param string $showHidden : query String containing enable fields
	 * @param string $deleteClause : query String to check for deleted items
	 * @param int $parentUid : uid of the referencing tt_content record
	 */
	public function collectContentData($shortcutItem, &$collectedItems, &$showHidden, &$deleteClause, $parentUid) {
		$shortcutItem = str_replace('tt_content_', '', $shortcutItem);
		if ((int)$shortcutItem !== (int)$parentUid) {
			$itemRow = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', 'tt_content', 'uid=' . $shortcutItem . $showHidden . $deleteClause);
			if ($GLOBALS['BE_USER']->workspace > 0) {
				BackendUtility::workspaceOL('tt_content', $itemRow, $GLOBALS['BE_USER']->workspace);
			}
			$collectedItems[] = $itemRow;
		}
	}

	/**
	 * Renders the HTML code for a single tt_content element
	 *
	 * @param PageLayoutView $parentObject : The parent object that triggered this hook
	 * @param array $itemRow : The data row to be rendered as HTML
	 *
	 * @return string
	 */
	public function renderSingleElementHTML(PageLayoutView $parentObject, $itemRow) {
		$singleElementHTML = $parentObject->tt_content_drawHeader($itemRow, $parentObject->tt_contentConfig['showInfo'] ? 15 : 5, $parentObject->defLangBinding && $parentObject->lP > 0, TRUE);
		$isRTE = $parentObject->RTE && $parentObject->isRTEforField('tt_content', $itemRow, 'bodytext');
		$singleElementHTML .= '<div ' . (!empty($itemRow['_ORIG_uid']) ? ' class="ver-element"' : '') . '><div class="t3-page-ce-body-inner t3-page-ce-body-inner-' . $itemRow['CType'] . '">' . $parentObject->tt_content_drawItem($itemRow, $isRTE) . '</div></div>';
		$footerContent = '';
		// Get processed values:
		$info = array();
		$parentObject->getProcessedValue('tt_content', 'starttime,endtime,fe_group,spaceBefore,spaceAfter', $itemRow, $info);
		// Display info from records fields:
		if (count($info)) {
			$footerContent = '<div class="t3-page-ce-info">
				' . implode('<br />', $info) . '
				</div>';
		}
		// Wrap it
		if (!empty($footerContent)) {
			$singleElementHTML .= '<div class="t3-page-ce-footer">' . $footerContent . '</div>';
		}
		return $singleElementHTML;
	}
}
