<?php

namespace GridElementsTeam\Gridelements\DataHandler;

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
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class/Function which offers TCE main hook functions.
 *
 * @author Jo Hasenau <info@cybercraft.de>
 * @package TYPO3
 * @subpackage tx_gridelements
 */
abstract class AbstractDataHandler
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var string
     */
    protected $table;

    /**
     * @var int
     */
    protected $pageUid;

    /**
     * @var DataHandler
     */
    protected $dataHandler;

    /**
     * @var LayoutSetup
     */
    protected $layoutSetup;

    /**
     * initializes this class
     *
     * @param string $table : The name of the table the data should be saved to
     * @param integer $uidPid : The uid of the record or page we are currently working on
     * @param DataHandler $dataHandler
     */
    public function init($table, $uidPid, DataHandler $dataHandler)
    {
        $this->setTable($table);
        $this->setPageUid($uidPid);
        $this->setTceMain($dataHandler);
        if (!$this->layoutSetup instanceof LayoutSetup) {
            if ($uidPid < 0) {
                $uidPid = Helper::getInstance()->getPidFromNegativeUid($uidPid);
            }
            $this->injectLayoutSetup(GeneralUtility::makeInstance(LayoutSetup::class)->init($uidPid));
        }
    }

    /**
     * setter for dataHandler object
     *
     * @param DataHandler $dataHandler
     */
    public function setTceMain(DataHandler $dataHandler)
    {
        $this->dataHandler = $dataHandler;
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
     * Function to handle record actions for current or former children of translated grid containers
     * as well as translated references
     *
     * @param int $uid
     */
    public function checkAndUpdateTranslatedElements($uid)
    {
        if ($uid <= 0) {
            return;
        }
        $queryBuilder = $this->getQueryBuilder();
        $currentValues = $queryBuilder
            ->select('uid', 'tx_gridelements_container', 'tx_gridelements_columns', 'sys_language_uid', 'colPos',
                'l18n_parent')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter((int)$uid, \PDO::PARAM_INT))
            )
            ->setMaxResults(1)
            ->execute()
            ->fetch();
        if (!empty($currentValues['l18n_parent'])) {
            $queryBuilder = $this->getQueryBuilder();
            $currentValues = $queryBuilder
                ->select('uid', 'tx_gridelements_container', 'tx_gridelements_columns', 'sys_language_uid', 'colPos',
                    'l18n_parent')
                ->from('tt_content')
                ->where(
                    $queryBuilder->expr()->eq('uid',
                        $queryBuilder->createNamedParameter((int)$currentValues['l18n_parent'], \PDO::PARAM_INT))
                )
                ->setMaxResults(1)
                ->execute()
                ->fetch();
        }
        if (empty($currentValues['uid'])) {
            return;
        }
        $queryBuilder = $this->getQueryBuilder();
        $translatedElementQuery = $queryBuilder
            ->select('uid', 'tx_gridelements_container', 'tx_gridelements_columns', 'sys_language_uid', 'colPos',
                'l18n_parent')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('l18n_parent',
                    $queryBuilder->createNamedParameter((int)$currentValues['uid'], \PDO::PARAM_INT))
            )
            ->execute();
        $translatedElements = [];
        while ($translatedElement = $translatedElementQuery->fetch()) {
            $translatedElements[$translatedElement['uid']] = $translatedElement;
        }
        if (empty($translatedElements)) {
            return;
        }
        $translatedContainers = [];
        if ($currentValues['tx_gridelements_container'] > 0) {
            $queryBuilder = $this->getQueryBuilder();
            $translatedContainerQuery = $queryBuilder
                ->select('uid', 'sys_language_uid')
                ->from('tt_content')
                ->where(
                    $queryBuilder->expr()->eq('l18n_parent',
                        $queryBuilder->createNamedParameter((int)$currentValues['tx_gridelements_container'],
                            \PDO::PARAM_INT))
                )
                ->execute();
            while ($translatedContainer = $translatedContainerQuery->fetch()) {
                $translatedContainers[$translatedContainer['sys_language_uid']] = $translatedContainer;
            }
        }
        $containerUpdateArray = [];
        foreach ($translatedElements as $translatedUid => $translatedElement) {
            $updateArray = [];
            if (isset($translatedContainers[$translatedElement['sys_language_uid']])) {
                $updateArray['tx_gridelements_container'] = (int)$translatedContainers[$translatedElement['sys_language_uid']]['uid'];
                $updateArray['tx_gridelements_columns'] = (int)$currentValues['tx_gridelements_columns'];
            } else if ($translatedElement['tx_gridelements_container'] == $currentValues['tx_gridelements_container']) {
                $updateArray['tx_gridelements_container'] = (int)$currentValues['tx_gridelements_container'];
                $updateArray['tx_gridelements_columns'] = (int)$currentValues['tx_gridelements_columns'];
            }
            $updateArray['colPos'] = (int)$currentValues['colPos'];

            $this->getConnection()->update(
                'tt_content',
                $updateArray,
                ['uid' => (int)$translatedUid]
            );

            if (isset($updateArray['tx_gridelements_container']) &&
                $translatedElement['tx_gridelements_container'] !== $updateArray['tx_gridelements_container']) {
                $containerUpdateArray[$translatedElement['tx_gridelements_container']] -= 1;
                $containerUpdateArray[$updateArray['tx_gridelements_container']] += 1;
                $this->getTceMain()->updateRefIndex('tt_content', (int)$translatedElement['tx_gridelements_container']);
                $this->getTceMain()->updateRefIndex('tt_content', (int)$updateArray['tx_gridelements_container']);
            }
        }
        if (!empty($containerUpdateArray)) {
            $this->doGridContainerUpdate($containerUpdateArray);
        }
    }

    /**
     * getter for queryBuilder
     *
     * @return QueryBuilder queryBuilder
     */
    public function getQueryBuilder()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        return $queryBuilder;
    }


    /**
     * setter for Connection object
     *
     * @return Connection
     */
    public function getConnection()
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tt_content');;
    }

    /**
     * getter for dataHandler
     *
     * @return DataHandler dataHandler
     */
    public function getTceMain()
    {
        return $this->dataHandler;
    }

    /**
     * Function to handle record actions between different grid containers
     *
     * @param array $containerUpdateArray
     */
    public function doGridContainerUpdate($containerUpdateArray = [])
    {
        if (is_array($containerUpdateArray) && !empty($containerUpdateArray)) {
            $queryBuilder = $this->getQueryBuilder();
            $currentContainers = $queryBuilder
                ->select('uid', 'tx_gridelements_children')
                ->from('tt_content')
                ->where(
                    $queryBuilder->expr()->in('uid', implode(',', array_keys($containerUpdateArray)))
                )
                ->execute()
                ->fetchAll();
            if (!empty($currentContainers)) {
                foreach ($currentContainers as $fieldArray) {
                    $fieldArray['tx_gridelements_children'] = (int)$fieldArray['tx_gridelements_children'] + (int)$containerUpdateArray[$fieldArray['uid']];
                    $this->getConnection()->update(
                        'tt_content',
                        $fieldArray,
                        ['uid' => (int)$fieldArray['uid']]
                    );
                    $this->getTceMain()->updateRefIndex('tt_content', (int)$fieldArray['uid']);
                }
            }
        }
    }

    /**
     * getter for table
     *
     * @return string table
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * setter for table
     *
     * @param string $table
     */
    public function setTable($table)
    {
        $this->table = $table;
    }

    /**
     * getter for pageUid
     *
     * @return integer pageUid
     */
    public function getPageUid()
    {
        return $this->pageUid;
    }

    /**
     * setter for pageUid
     *
     * @param integer $pageUid
     */
    public function setPageUid($pageUid)
    {
        $this->pageUid = $pageUid;
    }

}
