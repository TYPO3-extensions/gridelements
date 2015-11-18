<?php
namespace GridElementsTeam\Gridelements\Hooks;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Jo Hasenau <info@cybercraft.de>, Dirk Hoffmann <hoffmann@vmd-jena.de>
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

use GridElementsTeam\Gridelements\Helper\Helper;
use TYPO3\CMS\Recordlist\RecordList\RecordListHookInterface;

/**
 * Class/Function which offers TCE main hook functions.
 *
 * @author         Jo Hasenau <info@cybercraft.de>
 * @author         Dirk Hoffmann <hoffmann@vmd-jena.de>
 * @package        TYPO3
 * @subpackage     tx_gridelements
 */
class DatabaseRecordList implements RecordListHookInterface {

	/**
	 * modifies Web>List clip icons (copy, cut, paste, etc.) of a displayed row
	 *
	 * @param    string $table the current database table
	 * @param    array $row the current record row
	 * @param    array $cells the default clip-icons to get modified
	 * @param    object $parentObject Instance of calling object
	 *
	 * @return    array        the modified clip-icons
	 */
	public function makeClip($table, $row, $cells, &$parentObject) {

		/*if ($table == 'tt_content' && get_class($parentObject) == 'localRecordList') {
			if ((int)$row['colPos'] < 0)) {
				$cells['pasteInto'] = $parentObject->spaceIcon;
				$cells['pasteAfter'] = $parentObject->spaceIcon;
			}
		}*/
		if ($table === 'tt_content') {
			$cells['moveUp'] = '';
		}

		return $cells;
	}

	/**
	 * modifies Web>List control icons of a displayed row
	 *
	 * @param    string $table the current database table
	 * @param    array $row the current record row
	 * @param    array $cells the default control-icons to get modified
	 * @param    object $parentObject Instance of calling object
	 *
	 * @return    array        the modified control-icons
	 */
	public function makeControl($table, $row, $cells, &$parentObject) {
		/*if ($table == 'tt_content' && get_class($parentObject) == 'localRecordList') {
			if ((int)$row['colPos'] < 0) {
				$cells['move'] = $parentObject->spaceIcon;
				$cells['new'] = $parentObject->spaceIcon;
				$cells['moveUp'] = $parentObject->spaceIcon;
				$cells['moveDown'] = $parentObject->spaceIcon;
			}
		}*/

		return $cells;
	}

	/**
	 * modifies Web>List header row columns/cells
	 *
	 * @param    string $table the current database table
	 * @param    array $currentIdList Array of the currently displayed uids of the table
	 * @param    array $headerColumns An array of rendered cells/columns
	 * @param    object $parentObject Instance of calling (parent) object
	 *
	 * @return    array        Array of modified cells/columns
	 */
	public function renderListHeader($table, $currentIdList, $headerColumns, &$parentObject) {
		return $headerColumns;
	}

	/**
	 * modifies Web>List header row clipboard/action icons
	 *
	 * @param    string $table the current database table
	 * @param    array $currentIdList Array of the currently displayed uids of the table
	 * @param    array $cells An array of the current clipboard/action icons
	 * @param    object $parentObject Instance of calling (parent) object
	 *
	 * @return    array        Array of modified clipboard/action icons
	 */
	public function renderListHeaderActions($table, $currentIdList, $cells, &$parentObject) {
		return $cells;
	}

	/**
	 * check if current row has child elements and add info to $theData array
	 *
	 * @param string $table
	 * @param array $row
	 * @param int $level
	 * @param array $theData
	 * @param object $parentObject
	 *
	 * @return void
	 */
	public function checkChildren($table, $row, $level, &$theData, &$parentObject) {
		if ($table === 'tt_content' && $row['CType'] === 'gridelements_pi1') {
			$elementChildren = Helper::getInstance()
					->getChildren($table, $row['uid']);
			if (count($elementChildren) > 0) {
				$theData['_EXPANDABLE_'] = TRUE;
				$theData['_EXPAND_ID_'] = $table . ':' . $row['uid'];
				$theData['_EXPAND_TABLE_'] = $table;
				$theData['_LEVEL_'] = $level;
			}
		}
	}

	/**
	 * return content collapse icon
	 *
	 * @param array $data
	 * @param string $sortField
	 * @param int $level
	 * @param string $contentCollapseIcon
	 * @param object $parentObject
	 *
	 * @return void
	 */
	public function contentCollapseIcon($data, $sortField, $level, &$contentCollapseIcon, &$parentObject) {
		if ($data['_EXPAND_TABLE_'] === 'tt_content') {
			$contentCollapseIcon = '
				<a href="javascript:GridElementsListView.elExpandCollapse(\'' . $data['_EXPAND_ID_'] . '\',\'' . $sortField . '\', ' . $level . ')" title="' . $GLOBALS['LANG']->sL('LLL:EXT:gridelements/Resources/Private/Language/locallang.xml:list.collapseElement', TRUE) . '" rel="' . $data['_EXPAND_ID_'] . '">
					<span class="t3-icon t3-icon-actions t3-icon-actions-view t3-icon-pagetree-collapse collapseIcon">&nbsp;</span>
				</a>
			';
		}
	}

}
