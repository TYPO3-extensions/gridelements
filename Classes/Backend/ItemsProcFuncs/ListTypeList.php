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

use GridElementsTeam\Gridelements\Backend\LayoutSetup;
use GridElementsTeam\Gridelements\Helper\Helper;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class/Function which manipulates the item-array for table/field tt_content CType.
 *
 * @author Jo Hasenau <info@cybercraft.de>
 * @package TYPO3
 * @subpackage tx_gridelements
 */
class ListTypeList extends AbstractItemsProcFunc
{

    /**
     * @var LayoutSetup
     */
    protected $layoutSetup;

    /**
     * ItemProcFunc for CType items
     *
     * @param array $params The array of parameters that is used to render the item list
     */
    public function itemsProcFunc(array &$params)
    {
        if ((int)$params['row']['pid'] > 0) {
            $this->checkForAllowedListTypes($params['items'], $params['row']['pid'], $params['row']['colPos'][0],
                $params['row']['tx_gridelements_container'], $params['row']['tx_gridelements_columns']);
        } else {
            $this->init((int)$params['row']['pid']);
            // negative uid_pid values indicate that the element has been inserted after an existing element
            // so there is no pid to get the backendLayout for and we have to get that first
            $existingElement = BackendUtility::getRecordWSOL('tt_content', -((int)$params['row']['pid']), 'pid,list_type,colPos,tx_gridelements_container,tx_gridelements_columns');
            if ((int)$existingElement['pid'] > 0) {
                $this->checkForAllowedListTypes($params['items'], $existingElement['pid'], $existingElement['colPos'],
                    $existingElement['tx_gridelements_container'], $existingElement['tx_gridelements_columns']);
            }
        }
    }

    /**
     * Checks if a ListType is allowed in this particular page or grid column - only this one column defines the allowed CTypes regardless of any parent column
     *
     * @param array $items The items of the current CType list
     * @param int $pageId The id of the page we are currently working on
     * @param int $pageColumn The page column the element is a child of
     * @param int $gridContainerId The ID of the current container
     * @param int $gridColumn The grid column the element is a child of
     */
    public function checkForAllowedListTypes(array &$items, $pageId, $pageColumn, $gridContainerId, $gridColumn)
    {
        $allowed = '*';
        $disallowed = '';
        if ((int)$pageColumn >= 0 || (int)$pageColumn === -2) {
            $column = $pageColumn ? $pageColumn : 0;
            $backendLayout = $this->getSelectedBackendLayout($pageId);
        } else {
            $this->init($pageId);
            $column = $gridColumn ? (int)$gridColumn : 0;
            $gridElement = $this->layoutSetup->cacheCurrentParent($gridContainerId, true);
            $backendLayout = $this->layoutSetup->getLayoutSetup($gridElement['tx_gridelements_backend_layout']);
        }
        if (!empty($backendLayout)) {
            if (is_array($backendLayout['allowed']) && is_array($backendLayout['allowed'][$column]) && !empty($backendLayout['allowed'][$column]['list_type'])) {
                $allowed = $backendLayout['allowed'][$column]['list_type'];
            }
            if (is_array($backendLayout['disallowed']) && is_array($backendLayout['disallowed'][$column]) && !empty($backendLayout['disallowed'][$column]['list_type'])){
                $disallowed = $backendLayout['disallowed'][$column]['list_type'];
            }
        }
        if (isset($backendLayout) && (!empty($allowed) || !empty($disallowed))) {
            foreach ($items as $key => $item) {
                if ((
                        !empty($allowed) &&
                        !isset($allowed['*']) &&
                        !isset($allowed[$item[1]])
                    ) ||
                    (
                        !empty($disallowed) &&
                        (
                            isset($disallowed['*']) ||
                            isset($disallowed[$item[1]])
                        )
                    )) {
                    unset($items[$key]);
                }
            }
        }
    }

    /**
     * initializes this class
     *
     * @param int $pageId
     */
    public function init($pageId = 0)
    {
        parent::init();
        if (!$this->layoutSetup) {
            if ($pageId < 0) {
                $pageId = Helper::getInstance()->getPidFromNegativeUid($pageId);
            }
            $this->injectLayoutSetup(GeneralUtility::makeInstance(LayoutSetup::class)->init($pageId));
        }
    }

    /**
     * injects layout setup
     *
     * @param LayoutSetup $layoutSetup
     */
    public function injectLayoutSetup(LayoutSetup $layoutSetup)
    {
        $this->layoutSetup = $layoutSetup;
    }
}
