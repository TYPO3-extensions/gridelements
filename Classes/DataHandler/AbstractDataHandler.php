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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DefaultRestrictionContainer;
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
     * @var QueryBuilder
     */
    protected $queryBuilder;

    /**
     * @var ExpressionBuilder
     */
    protected $expressionBuilder;

    /**
     * @var DefaultRestrictionContainer
     */
    protected $restrictions;

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
     * @param string $table : The name of the table the data should be saved to
     * @param integer $pageUid : The uid of the page we are currently working on
     * @param DataHandler $dataHandler
     */
    public function init($table, $pageUid, DataHandler $dataHandler)
    {
        $this->setTable($table);
        $this->setPageUid($pageUid);
        $this->setTceMain($dataHandler);
        $this->setQueryBuilder();
        $this->setExpressionBuilder();
        if (!$this->layoutSetup instanceof LayoutSetup) {
            if ($pageUid < 0) {
                $pageUid = Helper::getInstance()->getPidFromNegativeUid($pageUid);
            }
            $this->injectLayoutSetup(GeneralUtility::makeInstance(LayoutSetup::class)->init($pageUid));
        }
    }

    /**
     * Function to handle record actions between different grid containers
     *
     * @param array $containerUpdateArray
     */
    public function doGridContainerUpdate($containerUpdateArray = array())
    {
        if (is_array($containerUpdateArray) && !empty($containerUpdateArray)) {
            foreach ($containerUpdateArray as $containerUid => $newElement) {
                $fieldArray = array('tx_gridelements_children' => 'tx_gridelements_children + ' . (int)$newElement);
                $this->databaseConnection->exec_UPDATEquery('tt_content', 'uid=' . (int)$containerUid, $fieldArray,
                    'tx_gridelements_children');
                $this->getTceMain()->updateRefIndex('tt_content', (int)$containerUid);
            }
        }
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
        $currentValues = $this->getQueryBuilder()
            ->select('uid', 'tx_gridelements_container', 'tx_gridelements_columns', 'sys_language_uid', 'colPos', 'l18n_parent')
            ->from('tt_content')
            ->where(
                $this->getExpressionBuilder()->andX(
                    $this->getExpressionBuilder()->eq('deleted', 0),
                    $this->getExpressionBuilder()->eq('uid', (int)$uid)
                )
            )
            ->setMaxResults(1)
            ->execute()
            ->fetch();
        if (!empty($currentValues['l18n_parent'])) {
            $currentValues = $this->getQueryBuilder()
                ->select('uid', 'tx_gridelements_container', 'tx_gridelements_columns', 'sys_language_uid', 'colPos', 'l18n_parent')
                ->from('tt_content')
                ->where(
                    $this->getExpressionBuilder()->andX(
                        $this->getExpressionBuilder()->eq('deleted', 0),
                        $this->getExpressionBuilder()->eq('uid', (int)$currentValues['l18n_parent'])
                    )
                )
                ->setMaxResults(1)
                ->execute()
                ->fetch();
        }
        if (empty($currentValues['uid'])) {
            return;
        }
        $translatedElementQuery = $this->getQueryBuilder()
            ->select('uid', 'tx_gridelements_container', 'tx_gridelements_columns', 'sys_language_uid', 'colPos', 'l18n_parent')
            ->from('tt_content')
            ->where(
                $this->getExpressionBuilder()->andX(
                    $this->getExpressionBuilder()->eq('deleted', 0),
                    $this->getExpressionBuilder()->eq('l18n_parent', (int)$currentValues['uid'])
                )
            )
            ->execute();
        $translatedElements = [];
        while ($translatedElement = $translatedElementQuery->fetch()) {
            $translatedElements[$translatedElements['uid']] = $translatedElements;
        }
        if (empty($translatedElements)) {
            return;
        }
        $translatedContainers = [];
        if ($currentValues['tx_gridelements_container'] > 0) {
            $translatedContainerQuery = $this->getQueryBuilder()
                ->select('uid', 'sys_language_uid')
                ->from('tt_content')
                ->where(
                    $this->getExpressionBuilder()->andX(
                        $this->getExpressionBuilder()->eq('deleted', 0),
                        $this->getExpressionBuilder()->eq('l18n_parent', (int)$currentValues['tx_gridelements_container'])
                    )
                )
                ->execute();
            while ($translatedContainer = $translatedContainerQuery->fetch()) {
                $translatedContainers[$translatedContainer['sys_language_uid']] = $translatedContainer;
            }
        }
        $containerUpdateArray = array();
        foreach ($translatedElements as $translatedUid => $translatedElement) {
            $updateArray = array();
            if (isset($translatedContainers[$translatedElement['sys_language_uid']])) {
                $updateArray['tx_gridelements_container'] = (int)$translatedContainers[$translatedElement['sys_language_uid']]['uid'];
                $updateArray['tx_gridelements_columns'] = (int)$currentValues['tx_gridelements_columns'];
            }
            $updateArray['colPos'] = (int)$currentValues['colPos'];

            $this->databaseConnection->exec_UPDATEquery('tt_content', 'uid=' . (int)$translatedUid,
                $updateArray,
                'tx_gridelements_container,tx_gridelements_columns,colPos'
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
     * setter for table
     *
     * @param string $table
     */
    public function setTable($table)
    {
        $this->table = $table;
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
     * setter for pageUid
     *
     * @param integer $pageUid
     */
    public function setPageUid($pageUid)
    {
        $this->pageUid = $pageUid;
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
     * setter for dataHandler object
     *
     * @param DataHandler $dataHandler
     */
    public function setTceMain(DataHandler $dataHandler)
    {
        $this->dataHandler = $dataHandler;
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
     * setter for queryBuilder object
     *
     * @return void
     */
    public function setQueryBuilder()
    {
        $this->queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tt_content');
    }

    /**
     * getter for queryBuilder
     *
     * @return QueryBuilder queryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }

    /**
     * setter for expressionBuilder object
     *
     * @return void
     */
    public function setExpressionBuilder()
    {
        $this->expressionBuilder = $this->getQueryBuilder()->expr();
    }

    /**
     * getter for ExpressionBuilder
     *
     * @return ExpressionBuilder
     */
    public function getExpressionBuilder()
    {
        return $this->expressionBuilder;
    }

    /**
     * setter for restrictions object
     *
     * @return void
     */
    public function setRestrictions()
    {
        $this->restrictions = GeneralUtility::makeInstance(DefaultRestrictionContainer::class);
    }

    /**
     * getter for restrictions
     *
     * @return DefaultRestrictionContainer restrictions
     */
    public function getRestrictions()
    {
        return $this->restrictions;
    }

}
