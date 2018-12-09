<?php

namespace GridElementsTeam\Gridelements\Backend\ItemsProcFuncs;

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

/**
 * Class/Function which manipulates the item-array for table/field tt_content CType.
 *
 * @author Jo Hasenau <info@cybercraft.de>
 */
class SysLanguageUidList extends AbstractItemsProcFunc
{
    /**
     * ItemProcFunc for CType items
     *
     * @param array $params The array of parameters that is used to render the item list
     */
    public function itemsProcFunc(array &$params)
    {
        if ((int)$params['row']['pid'] > 0 && (int)$params['row']['tx_gridelements_container'] > 0 && isset($params['items'])) {
            $this->checkForAllowedLanguages($params['items'], $params['row']['tx_gridelements_container']);
        }
    }

    /**
     * Checks if a language is allowed in this particular container - only this one container defines the allowed languages regardless of any parent
     *
     * @param array $items The items of the current language list
     * @param int $gridContainerId The ID of the current container
     */
    public function checkForAllowedLanguages(array &$items, $gridContainerId)
    {
        if (!$gridContainerId) {
            return;
        }
        $parentContainer = BackendUtility::getRecordWSOL('tt_content', $gridContainerId);
        if (!empty($items) && (int)$parentContainer['uid'] > 0) {
            foreach ($items as $item => $valueArray) {
                if ((int)$parentContainer['sys_language_uid'] > -1 && (int)$valueArray[1] !== (int)$parentContainer['sys_language_uid']) {
                    unset($items[$item]);
                }
            }
        }
    }
}
