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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class/Function which manipulates the item-array for table/field tt_content CType.
 *
 * @author Jo Hasenau <info@cybercraft.de>
 */
class CTypeList extends AbstractItemsProcFunc
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
            $colPos = is_array($params['row']['colPos']) ? $params['row']['colPos'][0] : $params['row']['colPos'];
            $this->checkForAllowedCTypes(
                $params['items'],
                $params['row']['pid'],
                $colPos,
                $params['row']['tx_gridelements_container'],
                $params['row']['tx_gridelements_columns']
            );
        } else {
            $this->init((int)$params['row']['pid']);
            // negative uid_pid values indicate that the element has been inserted after an existing element
            // so there is no pid to get the backendLayout for and we have to get that first
            $existingElement = BackendUtility::getRecordWSOL('tt_content', -((int)$params['row']['pid']), 'pid,CType,colPos,tx_gridelements_container,tx_gridelements_columns');
            if ((int)$existingElement['pid'] > 0) {
                $this->checkForAllowedCTypes(
                    $params['items'],
                    $existingElement['pid'],
                    $existingElement['colPos'],
                    $existingElement['tx_gridelements_container'],
                    $existingElement['tx_gridelements_columns']
                );
            }
        }
    }

    /**
     * Checks if a CType is allowed in this particular page or grid column - only this one column defines the allowed CTypes regardless of any parent column
     *
     * @param array $items The items of the current CType list
     * @param int $pageId The id of the page we are currently working on
     * @param int $pageColumn The page column the element is a child of
     * @param int $gridContainerId The ID of the current container
     * @param int $gridColumn The grid column the element is a child of
     */
    public function checkForAllowedCTypes(array &$items, $pageId, $pageColumn, $gridContainerId, $gridColumn)
    {
        $allowed = [];
        $disallowed = [];
        if ((int)$pageColumn >= 0 || (int)$pageColumn === -2) {
            $column = $pageColumn ? $pageColumn : 0;
            $layout = $this->getSelectedBackendLayout($pageId);
        } else {
            $this->init($pageId);
            $column = $gridColumn ? (int)$gridColumn : 0;
            $gridElement = $this->layoutSetup->cacheCurrentParent($gridContainerId, true);
            $layout = $this->layoutSetup->getLayoutSetup($gridElement['tx_gridelements_backend_layout']);
        }
        if (!empty($layout)) {
            if (is_array($layout['allowed']) && is_array($layout['allowed'][$column]) && !empty($layout['allowed'][$column]['CType'])) {
                $allowed = $layout['allowed'][$column]['CType'];
            }
            if (is_array($layout['disallowed']) && is_array($layout['disallowed'][$column]) && !empty($layout['disallowed'][$column]['CType'])) {
                $disallowed = $layout['disallowed'][$column]['CType'];
            }
        }
        if (isset($layout) &&  (!empty($allowed) || !empty($disallowed))) {
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
