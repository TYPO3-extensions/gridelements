<?php

namespace GridElementsTeam\Gridelements\Helper;

/***************************************************************
 *  Copyright notice
 *  (c) 2013 Dirk Hoffmann <dirk-hoffmann@telekom.de>
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

use TYPO3\CMS\Backend\View\BackendLayoutView;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Gridelements helper class
 *
 * @author Dirk Hoffmann <dirk-hoffmann@telekom.de>
 */
class Helper implements SingletonInterface
{
    /**
     * Local instance of the helper
     *
     * @var Helper
     */
    protected static $instance = null;

    /**
     * Get instance from the class.
     *
     * @return Helper
     */
    public static function getInstance()
    {
        if (!self::$instance instanceof Helper) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param string $table
     * @param int $uid
     * @param int $pid
     * @param string $sortingField
     * @param int $sortRev
     * @param string $selectFieldList
     * @return array
     */
    public function getChildren($table = '', $uid = 0, $pid = 0, $sortingField = '', $sortRev = 0, $selectFieldList = '')
    {
        $retVal = [];

        if (trim($table) === 'tt_content' && $uid > 0) {
            $selectFieldList .= ',sorting,tx_gridelements_columns';
            $selectFieldArray = GeneralUtility::trimExplode(',', $selectFieldList);
            $queryBuilder = self::getQueryBuilder();
            $children = $queryBuilder
                ->select(...$selectFieldArray)
                ->from('tt_content')
                ->where(
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->eq(
                            'tx_gridelements_container',
                            $queryBuilder->createNamedParameter((int)$uid, \PDO::PARAM_INT)
                        ),
                        $queryBuilder->expr()->eq(
                            'pid',
                            $queryBuilder->createNamedParameter((int)$pid, \PDO::PARAM_INT)
                        )
                    )
                )
                ->execute()
                ->fetchAll();

            foreach ($children as $child) {
                if (trim($sortingField) && isset($child[$sortingField]) && $sortingField !== 'sorting') {
                    $sortField = $child[$sortingField];
                } else {
                    $sortField = sprintf('%1$011d', $child['sorting']);
                }
                $sortKey = sprintf(
                    '%1$011d',
                        $child['tx_gridelements_columns']
                ) . '.' . $sortField . ':' . sprintf('%1$011d', $child['uid']);

                $retVal[$sortKey] = $child;
            }
        }

        ksort($retVal);
        if ($sortRev) {
            $retVal = array_reverse($retVal);
        }

        return array_values($retVal);
    }

    /**
     * @param $backendLayout
     * @param bool $csvValues
     * @return mixed
     */
    public function mergeAllowedDisallowedSettings($backendLayout, $csvValues = false)
    {
        if (!empty($backendLayout['allowed'])) {
            foreach ($backendLayout['allowed'] as $column => &$allowedFields) {
                if (isset($fields['CType']) && $allowedFields['CType'] !== '*') {
                    if (!empty($allowedFields['list_type']) && strpos($allowedFields['CType'], 'list') === false) {
                        $allowedFields['CType'] .= ',list';
                    }
                    if (!empty($allowedFields['tx_gridelements_backend_layout']) && strpos($allowedFields['CType'], 'gridelements_pi1') === false) {
                        $allowedFields['CType'] .= ',gridelements_pi1';
                    }
                }
                if (!isset($allowedFields['CType'])) {
                    $allowedFields['CType'] = '*';
                }
                if (!empty($allowedFields['CType']) && !$csvValues) {
                    $allowedFields['CType'] = array_flip(GeneralUtility::trimExplode(',', $allowedFields['CType']));
                }
                if (!empty($allowedFields['list_type']) && !$csvValues) {
                    $allowedFields['list_type'] = array_flip(GeneralUtility::trimExplode(',', $allowedFields['list_type']));
                }
                if (!empty($allowedFields['tx_gridelements_backend_layout']) && !$csvValues) {
                    $allowedFields['tx_gridelements_backend_layout'] = array_flip(GeneralUtility::trimExplode(',', $allowedFields['tx_gridelements_backend_layout']));
                }
            }
        }
        if (!empty($backendLayout['disallowed']) && !$csvValues) {
            foreach ($backendLayout['disallowed'] as $column => &$disallowedFields) {
                if (!empty($disallowedFields['CType'])) {
                    $disallowedFields['CType'] = array_flip(GeneralUtility::trimExplode(',', $disallowedFields['CType']));
                }
                if (!empty($disallowedFields['list_type'])) {
                    $disallowedFields['list_type'] = array_flip(GeneralUtility::trimExplode(',', $disallowedFields['list_type']));
                }
                if (!empty($disallowedFields['tx_gridelements_backend_layout'])) {
                    $disallowedFields['tx_gridelements_backend_layout'] = array_flip(GeneralUtility::trimExplode(',', $disallowedFields['tx_gridelements_backend_layout']));
                }
            }
        }
        return $backendLayout;
    }

    /**
     * converts tt_content uid into a pid
     *
     * @param int $uid the uid value of a tt_content record
     *
     * @return int
     */
    public function getPidFromUid($uid = 0)
    {
        $queryBuilder = self::getQueryBuilder();
        $triggerElement = $queryBuilder
            ->select('pid')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter(abs($uid), \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetch();
        $pid = (int)$triggerElement['pid'];
        return is_array($triggerElement) && $pid ? $pid : 0;
    }

    /**
     * getter for queryBuilder
     *
     * @return QueryBuilder queryBuilder
     */
    public function getQueryBuilder()
    {
        /** @var $queryBuilder QueryBuilder  */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()
            ->removeByType(HiddenRestriction::class)
            ->removeByType(StartTimeRestriction::class)
            ->removeByType(EndTimeRestriction::class);
        return $queryBuilder;
    }

    /**
     * Gets the uid of a record depending on the current context.
     * If in workspace mode, the overlay uid is used (if available),
     * otherwise the regular uid is used.
     *
     * @param array $record Overlaid record data
     *
     * @return int[]
     */
    public function getSpecificIds(array $record)
    {
        $specificIds = [];
        $specificIds['uid'] = (int)$record['uid'];
        $specificIds['pid'] = (int)$record['pid'];

        if (self::getBackendUser()->workspace > 0 && !empty($record['t3ver_oid'])) {
            $specificIds['uid'] = (int)$record['t3ver_oid'];
            $specificIds['pid'] = -1;
        }

        return $specificIds;
    }

    /**
     * @param $pageId
     * @return mixed
     */
    public function getSelectedBackendLayout($pageId)
    {
        if (empty($GLOBALS['tx_gridelements']['pageBackendLayoutData'][$pageId])) {
            $backendLayoutData = GeneralUtility::callUserFunction(
                BackendLayoutView::class . '->getSelectedBackendLayout',
                $pageId,
                $this
            );
            // add allowed CTypes to the columns, since this is not done by the native core methods
            if (!empty($backendLayoutData['__items'])) {
                $backendLayoutData['columns']['CSV'] = '-2,-1';
                if (!empty($backendLayoutData['__config']['backend_layout.']['rows.'])) {
                    $allowed = [];
                    $disallowed = [];
                    $maxItems = [];
                    foreach ($backendLayoutData['__config']['backend_layout.']['rows.'] as $row) {
                        if (!empty($row['columns.'])) {
                            foreach ($row['columns.'] as $column) {
                                if (!isset($column['colPos'])) {
                                    continue;
                                }
                                $colPos = (int)$column['colPos'];
                                if (isset($column['allowed.'])) {
                                    $column['allowed'] = $column['allowed.'];
                                }
                                if (isset($column['disallowed.'])) {
                                    $column['disallowed'] = $column['disallowed.'];
                                }
                                if (!is_array($column['allowed']) && !empty($column['allowed'])) {
                                    $allowed[$colPos] = ['CType' => $column['allowed']];
                                } elseif (empty($column['allowed'])) {
                                    $allowed[$colPos] = ['CType' => '*'];
                                } else {
                                    $allowed[$colPos] = $column['allowed'];
                                }
                                if ($column['allowedGridTypes']) {
                                    $allowed[$colPos]['tx_gridelements_backend_layout'] = $column['allowedGridTypes'];
                                }
                                if (!empty($column['disallowed'])) {
                                    $disallowed[$colPos] = $column['disallowed'];
                                }
                                if (!empty($column['maxitems'])) {
                                    $maxItems[$colPos] = $column['maxitems'];
                                }
                                $backendLayoutData['columns']['CSV'] .= ',' . $colPos;
                            }
                        }
                    }
                    $backendLayoutData['allowed'] = $allowed;
                    if (!empty($disallowed)) {
                        $backendLayoutData['disallowed'] = $disallowed;
                    }
                    if (!empty($maxItems)) {
                        $backendLayoutData['maxitems'] = $maxItems;
                    }
                }
                $backendLayoutData = $this->mergeAllowedDisallowedSettings($backendLayoutData);
            }
            $GLOBALS['tx_gridelements']['pageBackendLayoutData'][$pageId] = $backendLayoutData;
        }
        return $GLOBALS['tx_gridelements']['pageBackendLayoutData'][$pageId];
    }

    /**
     * Gets the current backend user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    public function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
