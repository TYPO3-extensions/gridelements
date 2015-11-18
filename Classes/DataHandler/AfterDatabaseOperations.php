<?php
namespace GridElementsTeam\Gridelements\DataHandler;

/**
 * Class/Function which offers TCE main hook functions.
 *
 * @author         Jo Hasenau <info@cybercraft.de>
 * @package        TYPO3
 * @subpackage     tx_gridelements
 */
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Jo Hasenau <info@cybercraft.de>
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
class AfterDatabaseOperations extends AbstractDataHandler {

	/**
	 * Function to set the colPos of an element depending on
	 * whether it is a child of a parent container or not
	 * will set colPos according to availability of the current grid column of an element
	 * 0 = no column at all
	 * -1 = grid element column
	 * -2 = non used elements column
	 * changes are applied to the field array of the parent object by reference
	 *
	 * @param    array $fieldArray : The array of fields and values that have been saved to the datamap
	 * @param    string $table : The name of the table the data should be saved to
	 * @param    integer $id : The parent uid of either the page or the container we are currently working on
	 * @param    \TYPO3\CMS\Core\DataHandling\DataHandler $parentObj : The parent object that triggered this hook
	 *
	 * @return void
	 */
	public function execute_afterDatabaseOperations(&$fieldArray, $table, $id, &$parentObj) {
		if ($table === 'tt_content') {
			$this->init($table, $id, $parentObj);
			if (!$this->getTceMain()->isImporting) {
				$this->saveCleanedUpFieldArray($fieldArray);
			}
		}
	}

	/**
	 * save cleaned up field array
	 *
	 * @param array $changedFieldArray
	 *
	 * @return array cleaned up field array
	 */
	public function saveCleanedUpFieldArray(array $changedFieldArray) {
		unset($changedFieldArray['pi_flexform']);
		if ((isset($changedFieldArray['tx_gridelements_backend_layout']) && $this->getTable() === 'tt_content') || (isset($changedFieldArray['backend_layout']) && $this->getTable() == 'pages') || (isset($changedFieldArray['backend_layout_next_level']) && $this->getTable() == 'pages')) {
			$this->setUnusedElements($changedFieldArray);
		}
	}

	/**
	 * Function to move elements to/from the unused elements column while changing the layout of a page or a grid element
	 *
	 * @param    array $fieldArray : The array of fields and values that have been saved to the datamap
	 *                             return void
	 */
	public function setUnusedElements(&$fieldArray) {
		$changedGridElements = array();
		$changedElements = array();
		$changedSubPageElements = array();

		if ($this->getTable() === 'tt_content') {
			$changedGridElements[$this->getPageUid()] = TRUE;
			$childElementsInUnavailableColumns = array();
			$childElementsInAvailableColumns = array();
			$availableColumns = $this->getAvailableColumns($fieldArray['tx_gridelements_backend_layout'], 'tt_content', $this->getPageUid());
			if (!empty($availableColumns) || $availableColumns === '0') {
				$childElementsInUnavailableColumns = array_keys($this->databaseConnection->exec_SELECTgetRows('uid', 'tt_content', 'tx_gridelements_container = ' . $this->getPageUid() . '
					AND tx_gridelements_columns NOT IN (' . $availableColumns . ')', '', '', '', 'uid'));
				if (count($childElementsInUnavailableColumns) > 0) {
					$this->databaseConnection->sql_query('
						UPDATE tt_content
						SET colPos = -2, backupColPos = -1
						WHERE uid IN (' . join(',', $childElementsInUnavailableColumns) . ')
					');
					array_flip($childElementsInUnavailableColumns);
				}

				$childElementsInAvailableColumns = array_keys($this->databaseConnection->exec_SELECTgetRows('uid', 'tt_content', 'tx_gridelements_container = ' . $this->getPageUid() . '
						AND tx_gridelements_columns IN (' . $availableColumns . ')', '', '', '', 'uid'));
				if (count($childElementsInAvailableColumns) > 0) {
					$this->databaseConnection->sql_query('
						UPDATE tt_content
						SET colPos = -1, backupColPos = -2
						WHERE uid IN (' . join(',', $childElementsInAvailableColumns) . ')
					');
					array_flip($childElementsInAvailableColumns);
				}
			}
			$changedGridElements = array_merge($changedGridElements, $childElementsInUnavailableColumns, $childElementsInAvailableColumns);
		}

		if ($this->getTable() === 'pages') {
			$rootline = BackendUtility::BEgetRootLine($this->getPageUid());
			for ($i = count($rootline); $i > 0; $i--) {
				$page = $this->databaseConnection->exec_SELECTgetSingleRow('uid, backend_layout, backend_layout_next_level', 'pages', 'uid=' . (int)$rootline[$i]['uid']);
				$selectedBackendLayoutNextLevel = (int)$page['backend_layout_next_level'];
				if ($page['uid'] === $this->getPageUid()) {
					if ($fieldArray['backend_layout_next_level'] !== 0) {
						// Backend layout for subpages of the current page is set
						$backendLayoutNextLevelUid = (int)$fieldArray['backend_layout_next_level'];
					}
					if ($fieldArray['backend_layout'] !== 0) {
						// Backend layout for current page is set
						$backendLayoutUid = $fieldArray['backend_layout'];
						break;
					}
				} else if ($selectedBackendLayoutNextLevel === -1 && $page['uid'] !== $this->getPageUid()) {
					// Some previous page in our rootline sets layout_next to "None"
					break;
				} else if ($selectedBackendLayoutNextLevel > 0 && $page['uid'] !== $this->getPageUid()) {
					// Some previous page in our rootline sets some backend_layout, use it
					$backendLayoutUid = $selectedBackendLayoutNextLevel;
					break;
				}
			}

			if (isset($fieldArray['backend_layout'])) {
				$availableColumns = $this->getAvailableColumns($backendLayoutUid, 'pages', $this->getPageUid());
				$elementsInUnavailableColumns = array_keys($this->databaseConnection->exec_SELECTgetRows('uid', 'tt_content', 'pid = ' . $this->getPageUid() . '
						AND colPos NOT IN (' . $availableColumns . ')', '', '', '', 'uid'));
				if (count($elementsInUnavailableColumns) > 0) {
					$this->databaseConnection->sql_query('
						UPDATE tt_content
						SET backupColPos = colPos, colPos = -2
						WHERE uid IN (' . join(',', $elementsInUnavailableColumns) . ')
					');
					array_flip($elementsInUnavailableColumns);
				} else {
					$elementsInUnavailableColumns = array();
				}

				$elementsInAvailableColumns = array_keys($this->databaseConnection->exec_SELECTgetRows('uid', 'tt_content', 'pid = ' . $this->getPageUid() . '
						AND pid = ' . $this->getPageUid() . '
						AND backupColPos != -2
						AND backupColPos IN (' . $availableColumns . ')', '', '', '', 'uid'));
				if (count($elementsInAvailableColumns) > 0) {
					$this->databaseConnection->sql_query('
						UPDATE tt_content
						SET colPos = backupColPos, backupColPos = -2
						WHERE uid IN (' . join(',', $elementsInAvailableColumns) . ')
					');
					array_flip($elementsInAvailableColumns);
				} else {
					$elementsInAvailableColumns = array();
				}

				$changedElements = array_merge($elementsInUnavailableColumns, $elementsInAvailableColumns);
			}

			if (isset($fieldArray['backend_layout_next_level'])) {
				$backendLayoutUid = $backendLayoutNextLevelUid ? $backendLayoutNextLevelUid : $backendLayoutUid;
				$subpages = array();
				$this->getSubpagesRecursively($this->getPageUid(), $subpages);
				if (count($subpages)) {
					$changedSubPageElements = array();
					foreach ($subpages as $page) {
						$availableColumns = $this->getAvailableColumns($backendLayoutUid, 'pages', $page['uid']);
						$subPageElementsInUnavailableColumns = array_keys($this->databaseConnection->exec_SELECTgetRows('uid', 'tt_content', 'pid = ' . $page['uid'] . '
								AND colPos NOT IN (' . $availableColumns . ')', '', '', '', 'uid'));
						if (count($subPageElementsInUnavailableColumns) > 0) {
							$this->databaseConnection->sql_query('
								UPDATE tt_content
								SET backupColPos = colPos, colPos = -2
								WHERE uid IN (' . join(',', $subPageElementsInUnavailableColumns) . ')
							');
							array_flip($subPageElementsInUnavailableColumns);
						} else {
							$subPageElementsInUnavailableColumns = array();
						}

						$subPageElementsInAvailableColumns = array_keys($this->databaseConnection->exec_SELECTgetRows('uid', 'tt_content', 'pid = ' . (int)$page['uid'] . '
								AND backupColPos != -2
								AND backupColPos IN (' . $availableColumns . ')', '', '', '', 'uid'));
						if (count($subPageElementsInAvailableColumns) > 0) {
							$this->databaseConnection->sql_query('
								UPDATE tt_content
								SET colPos = backupColPos, backupColPos = -2
								WHERE uid IN (' . join(',', $subPageElementsInAvailableColumns) . ')
							');
							array_flip($subPageElementsInAvailableColumns);
						} else {
							$subPageElementsInAvailableColumns = array();
						}

						$changedPageElements = array_merge($subPageElementsInUnavailableColumns, $subPageElementsInAvailableColumns);
						$changedSubPageElements = array_merge($changedSubPageElements, $changedPageElements);
					}
				}
			}
		}

		$changedElementUids = array_merge($changedGridElements, $changedElements, $changedSubPageElements);
		if (count($changedElementUids) > 0) {
			foreach ($changedElementUids as $uid => $value) {
				$this->dataHandler->updateRefIndex('tt_content', $uid);
			}
		}
	}

	/**
	 * gets all subpages of the current page and traverses recursivley unless backend_layout_next_level is set or unset (!= 0)
	 *
	 * @param $pageUid
	 * @param $subpages
	 *
	 * @internal param int $id : the uid of the parent page
	 * @return  array   $subpages: Reference to a list of all subpages
	 */
	public function getSubpagesRecursively($pageUid, &$subpages) {
		$childPages = $this->databaseConnection->exec_SELECTgetRows('uid, backend_layout, backend_layout_next_level', 'pages', 'pid = ' . $pageUid);

		if (count($childPages)) {
			foreach ($childPages as $page) {
				if (empty($page['backend_layout'])) {
					$subpages[] = $page;
				}
				if (empty($page['backend_layout_next_level'])) {
					$this->getSubpagesRecursively($page['uid'], $subpages);
				}
			}
		}
	}

	/**
	 * fetches all available columns for a certain grid container based on TCA settings and layout records
	 *
	 * @param   string $layout : The selected backend layout of the grid container or the page
	 * @param   string $table : The name of the table to get the layout for
	 * @param   int $id : the uid of the parent container - being the page id for the table "pages"
	 *
	 * @return  CSV     $tcaColumns: The columns available for the selected layout as CSV list
	 *
	 */
	public function getAvailableColumns($layout = '', $table = '', $id = 0) {
		$tcaColumns = array();

		if ($layout && $table === 'tt_content') {
			$tcaColumns = $this->layoutSetup->getLayoutColumns($layout);
			$tcaColumns = $tcaColumns['CSV'];
		} else if ($table === 'pages') {
			$tcaColumns = GeneralUtility::callUserFunction('TYPO3\\CMS\\Backend\\View\\BackendLayoutView->getColPosListItemsParsed', $id, $this);
			$temp = array();
			foreach ($tcaColumns AS $item) {
				if (trim($item[1]) !== '') {
					$temp[] = (int)$item[1];
				}
			}
			// Implode into a CSV string as BackendLayoutView->getColPosListItemsParsed returns an array
			$tcaColumns = '-2,-1,' . implode(',', $temp);
		}

		return $tcaColumns;
	}

}