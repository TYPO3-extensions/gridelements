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

use TYPO3\CMS\Core\Utility\GeneralUtility;

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
        $backendLayoutData = GeneralUtility::callUserFunction('TYPO3\\CMS\\Backend\\View\\BackendLayoutView->getSelectedBackendLayout', $id, $this);
        // add allowed CTypes to the columns, since this is not done by the native core methods
        if(count($backendLayoutData['__items']) > 0) {
            if (!empty($backendLayoutData['__config']['backend_layout.']['rows.'])) {
                foreach ($backendLayoutData['__config']['backend_layout.']['rows.'] as $row) {
                    if (!empty($row['columns.'])) {
                        foreach ($row['columns.'] as $column) {
                            $backendLayoutData['columns'][$column['colPos']] = $column['allowed'] ? $column['allowed'] : '*';
                            $backendLayoutData['columns']['allowed'] .= $backendLayoutData['columns']['allowed'] ?
                                ',' . $backendLayoutData['columns'][$column['colPos']] :
                                $backendLayoutData['columns'][$column['colPos']];
                        }
                    }
                }
            }
        };
		return $backendLayoutData;
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

