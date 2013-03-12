<?php
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
 * @author		Jo Hasenau <info@cybercraft.de>
 * @package		TYPO3
 * @subpackage	tx_gridelements
 */
class tx_gridelements_tcemain_processCmdmap extends tx_gridelements_tcemain_abstract {

	/**
	 * Function to process the drag & drop copy action
	 *
	 * @param string            $command: The command to be handled by the command map
	 * @param string            $table: The name of the table we are working on
	 * @param int               $id: The id of the record that is going to be copied
	 * @param string            $value: The value that has been sent with the copy command
	 * @param boolean           $commandIsProcessed: A switch to tell the parent object, if the record has been copied
	 * @param t3lib_TCEmain     $parentObj: The parent object that triggered this hook
	 * @return	void
	 *
	 */
	public function processCmdmap($command, $table, $id, $value, &$commandIsProcessed, t3lib_TCEmain $parentObj) {
		$this->init($table, $id, $parentObj);
		// @todo Either create a new command map type, e.g. "reference" and process it with a hook instead of using $_GET //olly
		$DDcopy = intval(t3lib_div::_GET('DDcopy'));
		$reference = intval(t3lib_div::_GET('reference'));
		$containerUpdateArray = array();

		if ($command == 'copy' &&
			!$commandIsProcessed &&
			$table == 'tt_content' &&
			!$this->getTceMain()->isImporting
		) {

			$copyAfterDuplicationFields = $GLOBALS['TCA']['tt_content']['ctrl']['copyAfterDuplFields'];
			$GLOBALS['TCA']['tt_content']['ctrl']['copyAfterDuplFields'] .= ',tx_gridelements_container,tx_gridelements_columns';

			if($DDcopy == 1 || $reference == 1) {

				$overrideArray = array();

				if($reference == 1) {
					t3lib_div::loadTCA('tt_content');
					foreach($GLOBALS['TCA']['tt_content']['columns'] as $key => $column) {
						if(strpos(',' . $GLOBALS['TCA']['tt_content']['ctrl']['copyAfterDuplFields'] . ',', ',' . $key . ',') === FALSE) {
							$overrideArray[$key] = '';
						}
					}
					$overrideArray['CType'] = 'shortcut';
					$overrideArray['records'] = $id;
					$overrideArray['header'] = 'Reference';
				}

				if (strpos($value, 'x') !== false) {
					$valueArray = t3lib_div::trimExplode('x', $value);
					$overrideArray['sorting'] = 0;

					if ((intval($valueArray[0]) > 0 && $valueArray[1] != '') || (abs($valueArray[0]) == $id)) {
						$overrideArray['tx_gridelements_container'] = 0;
						$overrideArray['tx_gridelements_columns'] = 0;
						$overrideArray['colPos'] = intval($valueArray[1]);
					} else if ($valueArray[1] != '') {
						$containerUpdateArray[abs($valueArray[0])] = 1;
						$overrideArray['colPos'] = -1;
						$overrideArray['tx_gridelements_container'] = abs($valueArray[0]);
						$overrideArray['tx_gridelements_columns'] = intval($valueArray[1]);
					}
					$this->getTceMain()->copyRecord($table, $id, intval($valueArray[0]), 1, $overrideArray);
					$this->doGridContainerUpdate($containerUpdateArray);
				} else {
					$this->getTceMain()->copyRecord($table, $id, $value, 1, $overrideArray);
					if(intval($value) < 0) {
						$targetRecord = t3lib_BEfunc::getRecordWSOL('tt_content', -$value, 'tx_gridelements_container');
						if($targetRecord['tx_gridelements_container'] > 0) {
							$containerUpdateArray[$targetRecord['tx_gridelements_container']] = 1;
							$this->doGridContainerUpdate($containerUpdateArray);
						}
					}
				}
			} else {
				$this->getTceMain()->copyRecord($table, $id, $value, 1);
			}

			$commandIsProcessed = true;

			$GLOBALS['TCA']['tt_content']['ctrl']['copyAfterDuplFields'] = $copyAfterDuplicationFields;
		}
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/lib/tcemain/class.tx_gridelements_tcemain_processCmdmap.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/lib/class.tx_gridelements_tcemain_processCmdmap.php']);
}
?>