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

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class/Function which manipulates the item-array for table/field tt_content_tx_gridelements_columns.
 *
 * @author Jo Hasenau <info@cybercraft.de>
 */
class TtContent
{

    /**
     * @var LayoutSetup
     */
    protected $layoutSetup;

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
            if (!empty($ContentType) && is_array($params['items'])) {
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
     * initializes this class
     *
     * @param int $pageId
     */
    public function init($pageId)
    {
        if (!$this->layoutSetup instanceof LayoutSetup) {
            $this->injectLayoutSetup(GeneralUtility::makeInstance(LayoutSetup::class)->init($pageId));
        }
    }

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
     * ItemProcFunc for container items
     * removes items of the children chain from the list of selectable containers
     * if the element itself already is a container
     *
     * @param array $params An array containing the items and parameters for the list of items
     */
    public function containerItemsProcFunc(array &$params)
    {
        $this->init($params['row']['pid']);
        $possibleContainers = [];
        $this->removeItemsFromListOfSelectableContainers($params, $possibleContainers);

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
            $this->deleteDisallowedContainers($params, $itemUidList);
        }
    }

    /**
     * removes items of the children chain from the list of selectable containers
     *
     * @param array $params
     * @param array $possibleContainers
     */
    public function removeItemsFromListOfSelectableContainers(array &$params, array &$possibleContainers)
    {
        $contentType = is_array($params['row']['CType']) ? $params['row']['CType'][0] : $params['row']['CType'];
        if ($contentType === 'gridelements_pi1' && count($params['items']) > 1) {
            $items = $params['items'];
            $params['items'] = [0 => array_shift($items)];

            foreach ($items as $item) {
                $possibleContainers[$item['1']] = $item;
            }

            if ($params['row']['uid'] > 0) {
                $this->lookForChildContainersRecursively((int)$params['row']['uid'], $possibleContainers);
            }
        }
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
        $containerIds = GeneralUtility::intExplode(',', $containerIds);
        $queryBuilder = $this->getQueryBuilder();
        $childrenOnNextLevel = $queryBuilder
            ->select('uid', 'tx_gridelements_container')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter('gridelements_pi1')),
                    $queryBuilder->expr()->in(
                        'tx_gridelements_container',
                        $queryBuilder->createNamedParameter($containerIds, Connection::PARAM_INT_ARRAY)
                    )
                )
            )
            ->execute()
            ->fetchAll();

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
     * getter for queryBuilder
     *
     * @return QueryBuilder queryBuilder
     */
    public function getQueryBuilder()
    {
        /** @var $queryBuilder QueryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        return $queryBuilder;
    }

    /**
     * delete containers from params which are not allowed
     *
     * @param array $params
     * @param string $itemUidList comma separated list of uids
     */
    public function deleteDisallowedContainers(array &$params, $itemUidList = '')
    {
        $contentType = is_array($params['row']['CType']) ? $params['row']['CType'][0] : $params['row']['CType'];
        $listType = '';
        if ($contentType === 'list') {
            $listType = is_array($params['row']['list_type']) ? $params['row']['list_type'][0] : $params['row']['list_type'];
        }
        $layoutSetups = $this->layoutSetup->getLayoutSetup();
        if ($itemUidList) {
            $itemUidList = GeneralUtility::intExplode(',', $itemUidList);
            $queryBuilder = $this->getQueryBuilder();
            $containerQuery = $queryBuilder
                ->select('uid', 'tx_gridelements_backend_layout')
                ->from('tt_content')
                ->where(
                    $queryBuilder->expr()->in(
                        'uid',
                        $queryBuilder->createNamedParameter($itemUidList, Connection::PARAM_INT_ARRAY)
                    )
                )
                ->execute();
            $containers = [];
            while ($container = $containerQuery->fetch()) {
                $containers[$container['uid']] = $container;
            }
            foreach ($params['items'] as $key => $container) {
                $backendLayout = $containers[$container[1]]['tx_gridelements_backend_layout'];
                $gridColumn = $params['row']['tx_gridelements_columns'];
                $allowed = $layoutSetups[$backendLayout]['allowed'][$gridColumn];
                $disallowed = $layoutSetups[$backendLayout]['disallowed'][$gridColumn];
                if ($container[1] > 0 && (!empty($allowed) || !empty($disallowed))) {
                    if ((
                            !empty($allowed) &&
                            !isset($allowed['CType']['*']) &&
                            !isset($allowed['CType'][$contentType])
                        ) ||
                        (
                            !empty($disallowed) &&
                            (
                                isset($disallowed['CType']['*']) ||
                                isset($disallowed['CType'][$contentType])
                            )
                        )) {
                        unset($params['items'][$key]);
                    }
                    if (!empty($listType)) {
                        if ((
                                !empty($allowed) &&
                                !isset($allowed['CType']['*']) &&
                                !(
                                    isset($allowed['list_type']['*']) ||
                                    isset($allowed['list_type'][$listType])
                                )
                            ) ||
                            (
                                !empty($disallowed) &&
                                (
                                    isset($disallowed['CType']['*']) ||
                                    isset($disallowed['list_type']['*']) ||
                                    isset($disallowed['list_type'][$listType])
                                )
                            )) {
                            unset($params['items'][$key]);
                        }
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
        $layoutSelectItems = $this->layoutSetup->getLayoutSelectItems(
            isset($params['row']['colPos'][0]) ? $params['row']['colPos'][0] : $params['row']['colPos'],
            $params['row']['tx_gridelements_columns'],
            $params['row']['tx_gridelements_container'],
            $this->layoutSetup->getRealPid()
        );
        $params['items'] = ArrayUtility::keepItemsInArray($layoutSelectItems, $params['items'], true);
    }
}
