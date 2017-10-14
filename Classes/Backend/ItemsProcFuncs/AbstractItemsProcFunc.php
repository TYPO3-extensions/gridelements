<?php

namespace GridElementsTeam\Gridelements\Backend\ItemsProcFuncs;

/***************************************************************
 *  Copyright notice
 *  (c) 2013 Jo Hasenau <info@cybercraft.de>
 *  (c) 2013 Stefan froemken <froemken@gmail.com>
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
use TYPO3\CMS\Backend\View\BackendLayoutView;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\QueryGenerator;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Class/Function which offers TCE main hook functions.
 *
 * @author Jo Hasenau <info@cybercraft.de>
 * @package TYPO3
 * @subpackage tx_gridelements
 */
abstract class AbstractItemsProcFunc implements SingletonInterface
{
    /**
     * @var LanguageService
     */
    protected $languageService;

    /**
     * @var QueryGenerator
     */
    protected $tree;

    /**
     * initializes this class
     */
    public function init()
    {
        $this->setLanguageService($GLOBALS['LANG']);
    }

    /**
     * Gets the selected backend layout
     *
     * @param int $pageId The uid of the page we are currently working on
     *
     * @return array|null An array containing the data of the selected backend layout as well as a parsed version of the layout configuration
     */
    public function getSelectedBackendLayout($pageId)
    {
        $backendLayoutData = GeneralUtility::callUserFunction(BackendLayoutView::class . '->getSelectedBackendLayout',
            $pageId, $this);
        // add allowed CTypes to the columns, since this is not done by the native core methods
        if (!empty($backendLayoutData['__items'])) {
            if (!empty($backendLayoutData['__config']['backend_layout.']['rows.'])) {
                foreach ($backendLayoutData['__config']['backend_layout.']['rows.'] as $row) {
                    if (!empty($row['columns.'])) {
                        foreach ($row['columns.'] as $column) {
                            $backendLayoutData['columns'][$column['colPos']] = $column['allowed'] ? $column['allowed'] : '*';
                            $backendLayoutData['columns']['allowed'] .= $backendLayoutData['columns']['allowed']
                                ? ',' . $backendLayoutData['columns'][$column['colPos']]
                                : $backendLayoutData['columns'][$column['colPos']];
                        }
                    }
                }
            }
            foreach ($backendLayoutData['__items'] as $key => $item) {
                $backendLayoutData['__items'][$key][3] = $backendLayoutData['columns'][$item[1]];
            }
        };

        return $backendLayoutData;
    }

    /**
     * This method is a wrapper for unitTests because of the static method
     *
     * @param int $pageId
     *
     * @return array
     */
    public function getRootline($pageId)
    {
        return BackendUtility::BEgetRootLine($pageId);
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
     * getter for languageService
     *
     * @return LanguageService $languageService
     */
    public function getLanguageService()
    {
        return $this->languageService;
    }

    /**
     * setter for languageService object
     *
     * @param LanguageService $languageService
     *
     * @return void
     */
    public function setLanguageService(LanguageService $languageService)
    {
        $this->languageService = $languageService;
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
