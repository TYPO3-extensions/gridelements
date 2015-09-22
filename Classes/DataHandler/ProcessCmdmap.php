<?php
namespace GridElementsTeam\Gridelements\DataHandler;

/***************************************************************
 *  Copyright notice
 *  (c) 2013 Jo Hasenau <info@cybercraft.de>
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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class/Function which offers TCE main hook functions.
 * @author Jo Hasenau <info@cybercraft.de>
 * @package TYPO3
 * @subpackage tx_gridelements
 */
class ProcessCmdmap extends AbstractDataHandler {

	/**
	 * Function to process the drag & drop copy action
	 * @param string $command : The command to be handled by the command map
	 * @param string $table : The name of the table we are working on
	 * @param int $id : The id of the record that is going to be copied
	 * @param string $value : The value that has been sent with the copy command
	 * @param boolean $commandIsProcessed : A switch to tell the parent object, if the record has been copied
	 * @param DataHandler $parentObj : The parent object that triggered this hook
	 * @return    void
	 */
	public function execute_processCmdmap($command, $table, $id, $value, &$commandIsProcessed, DataHandler &$parentObj = null) {
		$this->init($table, $id, $parentObj);
		// @todo Either create a new command map type, e.g. "reference" and process it with a hook instead of using $_GET //olly
		$DDcopy = (int)GeneralUtility::_GET('DDcopy');
		$reference = (int)GeneralUtility::_GET('reference');
		$containerUpdateArray = array();

		if ($command === 'copy' && !$commandIsProcessed && $table === 'tt_content' && !$this->getTceMain()->isImporting) {

			$copyAfterDuplicationFields = $GLOBALS['TCA']['tt_content']['ctrl']['copyAfterDuplFields'];
			$GLOBALS['TCA']['tt_content']['ctrl']['copyAfterDuplFields'] .= ',tx_gridelements_container,tx_gridelements_columns';

			if ((int)$DDcopy === 1 || (int)$reference === 1) {

				$overrideArray = array();

				if ((int)$reference === 1) {
					foreach ($GLOBALS['TCA']['tt_content']['columns'] as $key => $column) {
						if (strpos(',' . $GLOBALS['TCA']['tt_content']['ctrl']['copyAfterDuplFields'] . ',', ',' . $key . ',') === false) {
							$overrideArray[$key] = '';
						}
					}
					$overrideArray['CType'] = 'shortcut';
					$overrideArray['records'] = $id;
					$overrideArray['header'] = 'Reference';
				}

				if (strpos($value, 'x') !== false) {

					$valueArray = explode('x', $value);
					$overrideArray['sorting'] = 0;

					if (((int)$valueArray[0] > 0 && $valueArray[1] !== '') || (abs($valueArray[0]) === $id)) {
						$targetTable = 'pages';
						$overrideArray['tx_gridelements_container'] = 0;
						$overrideArray['tx_gridelements_columns'] = 0;
						$overrideArray['colPos'] = (int)$valueArray[1];
					} else if ($valueArray[1] !== '') {
						$targetTable = 'tt_content';
						$containerUpdateArray[abs($valueArray[0])] = 1;
						$overrideArray['colPos'] = -1;
						$overrideArray['tx_gridelements_container'] = abs($valueArray[0]);
						$overrideArray['tx_gridelements_columns'] = (int)$valueArray[1];
					} else {
						throw new \UnexpectedValueException('No target table identified.', 1442269491);
					}
					$targetRecord = BackendUtility::getRecordWSOL($targetTable, abs($valueArray[0]));
					if ($targetRecord[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']]) {
						$overrideArray['sys_language_uid'] = $targetRecord['sys_language_uid'];
					}
					$this->getTceMain()->copyRecord($table, $id, (int)$valueArray[0], 1, $overrideArray);
					$this->doGridContainerUpdate($containerUpdateArray);
					if ($targetTable === 'tt_content') {
						$this->checkAndUpdateTranslatedChildren($containerUpdateArray);
					}
				} else {
					$value = (int)$value;
					if ($value < 0) {
						$targetTable = 'tt_content';
					} else {
						$targetTable = 'pages';
					}
					$targetRecord = BackendUtility::getRecordWSOL($targetTable, abs($value));
					if ($targetRecord[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']]) {
						if ($targetTable === 'tt_content') {
							$overrideArray['tx_gridelements_container'] = $targetRecord['tx_gridelements_container'];
						}
						$overrideArray['sys_language_uid'] = $targetRecord['sys_language_uid'];
					}
					$this->getTceMain()->copyRecord($table, $id, $value, 1, $overrideArray);
					if ($value < 0) {
						if ($targetRecord['tx_gridelements_container'] > 0) {
							$containerUpdateArray[$targetRecord['tx_gridelements_container']] = 1;
							$this->doGridContainerUpdate($containerUpdateArray);
						}
					}
				}
			} else {
				$value = (int)$value;
				if ($value < 0) {
					$targetTable = 'tt_content';
				} else {
					$targetTable = 'pages';
				}
				$originalRecord = BackendUtility::getRecordWSOL($targetTable, abs($id));
				$overrideArray = [];
				if ($value > 0) {
					$overrideArray['tx_gridelements_container'] = 0;
					$overrideArray['tx_gridelements_columns'] = 0;
					if ($originalRecord['colPos'] === -1) {
						$overrideArray['colPos'] = 0;
					}
					$overrideArray['sorting'] = 0;
				}
				$targetRecord = BackendUtility::getRecordWSOL($targetTable, abs($value));
				if ($targetRecord[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']]) {
					if ($targetTable === 'tt_content') {
						$overrideArray['tx_gridelements_container'] = $targetRecord['tx_gridelements_container'];
					}
					$overrideArray['sys_language_uid'] = $targetRecord['sys_language_uid'];
				}
				$this->getTceMain()->copyRecord($table, $id, $value, 1, $overrideArray);
				if ($value < 0) {
					if ($targetRecord['tx_gridelements_container'] > 0) {
						$containerUpdateArray[$targetRecord['tx_gridelements_container']] = 1;
						$this->doGridContainerUpdate($containerUpdateArray);
					}
				}
			}

			$commandIsProcessed = true;

			$GLOBALS['TCA']['tt_content']['ctrl']['copyAfterDuplFields'] = $copyAfterDuplicationFields;
		}
	}
}
