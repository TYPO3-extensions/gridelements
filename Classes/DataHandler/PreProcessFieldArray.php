<?php
namespace GridElementsTeam\Gridelements\DataHandler;

/**
 * Class/Function which offers TCE main hook functions.
 *
 * @author		Jo Hasenau <info@cybercraft.de>
 * @package		TYPO3
 * @subpackage	tx_gridelements
 */
use GridElementsTeam\Gridelements\DataHandler\AbstractDataHandler;
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

class PreProcessFieldArray extends AbstractDataHandler {

	/**
	 * Function to set the colPos of an element depending on
	 * whether it is a child of a parent container or not
	 * will set colPos according to availability of the current grid column of an element
	 * 0 = no column at all
	 * -1 = grid element column
	 * -2 = non used elements column
	 * changes are applied to the field array of the parent object by reference
	 *
	 * @param	array $fieldArray: The array of fields and values that have been saved to the datamap
	 * @param	string $table: The name of the table the data should be saved to
	 * @param	integer $id: The parent uid of either the page or the container we are currently working on
	 * @param	\TYPO3\CMS\Core\DataHandling\DataHandler $parentObj: The parent object that triggered this hook
	 * @return void
	 */
	public function execute_preProcessFieldArray(&$fieldArray, $table, $id, &$parentObj) {
		$this->init($table, $id, $parentObj);
		if(!$this->getTceMain()->isImporting) {
			$this->saveCleanedUpFieldArray($fieldArray);
			$this->processFieldArrayForTtContent($fieldArray);
		}
	}

	/**
	 * save cleaned up field array
	 *
	 * @param array $fieldArray
	 * @return array cleaned up field array
	 */
	public function saveCleanedUpFieldArray(array $fieldArray) {
		unset($fieldArray['pi_flexform']);
		$changedFieldArray = $this->dataHandler->compareFieldArrayWithCurrentAndUnset($this->getTable(), $this->getPageUid(), $fieldArray);
		if ((isset($changedFieldArray['tx_gridelements_backend_layout']) && $this->getTable() == 'tt_content') || (isset($changedFieldArray['backend_layout']) && $this->getTable() == 'pages') || (isset($changedFieldArray['backend_layout_next_level']) && $this->getTable() == 'pages')) {
			$this->setUnusedElements($changedFieldArray);
		}
	}

	/**
	 * process field array for table tt_content
	 *
	 * @param array $fieldArray
	 * @return void
	 */
	public function processFieldArrayForTtContent(array &$fieldArray) {
		if ($this->getTable() == 'tt_content') {
			$pid = (int)\TYPO3\CMS\Core\Utility\GeneralUtility::_GET('DDinsertNew');

			if(abs($pid) > 0) {
				$this->setDefaultFieldValues($fieldArray, $pid);
				$this->getDefaultFlexformValues($fieldArray);
			}
		}
		$this->setFieldEntries($fieldArray, $pid);
	}

	/**
	 * set default field values for new records
	 *
	 * @param array $fieldArray
	 * @param int $pid
	 * @return void
	 */

	public function setDefaultFieldValues(&$fieldArray, $pid = 0) {
		// Default values:
		$newRow = array(); // Used to store default values as found here:

		// Default values as set in userTS:
		$TCAdefaultOverride = $GLOBALS['BE_USER']->getTSConfigProp('TCAdefaults');
		if (is_array($TCAdefaultOverride['tt_content.'])) {
			foreach ($TCAdefaultOverride['tt_content.'] as $theF => $theV) {
				if (isset($GLOBALS['TCA']['tt_content']['columns'][$theF])) {
					$newRow[$theF] = $theV;
				}
			}
		}

		if ($pid < 0) {
			$record = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('tt_content', abs($pid), 'pid');
			$id = $record['pid'];
			unset($record);
		} else {
			$id = (int)$pid;
		}

		$pageTS = \TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig($id);

		if (isset($pageTS['TCAdefaults.'])) {
			$TCAPageTSOverride = $pageTS['TCAdefaults.'];
			if (is_array($TCAPageTSOverride['tt_content.'])) {
				foreach ($TCAPageTSOverride['tt_content.'] as $theF => $theV) {
					if (isset($GLOBALS['TCA']['tt_content']['columns'][$theF])) {
						$newRow[$theF] = $theV;
					}
				}
			}
		}

		// Default values as submitted:
		$this->defVals = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('defVals');
		$this->overrideVals = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('overrideVals');
		if (!is_array($this->defVals) && is_array($this->overrideVals))	{
			$this->defVals = $this->overrideVals;
		}
		if (is_array($this->defVals['tt_content'])) {
			foreach ($this->defVals['tt_content'] as $theF => $theV) {
				if (isset($GLOBALS['TCA']['tt_content']['columns'][$theF])) {
					$newRow[$theF] = $theV;
				}
			}
		}

		// Fetch default values if a previous record exists
		if ($pid < 0 && $GLOBALS['TCA']['tt_content']['ctrl']['useColumnsForDefaultValues']) {
			// Fetches the previous record:
			$res = exec_SELECTquery('*', 'tt_content', 'uid=' . abs($id) . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('tt_content'));
			if ($row = $this->databaseConnection->sql_fetch_assoc($res)) {
				// Gets the list of fields to copy from the previous record.
				$fArr = explode(',', $GLOBALS['TCA']['tt_content']['ctrl']['useColumnsForDefaultValues']);
				foreach ($fArr as $theF) {
					$theF = trim($theF);
					if($theF === '') {
						continue;
					}
					if (isset($GLOBALS['TCA']['tt_content']['columns'][$theF])) {
						$newRow[$theF] = $row[$theF];
					}
				}
			}
		}
		$fieldArray = array_merge($newRow, $fieldArray);
	}

	/**
	 * checks for default flexform values for new records and sets them accordingly
	 *
	 * @param array $fieldArray
	 * @return void
	 */

	public function getDefaultFlexformValues(&$fieldArray) {
		foreach($GLOBALS['TCA']['tt_content']['columns']['pi_flexform']['config']['ds'] as $key => $dataStructure) {
			$types = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $key);
			if(($types[0] == $fieldArray['list_type'] || $types[0] == '*') && ($types[1] == $fieldArray['CType'] || $types[1] == '*')) {
				$fieldArray['pi_flexform'] = $this->extractDefaultDataFromDatastructure($dataStructure);
			}
		}
	}

	/**
	 * extracts the default data out of a given XML data structure
	 *
	 * @param string $dataStructure
	 * @return string $defaultData
	 */

	public function extractDefaultDataFromDataStructure($dataStructure) {
		$returnXML = '';
		$sheetArray = array();
		if($dataStructure) {
			$structureArray = \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($dataStructure);
			if(!isset($structureArray['sheets']) && isset($structureArray['ROOT'])) {
				$structureArray['sheets']['sDEF']['ROOT'] = $structureArray['ROOT'];
				unset($structureArray['ROOT']);
			}
			if(isset($structureArray['sheets']) && count($structureArray['sheets']) > 0) {
				foreach($structureArray['sheets'] as $sheetName => $sheet) {
					if(is_array($sheet['ROOT']['el']) && count($sheet['ROOT']['el']) > 0) {
						$elArray = array();
						foreach($sheet['ROOT']['el'] as $elName => $elConf) {
							$config = $elConf['TCEforms']['config'];
							$elArray[$elName]['vDEF'] = $config['default'];
							if(!$elArray[$elName]['vDEF'] && $config['type'] == 'select' && count($config['items']) > 0) {
								$elArray[$elName]['vDEF'] = $config['items'][0][1];
							}
						}
						$sheetArray['data'][$sheetName]['lDEF'] = $elArray;
					}
				};
			}
			if(count($sheetArray) > 0) {
				$flexformTools = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools');
				$returnXML = $flexformTools->flexArray2Xml($sheetArray, TRUE);
			}
		}
		return $returnXML;
	}

	/**
	 * set initial entries to field array
	 *
	 * @param array $fieldArray
	 * @param integer $pid
	 * @return void
	 */
	public function setFieldEntries(array &$fieldArray, $pid) {
		if ($pid > 0) {
			$this->setFieldEntriesForTargets($fieldArray, $pid);
		} else if ((int)$fieldArray['tx_gridelements_container'] > 0 && strpos(key($this->getTceMain()->datamap['tt_content']), 'NEW') !== FALSE) {
			$containerUpdateArray[(int)$fieldArray['tx_gridelements_container']] = 1;
			$this->doGridContainerUpdate($containerUpdateArray);
		}
		$this->setFieldEntriesForGridContainers($fieldArray);
	}

	/**
	 * set initial entries to field array
	 *
	 * @param array $fieldArray
	 * @param integer $pid
	 * @return void
	 */
	public function setFieldEntriesForTargets(array &$fieldArray, $pid) {
		if (count($fieldArray) && strpos($fieldArray['pid'], 'x') !== FALSE) {
			$target = explode('x', $fieldArray['pid']);
			$fieldArray['pid'] = $pid;
			$targetUid = abs((int)$target[0]);
			$this->setFieldEntriesForColumnTargets($fieldArray, $targetUid, $target);
		} else {
			$this->setFieldEntriesForSimpleTargets($fieldArray);
		}
	}

	/**
	 * set entries to column targets
	 *
	 * @param array $fieldArray
	 * @param integer $targetUid
	 * @param array $target
	 * @return void
	 */
	public function setFieldEntriesForColumnTargets(array &$fieldArray, $targetUid, array $target) {
		if ($targetUid != $this->getPageUid()) {
			$fieldArray['colPos'] = -1;
			$fieldArray['sorting'] = 0;
			$fieldArray['tx_gridelements_container'] = $targetUid;
			$fieldArray['tx_gridelements_columns'] = (int)$target[1];
			$containerUpdateArray[$targetUid] = 1;
			$this->doGridContainerUpdate($containerUpdateArray);
		} else {
			$fieldArray['colPos'] = (int)$target[1];
			$fieldArray['sorting'] = 0;
			$fieldArray['tx_gridelements_container'] = 0;
			$fieldArray['tx_gridelements_columns'] = 0;
		}
	}

	/**
	 * set entries to simple targets
	 *
	 * @param array $fieldArray
	 * @return void
	 */
	public function setFieldEntriesForSimpleTargets(array &$fieldArray) {
		$targetElement = $this->databaseConnection->exec_SELECTgetSingleRow(
			'*',
			'tt_content',
			'uid=' . abs($fieldArray['pid'])
		);
		if ($targetElement['uid']) {
			if ($targetElement['tx_gridelements_container'] > 0) {
				$containerUpdateArray[$targetElement['tx_gridelements_container']] = 1;
				$this->doGridContainerUpdate($containerUpdateArray);
				$fieldArray['tx_gridelements_container'] = $targetElement['tx_gridelements_container'];
				$fieldArray['tx_gridelements_columns'] = $targetElement['tx_gridelements_columns'];
				$fieldArray['colPos'] = -1;
			}
			$fieldArray['colPos'] = $targetElement['colPos'];
			$fieldArray['sorting'] = $targetElement['sorting'] + 2;
		}
	}

	/**
	 * set/override entries to gridelements container
	 *
	 * @param array $fieldArray
	 * @return void
	 */
	public function setFieldEntriesForGridContainers(array &$fieldArray) {
		if ((int)$fieldArray['tx_gridelements_container'] > 0 && isset($fieldArray['colPos']) && (int)$fieldArray['colPos'] != -1) {
			$fieldArray['colPos'] = -1;
			$fieldArray['tx_gridelements_columns'] = 0;
		} else if (isset($fieldArray['tx_gridelements_container']) && (int)$fieldArray['tx_gridelements_container'] === 0 && (int)$fieldArray['colPos'] === -1) {
			$originalContainer = $this->databaseConnection->exec_SELECTgetSingleRow(
				'tx_gridelements_container',
				'tt_content',
				'uid=' . $this->getPageUid()
			);
			$containerUpdateArray[$originalContainer['tx_gridelements_container']] = -1;
			$this->doGridContainerUpdate($containerUpdateArray);

			$fieldArray['colPos'] = $this->checkForRootColumn((int)$this->getPageUid());
			$fieldArray['tx_gridelements_columns'] = 0;
		}
	}

	/**
	 * Function to move elements to/from the unused elements column while changing the layout of a page or a grid element
	 *
	 * @param	array $fieldArray: The array of fields and values that have been saved to the datamap
	 * return void
	 */
	public function setUnusedElements(&$fieldArray) {
		$changedGridElements = array();
		$changedElements = array();
		$changedSubPageElements = array();

		if ($this->getTable() == 'tt_content') {
			$changedGridElements[$this->getPageUid()] = TRUE;
			$availableColumns = $this->getAvailableColumns($fieldArray['tx_gridelements_backend_layout'], 'tt_content', $this->getPageUid());
			$childElementsInUnavailableColumns = array_keys(
				$this->databaseConnection->exec_SELECTgetRows(
					'uid',
					'tt_content',
					'tx_gridelements_container = ' . $this->getPageUid() . '
					AND tx_gridelements_columns NOT IN (' . $availableColumns . ')',
					'',
					'',
					'',
					'uid'
				)
			);
			if(count($childElementsInUnavailableColumns) > 0) {
				$this->databaseConnection->sql_query('
					UPDATE tt_content
					SET colPos = -2, backupColPos = -1
					WHERE uid IN (' . join(',', $childElementsInUnavailableColumns) . ')
				');
				array_flip($childElementsInUnavailableColumns);
			} else {
				$childElementsInUnavailableColumns = array();
			}

			$childElementsInAvailableColumns = array_keys(
				$this->databaseConnection->exec_SELECTgetRows(
					'uid',
					'tt_content',
					'tx_gridelements_container = ' . $this->getPageUid() . '
					AND tx_gridelements_columns IN (' . $availableColumns . ')',
					'',
					'',
					'',
					'uid'
				)
			);
			if(count($childElementsInAvailableColumns) > 0) {
				$this->databaseConnection->sql_query('
					UPDATE tt_content
					SET colPos = -1, backupColPos = -2
					WHERE uid IN (' . join(',', $childElementsInAvailableColumns) . ')
				');
				array_flip($childElementsInAvailableColumns);
			} else {
				$childElementsInAvailableColumns = array();
			}

			$changedGridElements = array_merge(
				$changedGridElements,
				$childElementsInUnavailableColumns,
				$childElementsInAvailableColumns
			);
		}

		if ($this->getTable() == 'pages') {
			$rootline = $this->beFunc->BEgetRootLine($this->getPageUid());
			for ($i = count($rootline); $i > 0; $i--) {
				$page = $this->databaseConnection->exec_SELECTgetSingleRow(
					'uid, backend_layout, backend_layout_next_level',
					'pages',
					'uid=' . (int)$rootline[$i]['uid']
				);
				$selectedBackendLayoutNextLevel = (int)$page['backend_layout_next_level'];
				if ($page['uid'] == $this->getPageUid()) {
					if ($fieldArray['backend_layout_next_level'] != 0) {
						// Backend layout for subpages of the current page is set
						$backendLayoutNextLevelUid = (int)$fieldArray['backend_layout_next_level'];
					}
					if ($fieldArray['backend_layout'] != 0) {
						// Backend layout for current page is set
						$backendLayoutUid = $fieldArray['backend_layout'];
						break;
					}
				} else if ($selectedBackendLayoutNextLevel == -1 && $page['uid'] != $this->getPageUid()) {
					// Some previous page in our rootline sets layout_next to "None"
					break;
				} else if ($selectedBackendLayoutNextLevel > 0 && $page['uid'] != $this->getPageUid()) {
					// Some previous page in our rootline sets some backend_layout, use it
					$backendLayoutUid = $selectedBackendLayoutNextLevel;
					break;
				}
			}

			if (isset($fieldArray['backend_layout'])) {
                $availableColumns = $this->getAvailableColumns($backendLayoutUid, 'pages', $this->getPageUid());
				$elementsInUnavailableColumns = array_keys(
					$this->databaseConnection->exec_SELECTgetRows(
						'uid',
						'tt_content',
						'pid = ' . $this->getPageUid() . '
						AND colPos NOT IN (' . $availableColumns . ')',
						'',
						'',
						'',
						'uid'
					)
				);
				if(count($elementsInUnavailableColumns) > 0) {
					$this->databaseConnection->sql_query('
						UPDATE tt_content
						SET backupColPos = colPos, colPos = -2
						WHERE uid IN (' . join(',', $elementsInUnavailableColumns) . ')
					');
					array_flip($elementsInUnavailableColumns);
				} else {
					$elementsInUnavailableColumns = array();
				}

				$elementsInAvailableColumns = array_keys(
					$this->databaseConnection->exec_SELECTgetRows(
						'uid',
						'tt_content',
						'pid = ' . $this->getPageUid() . '
						AND pid = ' . $this->getPageUid() . '
						AND backupColPos != -2
						AND backupColPos IN (' . $availableColumns . ')',
						'',
						'',
						'',
						'uid'
					)
				);
				if(count($childElementsInAvailableColumns) > 0) {
					$this->databaseConnection->sql_query('
						UPDATE tt_content
						SET colPos = backupColPos, backupColPos = -2
						WHERE uid IN (' . join(',', $elementsInAvailableColumns) . ')
					');
					array_flip($elementsInAvailableColumns);
				} else {
					$elementsInAvailableColumns = array();
				}

				$changedElements = array_merge(
					$elementsInUnavailableColumns,
					$elementsInAvailableColumns
				);
			}

			if (isset($fieldArray['backend_layout_next_level'])) {
				$backendLayoutUid = $backendLayoutNextLevelUid ? $backendLayoutNextLevelUid : $backendLayoutUid;
				$subpages = array();
				$this->getSubpagesRecursively($this->getPageUid(), $subpages);
				if (count($subpages)) {
					$changedSubPageElements = array();
					foreach ($subpages as $page) {
						$availableColumns = $this->getAvailableColumns($backendLayoutUid, 'pages', $page['uid']);
						$subPageElementsInUnavailableColumns = array_keys(
							$this->databaseConnection->exec_SELECTgetRows(
								'uid',
								'tt_content',
								'pid = ' . $page['uid'] . '
								AND colPos NOT IN (' . $availableColumns . ')',
								'',
								'',
								'',
								'uid'
							)
						);
						if(count($subPageElementsInUnavailableColumns) > 0) {
							$this->databaseConnection->sql_query('
								UPDATE tt_content
								SET backupColPos = colPos, colPos = -2
								WHERE uid IN (' . join(',', $subPageElementsInUnavailableColumns) . ')
							');
							array_flip($subPageElementsInUnavailableColumns);
						} else {
							$subPageElementsInUnavailableColumns = array();
						}

						$subPageElementsInAvailableColumns = array_keys(
							$this->databaseConnection->exec_SELECTgetRows(
								'uid',
								'tt_content',
								'pid = ' . $page['uid'] . '
								AND backupColPos != -2
								AND backupColPos IN (' . $availableColumns . ')',
								'',
								'',
								'',
								'uid'
							)
						);
						if(count($subPageElementsInAvailableColumns) > 0) {
							$this->databaseConnection->sql_query('
								UPDATE tt_content
								SET colPos = backupColPos, backupColPos = -2
								WHERE uid IN (' . join(',', $subPageElementsInAvailableColumns) . ')
							');
							array_flip($subPageElementsInAvailableColumns);
						} else {
							$subPageElementsInAvailableColumns = array();
						}

						$changedPageElements = array_merge(
							$subPageElementsInUnavailableColumns,
							$subPageElementsInAvailableColumns
						);
						$changedSubPageElements = array_merge(
							$changedSubPageElements,
							$changedPageElements
						);
					}
				}
			}
		}

		$changedElementUids = array_merge(
			$changedGridElements,
			$changedElements,
			$changedSubPageElements
		);
		if(count($changedElementUids) > 0) {
			foreach($changedElementUids as $uid => $value) {
				$this->dataHandler->updateRefIndex('tt_content', $uid);
			}
		}
	}

	/**
	 * gets all subpages of the current page and traverses recursivley unless backend_layout_next_level is set or unset (!= 0)
	 *
	 * @param $pageUid
	 * @param $subpages
	 * @internal param int $id : the uid of the parent page
	 * @return  array   $subpages: Reference to a list of all subpages
	 */
	public function getSubpagesRecursively($pageUid, &$subpages) {
		$childPages = $this->databaseConnection->exec_SELECTgetRows(
			'uid, backend_layout, backend_layout_next_level',
			'pages',
			'pid = ' . $pageUid
		);

		if (count($childPages)) {
			foreach ($childPages as $page) {
				if ($page['backend_layout'] == 0) {
					$subpages[] = $page;
				}
				if ($page['backend_layout_next_level'] == 0) {
					$this->getSubpagesRecursively($page['uid'], $subpages);
				}
			}
		}
	}

	/**
	 * Function to recursively determine the colPos of the root container
	 * so that an element that has been removed from any container
	 * will still remain in the same major page column
	 *
	 * @param	integer	$contentId: The uid of the current content element
	 * @param	integer $colPos: The current column of this content element
	 * @return integer $colPos: The new column of this content element
	 */
	public function checkForRootColumn($contentId, $colPos = 0) {
		$parent = $this->databaseConnection->exec_SELECTgetSingleRow(
			't1.colPos, t1.tx_gridelements_container',
			'tt_content AS t1, tt_content AS t2',
			't1.uid=t2.tx_gridelements_container AND t2.uid=' . $contentId
		);

		if (count($parent) > 0 && $parent['tx_gridelements_container'] > 0) {
			$colPos = $this->checkForRootColumn($parent['tx_gridelements_container'], $parent['colPos']);
		} else {
			$colPos = (int)$parent['colPos'];
		}

		return $colPos;
	}

	/**
	 * fetches all available columns for a certain grid container based on TCA settings and layout records
	 *
	 * @param   string  $layout: The selected backend layout of the grid container or the page
	 * @param   string  $table: The name of the table to get the layout for
	 * @param   int     $id: the uid of the parent container - being the page id for the table "pages"
	 * @return  CSV     $tcaColumns: The columns available for the selected layout as CSV list
	 *
	 */
	public function getAvailableColumns($layout = '', $table = '', $id = 0) {
		$tcaColumns = array();

		if ($layout && $table == 'tt_content') {
			$tcaColumns = $this->layoutSetup->getLayoutColumns($layout);
			$tcaColumns = $tcaColumns['CSV'];
		} else if ($table == 'pages') {
            $tcaColumns = GeneralUtility::callUserFunction('TYPO3\\CMS\\Backend\\View\\BackendLayoutView->getColPosListItemsParsed', $id, $this);
			$temp = array();
			foreach ($tcaColumns AS $item) {
				if (trim($item[1]) !== '') {
					$temp[] = $item[1];
				}
			}
			// Implode into a CSV string as BackendLayoutView->getColPosListItemsParsed returns an array
			$tcaColumns = '-2,-1,' . implode(',', $temp);
		}

		return $tcaColumns;
	}

}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/Classes/DataHandler/PreProcessFieldArray.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/Classes/DataHandler/PreProcessFieldArray.php']);
}
