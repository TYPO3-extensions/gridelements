<?php
namespace GridElementsTeam\Gridelements\Xclass;

/*************************************************************
 *  Copyright notice
 *  (c) 1999-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
 *  (c) 2012 Sebastian Böttger <sebastian.boettger@typovision.de>
 *  (c) 2013 Dirk Hoffmann
 *  (c) 2014 Jo Hasenau
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * XCLASS of 'localRecordList' (class.db_list_extra.inc)
 * @author    Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author    Dirk Hoffmann <hoffmann@vmd-jena.de>
 * @package TYPO3
 * @subpackage gridelements
 */
class DatabaseRecordList extends \TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList {

	protected $maxDepth = 10;

	/**
	 * Creates the listing of records from a single table
	 * @param string $table Table name
	 * @param int $id Page id
	 * @param string $rowList List of fields to show in the listing. Pseudo fields will be added including the record header.
	 * @throws \UnexpectedValueException
	 * @return string HTML table with the listing for the record.
	 */
	public function getTable($table, $id, $rowList) {
		$backendUser = $this->getBackendUserAuthentication();
		$lang = $this->getLanguageService();
		$db = $this->getDatabaseConnection();
		// Init
		$addWhere = '';
		$titleCol = $GLOBALS['TCA'][$table]['ctrl']['label'];
		$thumbsCol = $GLOBALS['TCA'][$table]['ctrl']['thumbnail'];
		$l10nEnabled = $GLOBALS['TCA'][$table]['ctrl']['languageField'] && $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] && !$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerTable'];
		$tableCollapsed = (bool)$this->tablesCollapsed[$table];
		// prepare space icon
		$this->spaceIcon = '<span class="btn btn-default disabled">' . IconUtility::getSpriteIcon('empty-empty') . '</span>';
		// Cleaning rowlist for duplicates and place the $titleCol as the first column always!
		$this->fieldArray = array();
		// title Column
		// Add title column
		$this->fieldArray[] = $titleCol;
		// Control-Panel
		if (!GeneralUtility::inList($rowList, '_CONTROL_')) {
			$this->fieldArray[] = '_CONTROL_';
		}
		// Clipboard
		if ($this->showClipboard) {
			$this->fieldArray[] = '_CLIPBOARD_';
		}
		// Ref
		if (!$this->dontShowClipControlPanels) {
			$this->fieldArray[] = '_REF_';
		}
		// Path
		if ($this->searchLevels) {
			$this->fieldArray[] = '_PATH_';
		}
		// Localization
		if ($this->localizationView && $l10nEnabled) {
			$this->fieldArray[] = '_LOCALIZATION_';
			$this->fieldArray[] = '_LOCALIZATION_b';
			$addWhere .= ' AND (
				' . $GLOBALS['TCA'][$table]['ctrl']['languageField'] . '<=0
				OR
				' . $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] . ' = 0
			)';
		}
		// Cleaning up:
		$this->fieldArray = array_unique(array_merge($this->fieldArray, GeneralUtility::trimExplode(',', $rowList, true)));
		if ($this->noControlPanels) {
			$tempArray = array_flip($this->fieldArray);
			unset($tempArray['_CONTROL_']);
			unset($tempArray['_CLIPBOARD_']);
			$this->fieldArray = array_keys($tempArray);
		}
		// Creating the list of fields to include in the SQL query:
		$selectFields = $this->fieldArray;
		$selectFields[] = 'uid';
		$selectFields[] = 'pid';
		// adding column for thumbnails
		if ($thumbsCol) {
			$selectFields[] = $thumbsCol;
		}
		if ($table == 'pages') {
			$selectFields[] = 'module';
			$selectFields[] = 'extendToSubpages';
			$selectFields[] = 'nav_hide';
			$selectFields[] = 'doktype';
			$selectFields[] = 'shortcut';
			$selectFields[] = 'shortcut_mode';
			$selectFields[] = 'mount_pid';
		}
		if (is_array($GLOBALS['TCA'][$table]['ctrl']['enablecolumns'])) {
			$selectFields = array_merge($selectFields, $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']);
		}
		if ($GLOBALS['TCA'][$table]['ctrl']['type']) {
			$selectFields[] = $GLOBALS['TCA'][$table]['ctrl']['type'];
		}
		if ($GLOBALS['TCA'][$table]['ctrl']['typeicon_column']) {
			$selectFields[] = $GLOBALS['TCA'][$table]['ctrl']['typeicon_column'];
		}
		if ($GLOBALS['TCA'][$table]['ctrl']['versioningWS']) {
			$selectFields[] = 't3ver_id';
			$selectFields[] = 't3ver_state';
			$selectFields[] = 't3ver_wsid';
		}
		if ($l10nEnabled) {
			$selectFields[] = $GLOBALS['TCA'][$table]['ctrl']['languageField'];
			$selectFields[] = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'];
		}
		if ($GLOBALS['TCA'][$table]['ctrl']['label_alt']) {
			$selectFields = array_merge($selectFields, GeneralUtility::trimExplode(',', $GLOBALS['TCA'][$table]['ctrl']['label_alt'], true));
		}
		// Unique list!
		$selectFields = array_unique($selectFields);
		$fieldListFields = $this->makeFieldList($table, 1);
		if (empty($fieldListFields) && $GLOBALS['TYPO3_CONF_VARS']['BE']['debug']) {
			$message = sprintf($lang->sL('LLL:EXT:lang/locallang_mod_web_list.xlf:missingTcaColumnsMessage', true), $table, $table);
			$messageTitle = $lang->sL('LLL:EXT:lang/locallang_mod_web_list.xlf:missingTcaColumnsMessageTitle', true);
			/** @var FlashMessage $flashMessage */
			$flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $message, $messageTitle, FlashMessage::WARNING, true);
			/** @var $flashMessageService FlashMessageService */
			$flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
			/** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
			$defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
			$defaultFlashMessageQueue->enqueue($flashMessage);
		}
		// Making sure that the fields in the field-list ARE in the field-list from TCA!
		$selectFields = array_intersect($selectFields, $fieldListFields);
		// Implode it into a list of fields for the SQL-statement.
		$selFieldList = implode(',', $selectFields);
		$this->selFieldList = $selFieldList;
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['getTable'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['getTable'] as $classData) {
				$hookObject = GeneralUtility::getUserObj($classData);
				if (!$hookObject instanceof RecordListGetTableHookInterface) {
					throw new \UnexpectedValueException('$hookObject must implement interface ' . RecordListGetTableHookInterface::class, 1195114460);
				}
				$hookObject->getDBlistQuery($table, $id, $addWhere, $selFieldList, $this);
			}
		}
		// Create the SQL query for selecting the elements in the listing:
		// do not do paging when outputting as CSV
		if ($this->csvOutput) {
			$this->iLimit = 0;
		}
		if ($this->firstElementNumber > 2 && $this->iLimit > 0) {
			// Get the two previous rows for sorting if displaying page > 1
			$this->firstElementNumber = $this->firstElementNumber - 2;
			$this->iLimit = $this->iLimit + 2;
			// (API function from TYPO3\CMS\Recordlist\RecordList\AbstractDatabaseRecordList)
			$queryParts = $this->makeQueryArray($table, $id, $addWhere, $selFieldList);
			$this->firstElementNumber = $this->firstElementNumber + 2;
			$this->iLimit = $this->iLimit - 2;
		} else {
			// (API function from TYPO3\CMS\Recordlist\RecordList\AbstractDatabaseRecordList)
			$queryParts = $this->makeQueryArray($table, $id, $addWhere, $selFieldList);
		}

		// Finding the total amount of records on the page
		// (API function from TYPO3\CMS\Recordlist\RecordList\AbstractDatabaseRecordList)
		$this->setTotalItems($queryParts);

		// Init:
		$dbCount = 0;
		$out = '';
		$tableHeader = '';
		$result = null;
		$listOnlyInSingleTableMode = $this->listOnlyInSingleTableMode && !$this->table;
		// If the count query returned any number of records, we perform the real query,
		// selecting records.
		if ($this->totalItems) {
			// Fetch records only if not in single table mode or if in multi table mode and
			// not collapsed
			if ($listOnlyInSingleTableMode || !$this->table && $tableCollapsed) {
				$dbCount = $this->totalItems;
			} else {
				// Set the showLimit to the number of records when outputting as CSV
				if ($this->csvOutput) {
					$this->showLimit = $this->totalItems;
					$this->iLimit = $this->totalItems;
				}
				$result = $db->exec_SELECT_queryArray($queryParts);
				$dbCount = $db->sql_num_rows($result);
			}
		}
		// If any records was selected, render the list:
		if ($dbCount) {
			$tableTitle = $lang->sL($GLOBALS['TCA'][$table]['ctrl']['title'], true);
			if ($tableTitle === '') {
				$tableTitle = $table;
			}
			// Header line is drawn
			$theData = array();
			if ($this->disableSingleTableView) {
				$theData[$titleCol] = '<span class="c-table">' . BackendUtility::wrapInHelp($table, '', $tableTitle) . '</span> (<span class="t3js-table-total-items">' . $this->totalItems . '</span>)';
			} else {
				$icon = $this->table ? IconUtility::getSpriteIcon('actions-view-table-collapse', array('title' => $lang->getLL('contractView', true))) : IconUtility::getSpriteIcon('actions-view-table-expand', array('title' => $lang->getLL('expandView', true)));
				$theData[$titleCol] = $this->linkWrapTable($table, $tableTitle . ' (<span class="t3js-table-total-items">' . $this->totalItems . '</span>) ' . $icon);
			}
			if ($listOnlyInSingleTableMode) {
				$tableHeader .= BackendUtility::wrapInHelp($table, '', $theData[$titleCol]);
			} else {
				// Render collapse button if in multi table mode
				$collapseIcon = '';
				if (!$this->table) {
					$href = htmlspecialchars(($this->listURL() . '&collapse[' . $table . ']=' . ($tableCollapsed ? '0' : '1')));
					$title = $tableCollapsed ? $lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.expandTable', true) : $lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.collapseTable', true);
					$icon = $tableCollapsed ? IconUtility::getSpriteIcon('actions-view-list-expand', array('class' => 'collapseIcon')) : IconUtility::getSpriteIcon('actions-view-list-collapse', array('class' => 'collapseIcon'));
					$collapseIcon = '<a href="' . $href . '" title="' . $title . '" class="pull-right">' . $icon . '</a>';
				}
				$tableHeader .= $theData[$titleCol] . $collapseIcon;
			}
			// Render table rows only if in multi table view and not collapsed or if in
			// single table view
			$rowOutput = '';
			if (!$listOnlyInSingleTableMode && (!$tableCollapsed || $this->table)) {
				// Fixing a order table for sortby tables
				$this->currentTable = array();
				$currentIdList = array();
				$doSort = $GLOBALS['TCA'][$table]['ctrl']['sortby'] && !$this->sortField;
				$prevUid = 0;
				$prevPrevUid = 0;
				// Get first two rows and initialize prevPrevUid and prevUid if on page > 1
				if ($this->firstElementNumber > 2 && $this->iLimit > 0) {
					$row = $db->sql_fetch_assoc($result);
					$prevPrevUid = -((int)$row['uid']);
					$row = $db->sql_fetch_assoc($result);
					$prevUid = $row['uid'];
				}
				$accRows = array();
				// Accumulate rows here
				while ($row = $db->sql_fetch_assoc($result)) {
					if (!$this->isRowListingConditionFulfilled($table, $row)) {
						continue;
					}
					// In offline workspace, look for alternative record:
					BackendUtility::workspaceOL($table, $row, $backendUser->workspace, true);
					if (is_array($row)) {
						$accRows[] = $row;
						$currentIdList[] = $row['uid'];
						if ($doSort) {
							if ($prevUid) {
								$this->currentTable['prev'][$row['uid']] = $prevPrevUid;
								$this->currentTable['next'][$prevUid] = '-' . $row['uid'];
								$this->currentTable['prevUid'][$row['uid']] = $prevUid;
							}
							$prevPrevUid = isset($this->currentTable['prev'][$row['uid']]) ? -$prevUid : $row['pid'];
							$prevUid = $row['uid'];
						}
					}
				}
				$db->sql_free_result($result);
				$this->totalRowCount = count($accRows);
				// CSV initiated
				if ($this->csvOutput) {
					$this->initCSV();
				}
				// Render items:
				$this->CBnames = array();
				$this->duplicateStack = array();
				$this->eCounter = $this->firstElementNumber;
				$cc = 0;
				$lastColPos = '';
				foreach ($accRows as $key => $row) {
					// Render item row if counter < limit
					if ($cc < $this->iLimit) {
						$cc++;
						$this->translations = false;
						if (isset($row['colPos']) && ($row['colPos'] != $lastColPos)) {
							$lastColPos = $row['colPos'];
							$this->showMoveUp = false;
							$column = BackendUtility::getProcessedValueExtra($table, 'colPos', $row['colPos'], 100, $row['uid']);
							$rowOutput .= '<tr><td colspan="2"></td><td colspan="' . (count($this->fieldArray) - 1 + $this->maxDepth) . '" style="padding:5px;"><br /><strong>' . $GLOBALS['LANG']->sL('LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:list.columnName') . ' ' . (($column) ? $column : $row['colPos']) . '</strong></td></tr>';
						} else {
							$this->showMoveUp = true;
						}
						if (isset($row['colPos']) && isset($accRows[$key + 1]) && $row['colPos'] != $accRows[$key + 1]['colPos']) {
							$this->showMoveDown = false;
						} else {
							$this->showMoveDown = true;
						}
						$rowOutput .= $this->renderListRow($table, $row, $cc, $titleCol, $thumbsCol);
						// If localization view is enabled it means that the selected records are
						// either default or All language and here we will not select translations
						// which point to the main record:
						if ($this->localizationView && $l10nEnabled) {
							// For each available translation, render the record:
							if (is_array($this->translations)) {
								foreach ($this->translations as $lRow) {
									// $lRow isn't always what we want - if record was moved we've to work with the
									// placeholder records otherwise the list is messed up a bit
									if ($row['_MOVE_PLH_uid'] && $row['_MOVE_PLH_pid']) {
										$where = 't3ver_move_id="' . (int)$lRow['uid'] . '" AND pid="' . $row['_MOVE_PLH_pid'] . '" AND t3ver_wsid=' . $row['t3ver_wsid'] . BackendUtility::deleteClause($table);
										$tmpRow = BackendUtility::getRecordRaw($table, $where, $selFieldList);
										$lRow = is_array($tmpRow) ? $tmpRow : $lRow;
									}
									// In offline workspace, look for alternative record:
									BackendUtility::workspaceOL($table, $lRow, $backendUser->workspace, true);
									if (is_array($lRow) && $backendUser->checkLanguageAccess($lRow[$GLOBALS['TCA'][$table]['ctrl']['languageField']])) {
										$currentIdList[] = $lRow['uid'];
										$rowOutput .= $this->renderListRow($table, $lRow, $cc, $titleCol, $thumbsCol, 18);
									}
								}
							}
						}
					}
					// Counter of total rows incremented:
					$this->eCounter++;
				}
				// Record navigation is added to the beginning and end of the table if in single
				// table mode
				if ($this->table) {
					$rowOutput = $this->renderListNavigation('top') . $rowOutput . $this->renderListNavigation('bottom');
				} else {
					// Show that there are more records than shown
					if ($this->totalItems > $this->itemsLimitPerTable) {
						$countOnFirstPage = $this->totalItems > $this->itemsLimitSingleTable ? $this->itemsLimitSingleTable : $this->totalItems;
						$hasMore = $this->totalItems > $this->itemsLimitSingleTable;
						$colspan = $this->showIcon ? count($this->fieldArray) + $this->maxDepth : count($this->fieldArray);
						$rowOutput .= '<tr><td colspan="' . $colspan . '">
								<a href="' . htmlspecialchars(($this->listURL() . '&table=' . rawurlencode($table))) . '">' . '<img' . IconUtility::skinImg($this->backPath, 'gfx/pildown.gif', 'width="14" height="14"') . ' alt="" />' . ' <i>[1 - ' . $countOnFirstPage . ($hasMore ? '+' : '') . ']</i></a>
								</td></tr>';
					}
				}
				// The header row for the table is now created:
				$out .= $this->renderListHeader($table, $currentIdList);
			}
			// The list of records is added after the header:
			$out .= $rowOutput;
			// ... and it is all wrapped in a table:
			$out = '



			<!--
				DB listing of elements:	"' . htmlspecialchars($table) . '"
			-->
				<div class="panel panel-space panel-default">
					<div class="panel-heading">
					' . $tableHeader . '
					</div>
					<div class="table-fit">
						<table data-table="' . htmlspecialchars($table) . '" class="table table-striped table-hover' . ($listOnlyInSingleTableMode ? ' typo3-dblist-overview' : '') . '">
							' . $out . '
						</table>
					</div>
				</div>
			';
			// Output csv if...
			// This ends the page with exit.
			if ($this->csvOutput) {
				$this->outputCSV($table);
			}
		}

		// Return content:
		return $out;
	}

	/**
	 * Rendering the header row for a table
	 * @param string $table Table name
	 * @param int[] $currentIdList Array of the currently displayed uids of the table
	 * @throws \UnexpectedValueException
	 * @return string Header table row
	 * @access private
	 * @see getTable()
	 */
	public function renderListHeader($table, $currentIdList) {
		$lang = $this->getLanguageService();
		// Init:
		$theData = array();
		$icon = '';
		// Traverse the fields:
		foreach ($this->fieldArray as $fCol) {
			// Calculate users permissions to edit records in the table:
			$permsEdit = $this->calcPerms & ($table == 'pages' ? 2 : 16);
			switch ((string)$fCol) {
				case '_PATH_':
					// Path
					$theData[$fCol] = '<i>[' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:labels._PATH_', true) . ']</i>';
					break;
				case '_REF_':
					// References
					$theData[$fCol] = '<i>[' . $lang->sL('LLL:EXT:lang/locallang_mod_file_list.xlf:c__REF_', true) . ']</i>';
					break;
				case '_LOCALIZATION_':
					// Path
					$theData[$fCol] = '<i>[' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:labels._LOCALIZATION_', true) . ']</i>';
					break;
				case '_LOCALIZATION_b':
					// Path
					$theData[$fCol] = $lang->getLL('Localize', true);
					break;
				case '_CLIPBOARD_':
					if (!$this->getModule()->MOD_SETTINGS['clipBoard']) {
						break;
					}
					// Clipboard:
					$cells = array();
					// If there are elements on the clipboard for this table, then display the
					// "paste into" icon:
					$elFromTable = $this->clipObj->elFromTable($table);
					if (count($elFromTable)) {
						$href = htmlspecialchars($this->clipObj->pasteUrl($table, $this->id));
						$onClick = htmlspecialchars('return ' . $this->clipObj->confirmMsg('pages', $this->pageRow, 'into', $elFromTable));
						$cells['pasteAfter'] = '<a class="btn btn-default" href="' . $href . '" onclick="' . $onClick . '" title="' . $lang->getLL('clip_paste', true) . '">' . IconUtility::getSpriteIcon('actions-document-paste-after') . '</a>';
					}
					// If the numeric clipboard pads are enabled, display the control icons for that:
					if ($this->clipObj->current != 'normal') {
						// The "select" link:
						$spriteIcon = IconUtility::getSpriteIcon('actions-edit-copy', array('title' => $lang->getLL('clip_selectMarked', true)));
						$cells['copyMarked'] = $this->linkClipboardHeaderIcon($spriteIcon, $table, 'setCB');
						// The "edit marked" link:
						$editIdList = implode(',', $currentIdList);
						$editIdList = '\'+editList(\'' . $table . '\',\'' . $editIdList . '\')+\'';
						$params = '&edit[' . $table . '][' . $editIdList . ']=edit&disHelp=1';
						$onClick = htmlspecialchars(BackendUtility::editOnClick($params, $this->backPath, -1));
						$cells['edit'] = '<a class="btn btn-default" href="#" onclick="' . $onClick . '" title="' . $lang->getLL('clip_editMarked', true) . '">' . IconUtility::getSpriteIcon('actions-document-open') . '</a>';
						// The "Delete marked" link:
						$cells['delete'] = $this->linkClipboardHeaderIcon(IconUtility::getSpriteIcon('actions-edit-delete', array('title' => $lang->getLL('clip_deleteMarked', true))), $table, 'delete', sprintf($lang->getLL('clip_deleteMarkedWarning'), $lang->sL($GLOBALS['TCA'][$table]['ctrl']['title'])));
						// The "Select all" link:
						$onClick = htmlspecialchars(('checkOffCB(\'' . implode(',', $this->CBnames) . '\', this); return false;'));
						$cells['markAll'] = '<a class="btn btn-default" rel="" href="#" onclick="' . $onClick . '" title="' . $lang->getLL('clip_markRecords', true) . '">' . IconUtility::getSpriteIcon('actions-document-select') . '</a>';
					} else {
						$cells['empty'] = '';
					}
					/**
					 * @hook renderListHeaderActions: Allows to change the clipboard icons of the Web>List table headers
					 * @usage Above each listed table in Web>List a header row is shown.
					 *        This hook allows to modify the icons responsible for the clipboard functions
					 *        (shown above the clipboard checkboxes when a clipboard other than "Normal" is selected),
					 *        or other "Action" functions which perform operations on the listed records.
					 */
					if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'])) {
						foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'] as $classData) {
							$hookObject = GeneralUtility::getUserObj($classData);
							if (!$hookObject instanceof RecordListHookInterface) {
								throw new \UnexpectedValueException('$hookObject must implement interface ' . RecordListHookInterface::class, 1195567850);
							}
							$cells = $hookObject->renderListHeaderActions($table, $currentIdList, $cells, $this);
						}
					}
					$theData[$fCol] = '<div class="btn-group" role="group">' . implode('', $cells) . '</div>';
					break;
				case '_CONTROL_':
					// Control panel:
					if (!$GLOBALS['TCA'][$table]['ctrl']['readOnly']) {
						// If new records can be created on this page, add links:
						$permsAdditional = ($table === 'pages' ? 8 : 16);
						if ($this->calcPerms & $permsAdditional && $this->showNewRecLink($table)) {
							$spriteIcon = $table === 'pages' ? IconUtility::getSpriteIcon('actions-page-new') : IconUtility::getSpriteIcon('actions-document-new');
							if ($table === 'tt_content' && $this->newWizards) {
								// If mod.web_list.newContentWiz.overrideWithExtension is set, use that extension's create new content wizard instead:
								$tmpTSc = BackendUtility::getModTSconfig($this->pageinfo['uid'], 'mod.web_list');
								$tmpTSc = $tmpTSc['properties']['newContentWiz.']['overrideWithExtension'];
								$newContentWizScriptPath = ExtensionManagementUtility::isLoaded($tmpTSc) ? $this->backPath . ExtensionManagementUtility::extRelPath($tmpTSc) . 'mod1/db_new_content_el.php?id=' . $this->id : BackendUtility::getModuleUrl('new_content_element', array('id' => $this->id), $this->backPath);

								$onClick = 'return jumpExt(' . GeneralUtility::quoteJSvalue($newContentWizScriptPath) . ');';
								$icon = '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars($onClick) . '" title="' . $lang->getLL('new', true) . '">' . $spriteIcon . '</a>';
							} elseif ($table == 'pages' && $this->newWizards) {
								$href = $this->backPath . 'db_new.php?id=' . $this->id . '&pagesOnly=1&returnUrl=' . rawurlencode(GeneralUtility::getIndpEnv('REQUEST_URI'));
								$icon = '<a class="btn btn-default" href="' . htmlspecialchars($href) . '" title="' . $lang->getLL('new', true) . '">' . $spriteIcon . '</a>';
							} else {
								$params = '&edit[' . $table . '][' . $this->id . ']=new';
								if ($table == 'pages_language_overlay') {
									$params .= '&overrideVals[pages_language_overlay][doktype]=' . (int)$this->pageRow['doktype'];
								}
								$icon = '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick($params, $this->backPath, -1)) . '" title="' . $lang->getLL('new', true) . '">' . $spriteIcon . '</a>';
							}
						}
						// If the table can be edited, add link for editing ALL SHOWN fields for all listed records:
						if ($permsEdit && $this->table && is_array($currentIdList)) {
							$editIdList = implode(',', $currentIdList);
							if ($this->clipNumPane()) {
								$editIdList = '\'+editList(\'' . $table . '\',\'' . $editIdList . '\')+\'';
							}
							$params = '&edit[' . $table . '][' . $editIdList . ']=edit&columnsOnly=' . implode(',', $this->fieldArray) . '&disHelp=1';
							$icon .= '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick($params, $this->backPath, -1)) . '" title="' . $lang->getLL('editShownColumns', true) . '">' . IconUtility::getSpriteIcon('actions-document-open') . '</a>';
							$icon = '<div class="btn-group" role="group">' . $icon . '</div>';
						}
						// Add an empty entry, so column count fits again after moving this into $icon
						$theData[$fCol] = '&nbsp;';
					}
					break;
				default:
					// Regular fields header:
					$theData[$fCol] = '';

					// Check if $fCol is really a field and get the label and remove the colons
					// at the end
					$sortLabel = BackendUtility::getItemLabel($table, $fCol);
					if ($sortLabel !== null) {
						$sortLabel = $lang->sL($sortLabel, true);
						$sortLabel = rtrim(trim($sortLabel), ':');
					} else {
						// No TCA field, only output the $fCol variable with square brackets []
						$sortLabel = htmlspecialchars($fCol);
						$sortLabel = '<i>[' . rtrim(trim($sortLabel), ':') . ']</i>';
					}

					if ($this->table && is_array($currentIdList)) {
						// If the numeric clipboard pads are selected, show duplicate sorting link:
						if ($this->clipNumPane()) {
							$theData[$fCol] .= '<a class="btn btn-default" href="' . htmlspecialchars($this->listURL('', -1) . '&duplicateField=' . $fCol) . '" title="' . $lang->getLL('clip_duplicates', true) . '">' . IconUtility::getSpriteIcon('actions-document-duplicates-select') . '</a>';
						}
						// If the table can be edited, add link for editing THIS field for all
						// listed records:
						if (!$GLOBALS['TCA'][$table]['ctrl']['readOnly'] && $permsEdit && $GLOBALS['TCA'][$table]['columns'][$fCol]) {
							$editIdList = implode(',', $currentIdList);
							if ($this->clipNumPane()) {
								$editIdList = '\'+editList(\'' . $table . '\',\'' . $editIdList . '\')+\'';
							}
							$params = '&edit[' . $table . '][' . $editIdList . ']=edit&columnsOnly=' . $fCol . '&disHelp=1';
							$iTitle = sprintf($lang->getLL('editThisColumn'), $sortLabel);
							$theData[$fCol] .= '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick($params, $this->backPath, -1)) . '" title="' . htmlspecialchars($iTitle) . '">' . IconUtility::getSpriteIcon('actions-document-open') . '</a>';
						}
						if (strlen($theData[$fCol]) > 0) {
							$theData[$fCol] = '<div class="btn-group" role="group">' . $theData[$fCol] . '</div> ';
						}
					}
					$theData[$fCol] .= $this->addSortLink($sortLabel, $fCol, $table);
			}
		}
		/**
		 * @hook renderListHeader: Allows to change the contents of columns/cells of the Web>List table headers
		 * @usage Above each listed table in Web>List a header row is shown.
		 *        Containing the labels of all shown fields and additional icons to create new records for this
		 *        table or perform special clipboard tasks like mark and copy all listed records to clipboard, etc.
		 */
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'] as $classData) {
				$hookObject = GeneralUtility::getUserObj($classData);
				if (!$hookObject instanceof RecordListHookInterface) {
					throw new \UnexpectedValueException('$hookObject must implement interface ' . RecordListHookInterface::class, 1195567855);
				}
				$theData = $hookObject->renderListHeader($table, $currentIdList, $theData, $this);
			}
		}


		// Create and return header table row:
		return '<thead>' . $this->addelement(1, $icon, $theData, '', '', '', 'th') . '</thead>';
	}

	/**
	 * Creates a page browser for tables with many records
	 * @param string $renderPart Distinguish between 'top' and 'bottom' part of the navigation (above or below the records)
	 * @return string Navigation HTML
	 */
	protected function renderListNavigation($renderPart = 'top') {
		$totalPages = ceil($this->totalItems / $this->iLimit);
		// Show page selector if not all records fit into one page
		if ($totalPages <= 1) {
			return '';
		}
		$content = '';
		$listURL = $this->listURL('', $this->table);
		// 1 = first page
		// 0 = first element
		$currentPage = floor($this->firstElementNumber / $this->iLimit) + 1;
		// Compile first, previous, next, last and refresh buttons
		if ($currentPage > 1) {
			$labelFirst = $this->getLanguageService()->sL('LLL:EXT:lang/locallang_common.xlf:first');
			$labelPrevious = $this->getLanguageService()->sL('LLL:EXT:lang/locallang_common.xlf:previous');
			$first = '<li><a href="' . $listURL . '&pointer=' . $this->getPointerForPage(1) . '">' . IconUtility::getSpriteIcon('actions-view-paging-first', array('title' => $labelFirst)) . '</a></li>';
			$previous = '<li><a href="' . $listURL . '&pointer=' . $this->getPointerForPage($currentPage - 1) . '">' . IconUtility::getSpriteIcon('actions-view-paging-previous', array('title' => $labelPrevious)) . '</a></li>';
		} else {
			$first = '<li class="disabled"><span>' . IconUtility::getSpriteIcon('actions-view-paging-first') . '</span></li>';
			$previous = '<li class="disabled"><span>' . IconUtility::getSpriteIcon('actions-view-paging-previous') . '</span></li>';
		}
		if ($currentPage < $totalPages) {
			$labelNext = $this->getLanguageService()->sL('LLL:EXT:lang/locallang_common.xlf:next');
			$labelLast = $this->getLanguageService()->sL('LLL:EXT:lang/locallang_common.xlf:last');
			$next = '<li><a href="' . $listURL . '&pointer=' . $this->getPointerForPage($currentPage + 1) . '">' . IconUtility::getSpriteIcon('actions-view-paging-next', array('title' => $labelNext)) . '</a></li>';
			$last = '<li><a href="' . $listURL . '&pointer=' . $this->getPointerForPage($totalPages) . '">' . IconUtility::getSpriteIcon('actions-view-paging-last', array('title' => $labelLast)) . '</a></li>';
		} else {
			$next = '<li class="disabled"><span>' . IconUtility::getSpriteIcon('actions-view-paging-next') . '</span></li>';
			$last = '<li class="disabled"><span>' . IconUtility::getSpriteIcon('actions-view-paging-last') . '</span></li>';
		}
		$reload = '<li><a href="#" onclick="document.dblistForm.action=\'' . $listURL . '&pointer=\'+calculatePointer(document.getElementById(\'jumpPage-' . $renderPart . '\').value); document.dblistForm.submit(); return true;" title="' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_common.xlf:reload', true) . '">' . IconUtility::getSpriteIcon('actions-system-refresh') . '</a></li>';
		if ($renderPart === 'top') {
			// Add js to traverse a page select input to a pointer value
			$content = '
<script type="text/javascript">
/*<![CDATA[*/
	function calculatePointer(page) {
		if (page > ' . $totalPages . ') {
			page = ' . $totalPages . ';
		}
		if (page < 1) {
			page = 1;
		}
		return (page - 1) * ' . $this->iLimit . ';
	}
/*]]>*/
</script>
';
		}
		$pageNumberInput = '
			<input type="text" value="' . $currentPage . '" size="3" class="form-control input-sm paginator-input" id="jumpPage-' . $renderPart . '" name="jumpPage-' . $renderPart . '" onkeyup="if (event.keyCode == 13) { document.dblistForm.action=\'' . $listURL . '&pointer=\'+calculatePointer(this.value); document.dblistForm.submit(); } return true;" />
			';
		$pageIndicatorText = sprintf($this->getLanguageService()->sL('LLL:EXT:lang/locallang_mod_web_list.xlf:pageIndicator'), $pageNumberInput, $totalPages);
		$pageIndicator = '<li><span>' . $pageIndicatorText . '</span></li>';
		if ($this->totalItems > $this->firstElementNumber + $this->iLimit) {
			$lastElementNumber = $this->firstElementNumber + $this->iLimit;
		} else {
			$lastElementNumber = $this->totalItems;
		}
		$rangeIndicator = '<li><span>' . sprintf($this->getLanguageService()->sL('LLL:EXT:lang/locallang_mod_web_list.xlf:rangeIndicator'), ($this->firstElementNumber + 1), $lastElementNumber) . '</span></li>';

		$titleColumn = $this->fieldArray[0];
		$data = array($titleColumn => $content . '
				<nav class="pagination-wrap">
					<ul class="pagination pagination-block">
						' . $first . '
						' . $previous . '
						' . $rangeIndicator . '
						' . $pageIndicator . '
						' . $next . '
						' . $last . '
						' . $reload . '
					</ul>
				</nav>
			');

		return $this->addElement(1, '', $data);
	}

	/*********************************
	 * Rendering of various elements
	 *********************************/

	/**
	 * Creates the control panel for a single record in the listing.
	 * @param string $table The table
	 * @param mixed[] $row The record for which to make the control panel.
	 * @param string $level
	 * @throws \UnexpectedValueException
	 * @return string HTML table with the control panel (unless disabled)
	 */
	public function makeControl($table, $row, $level) {
		$module = $this->getModule();
		$rowUid = $row['uid'];
		if (ExtensionManagementUtility::isLoaded('version') && isset($row['_ORIG_uid'])) {
			$rowUid = $row['_ORIG_uid'];
		}
		$cells = array('primary' => array(), 'secondary' => array());
		// If the listed table is 'pages' we have to request the permission settings for each page:
		$localCalcPerms = 0;
		if ($table == 'pages') {
			$localCalcPerms = $this->getBackendUserAuthentication()->calcPerms(BackendUtility::getRecord('pages', $row['uid']));
		}
		// This expresses the edit permissions for this particular element:
		$permsEdit = $table === 'pages' && $localCalcPerms & 2 || $table !== 'pages' && $this->calcPerms & 16;
		// "Show" link (only pages and tt_content elements)
		if ($table == 'pages' || $table == 'tt_content') {
			$viewAction = '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars(BackendUtility::viewOnClick(($table === 'tt_content' ? $this->id : $row['uid']), $this->backPath, '', ($table === 'tt_content' ? '#' . $row['uid'] : ''))) . '" title="' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.showPage', true) . '">' . IconUtility::getSpriteIcon('actions-document-view') . '</a>';
			$this->addActionToCellGroup($cells, $viewAction, 'view');
		}
		// "Edit" link: ( Only if permissions to edit the page-record of the content of the parent page ($this->id)
		if ($permsEdit) {
			$params = '&edit[' . $table . '][' . $row['uid'] . ']=edit';
			$spriteIcon = ($GLOBALS['TCA'][$table]['ctrl']['readOnly'] ? 'actions-document-open-read-only' : 'actions-document-open');
			$editAction = '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick($params, $this->backPath, -1)) . '" title="' . $this->getLanguageService()->getLL('edit', true) . '">' . IconUtility::getSpriteIcon($spriteIcon) . '</a>';
			$this->addActionToCellGroup($cells, $editAction, 'edit');
		}
		// "Info": (All records)
		$onClick = 'top.launchView(\'' . $table . '\', \'' . $row['uid'] . '\'); return false;';
		$viewBigAction = '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars($onClick) . '" title="' . $this->getLanguageService()->getLL('showInfo', true) . '">' . IconUtility::getSpriteIcon('actions-document-info') . '</a>';
		$this->addActionToCellGroup($cells, $viewBigAction, 'viewBig');
		// "Move" wizard link for pages/tt_content elements:
		if (($table == 'tt_content' && $permsEdit || $table == 'pages') && $level == 0) {
			$onClick = 'return jumpExt(\'' . $this->backPath . BackendUtility::getModuleUrl('move_element') . '&table=' . $table . '&uid=' . $row['uid'] . '\');';
			$linkTitleLL = $this->getLanguageService()->getLL('move_' . ($table === 'tt_content' ? 'record' : 'page'), true);
			$spriteIcon = $table === 'tt_content' ? 'actions-document-move' : 'actions-page-move';
			$moveAction = '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars($onClick) . '" title="' . $linkTitleLL . '">' . IconUtility::getSpriteIcon($spriteIcon) . '</a>';
			$this->addActionToCellGroup($cells, $moveAction, 'move');
		}
		// If the table is NOT a read-only table, then show these links:
		if (!$GLOBALS['TCA'][$table]['ctrl']['readOnly']) {
			// "Revert" link (history/undo)
			$moduleUrl = BackendUtility::getModuleUrl('record_history', array('element' => $table . ':' . $row['uid']));
			$onClick = 'return jumpExt(' . GeneralUtility::quoteJSvalue($this->backPath . $moduleUrl) . ',\'#latest\');';
			$historyAction = '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars($onClick) . '" title="' . $this->getLanguageService()->getLL('history', true) . '">' . IconUtility::getSpriteIcon('actions-document-history-open') . '</a>';
			$this->addActionToCellGroup($cells, $historyAction, 'history');
			// Versioning:
			if (ExtensionManagementUtility::isLoaded('version') && !ExtensionManagementUtility::isLoaded('workspaces')) {
				$vers = BackendUtility::selectVersionsOfRecord($table, $row['uid'], 'uid', $this->getBackendUserAuthentication()->workspace, false, $row);
				// If table can be versionized.
				if (is_array($vers)) {
					$versionIcon = 'no-version';
					if (count($vers) > 1) {
						$versionIcon = count($vers) - 1;
					}
					$href = $this->backPath . BackendUtility::getModuleUrl('web_txversionM1', array('table' => $table, 'uid' => $row['uid']));
					$versionAction = '<a class="btn btn-default" href="' . htmlspecialchars($href) . '" title="' . $this->getLanguageService()->getLL('displayVersions', true) . '">' . IconUtility::getSpriteIcon(('status-version-' . $versionIcon)) . '</a>';
					$this->addActionToCellGroup($cells, $versionAction, 'version');
				}
			}
			// "Edit Perms" link:
			if ($table === 'pages' && $this->getBackendUserAuthentication()->check('modules', 'system_BeuserTxPermission') && ExtensionManagementUtility::isLoaded('beuser')) {
				$href = BackendUtility::getModuleUrl('system_BeuserTxPermission') . '&id=' . $row['uid'] . '&return_id=' . $row['uid'] . '&edit=1';
				$permsAction = '<a class="btn btn-default" href="' . htmlspecialchars($href) . '" title="' . $this->getLanguageService()->getLL('permissions', true) . '">' . IconUtility::getSpriteIcon('status-status-locked') . '</a>';
				$this->addActionToCellGroup($cells, $permsAction, 'perms');
			}
			// "New record after" link (ONLY if the records in the table are sorted by a "sortby"-row
			// or if default values can depend on previous record):
			if ($GLOBALS['TCA'][$table]['ctrl']['sortby'] || $GLOBALS['TCA'][$table]['ctrl']['useColumnsForDefaultValues']) {
				if ($table !== 'pages' && $this->calcPerms & 16 || $table === 'pages' && $this->calcPerms & 8) {
					if ($this->showNewRecLink($table)) {
						$params = '&edit[' . $table . '][' . -($row['_MOVE_PLH'] ? $row['_MOVE_PLH_uid'] : $row['uid']) . ']=new';
						$newAction = '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick($params, $this->backPath, -1)) . '" title="' . $this->getLanguageService()->getLL('new' . ($table == 'pages ' ? 'Page' : 'Record'), true) . '">' . ($table == 'pages' ? IconUtility::getSpriteIcon('actions-page-new') : IconUtility::getSpriteIcon('actions-document-new')) . '</a>';
						$this->addActionToCellGroup($cells, $newAction, 'new');
					}
				}
			}
			// "Up/Down" links
			if ($permsEdit && $GLOBALS['TCA'][$table]['ctrl']['sortby'] && !$this->sortField && !$this->searchLevels) {
				if (isset($this->currentTable['prev'][$row['uid']]) && $this->showMoveUp === true) {
					// Up
					if ($this->lastMoveDownParams) {
						$params = $this->lastMoveDownParams;
					} else {
						$params = '&cmd[' . $table . '][' . $row['uid'] . '][move]=' . $this->currentTable['prev'][$row['uid']];
					}
					$moveUpAction = '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars('return jumpToUrl(\'' . $module->doc->issueCommand($params, -1) . '\');') . '" title="' . $this->getLanguageService()->getLL('moveUp', true) . '">' . IconUtility::getSpriteIcon('actions-move-up') . '</a>';
				} else {
					$moveUpAction = $this->spaceIcon;
				}
				$this->addActionToCellGroup($cells, $moveUpAction, 'moveUp');

				if ($this->currentTable['next'][$row['uid']] && $this->showMoveDown === true) {
					// Down
					$params = '&cmd[' . $table . '][' . $row['uid'] . '][move]=' . $this->currentTable['next'][$row['uid']];
					$this->lastMoveDownParams = $params;
					$moveDownAction = '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars('return jumpToUrl(\'' . $module->doc->issueCommand($params, -1) . '\');') . '" title="' . $this->getLanguageService()->getLL('moveDown', true) . '">' . IconUtility::getSpriteIcon('actions-move-down') . '</a>';
				} else {
					$moveDownAction = $this->spaceIcon;
				}
				$this->addActionToCellGroup($cells, $moveDownAction, 'moveDown');
			}
			// "Hide/Unhide" links:
			$hiddenField = $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled'];
			if ($permsEdit && $hiddenField && $GLOBALS['TCA'][$table]['columns'][$hiddenField] && (!$GLOBALS['TCA'][$table]['columns'][$hiddenField]['exclude'] || $this->getBackendUserAuthentication()->check('non_exclude_fields', $table . ':' . $hiddenField))
			) {
				if ($row[$hiddenField]) {
					$params = 'data[' . $table . '][' . $rowUid . '][' . $hiddenField . ']=0';
					$hideAction = '<a class="btn btn-default t3js-record-hide" data-state="hidden" href="#"' . ' data-params="' . htmlspecialchars($params) . '"' . ' title="' . $this->getLanguageService()->getLL(('unHide' . ($table == 'pages' ? 'Page' : '')), true) . '">' . IconUtility::getSpriteIcon('actions-edit-unhide') . '</a>';
				} else {
					$params = 'data[' . $table . '][' . $rowUid . '][' . $hiddenField . ']=1';
					$hideAction = '<a class="btn btn-default t3js-record-hide" data-state="visible" href="#"' . ' data-params="' . htmlspecialchars($params) . '"' . ' title="' . $this->getLanguageService()->getLL(('hide' . ($table == 'pages' ? 'Page' : '')), true) . '">' . IconUtility::getSpriteIcon('actions-edit-hide') . '</a>';
				}
				$this->addActionToCellGroup($cells, $hideAction, 'hide');
			}
			// "Delete" link:
			if ($table === 'pages' && $localCalcPerms & 4 || $table !== 'pages' && $this->calcPerms & 16) {
				// Check if the record version is in "deleted" state, because that will switch the action to "restore"
				if ($this->getBackendUserAuthentication()->workspace > 0 && isset($row['t3ver_state']) && (int)$row['t3ver_state'] === 2) {
					$actionName = 'restore';
					$refCountMsg = '';
				} else {
					$actionName = 'delete';
					$refCountMsg = BackendUtility::referenceCount($table, $row['uid'], ' ' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.referencesToRecord'), $this->getReferenceCount($table, $row['uid'])) . BackendUtility::translationCount($table, $row['uid'], ' ' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.translationsOfRecord'));
				}

				$titleOrig = BackendUtility::getRecordTitle($table, $row, false, true);
				$title = GeneralUtility::slashJS(GeneralUtility::fixed_lgd_cs($titleOrig, $this->fixedL), true);
				$warningText = $this->getLanguageService()->getLL($actionName . 'Warning') . ' "' . $title . '" ' . '[' . $table . ':' . $row['uid'] . ']' . $refCountMsg;

				$params = 'cmd[' . $table . '][' . $row['uid'] . '][delete]=1';
				$icon = IconUtility::getSpriteIcon('actions-edit-' . $actionName);
				$linkTitle = $this->getLanguageService()->getLL($actionName, true);
				$deleteAction = '<a class="btn btn-default t3js-record-delete" href="#" ' . ' data-l10parent="' . htmlspecialchars($row['l10n_parent']) . '"' . ' data-params="' . htmlspecialchars($params) . '" data-title="' . htmlspecialchars($titleOrig) . '"' . ' data-message="' . htmlspecialchars($warningText) . '" title="' . $linkTitle . '"' . '>' . $icon . '</a>';
				$this->addActionToCellGroup($cells, $deleteAction, 'delete');
			}
			// "Levels" links: Moving pages into new levels...
			if ($permsEdit && $table == 'pages' && !$this->searchLevels) {
				// Up (Paste as the page right after the current parent page)
				if ($this->calcPerms & 8) {
					$params = '&cmd[' . $table . '][' . $row['uid'] . '][move]=' . -$this->id;
					$moveLeftAction = '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars('return jumpToUrl(\'' . $module->doc->issueCommand($params, -1) . '\');') . '" title="' . $this->getLanguageService()->getLL('prevLevel', true) . '">' . IconUtility::getSpriteIcon('actions-move-left') . '</a>';
					$this->addActionToCellGroup($cells, $moveLeftAction, 'moveLeft');
				}
				// Down (Paste as subpage to the page right above)
				if ($this->currentTable['prevUid'][$row['uid']]) {
					$localCalcPerms = $this->getBackendUserAuthentication()->calcPerms(BackendUtility::getRecord('pages', $this->currentTable['prevUid'][$row['uid']]));
					if ($localCalcPerms & 8) {
						$params = '&cmd[' . $table . '][' . $row['uid'] . '][move]=' . $this->currentTable['prevUid'][$row['uid']];
						$moveRightAction = '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars('return jumpToUrl(\'' . $module->doc->issueCommand($params, -1) . '\');') . '" title="' . $this->getLanguageService()->getLL('nextLevel', true) . '">' . IconUtility::getSpriteIcon('actions-move-right') . '</a>';
					} else {
						$moveRightAction = $this->spaceIcon;
					}
				} else {
					$moveRightAction = $this->spaceIcon;
				}
				$this->addActionToCellGroup($cells, $moveRightAction, 'moveRight');
			}
		}
		/**
		 * @hook recStatInfoHooks: Allows to insert HTML before record icons on various places
		 */
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['recStatInfoHooks'])) {
			$stat = '';
			$_params = array($table, $row['uid']);
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['recStatInfoHooks'] as $_funcRef) {
				$stat .= GeneralUtility::callUserFunction($_funcRef, $_params, $this);
			}
			$this->addActionToCellGroup($cells, $stat, 'stat');
		}
		/**
		 * @hook makeControl: Allows to change control icons of records in list-module
		 * @usage This hook method gets passed the current $cells array as third parameter.
		 *        This array contains values for the icons/actions generated for each record in Web>List.
		 *        Each array entry is accessible by an index-key.
		 *        The order of the icons is depending on the order of those array entries.
		 */
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'] as $classData) {
				$hookObject = GeneralUtility::getUserObj($classData);
				if (!$hookObject instanceof RecordListHookInterface) {
					throw new \UnexpectedValueException('$hookObject must implement interface ' . RecordListHookInterface::class, 1195567840);
				}
				// for compatibility reason, we move all icons to the rootlevel
				// before calling the hook
				foreach ($cells as $section => $actions) {
					foreach ($actions as $actionKey => $action) {
						$cells[$actionKey] = $action;
					}
				}
				$cells = $hookObject->makeControl($table, $row, $cells, $this);
			}
			// now sort icons again into primary and secondary sections
			// after all hooks are processed
			$hookCells = $cells;
			foreach ($hookCells as $key => $value) {
				if ($key === 'primary' || $key === 'secondary') {
					continue;
				}
				$this->addActionToCellGroup($cells, $value, $key);
			}
		}
		$output = '<!-- CONTROL PANEL: ' . $table . ':' . $row['uid'] . ' -->';
		foreach ($cells as $classification => $actions) {
			$visibilityClass = ($classification !== 'primary' && !$module->MOD_SETTINGS['bigControlPanel'] ? 'collapsed' : 'expanded');
			if ($visibilityClass === 'collapsed') {
				$cellOutput = '';
				foreach ($actions as $action) {
					$cellOutput .= $action;
				}
				$output .= ' <div class="btn-group">' . '<span id="actions_' . $table . '_' . $row['uid'] . '" class="btn-group collapse collapse-horizontal width">' . $cellOutput . '</span>' . '<a href="#actions_' . $table . '_' . $row['uid'] . '" class="btn btn-default collapsed" data-toggle="collapse" aria-expanded="false"><span class="t3-icon fa fa-cog"></span></a>' . '</div>';
			} else {
				$output .= ' <div class="btn-group" role="group">' . implode('', $actions) . '</div>';
			}
		}

		return $output;
	}

	/**
	 * Creates the clipboard panel for a single record in the listing.
	 * @param string $table The table
	 * @param mixed[] $row The record for which to make the clipboard panel.
	 * @throws \UnexpectedValueException
	 * @return string HTML table with the clipboard panel (unless disabled)
	 */
	public function makeClip($table, $row) {
		// Return blank, if disabled:
		if (!$this->getModule()->MOD_SETTINGS['clipBoard']) {
			return '';
		}
		$cells = array();
		$cells['pasteAfter'] = ($cells['pasteInto'] = $this->spaceIcon);
		//enables to hide the copy, cut and paste icons for localized records - doesn't make much sense to perform these options for them
		$isL10nOverlay = $this->localizationView && $table != 'pages_language_overlay' && $row[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']] != 0;
		// Return blank, if disabled:
		// Whether a numeric clipboard pad is active or the normal pad we will see different content of the panel:
		// For the "Normal" pad:
		if ($this->clipObj->current == 'normal') {
			// Show copy/cut icons:
			$isSel = (string)$this->clipObj->isSelected($table, $row['uid']);
			if (stripos(GeneralUtility::getIndpEnv('SCRIPT_NAME'), 'ajax') === false) {
				$copyUrl = $this->clipObj->selUrlDB($table, $row['uid'], 1, ($isSel == 'copy'), array('returnUrl' => ''));
				$cutUrl = $this->clipObj->selUrlDB($table, $row['uid'], 0, ($isSel == 'cut'), array('returnUrl' => ''));
			} else {
				$copyUrl = $this->selUrlDB($table, $row['uid'], $row['pid'], 1, ($isSel == 'copy'));
				$cutUrl = $this->selUrlDB($table, $row['uid'], $row['pid'], 0, ($isSel == 'cut'));
			}
			if ($isL10nOverlay) {
				$cells['copy'] = $this->spaceIcon;
				$cells['cut'] = $this->spaceIcon;
			} else {
				$cells['copy'] = '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars('return jumpSelf(\'' . $copyUrl . '\');') . '" title="' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:cm.copy', true) . '">' . (!$isSel == 'copy' ? IconUtility::getSpriteIcon('actions-edit-copy') : IconUtility::getSpriteIcon('actions-edit-copy-release')) . '</a>';
				$cells['cut'] = '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars('return jumpSelf(\'' . $cutUrl . '\');') . '" title="' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:cm.cut', true) . '">' . (!$isSel == 'cut' ? IconUtility::getSpriteIcon('actions-edit-cut') : IconUtility::getSpriteIcon('actions-edit-cut-release')) . '</a>';
			}
		} else {
			$buttons['csh'] = BackendUtility::cshItem('xMOD_csh_corebe', 'list_module', $GLOBALS['BACK_PATH'], '', true);
			// For the numeric clipboard pads (showing checkboxes where one can select elements on/off)
			// Setting name of the element in ->CBnames array:
			$n = $table . '|' . $row['uid'];
			$this->CBnames[] = $n;
			// Check if the current element is selected and if so, prepare to set the checkbox as selected:
			$checked = $this->clipObj->isSelected($table, $row['uid']) ? 'checked="checked" ' : '';
			// If the "duplicateField" value is set then select all elements which are duplicates...
			if ($this->duplicateField && isset($row[$this->duplicateField])) {
				$checked = '';
				if (in_array($row[$this->duplicateField], $this->duplicateStack)) {
					$checked = 'checked="checked" ';
				}
				$this->duplicateStack[] = $row[$this->duplicateField];
			}
			// Adding the checkbox to the panel:
			$cells['select'] = $isL10nOverlay ? $this->spaceIcon : '<label class="btn btn-default btn-checkbox"><input type="hidden" name="CBH[' . $n . ']" value="0" /><input type="checkbox"' . ' name="CBC[' . $n . ']" value="1" ' . $checked . '/><span class="t3-icon fa"></span></label>';
		}
		// Now, looking for selected elements from the current table:
		$elFromTable = $this->clipObj->elFromTable($table);
		if (count($elFromTable) && $GLOBALS['TCA'][$table]['ctrl']['sortby']) {
			if (stripos(GeneralUtility::getIndpEnv('SCRIPT_NAME'), 'ajax') === false) {
				$pasteUrl = $this->clipObj->pasteUrl($table, -$row['uid']);
			} else {
				$pasteUrl = $this->pasteUrl($table, -$row['uid'], $row['pid']);
			}
			// IF elements are found and they can be individually ordered, then add a "paste after" icon:
			$cells['pasteAfter'] = $isL10nOverlay ? $this->spaceIcon : '<a class="btn btn-default" href="' . htmlspecialchars($this->clipObj->pasteUrl($table, -$row['uid'])) . '" onclick="' . htmlspecialchars(('return ' . $this->clipObj->confirmMsg($table, $row, 'after', $elFromTable))) . '" title="' . $this->getLanguageService()->getLL('clip_pasteAfter', true) . '">' . IconUtility::getSpriteIcon('actions-document-paste-after') . '</a>';
		}
		// Now, looking for elements in general:
		$elFromTable = $this->clipObj->elFromTable('');
		if ($table == 'pages' && count($elFromTable)) {
			$cells['pasteInto'] = '<a class="btn btn-default" href="' . htmlspecialchars($this->clipObj->pasteUrl('', $row['uid'])) . '" onclick="' . htmlspecialchars('return ' . $this->clipObj->confirmMsg($table, $row, 'into', $elFromTable)) . '" title="' . $this->getLanguageService()->getLL('clip_pasteInto', true) . '">' . IconUtility::getSpriteIcon('actions-document-paste-into') . '</a>';
		}
		/**
		 * @hook makeClip: Allows to change clip-icons of records in list-module
		 * @usage This hook method gets passed the current $cells array as third parameter.
		 *        This array contains values for the clipboard icons generated for each record in Web>List.
		 *        Each array entry is accessible by an index-key.
		 *        The order of the icons is depending on the order of those array entries.
		 */
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'] as $classData) {
				$hookObject = GeneralUtility::getUserObj($classData);
				if (!$hookObject instanceof RecordListHookInterface) {
					throw new \UnexpectedValueException('$hookObject must implement interface ' . RecordListHookInterface::class, 1195567845);
				}
				$cells = $hookObject->makeClip($table, $row, $cells, $this);
			}
		}

		// Compile items into a DIV-element:
		return '<!-- CLIPBOARD PANEL: ' . $table . ':' . $row['uid'] . ' -->
			<div class="btn-group" role="group">' . implode('', $cells) . '</div>';
	}

	/**
	 * Rendering a single row for the list
	 * @param string $table Table name
	 * @param mixed[] $row Current record
	 * @param int $cc Counter, counting for each time an element is rendered (used for alternating colors)
	 * @param string $titleCol Table field (column) where header value is found
	 * @param string $thumbsCol Table field (column) where (possible) thumbnails can be found
	 * @param int $indent Indent from left.
	 * @param integer $level
	 * @return string Table row for the element
	 * @access private
	 * @see getTable()
	 */
	public function renderListRow($table, $row, $cc, $titleCol, $thumbsCol, $indent = 0, $level = 0) {
		if (!is_array($row)) {
			return '';
		}
		$rowOutput = '';
		$id_orig = null;
		// If in search mode, make sure the preview will show the correct page
		if ((string)$this->searchString !== '') {
			$id_orig = $this->id;
			$this->id = $row['pid'];
		}
		// Add special classes for first and last row
		$rowSpecial = '';
		if ($cc == 1 && $indent == 0) {
			$rowSpecial .= ' firstcol';
		}
		if ($cc == $this->totalRowCount || $cc == $this->iLimit) {
			$rowSpecial .= ' lastcol';
		}

		$row_bgColor = ' class="' . $rowSpecial . '"';

		// Overriding with versions background color if any:
		$row_bgColor = $row['_CSSCLASS'] ? ' class="' . $row['_CSSCLASS'] . '"' : $row_bgColor;
		// Incr. counter.
		$this->counter++;
		// The icon with link
		$altText = htmlspecialchars(BackendUtility::getRecordIconAltText($row, $table));
		$iconImg = IconUtility::getSpriteIconForRecord($table, $row, array('title' => $altText, 'style' => $indent ? ' margin-left: ' . $indent . 'px;' : ''));
		$theIcon = $this->clickMenuEnabled ? $this->getModule()->doc->wrapClickMenuOnIcon($iconImg, $table, $row['uid']) : $iconImg;
		// Preparing and getting the data-array
		$theData = array();
		$localizationMarkerClass = '';
		foreach ($this->fieldArray as $fCol) {
			if ($fCol == $titleCol) {
				$recTitle = BackendUtility::getRecordTitle($table, $row, false, true);
				$warning = '';
				// If the record is edit-locked	by another user, we will show a little warning sign:
				$lockInfo = BackendUtility::isRecordLocked($table, $row['uid']);
				if ($lockInfo) {
					$warning = '<a href="#" onclick="alert(' . GeneralUtility::quoteJSvalue($lockInfo['msg']) . '); return false;" title="' . htmlspecialchars($lockInfo['msg']) . '">' . IconUtility::getSpriteIcon('status-warning-in-use') . '</a>';
				}
				$theData[$fCol] = $warning . $this->linkWrapItems($table, $row['uid'], $recTitle, $row);
				// Render thumbnails, if:
				// - a thumbnail column exists
				// - there is content in it
				// - the thumbnail column is visible for the current type
				$type = 0;
				if (isset($GLOBALS['TCA'][$table]['ctrl']['type'])) {
					$typeColumn = $GLOBALS['TCA'][$table]['ctrl']['type'];
					$type = $row[$typeColumn];
				}
				// If current type doesn't exist, set it to 0 (or to 1 for historical reasons,
				// if 0 doesn't exist)
				if (!isset($GLOBALS['TCA'][$table]['types'][$type])) {
					$type = isset($GLOBALS['TCA'][$table]['types'][0]) ? 0 : 1;
				}
				$visibleColumns = $GLOBALS['TCA'][$table]['types'][$type]['showitem'];

				if ($this->thumbs && trim($row[$thumbsCol]) && preg_match('/(^|(.*(;|,)?))' . $thumbsCol . '(((;|,).*)|$)/', $visibleColumns) === 1
				) {
					$theData[$fCol] .= '<br />' . $this->thumbCode($row, $table, $thumbsCol);
				}
				if (isset($GLOBALS['TCA'][$table]['ctrl']['languageField']) && $row[$GLOBALS['TCA'][$table]['ctrl']['languageField']] != 0 && $row[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']] != 0
				) {
					// It's a translated record with a language parent
					$localizationMarkerClass = ' localization';
				}
			} elseif ($fCol == 'pid') {
				$theData[$fCol] = $row[$fCol];
			} elseif ($fCol == '_PATH_') {
				$theData[$fCol] = $this->recPath($row['pid']);
			} elseif ($fCol == '_REF_') {
				$theData[$fCol] = $this->createReferenceHtml($table, $row['uid']);
			} elseif ($fCol == '_CONTROL_') {
				$theData[$fCol] = $this->makeControl($table, $row, $level);
			} elseif ($fCol == '_CLIPBOARD_') {
				$theData[$fCol] = $this->makeClip($table, $row);
			} elseif ($fCol == '_LOCALIZATION_') {
				list($lC1, $lC2) = $this->makeLocalizationPanel($table, $row);
				$theData[$fCol] = $lC1;
			} elseif (!$fCol == '_LOCALIZATION_b') {
				$tmpProc = BackendUtility::getProcessedValueExtra($table, $fCol, $row[$fCol], 100, $row['uid']);
				$theData[$fCol] = $this->linkUrlMail(htmlspecialchars($tmpProc), $row[$fCol]);
				if ($this->csvOutput) {
					$row[$fCol] = BackendUtility::getProcessedValueExtra($table, $fCol, $row[$fCol], 0, $row['uid']);
				}
			} elseif ($fCol == '_LOCALIZATION_b') {
				$theData[$fCol] = $lC2;
			} else {
				$theData[$fCol] = htmlspecialchars(BackendUtility::getProcessedValueExtra($table, $fCol, $row[$fCol], 0, $row['uid']));
			}
		}
		// Reset the ID if it was overwritten
		if ((string)$this->searchString !== '') {
			$this->id = $id_orig;
		}
		// Add row to CSV list:
		if ($this->csvOutput) {
			$this->addToCSV($row, $table);
		}
		// Add classes to table cells
		$this->addElement_tdCssClass[$titleCol] = 'col-title' . $localizationMarkerClass;
		$this->addElement_tdCssClass['_CONTROL_'] = 'col-control';
		if ($this->getModule()->MOD_SETTINGS['clipBoard']) {
			$this->addElement_tdCssClass['_CLIPBOARD_'] = 'col-clipboard';
		}
		$this->addElement_tdCssClass['_PATH_'] = 'col-path';
		$this->addElement_tdCssClass['_LOCALIZATION_'] = 'col-localizationa';
		$this->addElement_tdCssClass['_LOCALIZATION_b'] = 'col-localizationb';
		/**
		 * @hook checkChildren
		 * @date 2014-02-11
		 * @request Alexander Grein <alexander.grein@in2code.de>
		 */
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'] as $classData) {
				$hookObject = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classData);
				if (is_object($hookObject) && method_exists($hookObject, 'checkChildren')) {
					$hookObject->checkChildren($table, $row, $level, $theData, $this);
				}
			}
		}
		// Create element in table cells:
		$theData['uid'] = $row['uid'];
		$rowOutput .= $this->addelement(1, $theIcon, $theData, $row_bgColor, '', '', '', $level);

		// Finally, return table row element:
		return $rowOutput;
	}

	/**
	 * Returns a table-row with the content from the fields in the input data array.
	 * OBS: $this->fieldArray MUST be set! (represents the list of fields to display)
	 * @param int $h Is an integer >=0 and denotes how tall a element is. Set to '0' makes a halv line, -1 = full line, set to 1 makes a 'join' and above makes 'line'
	 * @param string $icon Is the <img>+<a> of the record. If not supplied the first 'join'-icon will be a 'line' instead
	 * @param array $data Is the dataarray, record with the fields. Notice: These fields are (currently) NOT htmlspecialchar'ed before being wrapped in <td>-tags
	 * @param string $rowParams Is insert in the <tr>-tags. Must carry a ' ' as first character
	 * @param int OBSOLETE - NOT USED ANYMORE. $lMargin is the leftMargin (int)
	 * @param string $altLine Is the HTML <img>-tag for an alternative 'gfx/ol/line.gif'-icon (used in the top)
	 * @param string $colType Defines the tag being used for the columns. Default is td.
	 * @param int $level
	 * @return string HTML content for the table row
	 */
	public function addElement($h, $icon, $data, $rowParams = '', $lMargin = '', $altLine = '', $colType = 'td', $level = 0) {
		$colType = ($colType === 'th') ? 'th' : 'td';
		$noWrap = $this->no_noWrap ? '' : ' nowrap="nowrap"';
		// Start up:
		$out = '
		<!-- Element, begin: -->
		<tr ' . $rowParams . ' data-uid="' . (int)$data['uid'] . '">';
		if (count($data) > 1) {
			for ($i = 0; $i < $level; $i++) {
				$out .= '<td></td>';
			}

			if ($data['_EXPANDABLE_']) {
				$sortField = GeneralUtility::_GP('sortField') ? GeneralUtility::_GP('sortField') . ':' . (int)GeneralUtility::_GP('sortRev') : '';
				$contentCollapseIcon = '';
				/**
				 * @hook contentCollapseIcon
				 * @date 2014-02-11
				 * @request Alexander Grein <alexander.grein@in2code.de>
				 */
				if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'])) {
					foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'] as $classData) {
						$hookObject = GeneralUtility::getUserObj($classData);
						if (is_object($hookObject) && method_exists($hookObject, 'contentCollapseIcon')) {
							$hookObject->contentCollapseIcon($data, $sortField, $level, $contentCollapseIcon, $this);
						}
					}
				}
				$out .= '<td nowrap="nowrap" class="col-icon">' . $contentCollapseIcon . '</td>';
			} else {
				$last = IconUtility::getSpriteIcon('actions-view-paging-last-disabled');
				$out .= '<td></td>';
			}

		}
		// Show icon and lines
		if ($this->showIcon) {
			$out .= '
			<' . $colType . ' nowrap="nowrap" class="col-icon">';
			if (!$h) {
				$out .= '&nbsp;';
			} else {
				$next = IconUtility::getSpriteIcon('actions-view-paging-next-disabled');
				for ($a = 0; $a < $h; $a++) {
					if (!$a) {
						if ($icon) {
							$out .= $icon;
						}
						if ($this->table && is_array($currentIdList)) {
							// If the numeric clipboard pads are selected, show duplicate sorting link:
							if ($this->clipNumPane()) {
								$theData[$fCol] .= '<a href="' . htmlspecialchars(($this->listURL('', -1) . '&duplicateField=' . $fCol)) . '" title="' . $GLOBALS['LANG']->getLL('clip_duplicates', true) . '">' . IconUtility::getSpriteIcon('actions-document-duplicates-select') . '</a>';
							}
						}
						// If the table can be edited, add link for editing THIS field for all listed records:
						if (!$GLOBALS['TCA'][$table]['ctrl']['readOnly'] && $permsEdit && $GLOBALS['TCA'][$table]['columns'][$fCol]) {
							$editIdList = implode(',', $currentIdList);
							if ($this->clipNumPane()) {
								$editIdList = '\'+editList(\'' . $table . '\',\'' . $editIdList . '\')+\'';
							}
							$params = '&edit[' . $table . '][' . $editIdList . ']=edit&columnsOnly=' . $fCol . '&disHelp=1';
							$iTitle = sprintf($GLOBALS['LANG']->getLL('editThisColumn'), $sortLabel);
							$theData[$fCol] .= '<a href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick($params, $this->backPath, -1)) . '" title="' . htmlspecialchars($iTitle) . '">' . IconUtility::getSpriteIcon('actions-document-open') . '</a>';
						}
					}
					$theData[$fCol] .= $this->addSortLink($sortLabel, $fCol, $table);
				}
			}
			$out .= '</' . $colType . '>
			';
		}
		// Init rendering.
		$colsp = '';
		$lastKey = '';
		$c = 0;
		$ccount = 0;
		// Traverse field array which contains the data to present:
		foreach ($this->fieldArray as $vKey) {
			if (isset($data[$vKey])) {
				if ($ccount == 1) {
					$colsp = ' colspan="' . ($this->maxDepth - $level) . '"';
				}
				if ($lastKey) {
					$cssClass = $this->addElement_tdCssClass[$lastKey];
					if ($this->oddColumnsCssClass && $ccount % 2 == 0) {
						$cssClass = implode(' ', array($this->addElement_tdCssClass[$lastKey], $this->oddColumnsCssClass));
					}
					$out .= '
						<' . $colType . $noWrap . ' class="' . $cssClass . '"' . $colsp . $this->addElement_tdParams[$lastKey] . '>' . $data[$lastKey] . '</' . $colType . '>';
				}
				$lastKey = $vKey;
				$c = 1;
				$ccount++;
			} else {
				$first = IconUtility::getSpriteIcon('actions-view-paging-first-disabled');
				if (!$lastKey) {
					$lastKey = $vKey;
				}
				$c++;
			}
			$reload = '<a href="#" onclick="document.dblistForm.action=\'' . $listURL . '&pointer=\'+calculatePointer(document.getElementById(\'jumpPage-' . $renderPart . '\').value); document.dblistForm.submit(); return true;" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xlf:reload', true) . '">' . IconUtility::getSpriteIcon('actions-system-refresh') . '</a>';

			if (count($data) == 1) {
				$c++;
			}
			if ($c > 1) {
				$colsp = ' colspan="' . ($c + $this->maxDepth) . '"';
			} else {
				$previous = IconUtility::getSpriteIcon('actions-view-paging-previous-disabled');
				$colsp = '';
			}
		}
		if ($lastKey) {
			$cssClass = $this->addElement_tdCssClass[$lastKey];
			if ($this->oddColumnsCssClass) {
				$cssClass = implode(' ', array($this->addElement_tdCssClass[$lastKey], $this->oddColumnsCssClass));
			}
			$rangeIndicator = '<span class="pageIndicator">' . sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_web_list.xlf:rangeIndicator'), ($this->firstElementNumber + 1), $lastElementNumber) . '</span>';
			$out .= '
				<' . $colType . $noWrap . ' class="' . $cssClass . '"' . $colsp . $this->addElement_tdParams[$lastKey] . '>' . $data[$lastKey] . '</' . $colType . '>';
		}
		// End row
		$out .= '
		</tr>';

		// Return row.
		return $out;
	}

	/**
	 * pasteUrl of the element (database and file)
	 * For the meaning of $table and $uid, please read from ->makePasteCmdArray!!!
	 * The URL will point to tce_file or tce_db depending in $table
	 * @param string $table Tablename (_FILE for files)
	 * @param mixed $uid "destination": can be positive or negative indicating how the paste is done (paste into / paste after)
	 * @param bool $setRedirect If set, then the redirect URL will point back to the current script, but with CB reset.
	 * @param array|NULL $update Additional key/value pairs which should get set in the moved/copied record (via DataHandler)
	 * @return string
	 */
	public function pasteUrl($table, $uid, $setRedirect = true, array $update = null) {
		return ($table == '_FILE' ? BackendUtility::getModuleUrl('tce_file', array(), $this->backPath) : BackendUtility::getModuleUrl('tce_db', array(), $this->backPath)) . ($setRedirect ? '&redirect=' . rawurlencode(GeneralUtility::linkThisScript(array('CB' => ''))) : '') . '&vC=' . $GLOBALS['BE_USER']->veriCode() . '&prErr=1&uPT=1' . '&CB[paste]=' . rawurlencode($table . '|' . $uid) . '&CB[pad]=' . $this->current . (is_array($update) ? GeneralUtility::implodeArrayForUrl('CB[update]', $update) : '') . BackendUtility::getUrlToken('tceAction');
	}

	/**
	 * Returns the select-url for database elements
	 * @param string $table Table name
	 * @param int $uid Uid of record
	 * @param bool $copy If set, copymode will be enabled
	 * @param bool $deselect If set, the link will deselect, otherwise select.
	 * @param array $baseArray The base array of GET vars to be sent in addition. Notice that current GET vars WILL automatically be included.
	 * @return string URL linking to the current script but with the CB array set to select the element with table/uid
	 */
	public function selUrlDB($table, $uid, $copy = 0, $deselect = 0, $baseArray = array()) {
		$CB = array('el' => array(rawurlencode($table . '|' . $uid) => $deselect ? 0 : 1));
		if ($copy) {
			$CB['setCopyMode'] = 1;
		}
		$baseArray['CB'] = $CB;

		return GeneralUtility::linkThisScript($baseArray);
	}

	/**
	 * Creates the HTML for a reference count for the record with the UID $uid
	 * in the table $tableName.
	 * @param string $tableName
	 * @param int $uid
	 * @return string HTML of reference a link, will be empty if there are no
	 */
	protected function createReferenceHtml($tableName, $uid) {
		$db = $this->getDatabaseConnection();
		$referenceCount = $db->exec_SELECTcountRows('*', 'sys_refindex', 'ref_table = ' . $db->fullQuoteStr($tableName, 'sys_refindex') . ' AND ref_uid = ' . $uid . ' AND deleted = 0');

		return $this->generateReferenceToolTip($referenceCount, '\'' . $tableName . '\', \'' . $uid . '\'');
	}

	/**
	 * Creates the localization panel
	 * @param string $table The table
	 * @param mixed[] $row The record for which to make the localization panel.
	 * @return string[] Array with key 0/1 with content for column 1 and 2
	 */
	public function makeLocalizationPanel($table, $row) {
		$out = array(0 => '', 1 => '');
		// Reset translations
		$this->translations = array();
		$translations = $this->translateTools->translationInfo($table, $row['uid'], 0, $row, $this->selFieldList);
		// Language title and icon:
		$out[0] = $this->languageFlag($row[$GLOBALS['TCA'][$table]['ctrl']['languageField']]);
		if (is_array($translations)) {
			$this->translations = $translations['translations'];
			// Traverse page translations and add icon for each language that does NOT yet exist:
			$lNew = '';
			foreach ($this->pageOverlays as $lUid_OnPage => $lsysRec) {
				if (!isset($translations['translations'][$lUid_OnPage]) && $this->getBackendUserAuthentication()->checkLanguageAccess($lUid_OnPage)) {
					$url = substr($this->listURL(), strlen($this->backPath));
					$href = $this->getModule()->doc->issueCommand('&cmd[' . $table . '][' . $row['uid'] . '][localize]=' . $lUid_OnPage, $url . '&justLocalized=' . rawurlencode($table . ':' . $row['uid'] . ':' . $lUid_OnPage));
					$language = BackendUtility::getRecord('sys_language', $lUid_OnPage, 'title');
					if ($this->languageIconTitles[$lUid_OnPage]['flagIcon']) {
						$lC = IconUtility::getSpriteIcon($this->languageIconTitles[$lUid_OnPage]['flagIcon']);
					} else {
						$lC = $this->languageIconTitles[$lUid_OnPage]['title'];
					}
					$lC = '<a href="' . htmlspecialchars($href) . '" title="' . htmlspecialchars($language['title']) . '">' . $lC . '</a> ';
					$lNew .= $lC;
				}
			}
			if ($lNew) {
				$out[1] .= $lNew;
			}
		} elseif ($row['l18n_parent']) {
			$out[0] = '&nbsp;&nbsp;&nbsp;&nbsp;' . $out[0];
		}

		return $out;
	}

	/**
	 * Create the selector box for selecting fields to display from a table:
	 * @param string $table Table name
	 * @param bool $formFields If TRUE, form-fields will be wrapped around the table.
	 * @return string HTML table with the selector box (name: displayFields['.$table.'][])
	 */
	public function fieldSelectBox($table, $formFields = true) {
		$lang = $this->getLanguageService();
		// Init:
		$formElements = array('', '');
		if ($formFields) {
			$formElements = array('<form action="' . htmlspecialchars($this->listURL()) . '" method="post">', '</form>');
		}
		// Load already selected fields, if any:
		$setFields = is_array($this->setFields[$table]) ? $this->setFields[$table] : array();
		// Request fields from table:
		$fields = $this->makeFieldList($table, false, true);
		// Add pseudo "control" fields
		$fields[] = '_PATH_';
		$fields[] = '_REF_';
		$fields[] = '_LOCALIZATION_';
		$fields[] = '_CONTROL_';
		$fields[] = '_CLIPBOARD_';
		// Create an option for each field:
		$opt = array();
		$opt[] = '<option value=""></option>';
		foreach ($fields as $fN) {
			// Field label
			$fL = is_array($GLOBALS['TCA'][$table]['columns'][$fN]) ? rtrim($lang->sL($GLOBALS['TCA'][$table]['columns'][$fN]['label']), ':') : '[' . $fN . ']';
			$opt[] = '<option value="' . $fN . '"' . (in_array($fN, $setFields) ? ' selected="selected"' : '') . '>' . htmlspecialchars($fL) . '</option>';
		}
		// Compile the options into a multiple selector box:
		$lMenu = '<select size="' . MathUtility::forceIntegerInRange(count($fields) + 1, 3, 20) . '" multiple="multiple" name="displayFields[' . $table . '][]">' . implode('', $opt) . '</select>';
		// Table with the field selector::
		$content = $formElements[0] . '
			<!--
				Field selector for extended table view:
			-->
			<table border="0" cellpadding="0" cellspacing="0" id="typo3-dblist-fieldSelect">
				<tr>
					<td>
						' . $lMenu . '
					</td>
					<td>
						<input class="btn btn-default" type="submit" name="search" value="' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.setFields', true) . '" />
					</td>
				</tr>
			</table>
			' . $formElements[1];

		return '<div class="db_list-fieldSelect">' . $content . '</div>';
	}

}
