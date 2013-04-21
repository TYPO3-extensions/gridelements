<?php
namespace GridElementsTeam\Gridelements\Backend\ItemsProcFuncs;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Jo Hasenau <info@cybercraft.de>
 *  (c) 2013 Stefan froemken <froemken@gmail.com>
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
class AbstractItemsProcFunc {

	/**
	 * Gets the selected backend layout
	 *
	 * @param	int			$id: The uid of the page we are currently working on
	 * @return	array|null	$backendLayout: An array containing the data of the selected backend layout as well as a parsed version of the layout configuration
	 */
	public function getSelectedBackendLayout($id) {
		$rootline = $this->getRootline($id);
		$backendLayoutUid = NULL;

		for ($i = count($rootline); $i > 0; $i--) {
			$page = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
				'uid, backend_layout, backend_layout_next_level',
				'pages',
				'uid=' . intval($rootline[$i]['uid'])
			);
			$selectedBackendLayout = intval($page['backend_layout']);
			$selectedBackendLayoutNextLevel = intval($page['backend_layout_next_level']);
			if ($selectedBackendLayout != 0 && $page['uid'] == $id) {
				if ($selectedBackendLayout > 0) {
					// Backend layout for current page is set
					$backendLayoutUid = $selectedBackendLayout;
				}
				break;
			} else if ($selectedBackendLayoutNextLevel == -1 && $page['uid'] != $id) {
				// Some previous page in our rootline sets layout_next to "None"
				break;
			} else if ($selectedBackendLayoutNextLevel > 0 && $page['uid'] != $id) {
				// Some previous page in our rootline sets some backend_layout, use it
				$backendLayoutUid = $selectedBackendLayoutNextLevel;
				break;
			}
		}
		$backendLayout = NULL;
		if ($backendLayoutUid) {
			$backendLayout = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
				'*',
				'backend_layout',
				'uid=' . $backendLayoutUid
			);

			if ($backendLayout) {
				/** @var \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser $parser  */
				$parser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser');
				$parser->parse($backendLayout['config']);

				$backendLayout['__config'] = $parser->setup;
				$backendLayout['__items'] = array();
				$backendLayout['__colPosList'] = array();

				// create items and colPosList
				if ($backendLayout['__config']['backend_layout.'] && $backendLayout['__config']['backend_layout.']['rows.']) {
					foreach ($backendLayout['__config']['backend_layout.']['rows.'] as $row) {
						if (isset($row['columns.']) && is_array($row['columns.'])) {
							foreach ($row['columns.'] as $column) {
								$backendLayout['__items'][] = array(
									\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($column['name'], 'LLL:')
										? $GLOBALS['LANG']->sL($column['name']) : $column['name'],
									$column['colPos'],
									NULL,
									$column['allowed']
								);
								$backendLayout['__colPosList'][] = $column['colPos'];
								$backendLayout['columns'][$column['colPos']] = $column['allowed'] ? $column['allowed'] : '*';
								$backendLayout['allowed'] .= $backendLayout['allowed'] ? ',' . $backendLayout['column'][$column['colPos']] : $backendLayout['column'][$column['colPos']];
							}
						}
					}
				}
			}
		}

		return $backendLayout;
	}

	/**
	 * This method is a wrapper for unitTests because of the static method
	 *
	 * @param $pageUid
	 * @return array
	 */
	public function getRootline($pageUid) {
		return \TYPO3\CMS\Backend\Utility\BackendUtility::BEgetRootLine($pageUid);
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/Classes/Backend/ItemsProcFuncs/AbstractItemsProcFunc.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/Classes/Backend/ItemsProcFuncs/AbstractItemsProcFunc.php']);
}

?>