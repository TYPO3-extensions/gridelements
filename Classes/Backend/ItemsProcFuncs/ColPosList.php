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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class/Function which manipulates the item-array for table/field tt_content colPos.
 *
 * @author Jo Hasenau <info@cybercraft.de>
 * @package TYPO3
 * @subpackage tx_gridelements
 */
class ColPosList extends AbstractItemsProcFunc
{
    /**
     * ItemProcFunc for colpos items
     *
     * @param array $params The array of parameters that is used to render the item list
     */
    public function itemsProcFunc(array &$params)
    {
        $this->init();
        if ($params['row']['pid'] > 0) {
            $ContentType = is_array($params['row']['CType']) ? $params['row']['CType'][0] : $params['row']['CType'];
            $params['items'] = $this->addColPosListLayoutItems($params['row']['pid'], $params['items'], $ContentType, $params['row']['tx_gridelements_container']);
        } else {
            // negative uid_pid values indicate that the element has been inserted after an existing element
            // so there is no pid to get the backendLayout for and we have to get that first
            $existingElement = $this->databaseConnection->exec_SELECTgetSingleRow('pid, CType, tx_gridelements_container', 'tt_content', 'uid=' . -((int)$params['row']['pid']));
            if ($existingElement['pid'] > 0) {
                $params['items'] = $this->addColPosListLayoutItems($existingElement['pid'], $params['items'], $existingElement['CType'], $existingElement['tx_gridelements_container']);
            }
        }
    }

    /**
     * Adds items to a colpos list
     *
     * @param int $pageId The uid of the page we are currently working on
     * @param array $items The array of items before the action
     * @param string $CType The content type of the item holding the colPosList
     * @param int $container
     *
     * @return array $items The ready made array of items
     */
    protected function addColPosListLayoutItems($pageId, array $items, $CType = '', $container = 0)
    {
        if (empty($container)) {
            $layout = $this->getSelectedBackendLayout($pageId);

            if ($layout) {
                if ($CType !== '' && !empty($layout['__items'])) {
                    foreach ($layout['__items'] as $itemKey => $itemArray) {
                        if ($itemArray[3] !== '' && !GeneralUtility::inList($itemArray[3], $CType) && !GeneralUtility::inList($itemArray[3], '*')) {
                            unset($layout['__items'][$itemKey]);
                        }
                    }
                }
                if (!empty($layout['__items'])) {
                    $items = $layout['__items'];
                }
            }
        } else {
            $items = array();
            $items[] = array(
                $this->languageService->sL('LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tt_content.tx_gridelements_container'),
                '-1',
                null,
                null
            );
        }

        return $items;
    }
}
