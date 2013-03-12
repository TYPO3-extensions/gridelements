<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Sebastian Böttger <sebastian.boettger@typovision.de>
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
 * XCLASS of 'localRecordList' (class.db_list_extra.inc)
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author	Dirk Hoffmann <hoffmann@vmd-jena.de>
 * @package TYPO3
 * @subpackage gridelements
 */

class ux_localRecordList extends localRecordList {

	protected $maxDepth = 10;

	/**
	 * Creates the listing of records from a single table
	 *
	 * @param	string		Table name
	 * @param	integer		Page id
	 * @param	string		List of fields to show in the listing. Pseudo fields will be added including the record header.
	 * @return	string		HTML table with the listing for the record.
	 */
	function getTable($table,$id,$rowlist)	{
		// Loading all TCA details for this table:

		t3lib_div::loadTCA($table);
		// Init
		$addWhere = '';
		$titleCol = $GLOBALS['TCA'][$table]['ctrl']['label'];
		$thumbsCol = $GLOBALS['TCA'][$table]['ctrl']['thumbnail'];
		$l10nEnabled = $GLOBALS['TCA'][$table]['ctrl']['languageField'] && $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']
			&& !$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerTable'];
		$tableCollapsed = (!$this->tablesCollapsed[$table]) ? FALSE : TRUE;

		// prepare space icon
		$this->spaceIcon = t3lib_iconWorks::getSpriteIcon('empty-empty', array('style' => 'background-position: 0 10px;'));

		// Cleaning rowlist for duplicates and place the $titleCol as the first column always!
		$this->fieldArray=array();
		// title Column
		$this->fieldArray[] = $titleCol;	// Add title column
		// Control-Panel
		if (!t3lib_div::inList($rowlist,'_CONTROL_'))	{
			$this->fieldArray[] = '_CONTROL_';
			$this->fieldArray[] = '_AFTERCONTROL_';
		}
		// Clipboard
		if ($this->showClipboard)	{
			$this->fieldArray[] = '_CLIPBOARD_';
		}
		// Ref
		if (!$this->dontShowClipControlPanels)	{
			$this->fieldArray[]='_REF_';
			$this->fieldArray[]='_AFTERREF_';
		}
		// Path
		if ($this->searchLevels)	{
			$this->fieldArray[]='_PATH_';
		}
		// Localization
		if ($this->localizationView && $l10nEnabled)	{
			$this->fieldArray[] = '_LOCALIZATION_';
			$this->fieldArray[] = '_LOCALIZATION_b';
			$addWhere.=' AND (
				' . $GLOBALS['TCA'][$table]['ctrl']['languageField'] . '<=0
				OR
				' . $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] . ' = 0
			)';
		}
		// Cleaning up:
		$this->fieldArray=array_unique(array_merge($this->fieldArray,t3lib_div::trimExplode(',',$rowlist,1)));
		if ($this->noControlPanels)	{
			$tempArray = array_flip($this->fieldArray);
			unset($tempArray['_CONTROL_']);
			unset($tempArray['_CLIPBOARD_']);
			$this->fieldArray = array_keys($tempArray);
		}

		// Creating the list of fields to include in the SQL query:
		$selectFields = $this->fieldArray;
		$selectFields[] = 'uid';
		$selectFields[] = 'pid';
		if ($thumbsCol)	$selectFields[] = $thumbsCol;	// adding column for thumbnails
		if ($table=='pages')	{
			if (t3lib_extMgm::isLoaded('cms'))	{
				$selectFields[] = 'module';
				$selectFields[] = 'extendToSubpages';
				$selectFields[] = 'nav_hide';
			}
			$selectFields[] = 'doktype';
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
		if ($l10nEnabled)	{
			$selectFields[] = $GLOBALS['TCA'][$table]['ctrl']['languageField'];
			$selectFields[] = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'];
		}
		if ($GLOBALS['TCA'][$table]['ctrl']['label_alt'])	{
			$selectFields = array_merge(
				$selectFields,
				t3lib_div::trimExplode(',', $GLOBALS['TCA'][$table]['ctrl']['label_alt'], 1)
			);
		}
		$selectFields = array_unique($selectFields);		// Unique list!
		$fieldListFields = $this->makeFieldList($table, 1);
		if (empty($fieldListFields) && $GLOBALS['TYPO3_CONF_VARS']['BE']['debug']) {

			$message = sprintf(
				$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_web_list.php:missingTcaColumnsMessage', TRUE),
				$table,
				$table
			);
			$messageTitle = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_web_list.php:missingTcaColumnsMessageTitle', TRUE);

			$flashMessage = t3lib_div::makeInstance(
				't3lib_FlashMessage',
				$message,
				$messageTitle,
				t3lib_FlashMessage::WARNING,
				TRUE
			);
			/** @var t3lib_FlashMessage $flashMessage */
			t3lib_FlashMessageQueue::addMessage($flashMessage);
		}
		$selectFields = array_intersect($selectFields, $fieldListFields);	// Making sure that the fields in the field-list ARE in the field-list from TCA!
		$selFieldList = implode(',', $selectFields);		// implode it into a list of fields for the SQL-statement.
		$this->selFieldList = $selFieldList;

		/**
		 * @hook			DB-List getTable
		 * @date			2007-11-16
		 * @request		Malte Jansen  <mail@maltejansen.de>
		 */
		if(is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['getTable'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['getTable'] as $classData) {
				$hookObject = t3lib_div::getUserObj($classData);

				if(!($hookObject instanceof t3lib_localRecordListGetTableHook)) {
					throw new UnexpectedValueException('$hookObject must implement interface t3lib_localRecordListGetTableHook', 1195114460);
				}

				$hookObject->getDBlistQuery($table, $id, $addWhere, $selFieldList, $this);
			}
		}

		// Create the SQL query for selecting the elements in the listing:
		if ($this->csvOutput) {	// do not do paging when outputting as CSV
			$this->iLimit = 0;
		}

		if ($this->firstElementNumber > 2 && $this->iLimit > 0) {
			// Get the two previous rows for sorting if displaying page > 1
			$this->firstElementNumber = $this->firstElementNumber - 2;
			$this->iLimit = $this->iLimit + 2;
			$queryParts = $this->makeQueryArray($table, $id,$addWhere,$selFieldList);	// (API function from class.db_list.inc)
			$this->firstElementNumber = $this->firstElementNumber + 2;
			$this->iLimit = $this->iLimit - 2;
		} else {
			$queryParts = $this->makeQueryArray($table, $id,$addWhere,$selFieldList);	// (API function from class.db_list.inc)
		}
		$this->setTotalItems($queryParts);		// Finding the total amount of records on the page (API function from class.db_list.inc)

		// Init:
		$dbCount = 0;
		$out = '';
		$listOnlyInSingleTableMode = $this->listOnlyInSingleTableMode && !$this->table;

		// If the count query returned any number of records, we perform the real query, selecting records.
		if ($this->totalItems)	{
			// Fetch records only if not in single table mode or if in multi table mode and not collapsed
			if ($listOnlyInSingleTableMode || (!$this->table && $tableCollapsed)) {
				$dbCount = $this->totalItems;
			} else {
				// set the showLimit to the number of records when outputting as CSV
				if ($this->csvOutput) {
					$this->showLimit = $this->totalItems;
					$this->iLimit = $this->totalItems;
				}
				$result = $GLOBALS['TYPO3_DB']->exec_SELECT_queryArray($queryParts);
				$dbCount = $GLOBALS['TYPO3_DB']->sql_num_rows($result);
			}
		}

		// If any records was selected, render the list:
		if ($dbCount)	{
			// Half line is drawn between tables:
			if (!$listOnlyInSingleTableMode)	{
				$theData = Array();
				if (!$this->table && !$rowlist)	{
					$theData[$titleCol] = '<img src="clear.gif" width="'.($GLOBALS['SOBE']->MOD_SETTINGS['bigControlPanel']?'230':'350').'" height="1" alt="" />';
					if (in_array('_CONTROL_',$this->fieldArray))	$theData['_CONTROL_']='';
					if (in_array('_CLIPBOARD_',$this->fieldArray))	$theData['_CLIPBOARD_']='';
				}
				$out.=$this->addelement(0,'',$theData,'class="c-table-row-spacer"',$this->leftMargin);
			}

			$tableTitle = $GLOBALS['LANG']->sL($GLOBALS['TCA'][$table]['ctrl']['title'], TRUE);
			if ($tableTitle === '') {
				$tableTitle = $table;
			}
			// Header line is drawn
			$theData = array();
			if ($this->disableSingleTableView)	{
				$theData[$titleCol] = '<span class="c-table">' .
					t3lib_BEfunc::wrapInHelp($table, '', $tableTitle) .
					'</span> (' . $this->totalItems . ')';
			} else {
				$theData[$titleCol] = $this->linkWrapTable($table, '<span class="c-table">' . $tableTitle . '</span> (' . $this->totalItems . ') ' .
					($this->table ? t3lib_iconWorks::getSpriteIcon('actions-view-table-collapse', array('title' => $GLOBALS['LANG']->getLL('contractView', TRUE))) : t3lib_iconWorks::getSpriteIcon('actions-view-table-expand', array('title' => $GLOBALS['LANG']->getLL('expandView', TRUE))))
				);
			}

			if ($listOnlyInSingleTableMode)	{
				$out.='
					<tr>
						<td class="t3-row-header" style="width:95%;">' . t3lib_BEfunc::wrapInHelp($table, '', $theData[$titleCol]) . '</td>
					</tr>';
			} else {
				// Render collapse button if in multi table mode
				$collapseIcon = '';
				if (!$this->table) {
					$collapseIcon = '<a href="' . htmlspecialchars($this->listURL() . '&collapse[' . $table . ']=' . ($tableCollapsed ? '0' : '1')) . '" title="' . ($tableCollapsed ? $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.expandTable', TRUE) : $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.collapseTable', TRUE)) . '">' .
						($tableCollapsed ? t3lib_iconWorks::getSpriteIcon('actions-view-list-expand', array('class' => 'collapseIcon')) : t3lib_iconWorks::getSpriteIcon('actions-view-list-collapse', array('class' => 'collapseIcon'))) .
						'</a>';
				}
				$out .= $this->addElement(1, $collapseIcon, $theData, ' class="t3-row-header"', '');
			}

			// Render table rows only if in multi table view and not collapsed or if in single table view
			if (!$listOnlyInSingleTableMode && (!$tableCollapsed || $this->table)) {
				// Fixing a order table for sortby tables
				$this->currentTable = array();
				$currentIdList = array();
				$doSort = ($GLOBALS['TCA'][$table]['ctrl']['sortby'] && !$this->sortField);

				$prevUid = 0;
				$prevPrevUid = 0;

				// Get first two rows and initialize prevPrevUid and prevUid if on page > 1
				if ($this->firstElementNumber > 2 && $this->iLimit > 0) {
					$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
					$prevPrevUid = -(int) $row['uid'];
					$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
					$prevUid = $row['uid'];
				}

				$accRows = array();	// Accumulate rows here
				while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result))	{

					// In offline workspace, look for alternative record:
					t3lib_BEfunc::workspaceOL($table, $row, $GLOBALS['BE_USER']->workspace, TRUE);

					if (is_array($row))	{
						$accRows[] = $row;
						$currentIdList[] = $row['uid'];
						if ($doSort)	{
							if ($prevUid)	{
								$this->currentTable['prev'][$row['uid']] = $prevPrevUid;
								$this->currentTable['next'][$prevUid] = '-'.$row['uid'];
								$this->currentTable['prevUid'][$row['uid']] = $prevUid;
							}
							$prevPrevUid = isset($this->currentTable['prev'][$row['uid']]) ? -$prevUid : $row['pid'];
							$prevUid=$row['uid'];
						}
					}
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($result);

				$this->totalRowCount = count($accRows);

				// CSV initiated
				if ($this->csvOutput) $this->initCSV();

				// Render items:
				$this->CBnames=array();
				$this->duplicateStack=array();
				$this->eCounter=$this->firstElementNumber;

				$iOut = '';
				$cc = 0;

				$lastColPos='';
				foreach($accRows as $key => $row)	{
					// Render item row if counter < limit
					if ($cc < $this->iLimit) {
						$cc++;
						$this->translations = FALSE;

						if (isset($row['colPos']) && ($row['colPos'] != $lastColPos)) {
							$lastColPos = $row['colPos'];
							$this->showMoveUp = false;
							$column = t3lib_BEfunc::getProcessedValueExtra($table, 'colPos', $row['colPos'], 100, $row['uid']);
							$iOut .= '<tr><td></td><td colspan="' . (count($this->fieldArray)-1+$this->maxDepth) . '" style="padding:5px;"><br /><strong>' .
								$GLOBALS['LANG']->sL('LLL:EXT:gridelements/locallang_db.xml:list.columnName') . ' ' .
								(($column) ? $column : $row['colPos']) . '</strong></td></tr>';
						} else {
							$this->showMoveUp = true;
						}
						if (isset($row['colPos']) && isset($accRows[$key + 1]) && $row['colPos'] != $accRows[$key + 1]['colPos']) {
							$this->showMoveDown = false;
						} else {
							$this->showMoveDown = true;
						}
						$iOut.= $this->renderListRow($table,$row,$cc,$titleCol,$thumbsCol);

						// If localization view is enabled it means that the selected records are either default or All language and here we will not select translations which point to the main record:
						if ($this->localizationView && $l10nEnabled)	{
							// For each available translation, render the record:
							if (is_array($this->translations)) {
								foreach ($this->translations as $lRow) {
									// $lRow isn't always what we want - if record was moved we've to work with the placeholder records otherwise the list is messed up a bit
									if ($row['_MOVE_PLH_uid'] && $row['_MOVE_PLH_pid']) {
										$tmpRow = t3lib_BEfunc::getRecordRaw($table, 't3ver_move_id="'.intval($lRow['uid']) . '" AND pid="' . $row['_MOVE_PLH_pid'] . '" AND t3ver_wsid=' . $row['t3ver_wsid'] . t3lib_beFunc::deleteClause($table), $selFieldList);
										$lRow = is_array($tmpRow)?$tmpRow:$lRow;
									}
									// In offline workspace, look for alternative record:
									t3lib_BEfunc::workspaceOL($table, $lRow, $GLOBALS['BE_USER']->workspace, TRUE);
									if (is_array($lRow) && $GLOBALS['BE_USER']->checkLanguageAccess($lRow[$GLOBALS['TCA'][$table]['ctrl']['languageField']])) {
										$currentIdList[] = $lRow['uid'];
										$iOut.=$this->renderListRow($table,$lRow,$cc,$titleCol,$thumbsCol,18);
									}
								}
							}
						}
					}

					// Counter of total rows incremented:
					$this->eCounter++;
				}

				// Record navigation is added to the beginning and end of the table if in single table mode
				if ($this->table) {
					$iOut = $this->renderListNavigation('top') . $iOut . $this->renderListNavigation('bottom');
				} else {
					// show that there are more records than shown
					if ($this->totalItems > $this->itemsLimitPerTable) {
						$countOnFirstPage = $this->totalItems > $this->itemsLimitSingleTable ? $this->itemsLimitSingleTable : $this->totalItems;
						$hasMore = ($this->totalItems > $this->itemsLimitSingleTable);
						$iOut .= '<tr><td colspan="' . (count($this->fieldArray)+$this->maxDepth) . '" style="padding:5px;">
								<a href="'.htmlspecialchars($this->listURL() . '&table=' . rawurlencode($table)) . '">' .
							'<img' . t3lib_iconWorks::skinImg($this->backPath,'gfx/pildown.gif', 'width="14" height="14"') .' alt="" />'.
							' <i>[1 - ' . $countOnFirstPage . ($hasMore ? '+' : '') . ']</i></a>
								</td></tr>';
					}

				}

				// The header row for the table is now created:
				$out .= $this->renderListHeader($table,$currentIdList);
			}

			// The list of records is added after the header:
			$out .= $iOut;
			unset($iOut);

			// ... and it is all wrapped in a table:
			$out='



			<!--
				DB listing of elements:	"'.htmlspecialchars($table).'"
			-->
				<table border="0" cellpadding="0" cellspacing="0" class="typo3-dblist'.($listOnlyInSingleTableMode?' typo3-dblist-overview':'').'">
					'.$out.'
				</table>';

			// Output csv if...
			if ($this->csvOutput)	$this->outputCSV($table);	// This ends the page with exit.
		}

		// Return content:
		return $out;
	}

	/**
	 * Creates the control panel for a single record in the listing.
	 *
	 * @param	string		The table
	 * @param	array		The record for which to make the control panel.
	 * @return	string		HTML table with the control panel (unless disabled)
	 */
	function makeControl($table,$row,$level)	{
		if ($this->dontShowClipControlPanels) {
			return '';
		}

		$rowUid = tx_gridelements_helper::getInstance()->getSpecificUid($row);

		// Initialize:
		t3lib_div::loadTCA($table);
		$cells = array();

		// If the listed table is 'pages' we have to request the permission settings for each page:
		if ($table=='pages')	{
			$localCalcPerms = $GLOBALS['BE_USER']->calcPerms(t3lib_BEfunc::getRecord('pages',$row['uid']));
		}

		// This expresses the edit permissions for this particular element:
		$permsEdit = ($table=='pages' && ($localCalcPerms&2)) || ($table!='pages' && ($this->calcPerms&16));

		// "Show" link (only pages and tt_content elements)
		if ($table=='pages' || $table=='tt_content')	{
			$params='&edit['.$table.']['.$row['uid'].']=edit';
			$cells['view'] = '<a href="#" onclick="' . htmlspecialchars(t3lib_BEfunc::viewOnClick(
					$table=='tt_content' ? $this->id . '#' . $row['uid'] : $row['uid'],
					$this->backPath)
			) . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.showPage', TRUE) . '">' .
				t3lib_iconWorks::getSpriteIcon('actions-document-view') . '</a>';
		} elseif(!$this->table) {
			$cells['view'] = $this->spaceIcon;
		}

		// "Edit" link: ( Only if permissions to edit the page-record of the content of the parent page ($this->id)
		if ($permsEdit)	{
			$params='&edit['.$table.']['.$row['uid'].']=edit';
			$cells['edit'] = '<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick($params, $this->backPath, -1)) .
				'" title="' . $GLOBALS['LANG']->getLL('edit', TRUE) . '">' .
				( $GLOBALS['TCA'][$table]['ctrl']['readOnly'] ? t3lib_iconWorks::getSpriteIcon('actions-document-open-read-only') : t3lib_iconWorks::getSpriteIcon('actions-document-open')) .
				'</a>';
		} elseif(!$this->table) {
			$cells['edit'] = $this->spaceIcon;
		}

		// "Move" wizard link for pages/tt_content elements:
#		t3lib_utility_Debug::debug($level);
		if ((($table=="tt_content" && $permsEdit) || ($table=='pages')) && $level == 0)	{
			$cells['move'] = '<a href="#" onclick="' .
				htmlspecialchars(
					'return jumpExt(\'' . $this->backPath . 'move_el.php?table=' . $table . '&uid='.$row['uid'] . '\');'
				) .'" title="' . $GLOBALS['LANG']->getLL('move_' . ($table == 'tt_content' ? 'record' : 'page'), TRUE) . '">' .
				($table == 'tt_content' ? t3lib_iconWorks::getSpriteIcon('actions-document-move') : t3lib_iconWorks::getSpriteIcon('actions-page-move')) .
				'</a>';
		} elseif(!$this->table || $level > 0) {
			$cells['move'] = $this->spaceIcon;
		}

		// If the extended control panel is enabled OR if we are seeing a single table:
		if ($GLOBALS['SOBE']->MOD_SETTINGS['bigControlPanel'] || $this->table)	{

			// "Info": (All records)
			$cells['viewBig'] = '<a href="#" onclick="' . htmlspecialchars(
				'top.launchView(\'' . $table . '\', \''.$row['uid'] . '\'); return false;'
			) . '" title="' . $GLOBALS['LANG']->getLL('showInfo', TRUE) . '">'.
				t3lib_iconWorks::getSpriteIcon('actions-document-info') .
				'</a>';

			// If the table is NOT a read-only table, then show these links:
			if (!$GLOBALS['TCA'][$table]['ctrl']['readOnly']) {

				// "Revert" link (history/undo)
				$cells['history'] = '<a href="#" onclick="' . htmlspecialchars(
					'return jumpExt(\'' . $this->backPath . 'show_rechis.php?element=' . rawurlencode($table . ':' . $row['uid']) . '\',\'#latest\');') .
					'" title="' . $GLOBALS['LANG']->getLL('history', TRUE) . '">'.
					t3lib_iconWorks::getSpriteIcon('actions-document-history-open') .
					'</a>';

				// Versioning:
				if (t3lib_extMgm::isLoaded('version') && !t3lib_extMgm::isLoaded('workspaces')) {
					$vers = t3lib_BEfunc::selectVersionsOfRecord($table, $row['uid'], 'uid', $GLOBALS['BE_USER']->workspace, FALSE, $row);
					if (is_array($vers))	{	// If table can be versionized.
						$versionIcon = 'no-version';
						if (count($vers) > 1) {
							$versionIcon = count($vers) - 1;
						}

						$cells['version'] = '<a href="' . htmlspecialchars($this->backPath . t3lib_extMgm::extRelPath('version') . 'cm1/index.php?table=' . rawurlencode($table) . '&uid=' . rawurlencode($row['uid'])) . '" title="' . $GLOBALS['LANG']->getLL('displayVersions', TRUE) . '">' .
							t3lib_iconWorks::getSpriteIcon('status-version-' . $versionIcon) .
							'</a>';
					} elseif(!$this->table) {
						$cells['version'] = $this->spaceIcon;
					}
				}

				// "Edit Perms" link:
				if ($table == 'pages' && $GLOBALS['BE_USER']->check('modules','web_perm') && t3lib_extMgm::isLoaded('perm'))	{
					$cells['perms'] =
						'<a href="' .
							htmlspecialchars(
								t3lib_extMgm::extRelPath('perm') . 'mod1/index.php' .
									'?id=' . $row['uid'] . '&return_id=' . $row['uid'] . '&edit=1'
							) .
							'" title="' . $GLOBALS['LANG']->getLL('permissions', TRUE) .
							'">'.
							t3lib_iconWorks::getSpriteIcon('status-status-locked') .
							'</a>';
				} elseif((!$this->table && $GLOBALS['BE_USER']->check('modules','web_perm'))) {
					$cells['perms'] = $this->spaceIcon;
				}
				// "New record after" link (ONLY if the records in the table are sorted by a "sortby"-row or if default values can depend on previous record):
				if ($GLOBALS['TCA'][$table]['ctrl']['sortby'] || $GLOBALS['TCA'][$table]['ctrl']['useColumnsForDefaultValues']) {
					if (
						($table!='pages' && ($this->calcPerms&16)) || 	// For NON-pages, must have permission to edit content on this parent page
						($table=='pages' && ($this->calcPerms&8))		// For pages, must have permission to create new pages here.
					)	{

						if ($this->showNewRecLink($table))	{
							$params='&edit['.$table.']['.(-($row['_MOVE_PLH']?$row['_MOVE_PLH_uid']:$row['uid'])).']=new';
							$cells['new'] = '<a href="#" onclick="' . htmlspecialchars(
								t3lib_BEfunc::editOnClick($params, $this->backPath, -1)) .
								'" title="' . $GLOBALS['LANG']->getLL('new' . ($table == 'pages '? 'Page' : 'Record'), TRUE) . '">' .
								($table == 'pages' ? t3lib_iconWorks::getSpriteIcon('actions-page-new') : t3lib_iconWorks::getSpriteIcon('actions-document-new')) .
								'</a>';
						}
					}
				} elseif(!$this->table) {
					$cells['new'] = $this->spaceIcon;
				}

				// "Up/Down" links
				if ($permsEdit && $GLOBALS['TCA'][$table]['ctrl']['sortby']  && !$this->sortField && !$this->searchLevels) {
					if (isset($this->currentTable['prev'][$row['uid']]) && $this->showMoveUp === true)	{	// Up
						if($this->lastMoveDownParams) {
							$params= $this->lastMoveDownParams;
						} else {
							$params='&cmd['.$table.']['.$row['uid'].'][move]='.$this->currentTable['prev'][$row['uid']];
						}
						$cells['moveUp'] = '<a href="#" onclick="' . htmlspecialchars(
							'return jumpToUrl(\'' . $GLOBALS['SOBE']->doc->issueCommand($params, -1) . '\');'
						) .'" title="'.$GLOBALS['LANG']->getLL('moveUp', TRUE) . '">' .
							t3lib_iconWorks::getSpriteIcon('actions-move-up') .
							'</a>';
					} else {
						$cells['moveUp'] = $this->spaceIcon;
					}
					if ($this->currentTable['next'][$row['uid']] && $this->showMoveDown === true)	{	// Down
						$params='&cmd['.$table.']['.$row['uid'].'][move]='.$this->currentTable['next'][$row['uid']];
						$this->lastMoveDownParams = $params;
						$cells['moveDown']='<a href="#" onclick="'.htmlspecialchars('return jumpToUrl(\''.$GLOBALS['SOBE']->doc->issueCommand($params,-1).'\');').'" title="'.$GLOBALS['LANG']->getLL('moveDown', TRUE) . '">' .
							t3lib_iconWorks::getSpriteIcon('actions-move-down') .
							'</a>';
					} else {
						$cells['moveDown'] = $this->spaceIcon;
					}
				} elseif(!$this->table) {
					$cells['moveUp']  = $this->spaceIcon;
					$cells['moveDown'] = $this->spaceIcon;
				}

				// "Hide/Unhide" links:
				$hiddenField = $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled'];
				if ($permsEdit && $hiddenField && $GLOBALS['TCA'][$table]['columns'][$hiddenField] && (!$GLOBALS['TCA'][$table]['columns'][$hiddenField]['exclude'] || $GLOBALS['BE_USER']->check('non_exclude_fields', $table . ':' . $hiddenField))) {
					if ($row[$hiddenField])	{
						$params = '&data[' . $table . '][' . $rowUid . '][' . $hiddenField . ']=0';
						$cells['hide']='<a href="#" onclick="' . htmlspecialchars('return jumpToUrl(\'' . $GLOBALS['SOBE']->doc->issueCommand($params, -1) . '\');') . '" title="'.$GLOBALS['LANG']->getLL('unHide' . ($table == 'pages' ? 'Page' : ''), TRUE) . '">' .
							t3lib_iconWorks::getSpriteIcon('actions-edit-unhide') .
							'</a>';
					} else {
						$params = '&data[' . $table . '][' . $rowUid . '][' . $hiddenField . ']=1';
						$cells['hide']='<a href="#" onclick="' . htmlspecialchars('return jumpToUrl(\'' . $GLOBALS['SOBE']->doc->issueCommand($params, -1) . '\');') . '" title="' . $GLOBALS['LANG']->getLL('hide' . ($table == 'pages' ? 'Page' : ''), TRUE) . '">' .
							t3lib_iconWorks::getSpriteIcon('actions-edit-hide') .
							'</a>';
					}
				} elseif(!$this->table) {
					$cells['hide'] = $this->spaceIcon;
				}

				// "Delete" link:
				if (($table=='pages' && ($localCalcPerms&4)) || ($table!='pages' && ($this->calcPerms&16))) {
					$titleOrig = t3lib_BEfunc::getRecordTitle($table,$row,FALSE,TRUE);
					$title = t3lib_div::slashJS(t3lib_div::fixed_lgd_cs($titleOrig, $this->fixedL), 1);
					$params = '&cmd['.$table.']['.$row['uid'].'][delete]=1';

					$refCountMsg = t3lib_BEfunc::referenceCount(
						$table,
						$row['uid'],
						' ' . $GLOBALS['LANG']->sL(
							'LLL:EXT:lang/locallang_core.xml:labels.referencesToRecord'
						),
						$this->getReferenceCount($table, $row['uid'])
					) .
						t3lib_BEfunc::translationCount($table, $row['uid'], ' ' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.translationsOfRecord'));
					$cells['delete'] = '<a href="#" onclick="' . htmlspecialchars('if (confirm(' . $GLOBALS['LANG']->JScharCode($GLOBALS['LANG']->getLL('deleteWarning') . ' "' .  $title . '" ' . $refCountMsg) . ')) {jumpToUrl(\'' . $GLOBALS['SOBE']->doc->issueCommand($params, -1) . '\');} return false;') . '" title="' . $GLOBALS['LANG']->getLL('delete', TRUE) . '">' .
						t3lib_iconWorks::getSpriteIcon('actions-edit-delete') .
						'</a>';
				} elseif(!$this->table) {
					$cells['delete'] = $this->spaceIcon;
				}

				// "Levels" links: Moving pages into new levels...
				if ($permsEdit && $table=='pages' && !$this->searchLevels)	{

					// Up (Paste as the page right after the current parent page)
					if ($this->calcPerms&8)	{
						$params='&cmd['.$table.']['.$row['uid'].'][move]='.-$this->id;
						$cells['moveLeft'] = '<a href="#" onclick="' . htmlspecialchars('return jumpToUrl(\'' . $GLOBALS['SOBE']->doc->issueCommand($params, -1) . '\');') . '" title="' . $GLOBALS['LANG']->getLL('prevLevel', TRUE) . '">' .
							t3lib_iconWorks::getSpriteIcon('actions-move-left') .
							'</a>';
					}
					// Down (Paste as subpage to the page right above)
					if ($this->currentTable['prevUid'][$row['uid']])	{
						$localCalcPerms = $GLOBALS['BE_USER']->calcPerms(t3lib_BEfunc::getRecord('pages',$this->currentTable['prevUid'][$row['uid']]));
						if ($localCalcPerms&8)	{
							$params='&cmd['.$table.']['.$row['uid'].'][move]='.$this->currentTable['prevUid'][$row['uid']];
							$cells['moveRight'] = '<a href="#" onclick="' . htmlspecialchars('return jumpToUrl(\'' . $GLOBALS['SOBE']->doc->issueCommand($params, -1) . '\');') . '" title="' . $GLOBALS['LANG']->getLL('nextLevel', TRUE) . '">' .
								t3lib_iconWorks::getSpriteIcon('actions-move-right') .
								'</a>';
						} else {
							$cells['moveRight'] = $this->spaceIcon;
						}
					} else {
						$cells['moveRight'] = $this->spaceIcon;
					}
				} elseif(!$this->table) {
					$cells['moveLeft'] = $this->spaceIcon;
					$cells['moveRight'] = $this->spaceIcon;
				}
			}
		}


		/**
		 * @hook			recStatInfoHooks: Allows to insert HTML before record icons on various places
		 * @date			2007-09-22
		 * @request		Kasper Skårhøj  <kasper2007@typo3.com>
		 */
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['recStatInfoHooks']))	{
			$stat='';
			$_params = array($table,$row['uid']);
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['recStatInfoHooks'] as $_funcRef)	{
				$stat.=t3lib_div::callUserFunction($_funcRef,$_params,$this);
			}
			$cells['stat'] = $stat;
		}

		/**
		 * @hook			makeControl: Allows to change control icons of records in list-module
		 * @date			2007-11-20
		 * @request		Bernhard Kraft  <krafbt@kraftb.at>
		 * @usage		This hook method gets passed the current $cells array as third parameter. This array contains values for the icons/actions generated for each record in Web>List. Each array entry is accessible by an index-key. The order of the icons is dependend on the order of those array entries.
		 */
		if(is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'] as $classData) {
				$hookObject = t3lib_div::getUserObj($classData);
				if(!($hookObject instanceof localRecordList_actionsHook))	{
					throw new UnexpectedValueException('$hookObject must implement interface localRecordList_actionsHook', 1195567840);
				}
				$cells = $hookObject->makeControl($table, $row, $cells, $this);
			}
		}

		// Compile items into a DIV-element:
		return '
											<!-- CONTROL PANEL: '.$table.':'.$row['uid'].' -->
											<div class="typo3-DBctrl">'.implode('',$cells).'</div>';
	}

	/**
	 * Creates the clipboard panel for a single record in the listing.
	 *
	 * @param	string		The table
	 * @param	array		The record for which to make the clipboard panel.
	 * @return	string		HTML table with the clipboard panel (unless disabled)
	 */
	function makeClip($table,$row)	{
		// Return blank, if disabled:
		if ($this->dontShowClipControlPanels)	return '';
		$cells=array();

		$cells['pasteAfter'] = $cells['pasteInto'] = $this->spaceIcon;
		//enables to hide the copy, cut and paste icons for localized records - doesn't make much sense to perform these options for them
		$isL10nOverlay = $this->localizationView && $table != 'pages_language_overlay' && $row[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']] != 0;
		// Return blank, if disabled:
		// Whether a numeric clipboard pad is active or the normal pad we will see different content of the panel:
		if ($this->clipObj->current=='normal')	{	// For the "Normal" pad:

			// Show copy/cut icons:
			$isSel = (string)$this->clipObj->isSelected($table,$row['uid']);
			$cells['copy'] = $isL10nOverlay ? $this->spaceIcon : '<a href="#" onclick="' . htmlspecialchars('return jumpSelf(\'' . $this->clipObj->selUrlDB($table, $row['uid'], 1, ($isSel=='copy'), array('returnUrl'=>'')) . '\');') . '" title="'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:cm.copy', TRUE) . '">' .
				((!$isSel=='copy') ? t3lib_iconWorks::getSpriteIcon('actions-edit-copy') : t3lib_iconWorks::getSpriteIcon('actions-edit-copy-release')) .
				'</a>';
			$cells['cut'] = $isL10nOverlay ? $this->spaceIcon : '<a href="#" onclick="' . htmlspecialchars('return jumpSelf(\'' . $this->clipObj->selUrlDB($table, $row['uid'], 0, ($isSel == 'cut'), array('returnUrl'=>'')) . '\');') . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:cm.cut', TRUE) . '">' .
				((!$isSel=='cut') ? t3lib_iconWorks::getSpriteIcon('actions-edit-cut') : t3lib_iconWorks::getSpriteIcon('actions-edit-cut-release')) .
				'</a>';

		} else {	// For the numeric clipboard pads (showing checkboxes where one can select elements on/off)

			// Setting name of the element in ->CBnames array:
			$n=$table.'|'.$row['uid'];
			$this->CBnames[]=$n;

			// Check if the current element is selected and if so, prepare to set the checkbox as selected:
			$checked = ($this->clipObj->isSelected($table,$row['uid'])?' checked="checked"':'');

			// If the "duplicateField" value is set then select all elements which are duplicates...
			if ($this->duplicateField && isset($row[$this->duplicateField]))	{
				$checked='';
				if (in_array($row[$this->duplicateField], $this->duplicateStack))	{
					$checked=' checked="checked"';
				}
				$this->duplicateStack[] = $row[$this->duplicateField];
			}

			// Adding the checkbox to the panel:
			$cells['select'] = $isL10nOverlay ? $this->spaceIcon : '<input type="hidden" name="CBH['.$n.']" value="0" /><input type="checkbox" name="CBC['.$n.']" value="1" class="smallCheckboxes"'.$checked.' />';
		}

		// Now, looking for selected elements from the current table:
		$elFromTable = $this->clipObj->elFromTable($table);
		if (count($elFromTable) && $GLOBALS['TCA'][$table]['ctrl']['sortby']){

			if (stripos(t3lib_div::getIndpEnv('SCRIPT_NAME'), 'ajax') === false) {
				$pasteUrl = $this->clipObj->pasteUrl($table, -$row['uid']);
			} else {
				$pasteUrl = $this->pasteUrl($table, -$row['uid'], $row['pid']);
			}

			// IF elements are found and they can be individually ordered, then add a "paste after" icon:
			$cells['pasteAfter'] = $isL10nOverlay ? $this->spaceIcon : '<a href="' . htmlspecialchars($pasteUrl) . '" onclick="' . htmlspecialchars('return '. $this->clipObj->confirmMsg($table, $row, 'after', $elFromTable)) . '" title="' . $GLOBALS['LANG']->getLL('clip_pasteAfter', TRUE) . '">' .
				t3lib_iconWorks::getSpriteIcon('actions-document-paste-after') .
				'</a>';
		}

		// Now, looking for elements in general:
		$elFromTable = $this->clipObj->elFromTable('');
		if ($table=='pages' && count($elFromTable))	{
			$cells['pasteInto'] = '<a href="' . htmlspecialchars($this->clipObj->pasteUrl('', $row['uid'])) . '" onclick="' . htmlspecialchars('return ' . $this->clipObj->confirmMsg($table, $row, 'into', $elFromTable)) . '" title="' . $GLOBALS['LANG']->getLL('clip_pasteInto', TRUE) . '">' .
				t3lib_iconWorks::getSpriteIcon('actions-document-paste-into') .
				'</a>';
		}

		/*
		 * @hook			makeClip: Allows to change clip-icons of records in list-module
		 * @date			2007-11-20
		 * @request		Bernhard Kraft  <krafbt@kraftb.at>
		 * @usage		This hook method gets passed the current $cells array as third parameter. This array contains values for the clipboard icons generated for each record in Web>List. Each array entry is accessible by an index-key. The order of the icons is dependend on the order of those array entries.
		 */
		if(is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'] as $classData) {
				$hookObject = t3lib_div::getUserObj($classData);
				if(!($hookObject instanceof localRecordList_actionsHook))	{
					throw new UnexpectedValueException('$hookObject must implement interface localRecordList_actionsHook', 1195567845);
				}
				$cells = $hookObject->makeClip($table, $row, $cells, $this);
			}
		}

		// Compile items into a DIV-element:
		return '							<!-- CLIPBOARD PANEL: '.$table.':'.$row['uid'].' -->
											<div class="typo3-clipCtrl">'.implode('',$cells).'</div>';
	}

	/**
	 * Rendering a single row for the list
	 *
	 * @param	string		Table name
	 * @param	array		Current record
	 * @param	integer		Counter, counting for each time an element is rendered (used for alternating colors)
	 * @param	string		Table field (column) where header value is found
	 * @param	string		Table field (column) where (possible) thumbnails can be found
	 * @param	integer		Indent from left.
	 * @return	string		Table row for the element
	 * @access private
	 * @see getTable()
	 */
	function renderListRow($table,$row,$cc,$titleCol,$thumbsCol,$indent=0, $level=0)	{
		$iOut = '';
		if (strlen($this->searchString))	{	// If in search mode, make sure the preview will show the correct page
			$id_orig = $this->id;
			$this->id = $row['pid'];
		}

		if (is_array($row))	{
			// add special classes for first and last row
			$rowSpecial = '';
			if ($cc == 1 && $indent == 0) {
				$rowSpecial .= ' firstcol';
			}
			if ($cc == $this->totalRowCount || $cc == $this->iLimit) {
				$rowSpecial .= ' lastcol';
			}

			// Background color, if any:
			if ($this->alternateBgColors) {
				$row_bgColor = ($cc%2) ? ' class="db_list_normal'.$rowSpecial.'"' : ' class="db_list_alt'.$rowSpecial.'"';
			} else {
				$row_bgColor = ' class="db_list_normal'.$rowSpecial.'"';
			}
			// Overriding with versions background color if any:
			$row_bgColor = $row['_CSSCLASS'] ? ' class="'.$row['_CSSCLASS'].'"' : $row_bgColor;

			// Incr. counter.
			$this->counter++;

			// The icon with link
			$alttext = t3lib_BEfunc::getRecordIconAltText($row,$table);
			$iconImg = t3lib_iconWorks::getSpriteIconForRecord($table, $row, array('title' => htmlspecialchars($alttext), 'style' => ($indent ? ' margin-left: ' . $indent . 'px;' : '')));

			$theIcon = $this->clickMenuEnabled ? $GLOBALS['SOBE']->doc->wrapClickMenuOnIcon($iconImg,$table,$row['uid']) : $iconImg;

			// Have labels respect possible itemsProcFunc results
			/** @var $formEngine t3lib_TCEforms */
			$formEngine = t3lib_div::makeInstance('t3lib_TCEForms');
			if(isset($GLOBALS['TCA'][$table]['columns'])) {
				foreach($GLOBALS['TCA'][$table]['columns'] as $field => $fieldSetup) {
					if(isset($fieldSetup['config']['itemsProcFunc'])) {
						$GLOBALS['TCA'][$table]['columns'][$field]['config']['items'] = $formEngine->procItems(
							$fieldSetup['config']['items'],
							$fieldSetup['config']['itemsProcFunc'],
							$fieldSetup['config'],
							$table,
							$row,
							$field
						);
					}
				}
			}

			// Preparing and getting the data-array
			$theData = Array();
			foreach($this->fieldArray as $fCol)	{
				if ($fCol==$titleCol)	{
					$recTitle = t3lib_BEfunc::getRecordTitle($table,$row,FALSE,TRUE);
					// If the record is edit-locked	by another user, we will show a little warning sign:
					if (($lockInfo = t3lib_BEfunc::isRecordLocked($table, $row['uid']))) {
						$warning = '<a href="#" onclick="' . htmlspecialchars('alert(' . $GLOBALS['LANG']->JScharCode($lockInfo['msg']) . '); return false;') . '" title="' . htmlspecialchars($lockInfo['msg']) . '">' .
							t3lib_iconWorks::getSpriteIcon('status-warning-in-use') .
							'</a>';
					}
					$theData[$fCol] = $warning . $this->linkWrapItems($table, $row['uid'], $recTitle, $row);

					// Render thumbsnails if a thumbnail column exists and there is content in it:
					if ($this->thumbs && trim($row[$thumbsCol])) {
						$theData[$fCol] .= '<br />' . $this->thumbCode($row,$table,$thumbsCol);
					}

					$localizationMarkerClass = '';
					if (isset($GLOBALS['TCA'][$table]['ctrl']['languageField'])
						&& $row[$GLOBALS['TCA'][$table]['ctrl']['languageField']] != 0
						&& $row[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']] != 0) {
						// it's a translated record with a language parent
						$localizationMarkerClass = ' localization';
					}
				} elseif ($fCol == 'pid') {
					$theData[$fCol]=$row[$fCol];
				} elseif ($fCol == '_PATH_') {
					$theData[$fCol]=$this->recPath($row['pid']);
				} elseif ($fCol == '_REF_') {
					$theData[$fCol] = $this->createReferenceHtml($table, $row['uid']);
				} elseif ($fCol == '_CONTROL_') {
					$theData[$fCol]=$this->makeControl($table,$row,$level);
				} elseif ($fCol == '_AFTERCONTROL_' || $fCol == '_AFTERREF_') {
					$theData[$fCol] = '&nbsp;';
				} elseif ($fCol == '_CLIPBOARD_') {
					$theData[$fCol]=$this->makeClip($table,$row);
				} elseif ($fCol == '_LOCALIZATION_') {
					list($lC1, $lC2) = $this->makeLocalizationPanel($table,$row);
					$theData[$fCol] = $lC1;
					$theData[$fCol.'b'] = $lC2;
				} elseif ($fCol == '_LOCALIZATION_b') {
					// Do nothing, has been done above.
				} else {
					$tmpProc = t3lib_BEfunc::getProcessedValueExtra($table, $fCol, $row[$fCol], 100, $row['uid']);
					$theData[$fCol] = $this->linkUrlMail(htmlspecialchars($tmpProc), $row[$fCol]);
					if ($this->csvOutput) {
						$row[$fCol] = t3lib_BEfunc::getProcessedValueExtra($table, $fCol, $row[$fCol], 0, $row['uid']);
					}
				}
			}

			if (strlen($this->searchString))	{	// Reset the ID if it was overwritten
				$this->id = $id_orig;
			}

			// Add row to CSV list:
			if ($this->csvOutput) {
				$this->addToCSV($row,$table);
			}

			// Add classes to table cells
			$this->addElement_tdCssClass[$titleCol]         = 'col-title' . $localizationMarkerClass;
			if (!$this->dontShowClipControlPanels) {
				$this->addElement_tdCssClass['_CONTROL_']       = 'col-control';
				$this->addElement_tdCssClass['_AFTERCONTROL_']  = 'col-control-space';
				$this->addElement_tdCssClass['_CLIPBOARD_']     = 'col-clipboard';
			}
			$this->addElement_tdCssClass['_PATH_']          = 'col-path';
			$this->addElement_tdCssClass['_LOCALIZATION_']  = 'col-localizationa';
			$this->addElement_tdCssClass['_LOCALIZATION_b'] = 'col-localizationb';

			if ($table == 'tt_content' && $row['CType'] == 'gridelements_pi1') {
				$elementChildren = tx_gridelements_helper::getInstance()->getChildren($table, $row['uid']);
				if (count($elementChildren) > 0) {
					$theData['_EXPANDABLE_'] = true;
					$theData['_EXPAND_ID_'] = $table . ':' . $row['uid'];
					$theData['_LEVEL_'] = $level;
				}
			}

//			$level = 0;
			// Create element in table cells:
#			if ($level >= 0) {
#				$row_bgColor.= ' class="gridelements-child-' . $row['uid']. '"';
#			}
#			t3lib_utility_Debug::debug($row_bgColor);
			$iOut.=$this->addelement(1,$theIcon,$theData,$row_bgColor, '', '', $level);

			// Finally, return table row element:
			return $iOut;
		}
	}

	/**
	 * Returns a table-row with the content from the fields in the input data array.
	 * OBS: $this->fieldArray MUST be set! (represents the list of fields to display)
	 *
	 * @param	integer		$h is an integer >=0 and denotes how tall a element is. Set to '0' makes a halv line, -1 = full line, set to 1 makes a 'join' and above makes 'line'
	 * @param	string		$icon is the <img>+<a> of the record. If not supplied the first 'join'-icon will be a 'line' instead
	 * @param	array		$data is the dataarray, record with the fields. Notice: These fields are (currently) NOT htmlspecialchar'ed before being wrapped in <td>-tags
	 * @param	string		$tdParams is insert in the <td>-tags. Must carry a ' ' as first character
	 * @param	integer		OBSOLETE - NOT USED ANYMORE. $lMargin is the leftMargin (integer)
	 * @param	string		$altLine is the HTML <img>-tag for an alternative 'gfx/ol/line.gif'-icon (used in the top)
	 * @return	string		HTML content for the table row
	 */
	function addElement($h, $icon, $data, $trParams = '', $lMargin = '', $altLine = '', $level = 0) {
		$noWrap = ($this->no_noWrap) ? '' : ' nowrap="nowrap"';

		// Start up:
		$out = '
		<!-- Element, begin: -->
		<tr ' . $trParams . '>';

		if (count($data) > 1) {
			$contentCollapseIcon = '&nbsp;';

			if ($data['_EXPANDABLE_']) {
				$sortField = t3lib_div::_GP('sortField') ? t3lib_div::_GP('sortField') . ':'  . (int)t3lib_div::_GP('sortRev') : '';

				$contentCollapseIcon = '
					<a href="javascript:GridElementsListView.elExpandCollapse(\'' .
						$data['_EXPAND_ID_'] . '\',\'' . $sortField . '\', ' . $level . ')" title="' . $GLOBALS['LANG']->sL('LLL:EXT:gridelements/locallang_db.xml:list.collapseElement', TRUE) . '" rel="' . $data['_EXPAND_ID_'] . '">
						<span class="t3-icon t3-icon-actions t3-icon-actions-view t3-icon-pagetree-collapse collapseIcon">&nbsp;</span>
					</a>
				';
			}
			for ($i = 0; $i < $level; $i++) {
//				t3lib_utility_Debug::debug($level);
				$out.=	'<td nowrap="nowrap"></td>';
			}

			$out.=	'<td nowrap="nowrap" class="col-icon"><a href="#">' . $contentCollapseIcon . '</a></td>';
		}

		// Show icon and lines
		if ($this->showIcon) {
			$out .= '
			<td nowrap="nowrap" class="col-icon">';

			if (!$h) {
				$out .= '<img src="clear.gif" width="1" height="8" alt="" />';
			} else {
				for ($a = 0; $a < $h; $a++) {
					if (!$a) {
						if ($icon) {
							$out .= $icon;
						}
					} else {
					}
				}
			}
			$out .= '</td>
			';
		}

		// Init rendering.
		$colsp = '';
		$lastKey = '';
		$c = 1;
		$ccount = 0;
#		t3lib_utility_Debug::debug($this->fieldArray);
		// Traverse field array which contains the data to present:
		foreach ($this->fieldArray as $vKey) {
			if (isset($data[$vKey])) {
				if ($ccount == 1) {
					$colsp = ' colspan="' . ($this->maxDepth-$level) . '"';
				}

				if ($lastKey) {
					$cssClass = $this->addElement_tdCssClass[$lastKey];
					if ($this->oddColumnsCssClass && $ccount % 2 == 0) {
						$cssClass = implode(' ', array($this->addElement_tdCssClass[$lastKey], $this->oddColumnsCssClass));
					}

					$out .= '
						<td' .
						$noWrap .
						' class="' . $cssClass . '"' .
						$colsp .
						$this->addElement_tdParams[$lastKey] .
						'>' . $data[$lastKey] . '</td>';
				}
				$lastKey = $vKey;
				$c = 1;
				$ccount++;
			} else {
				if (!$lastKey) {
					$lastKey = $vKey;
				}
				$c++;
			}

			if (count($data) == 1) {
				$c++;
			}

			if ($c > 1) {
				$colsp = ' colspan="' . ($c+$this->maxDepth) . '"';
			} else {
				$colsp = '';
			}
		}

		if ($lastKey) {
			$cssClass = $this->addElement_tdCssClass[$lastKey];
			if ($this->oddColumnsCssClass) {
				$cssClass = implode(' ', array($this->addElement_tdCssClass[$lastKey], $this->oddColumnsCssClass));
			}

			$out .= '
				<td' . $noWrap . ' class="' . $cssClass . '"' . $colsp . $this->addElement_tdParams[$lastKey] . '>' . $data[$lastKey] . '</td>';
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
	 *
	 * @param	string		Tablename (_FILE for files)
	 * @param	mixed		"destination": can be positive or negative indicating how the paste is done (paste into / paste after)
	 * @param	boolean		If set, then the redirect URL will point back to the current script, but with CB reset.
	 * @return	string
	 */
	function pasteUrl($table, $uid, $pid, $setRedirect = 1) {
		$rU = $this->backPath . ($table == '_FILE' ? 'tce_file.php' : 'tce_db.php') . '?' .
			($setRedirect ? 'redirect=' . rawurlencode('mod.php?M=web_list&id=' . $pid) : '') .
			'&vC=' . $GLOBALS['BE_USER']->veriCode() .
			'&prErr=1&uPT=1' .
			'&CB[paste]=' . rawurlencode($table . '|' . $uid) .
			'&CB[pad]=normal' .
			t3lib_BEfunc::getUrlToken('tceAction');
		return $rU;
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/xclass/class.ux_db_list_extra.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/xclass/class.ux_db_list_extra.php']);
}