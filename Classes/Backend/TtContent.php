<?php
namespace GridElementsTeam\Gridelements\Backend;

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

use GridElementsTeam\Gridelements\Helper\Helper;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class/Function which manipulates the item-array for table/field tt_content_tx_gridelements_columns.
 *
 * @author Jo Hasenau <info@cybercraft.de>
 * @package TYPO3
 * @subpackage tx_gridelements
 */
class TtContent
{
    /**
     * @var DatabaseConnection
     */
    protected $databaseConnection;

    /**
     * @var LayoutSetup
     */
    protected $layoutSetup;

    /**
     * inject layout setup
     *
     * @param LayoutSetup $layoutSetup
     */
    public function injectLayoutSetup(LayoutSetup $layoutSetup)
    {
        $this->layoutSetup = $layoutSetup;
    }

    /**
     * initializes this class
     *
     * @param int $pageUid
     */
    public function init($pageUid)
    {
        $this->setDatabaseConnection($GLOBALS['TYPO3_DB']);
        if (!$this->layoutSetup instanceof LayoutSetup) {
            if ($pageUid < 0) {
                $pageUid = Helper::getInstance()->getPidFromNegativeUid($pageUid);
            }
            $this->injectLayoutSetup(GeneralUtility::makeInstance(LayoutSetup::class)->init($pageUid));
        }
    }

    /**
     * ItemProcFunc for columns items
     *
     * @param array $params An array containing the items and parameters for the list of items
     */
    public function columnsItemsProcFunc(array &$params)
    {
        $this->init($params['row']['pid']);
        $gridContainerId = is_array($params['row']['tx_gridelements_container'])
            ? (int)$params['row']['tx_gridelements_container'][0]
            : (int)$params['row']['tx_gridelements_container'];

        if ($gridContainerId > 0) {
            $gridElement = $this->layoutSetup->cacheCurrentParent($gridContainerId, true);
            $params['items'] = $this->layoutSetup->getLayoutColumnsSelectItems($gridElement['tx_gridelements_backend_layout']);

            $ContentType = is_array($params['row']['CType']) ? $params['row']['CType'][0] : $params['row']['CType'];
            if ($ContentType !== '' && is_array($params['items'])) {
                foreach ($params['items'] as $itemKey => $itemArray) {
                    if ($itemArray[3] !== '' && $itemArray[3] !== '*'
                        && !GeneralUtility::inList($itemArray[3], $ContentType)
                    ) {
                        unset($params['items'][$itemKey]);
                    }
                }
            }
        }
    }

    /**
     * ItemProcFunc for container items
     * removes items of the children chain from the list of selectable containers
     * if the element itself already is a container
     *
     * @param array $params An array containing the items and parameters for the list of items
     */
    public function containerItemsProcFunc(array &$params)
    {
        $this->init($params['row']['pid']);
        $possibleContainers = array();
        $this->removesItemsFromListOfSelectableContainers($params, $possibleContainers);

        if (!empty($possibleContainers)) {
            $params['items'] = array_merge($params['items'], $possibleContainers);
        }

        $itemUidList = '';
        if (count($params['items']) > 1) {
            foreach ($params['items'] as $container) {
                if ($container[1] > 0) {
                    $itemUidList .= $itemUidList ? ',' . $container[1] : $container[1];
                }
            }
        }

        if ($itemUidList) {
            $this->deleteUnallowedContainer($params, $itemUidList);
        }
    }

    /**
     * removes items of the children chain from the list of selectable containers
     *
     * @param array $params
     * @param array $possibleContainers
     */
    public function removesItemsFromListOfSelectableContainers(array &$params, array &$possibleContainers)
    {
        $ContentType = is_array($params['row']['CType']) ? $params['row']['CType'][0] : $params['row']['CType'];
        if ($ContentType === 'gridelements_pi1' && count($params['items']) > 1) {
            $items = $params['items'];
            $params['items'] = array(0 => array_shift($items));

            foreach ($items as $item) {
                $possibleContainers[$item['1']] = $item;
            }

            if ($params['row']['uid'] > 0) {
                $this->lookForChildContainersRecursively((int)$params['row']['uid'], $possibleContainers);
            }
        }
    }

    /**
     * delete containers from params which are not allowed
     *
     * @param array $params
     * @param string $itemUidList comma separated list of uids
     */
    public function deleteUnallowedContainer(array &$params, $itemUidList = '')
    {
        $ContentType = is_array($params['row']['CType']) ? $params['row']['CType'][0] : $params['row']['CType'];
        $layoutSetups = $this->layoutSetup->getLayoutSetup();
        if ($itemUidList) {
            $itemUidList = implode(',', GeneralUtility::intExplode(',', $itemUidList));
            $containerRecords = $this->databaseConnection->exec_SELECTgetRows(
                'uid,tx_gridelements_backend_layout',
                'tt_content',
                'uid IN (' . $itemUidList . ')',
                '', '', '', 'uid'
            );

            foreach ($params['items'] as $key => $container) {
                $allowed = $layoutSetups[$containerRecords[$container[1]]['tx_gridelements_backend_layout']]['allowed'];
                if ($container[1] > 0 && $allowed) {
                    if (!GeneralUtility::inList($allowed, $ContentType) && !GeneralUtility::inList($allowed, '*')) {
                        unset($params['items'][$key]);
                    }
                }
            }
        }
    }

    /**
     * ItemProcFunc for layout items
     * removes items that are available for grid boxes on the first level only
     * and items that are excluded for a certain branch or user
     *
     * @param array $params An array containing the items and parameters for the list of items
     */
    public function layoutItemsProcFunc(array &$params)
    {
        $this->init($params['row']['pid']);
        $layoutSelectItems = $this->layoutSetup->getLayoutSelectItems($params['row']['colPos']);

        $params['items'] = ArrayUtility::keepItemsInArray($layoutSelectItems, $params['items'], true);
    }

    /**
     * Recursive function to remove any container from the list of possible containers
     * that is already a subcontainer on any level of the current container
     *
     * @param string $containerIds : A list determining containers that should be checked
     * @param array $possibleContainers : The result list containing the remaining containers after the check
     */
    public function lookForChildContainersRecursively($containerIds, array &$possibleContainers)
    {
        if (!$containerIds) {
            return;
        }
        $containerIds = implode(',', GeneralUtility::intExplode(',', $containerIds));
        $childrenOnNextLevel = $this->databaseConnection->exec_SELECTgetRows(
            'uid, tx_gridelements_container',
            'tt_content',
            'CType=\'gridelements_pi1\' AND tx_gridelements_container IN (' . $containerIds . ')'
        );

        if (!empty($childrenOnNextLevel) && !empty($possibleContainers)) {
            $containerIds = '';

            foreach ($childrenOnNextLevel as $childOnNextLevel) {
                if (isset($possibleContainers[$childOnNextLevel['uid']])) {
                    unset($possibleContainers[$childOnNextLevel['uid']]);
                }

                $containerIds .= $containerIds ? ',' . (int)$childOnNextLevel['uid'] : (int)$childOnNextLevel['uid'];

                if ($containerIds !== '') {
                    $this->lookForChildContainersRecursively($containerIds, $possibleContainers);
                }
            }
        }
    }

    /**
     * setter for databaseConnection object
     *
     * @param DatabaseConnection $databaseConnection
     */
    public function setDatabaseConnection(DatabaseConnection $databaseConnection)
    {
        $this->databaseConnection = $databaseConnection;
    }

    /**
     * getter for databaseConnection
     *
     * @return DatabaseConnection databaseConnection
     */
    public function getDatabaseConnection()
    {
        return $this->databaseConnection;
    }
}
