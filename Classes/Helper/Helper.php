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

use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Gridelements helper class
 *
 * @author Dirk Hoffmann <dirk-hoffmann@telekom.de>
 * @package TYPO3
 * @subpackage tx_gridelements
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
     * @var DatabaseConnection
     */
    protected $databaseConnection;

    /**
     * Get instance from the class.
     *
     * @return Helper
     */
    public static function getInstance()
    {
        if (!self::$instance instanceof Helper) {
            self::$instance = new self();
            self::$instance->setDatabaseConnection($GLOBALS['TYPO3_DB']);
        }

        return self::$instance;
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
     * @param string $table
     * @param int $uid
     * @param int $pid
     * @param string $sortingField
     * @param int $sortRev
     * @param string $selectFieldList
     * @return array
     */
    public function getChildren($table = '', $uid = 0, $pid = 0, $sortingField = '', $sortRev = 0, $selectFieldList)
    {
        $retVal = array();

        if (trim($table) === 'tt_content' && $uid > 0) {
            $children = self::getDatabaseConnection()->exec_SELECTgetRows($selectFieldList, 'tt_content', 'tx_gridelements_container = ' . (int)$uid . ' AND pid = ' . (int)$pid . ' AND deleted = 0', '');

            foreach ($children as $child) {
                if (trim($sortingField) && isset($child[$sortingField]) && $sortingField !== 'sorting') {
                    $sortField = $child[$sortingField];
                } else {
                    $sortField = sprintf('%1$011d', $child['sorting']);
                }
                $sortKey = sprintf('%1$011d', $child['tx_gridelements_columns']) . '.' . $sortField . ':' . sprintf('%1$011d', $child['uid']);

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
     * converts a negative tt_content uid into a positive pid
     *
     * @param int $negativeUid the negative uid value of a tt_content record
     *
     * @return int
     */
    public function getPidFromNegativeUid($negativeUid = 0)
    {
        if ($negativeUid >= 0) {
            return $negativeUid;
        }
        $triggerElement = $this->databaseConnection->exec_SELECTgetSingleRow('pid', 'tt_content', 'uid = ' . abs($negativeUid));
        $pid = (int)$triggerElement['pid'];
        return is_array($triggerElement) && $pid ? $pid : 0;
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
        $specificIds = array();
        $specificIds['uid'] = (int)$record['uid'];
        $specificIds['pid'] = (int)$record['pid'];

        if (self::getBackendUser()->workspace > 0 && !empty($record['t3ver_oid'])) {
            $specificIds['uid'] = (int)$record['t3ver_oid'];
            $specificIds['pid'] = -1;
        }

        return $specificIds;
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
