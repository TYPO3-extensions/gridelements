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
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Jo Hasenau <info@cybercraft.de>
 *  (c) 2013 Stefan Froemken <froemken@gmail.com>
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
 * @author         Jo Hasenau <info@cybercraft.de>
 * @package        TYPO3
 * @subpackage     tx_gridelements
 */
class MoveRecord extends AbstractDataHandler {

	/**
	 * Function to handle record movement to the first position of a column
	 *
	 * @param string $table : The name of the table we are working on
	 * @param int $uid : The uid of the record that is going to be moved
	 * @param string $destPid : The target the record should be moved to
	 * @param array $propArr : The array of properties for the move action
	 * @param array $moveRec : An array of some values of the record that is going to be moved
	 * @param int $resolvedPid : The calculated id of the page the record should be moved to
	 * @param boolean $recordWasMoved : A switch to tell the parent object, if the record has been moved
	 * @param \TYPO3\CMS\Core\DataHandling\DataHandler $parentObj
	 *
	 * @return void
	 */
	public function execute_moveRecord($table, $uid, &$destPid, &$propArr, &$moveRec, $resolvedPid, &$recordWasMoved, &$parentObj) {
		if ($table === 'tt_content') {
			$targetAvailable = TRUE;
			$record = BackendUtility::getRecordWSOL('tt_content', $uid, 'uid');
			$uid = (int)$record['_ORIG_uid'];

			$this->init($table, $uid, $parentObj);

			if ($table === 'tt_content' && !$this->getTceMain()->isImporting) {
				$cmd = GeneralUtility::_GET('cmd');
				$originalElement = BackendUtility::getRecordWSOL('tt_content', $uid, 'tx_gridelements_container');
				$containerUpdateArray[$originalElement['tx_gridelements_container']] = -1;
				if (strpos($cmd['tt_content'][$uid]['move'], 'x') !== FALSE) {
					$targetAvailable = $this->updateTargetContainerAndResolveTargetId($cmd, $uid, $destPid, $containerUpdateArray);
				}
				if ($targetAvailable === TRUE) {
					$this->doGridContainerUpdate($containerUpdateArray);
				} else {
					$recordWasMoved = TRUE;
				}
			}
		}
	}

	/**
	 * Function to handle record movement after another element
	 *
	 * @param string $table : The name of the table we are working on
	 * @param int $uid : The uid of the record that is going to be moved
	 * @param string $destPid : The resolved target the record should be moved to
	 * @param string $origDestPid : The original target the record should be moved to
	 * @param array $moveRec : An array of some values of the record that is going to be moved
	 * @param array $updateFields : An array of some values of the record that have been updated
	 * @param \TYPO3\CMS\Core\DataHandling\DataHandler $parentObj : The parent object that triggered this hook
	 *
	 */
	public function execute_moveRecord_afterAnotherElementPostProcess($table, $uid, $destPid, $origDestPid, $moveRec, $updateFields, \TYPO3\CMS\Core\DataHandling\DataHandler &$parentObj) {
		if ($table === 'tt_content') {
			$movedRecord = BackendUtility::getRecordWSOL('tt_content', $uid, 'uid,t3ver_oid,t3ver_move_id,l18n_parent');
			$targetElement = BackendUtility::getRecordWSOL('tt_content', -$origDestPid, 'uid,pid,colPos,tx_gridelements_container,tx_gridelements_columns');
			$targetContainer = (int)($targetElement['t3ver_oid'] ? $targetElement['t3ver_oid'] : $targetElement['uid']);

			$this->init($table, $uid, $parentObj);
			$cmd = GeneralUtility::_GET('cmd');

			$originalUid = (int)($movedRecord['_ORIG_uid'] ? $movedRecord['_ORIG_uid'] : $uid);
			$commandUid = key($cmd['tt_content']);
			$placeholderUid = (int)($movedRecord['t3ver_move_id'] ? $movedRecord['t3ver_move_id'] : $uid);

			if (strpos($cmd['tt_content'][$commandUid]['move'], 'x') !== FALSE) {
				$target = explode('x', $cmd['tt_content'][$commandUid]['move']);
				$column = (int)$target[1];
				$sortNumberArray = $this->dataHandler->getSortNumber('tt_content', $originalUid, $targetElement['pid']);
				if (is_array($sortNumberArray)) {
					$sortNumber = $sortNumberArray['sortNumber'];
				} else if (!empty($sortNumberArray)) {
					$sortNumber = $sortNumberArray;
				} else {
					$sortNumber = 0;
				}
				$GLOBALS['TCA']['tt_content']['ctrl']['copyAfterDuplFields'] = str_replace('colPos,', '', $GLOBALS['TCA']['tt_content']['ctrl']['copyAfterDuplFields']);
				if ($uid === -$origDestPid || $commandUid === -$origDestPid || $placeholderUid === -$origDestPid) {
					$updateArray = array(
							'colPos' => $column,
							'sorting' => $sortNumber,
							'tx_gridelements_container' => 0,
							'tx_gridelements_columns' => 0
					);
					$setPid = $targetElement['pid'];
					// $updateArray['header'] = $uid . ' # ' . $pointerUid . ' # ' . $origDestPid . ' mit X equal';
				} else {
					$GLOBALS['TCA']['tt_content']['ctrl']['copyAfterDuplFields'] = str_replace('colPos,', '', $GLOBALS['TCA']['tt_content']['ctrl']['copyAfterDuplFields']);
					$updateArray = array(
							'colPos' => -1,
							'sorting' => $sortNumber,
							'tx_gridelements_container' => $targetContainer,
							'tx_gridelements_columns' => $column
					);
					// $updateArray['header'] = $uid . ' # ' . $pointerUid . ' # ' . $origDestPid . ' mit X unequal';
				}
			} else {
				$updateArray = array(
						'colPos' => $targetElement['colPos'],
						'tx_gridelements_container' => $targetElement['tx_gridelements_container'],
						'tx_gridelements_columns' => $targetElement['tx_gridelements_columns']
				);
				// $updateArray['header'] = $uid . ' # ' . $pointerUid . ' # ' . $origDestPid . ' ohne X';
			}
			// $updateArray['bodytext'] = serialize($cmd) . ' originalUid: ' . $originalUid . ' pointerUid: ' . $pointerUid . ' commandUid: ' . $commandUid . ' placeholderUid: ' . $placeholderUid;
			if ($originalUid !== $uid) {
				$this->getTceMain()->updateDB('tt_content', $originalUid, $updateArray);
			}
			if ($setPid) {
				$updateArray['pid'] = $setPid;
			}
			$this->getTceMain()->updateDB('tt_content', $uid, $updateArray);
		}
	}

	/**
	 * move records to the top of a page or a container column
	 *
	 * @param array $cmd
	 * @param integer $recordUid
	 * @param string $destPid
	 * @param array $containerUpdateArray
	 * @return boolean Will be true, if there is a target page or container
	 */
	public function updateTargetContainerAndResolveTargetId($cmd, $recordUid, &$destPid, &$containerUpdateArray) {
		$target = explode('x', $cmd['tt_content'][$recordUid]['move']);
		$targetUid = abs((int)$target[0]);
		if ((int)$target[0] < 0) {
			$targetContainer = BackendUtility::getRecord('tt_content', $targetUid, 'uid');
			if (!isset($targetContainer['uid'])) {
				return FALSE;
			}
		} else {
			$targetPage = BackendUtility::getRecord('pages', $targetUid, 'uid');
			if (!isset($targetPage['uid'])) {
				return FALSE;
			}
		}
		if ($targetUid !== $recordUid && (int)$target[0] < 0) {
			$containerUpdateArray[$targetUid] += 1;
		}
		$destPid = -$recordUid;
		return TRUE;
	}

	/**
	 * create update array for split elements (tt_content)
	 *
	 * @param int $recordUid
	 * @param         $destPid
	 * @param int $targetUid
	 * @param array $target
	 * @param array $containerUpdateArray
	 *
	 * @return array UpdateArray
	 * @deprecated     Has been deprecated with Gridelements 3.1 and will be removed 2 minor versions later or with the next major version
	 */
	public function createUpdateArrayForSplitElements($recordUid, &$destPid, $targetUid, array $target, array &$containerUpdateArray) {
		GeneralUtility::logDeprecatedFunction();
		$targetElement = BackendUtility::getRecordWSOL('tt_content', $targetUid, 'pid');
		if ($targetUid !== $recordUid && (int)$target[0] < 0) {
			$containerUpdateArray[$targetUid] += 1;
			$column = (int)$target[1];
			$sortNumberArray = $this->dataHandler->getSortNumber('tt_content', $recordUid, $targetElement['pid']);
			$updateArray = array(
					'colPos' => -1,
					'sorting' => $sortNumberArray['sortNumber'],
					'tx_gridelements_container' => $targetUid,
					'tx_gridelements_columns' => $column,
					'pid' => $targetElement['pid']
			);
		} else {
			$sortNumber = $this->dataHandler->getSortNumber('tt_content', $recordUid, $targetElement['pid']);
			$updateArray = array(
					'colPos' => (int)$target[1],
					'sorting' => $sortNumber,
					'tx_gridelements_container' => 0,
					'tx_gridelements_columns' => 0
			);
			if ($targetUid !== $recordUid) {
				$updateArray['pid'] = (int)$target[0];
			}
		}

		$destPid = -$recordUid;

		return $updateArray;
	}

	/**
	 * create update array for split elements (tt_content)
	 *
	 * @param array $originalElement
	 *
	 * @return array UpdateArray
	 * @deprecated     Has been deprecated with Gridelements 3.1 and will be removed 2 minor versions later or with the next major version
	 */
	public function createUpdateArrayForContainerMove(array $originalElement) {
		GeneralUtility::logDeprecatedFunction();
		if ($originalElement['CType'] === 'gridelements_pi1') {
			$this->getTceMain()->moveChildren = TRUE;
		}

		$updateArray = array(
				'colPos' => 0,
				'sorting' => 0,
				'tx_gridelements_container' => 0,
				'tx_gridelements_columns' => 0
		);

		return $updateArray;
	}
}