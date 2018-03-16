<?php

namespace GridElementsTeam\Gridelements\Hooks;

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
use TYPO3\CMS\Backend\Controller\PageLayoutController;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\PageLayoutView;
use TYPO3\CMS\Backend\View\PageLayoutViewDrawFooterHookInterface;
use TYPO3\CMS\Backend\View\PageLayoutViewDrawItemHookInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction;
use TYPO3\CMS\Core\Database\QueryGenerator;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Class/Function which manipulates the rendering of item example content and replaces it with a grid of child elements.
 *
 * @author Jo Hasenau <info@cybercraft.de>
 * @package TYPO3
 * @subpackage tx_gridelements
 */
class DrawItem implements PageLayoutViewDrawItemHookInterface, SingletonInterface
{

    /**
     * @var array
     */
    protected $extentensionConfiguration;

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * @var LanguageService
     */
    protected $languageService;

    /**
     * Stores whether a certain language has translations in it
     *
     * @var array
     */
    protected $languageHasTranslationsCache = [];

    /**
     * @var QueryGenerator
     */
    protected $tree;

    /**
     * @var bool
     */
    protected $showHidden;

    /**
     * @var string
     */
    protected $backPath = '';

    public function __construct()
    {
        $this->extentensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['gridelements']);
        $this->setLanguageService($GLOBALS['LANG']);
        $this->helper = Helper::getInstance();
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->cleanupCollapsedStatesInUC();
    }

    /**
     * Processes the collapsed states of Gridelements columns and removes columns with 0 values
     */
    public function cleanupCollapsedStatesInUC()
    {
        $backendUser = $this->getBackendUser();
        if (is_array($backendUser->uc['moduleData']['page']['gridelementsCollapsedColumns'])) {
            $collapsedGridelementColumns = $backendUser->uc['moduleData']['page']['gridelementsCollapsedColumns'];
            foreach ($collapsedGridelementColumns as $item => $collapsed) {
                if (empty($collapsed)) {
                    unset($collapsedGridelementColumns[$item]);
                }
            }
            $backendUser->uc['moduleData']['page']['gridelementsCollapsedColumns'] = $collapsedGridelementColumns;
            $backendUser->writeUC($backendUser->uc);
        }
    }

    /**
     * @return BackendUserAuthentication
     */
    public function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Processes the item to be rendered before the actual example content gets rendered
     * Deactivates the original example content output
     *
     * @param PageLayoutView $parentObject : The parent object that triggered this hook
     * @param boolean $drawItem : A switch to tell the parent object, if the item still must be drawn
     * @param string $headerContent : The content of the item header
     * @param string $itemContent : The content of the item itself
     * @param array $row : The current data row for this item
     *
     * @return void
     */
    public function preProcess(PageLayoutView &$parentObject, &$drawItem, &$headerContent, &$itemContent, array &$row)
    {
        if ($row['CType']) {
            $this->showHidden = $parentObject->tt_contentConfig['showHidden'] ? true : false;

            if ($this->helper->getBackendUser()->uc['hideContentPreview']) {
                $itemContent = '';
                $drawItem = false;
            }

            switch ($row['CType']) {
                case 'gridelements_pi1':
                    $drawItem = false;
                    $itemContent .= $this->renderCTypeGridelements($parentObject, $row);
                    $refIndexObj = GeneralUtility::makeInstance(ReferenceIndex::class);
                    /* @var $refIndexObj \TYPO3\CMS\Core\Database\ReferenceIndex */
                    $refIndexObj->updateRefIndexTable('tt_content', (int)$row['uid']);
                    break;
                case 'shortcut':
                    $drawItem = false;
                    $itemContent .= $this->renderCTypeShortcut($parentObject, $row);
                    break;
            }
        }
        $listType = $row['list_type'] && $row['CType'] === 'list' ? ' data-list_type="' . $row['list_type'] . '"' : '';
        $gridType = $row['tx_gridelements_backend_layout'] && $row['CType'] === 'gridelements_pi1' ? ' data-tx_gridelements_backend_layout="' . $row['tx_gridelements_backend_layout'] . '"' : '';
        $headerContent = '<div id="element-tt_content-' . $row['uid'] . '" class="t3-ctype-identifier " data-ctype="' . $row['CType'] . '"' . $listType . $gridType . '>' . $headerContent . '</div>';
    }

    /**
     * renders the HTML output for elements of the CType gridelements_pi1
     *
     * @param PageLayoutView $parentObject : The parent object that triggered this hook
     * @param array $row : The current data row for this item
     *
     * @return string $itemContent: The HTML output for elements of the CType gridelements_pi1
     */
    protected function renderCTypeGridelements(PageLayoutView $parentObject, &$row)
    {
        $head = [];
        $gridContent = [];
        $editUidList = [];
        $colPosValues = [];
        $singleColumn = false;

        // get the layout record for the selected backend layout if any
        $gridContainerId = $row['uid'];
        if ($row['pid'] < 0) {
            $originalRecord = BackendUtility::getRecord('tt_content', $row['t3ver_oid']);
        } else {
            $originalRecord = $row;
        }
        /** @var $layoutSetup LayoutSetup */
        $layoutSetup = GeneralUtility::makeInstance(LayoutSetup::class)->init($originalRecord['pid']);
        $gridElement = $layoutSetup->cacheCurrentParent($gridContainerId, true);
        $layoutUid = $gridElement['tx_gridelements_backend_layout'];
        $layout = $layoutSetup->getLayoutSetup($layoutUid);
        $parserRows = null;
        if (isset($layout['config']) && isset($layout['config']['rows.'])) {
            $parserRows = $layout['config']['rows.'];
        }

        // if there is anything to parse, lets check for existing columns in the layout
        if (is_array($parserRows) && !empty($parserRows)) {
            $this->setMultipleColPosValues($parserRows, $colPosValues, $layout);
        } else {
            $singleColumn = true;
            $this->setSingleColPosItems($parentObject, $colPosValues, $gridElement);
        }
        // if there are any columns, lets build the content for them
        $outerTtContentDataArray = $parentObject->tt_contentData['nextThree'];
        if (!empty($colPosValues)) {
            $this->renderGridColumns($parentObject, $colPosValues, $gridContent, $gridElement, $editUidList,
                $singleColumn, $head);
        }
        $parentObject->tt_contentData['nextThree'] = $outerTtContentDataArray;

        // if we got a selected backend layout, we have to create the layout table now
        if ($layoutUid && isset($layout['config'])) {
            $itemContent = $this->renderGridLayoutTable($layout, $gridElement, $head, $gridContent);
        } else {
            $itemContent = '<div class="t3-grid-container t3-grid-element-container">';
            $itemContent .= '<table border="0" cellspacing="0" cellpadding="0" width="100%" height="100%" class="t3-page-columns t3-grid-table">';
            $itemContent .= '<tr><td valign="top" class="t3-grid-cell t3-page-column t3-page-column-0">' . $gridContent[0] . '</td></tr>';
            $itemContent .= '</table></div>';
        }

        return $itemContent;
    }

    /**
     * Sets column positions based on a selected gridelement layout
     *
     * @param array $parserRows : The parsed rows of the gridelement layout
     * @param array $colPosValues : The column positions that have been found for that layout
     * @param array $layout
     * @return void
     */
    protected function setMultipleColPosValues($parserRows, &$colPosValues, $layout)
    {
        if (is_array($parserRows)) {
            foreach ($parserRows as $parserRow) {
                if (is_array($parserRow['columns.']) && !empty($parserRow['columns.'])) {
                    foreach ($parserRow['columns.'] as $parserColumns) {
                        $name = $this->languageService->sL($parserColumns['name'], true);
                        if (isset($parserColumns['colPos']) && $parserColumns['colPos'] !== '') {
                            $columnKey = (int)$parserColumns['colPos'];
                            $colPosValues[$columnKey] = [
                                'name'       => $name,
                                'allowed'    => $layout['allowed'][$columnKey],
                                'disallowed' => $layout['disallowed'][$columnKey],
                                'maxitems'   => $layout['maxitems'][$columnKey],
                            ];
                        } else {
                            $colPosValues[32768] = [
                                'name'       => $this->languageService->getLL('notAssigned'),
                                'allowed'    => '',
                                'disallowed' => '*',
                                'maxitems'   => 0,
                            ];
                        }
                    }
                }
            }
        }
    }

    /**
     * Directly returns the items for a single column if the rendering mode is set to single columns only
     *
     * @param PageLayoutView $parentObject : The parent object that triggered this hook
     * @param array $colPosValues : The column positions that have been found for that layout
     * @param array $row : The current data row for the container item
     *
     * @return array collected items for this column
     */
    protected function setSingleColPosItems(PageLayoutView $parentObject, &$colPosValues, &$row)
    {
        $specificIds = $this->helper->getSpecificIds($row);

        $expressionBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tt_content')
            ->expr();
        $queryBuilder = $parentObject->getQueryBuilder(
            'tt_content',
            $specificIds['pid'], [
                $expressionBuilder->eq('colPos', -1),
                $expressionBuilder->in('tx_gridelements_container', [(int)$row['uid'], $specificIds['uid']]),
            ]
        );
        $restrictions = $queryBuilder->getRestrictions();
        if ($this->showHidden) {
            $restrictions->removeByType(HiddenRestriction::class);
        }
        $restrictions->removeByType(StartTimeRestriction::class);
        $restrictions->removeByType(EndTimeRestriction::class);
        $queryBuilder->setRestrictions($restrictions);

        $colPosValues[] = [0, ''];

        return $parentObject->getResult($queryBuilder->execute());
    }

    /**
     * renders the columns of a grid layout
     *
     * @param PageLayoutView $parentObject : The parent object that triggered this hook
     * @param array $colPosValues : The column positions we want to get the content for
     * @param array $gridContent : The rendered content data of the grid columns
     * @param array $row : The current data row for the container item
     * @param array $editUidList : determines if we will get edit icons or not
     * @param boolean $singleColumn : Determines if we are in single column mode or not
     * @param array $head : An array of headers for each of the columns
     *
     * @return void
     */
    protected function renderGridColumns(
        PageLayoutView $parentObject,
        &$colPosValues,
        &$gridContent,
        &$row,
        &$editUidList,
        &$singleColumn,
        &$head
    ) {
        $collectedItems = $this->collectItemsForColumns($parentObject, $colPosValues, $row);
        foreach ($colPosValues as $colPos => $values) {
            // first we have to create the column content separately for each column
            // so we can check for the first and the last element to provide proper sorting
            $counter = 0;
            if ($singleColumn === false) {
                $items = [];
                foreach ($collectedItems as $item) {
                    if ((int)$item['tx_gridelements_columns'] === $colPos) {
                        if (
                            $row['sys_language_uid'] === $item['sys_language_uid'] ||
                            ($row['sys_language_uid'] === -1 && $item['sys_language_uid'] === 0)
                        ) {
                            $counter++;
                        }
                        $items[] = $item;
                    }
                }
            } else {
                $items = [];
            }
            // if there are any items, we can create the HTML for them just like in the original TCEform
            $gridContent['numberOfItems'][$colPos] = $counter;
            $this->renderSingleGridColumn($parentObject, $items, $colPos, $values, $gridContent, $row, $editUidList);
            // we will need a header for each of the columns to activate mass editing for elements of that column
            $expanded = $this->helper->getBackendUser()->uc['moduleData']['page']['gridelementsCollapsedColumns'][$row['uid'] . '_' . $colPos] ? false : true;
            $this->setColumnHeader($parentObject, $head, $colPos, $values['name'], $editUidList, $expanded);
        }
    }

    /**
     * Collects tt_content data from a single tt_content element
     *
     * @param PageLayoutView $parentObject : The paren object that triggered this hook
     * @param array $colPosValues : The column position to collect the items for
     * @param array $row : The current data row for the container item
     *
     * @return mixed collected items for the given column
     */
    protected function collectItemsForColumns(PageLayoutView $parentObject, &$colPosValues, &$row)
    {
        $colPosList = array_keys($colPosValues);
        $specificIds = $this->helper->getSpecificIds($row);

        $queryBuilder = $this->getQueryBuilder();
        $constraints = [
            $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($row['pid'], \PDO::PARAM_INT)),
            $queryBuilder->expr()->eq('colPos', $queryBuilder->createNamedParameter(-1, \PDO::PARAM_INT)),
            $queryBuilder->expr()->in('tx_gridelements_container',
                $queryBuilder->createNamedParameter([(int)$row['uid'], $specificIds['uid']],
                    Connection::PARAM_INT_ARRAY)),
            $queryBuilder->expr()->in('tx_gridelements_columns',
                $queryBuilder->createNamedParameter($colPosList, Connection::PARAM_INT_ARRAY)),
        ];
        if (!$parentObject->tt_contentConfig['languageMode']) {
            $constraints[] = $queryBuilder->expr()->orX(
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter(-1, \PDO::PARAM_INT)),
                $queryBuilder->expr()->eq('sys_language_uid',
                    $queryBuilder->createNamedParameter((int)$parentObject->tt_contentConfig['sys_language_uid'],
                        \PDO::PARAM_INT))
            );
        } elseif ($row['sys_language_uid'] > 0) {
            $constraints[] = $queryBuilder->expr()->eq('sys_language_uid',
                $queryBuilder->createNamedParameter((int)$row['sys_language_uid'], \PDO::PARAM_INT));
        }
        if ($this->helper->getBackendUser()->workspace > 0 && $row['t3ver_wsid'] > 0) {
            $constraints[] = $queryBuilder->expr()->eq('t3ver_wsid',
                $queryBuilder->createNamedParameter((int)$row['t3ver_wsid'], \PDO::PARAM_INT));
        }

        $queryBuilder
            ->select('*')
            ->from('tt_content')
            ->where(
                ...$constraints
            )
            ->orderBy('sorting');

        $restrictions = $queryBuilder->getRestrictions();
        if ($this->showHidden) {
            $restrictions->removeByType(HiddenRestriction::class);
        }
        $restrictions->removeByType(StartTimeRestriction::class);
        $restrictions->removeByType(EndTimeRestriction::class);
        $queryBuilder->setRestrictions($restrictions);

        return $queryBuilder->execute()->fetchAll();
    }

    /**
     * getter for queryBuilder
     *
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        /**
         * @var $queryBuilder QueryBuilder
         */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tt_content');
        return $queryBuilder;
    }

    /**
     * renders a single column of a grid layout and sets the edit uid list
     *
     * @param PageLayoutView $parentObject : The parent object that triggered this hook
     * @param array $items : The content data of the column to be rendered
     * @param int $colPos : The column position we want to get the content for
     * @param array $values : The layout configuration values for the grid column
     * @param array $gridContent : The rendered content data of the grid column
     * @param $row
     * @param array $editUidList : determines if we will get edit icons or not
     */
    protected function renderSingleGridColumn(
        PageLayoutView $parentObject,
        &$items,
        &$colPos,
        $values,
        &$gridContent,
        $row,
        &$editUidList
    ) {

        $specificIds = $this->helper->getSpecificIds($row);
        $allowed = base64_encode(json_encode($values['allowed']));
        $disallowed = base64_encode(json_encode($values['disallowed']));
        $maxItems = (int)$values['maxitems'];
        $url = '';
        $pageinfo = BackendUtility::readPageAccess($parentObject->id, '');
        if (!empty($this->getPageLayoutController()) && get_class($this->getPageLayoutController()) === PageLayoutController::class) {
            $contentIsNotLockedForEditors = $this->getPageLayoutController()->contentIsNotLockedForEditors();
        } else {
            $contentIsNotLockedForEditors = true;
        }
        if ($colPos < 32768) {
            if ($contentIsNotLockedForEditors
                && $this->getBackendUser()->doesUserHaveAccess($pageinfo, Permission::CONTENT_EDIT)
                && (!$this->checkIfTranslationsExistInLanguage($items, $row['sys_language_uid'], $parentObject))
            ) {
                if ($parentObject->option_newWizard) {
                    $urlParameters = [
                        'id'                         => $parentObject->id,
                        'sys_language_uid'           => $row['sys_language_uid'],
                        'tx_gridelements_allowed'    => $allowed,
                        'tx_gridelements_disallowed' => $disallowed,
                        'tx_gridelements_container'  => $specificIds['uid'],
                        'tx_gridelements_columns'    => $colPos,
                        'colPos'                     => -1,
                        'uid_pid'                    => $parentObject->id,
                        'returnUrl'                  => GeneralUtility::getIndpEnv('REQUEST_URI'),
                    ];
                    $url = BackendUtility::getModuleUrl('new_content_element', $urlParameters);
                } else {
                    $urlParameters = [
                        'edit'      => [
                            'tt_content' => [
                                $parentObject->id => 'new',
                            ],
                        ],
                        'defVals'   => [
                            'tt_content' => [
                                'sys_language_uid'           => $row['sys_language_uid'],
                                'tx_gridelements_allowed'    => $allowed,
                                'tx_gridelements_disallowed' => $disallowed,
                                'tx_gridelements_container'  => $specificIds['uid'],
                                'tx_gridelements_columns'    => $colPos,
                                'colPos'                     => -1,
                            ],
                        ],
                        'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI'),
                    ];
                    $url = BackendUtility::getModuleUrl('record_edit', $urlParameters);
                }
            }
        }

        $iconsArray = [];

        if ($colPos !== '' && $colPos !== null && $colPos < 32768 && $url) {
            $iconsArray = [
                'new' => '<a href="' . htmlspecialchars($url) . '" title="' . $this->languageService->getLL('newContentElement',
                        true) . '" class="btn btn-default btn-sm">' . $this->iconFactory->getIcon('actions-document-new',
                        'small') . ' ' . $this->languageService->getLL('content', true) . '</a>',
            ];
        }

        $gridContent[$colPos] .= '<div class="t3-page-ce gridelements-collapsed-column-marker">' .
            $this->languageService->sL('LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tx_gridelements_contentcollapsed') .
            '</div>';

        $gridContent[$colPos] .= '
			<div data-colpos="' . $colPos . '" 
			     data-language-uid="' . $row['sys_language_uid'] . '" 
			     class="t3js-sortable t3js-sortable-lang t3js-sortable-lang-' . $row['sys_language_uid'] . ' t3-page-ce-wrapper ui-sortable">
			    <div class="t3-page-ce t3js-page-ce" 
			         data-container="' . $row['uid'] . '" 
			         id="' . str_replace('.', '', uniqid('', true)) . '">
					<div class="t3js-page-new-ce t3js-page-new-ce-allowed t3-page-ce-wrapper-new-ce btn-group btn-group-sm" 
					     id="colpos-' . $colPos . '-' . str_replace('.', '', uniqid('', true)) . '">' .
            implode('', $iconsArray) . '
					</div>
					<div class="t3-page-ce-dropzone-available t3js-page-ce-dropzone-available"></div>
				</div>';

        if (!empty($items)) {
            $counter = 0;
            foreach ($items as $item) {
                if (
                    $row['sys_language_uid'] === $item['sys_language_uid'] ||
                    ($row['sys_language_uid'] === -1 && $item['sys_language_uid'] === 0)
                ) {
                    $counter++;
                }
                if ((int)$item['t3ver_state'] === VersionState::DELETE_PLACEHOLDER) {
                    continue;
                }
                if (is_array($item)) {
                    $uid = (int)$item['uid'];
                    $pid = (int)$item['pid'];
                    $container = (int)$item['tx_gridelements_container'];
                    $gridColumn = (int)$item['tx_gridelements_columns'];
                    $language = (int)$item['sys_language_uid'];
                    $statusHidden = $parentObject->isDisabled('tt_content', $item) ? ' t3-page-ce-hidden' : '';
                    $maxItemsReached = $counter > $maxItems && $maxItems > 0 ? ' t3-page-ce-danger' : '';

                    $gridContent[$colPos] .= '
				<div class="t3-page-ce t3js-page-ce t3js-page-ce-sortable' . $statusHidden . $maxItemsReached . '" 
				     data-table="tt_content" id="element-tt_content-' . $uid . '" 
				     data-uid="' . $uid . '" 
				     data-container="' . $container . '" 
				     data-ctype="' . $item['CType'] . '">' .
                        $this->renderSingleElementHTML($parentObject, $item) .
                        '</div>';
                    if ($contentIsNotLockedForEditors
                        && $this->getBackendUser()->doesUserHaveAccess($pageinfo, Permission::CONTENT_EDIT)
                        && (!$this->checkIfTranslationsExistInLanguage($items, $row['sys_language_uid'], $parentObject))
                    ) {
                        // New content element:
                        $specificIds = $this->helper->getSpecificIds($item);
                        if ($parentObject->option_newWizard) {
                            $urlParameters = [
                                'id'                         => $parentObject->id,
                                'sys_language_uid'           => $language,
                                'tx_gridelements_allowed'    => $allowed,
                                'tx_gridelements_disallowed' => $disallowed,
                                'tx_gridelements_container'  => $container,
                                'tx_gridelements_columns'    => $gridColumn,
                                'colPos'                     => -1,
                                'uid_pid'                    => -$specificIds['uid'],
                                'returnUrl'                  => GeneralUtility::getIndpEnv('REQUEST_URI'),
                            ];
                            $url = BackendUtility::getModuleUrl('new_content_element', $urlParameters);
                        } else {
                            $urlParameters = [
                                'edit'      => [
                                    'tt_content' => [
                                        -$specificIds['uid'] => 'new',
                                    ],
                                ],
                                'defVals'   => [
                                    'tt_content' => [
                                        'sys_language_uid'           => $language,
                                        'tx_gridelements_allowed'    => $allowed,
                                        'tx_gridelements_disallowed' => $disallowed,
                                        'tx_gridelements_container'  => $container,
                                        'tx_gridelements_columns'    => $gridColumn,
                                        'colPos'                     => -1,
                                    ],
                                ],
                                'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI'),
                            ];
                            $url = BackendUtility::getModuleUrl('record_edit', $urlParameters);
                        }
                        $iconsArray = [
                            'new' => '<a 
                                href="' . htmlspecialchars($url) . '" 
                                title="' . $this->languageService->getLL('newContentElement', true) . '" 
                                class="btn btn-default btn-sm">' .
                                $this->iconFactory->getIcon('actions-document-new', 'small') . ' ' .
                                $this->languageService->getLL('content', true) .
                                '</a>',
                        ];
                    }

                    $gridContent[$colPos] .= '
                        <div class="t3-page-ce">
                            <div class="t3js-page-new-ce t3js-page-new-ce-allowed t3-page-ce-wrapper-new-ce btn-group btn-group-sm" 
                                 id="colpos-' . $gridColumn .
                        '-page-' . $pid .
                        '-gridcontainer-' . $container .
                        '-' . str_replace('.', '', uniqid('', true)) . '">' .
                        implode('', $iconsArray) . '
                            </div>
                        </div>
                        <div class="t3-page-ce-dropzone-available t3js-page-ce-dropzone-available"></div>
                    </div>
					';
                    $editUidList[$colPos] .= $editUidList[$colPos] ? ',' . $uid : $uid;
                }
            }
        }

        $gridContent[$colPos] .= '</div>';
    }

    /**
     * @return PageLayoutController
     */
    public function getPageLayoutController()
    {
        return $GLOBALS['SOBE'];
    }

    /**
     * Checks whether translated Content Elements exist in the desired language
     * If so, deny creating new ones via the UI
     *
     * @param array $contentElements
     * @param int $language
     * @param PageLayoutView $parentObject
     *
     * @return bool
     */
    protected function checkIfTranslationsExistInLanguage(
        array $contentElements,
        $language,
        PageLayoutView $parentObject
    ) {
        // If in default language, you may always create new entries
        // Also, you may override this strict behavior via user TS Config
        // If you do so, you're on your own and cannot rely on any support by the TYPO3 core
        // We jump out here since we don't need to do the expensive loop operations
        $allowInconsistentLanguageHandling = BackendUtility::getModTSconfig($parentObject->id,
            'mod.web_layout.allowInconsistentLanguageHandling');
        if ($language === 0 || $language === -1 || $allowInconsistentLanguageHandling['value'] === '1') {
            return false;
        }
        /**
         * Build up caches
         */
        if (!isset($this->languageHasTranslationsCache[$language])) {
            foreach ($contentElements as $contentElement) {
                if ((int)$contentElement['l18n_parent'] === 0) {
                    $this->languageHasTranslationsCache[$language]['hasStandAloneContent'] = true;
                }
                if ((int)$contentElement['l18n_parent'] > 0) {
                    $this->languageHasTranslationsCache[$language]['hasTranslations'] = true;
                }
            }
            // Check whether we have a mix of both
            if ($this->languageHasTranslationsCache[$language]['hasStandAloneContent']
                && $this->languageHasTranslationsCache[$language]['hasTranslations']
            ) {
                $message = GeneralUtility::makeInstance(
                    FlashMessage::class,
                    sprintf($this->getLanguageService()->getLL('staleTranslationWarning'),
                        $parentObject->languageIconTitles[$language]['title']),
                    sprintf($this->getLanguageService()->getLL('staleTranslationWarningTitle'),
                        $parentObject->languageIconTitles[$language]['title']),
                    FlashMessage::WARNING
                );
                $service = GeneralUtility::makeInstance(FlashMessageService::class);
                $queue = $service->getMessageQueueByIdentifier();
                $queue->addMessage($message);
            }
        }
        if ($this->languageHasTranslationsCache[$language]['hasTranslations']) {
            return true;
        }
        return false;
    }

    /**
     * getter for LanguageService
     *
     * @return LanguageService $languageService
     */
    public function getLanguageService()
    {
        return $this->languageService;
    }

    /**
     * setter for LanguageService object
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
     * Renders the HTML code for a single tt_content element
     *
     * @param PageLayoutView $parentObject : The parent object that triggered this hook
     * @param array $item : The data row to be rendered as HTML
     *
     * @return string
     */
    protected function renderSingleElementHTML(PageLayoutView $parentObject, $item)
    {
        $singleElementHTML = '';
        $parentObject->tt_contentData['nextThree'][$item['uid']] = $item['uid'];
        if (!$parentObject->tt_contentConfig['languageMode']) {
            $singleElementHTML .= '<div class="t3-page-ce-dragitem" id="' . StringUtility::getUniqueId() . '">';
        }
        $singleElementHTML .= $parentObject->tt_content_drawHeader($item,
            $parentObject->tt_contentConfig['showInfo'] ? 15 : 5, $parentObject->defLangBinding, true, true);
        $singleElementHTML .= (!empty($item['_ORIG_uid']) ? '<div class="ver-element">' : '')
            . '<div class="t3-page-ce-body-inner t3-page-ce-body-inner-' . $item['CType'] . '">'
            . $parentObject->tt_content_drawItem($item)
            . '</div>'
            . (!empty($item['_ORIG_uid']) ? '</div>' : '');
        $singleElementHTML .= $this->tt_content_drawFooter($parentObject, $item);
        if (!$parentObject->tt_contentConfig['languageMode']) {
            $singleElementHTML .= '</div>';
        }
        unset($parentObject->tt_contentData['nextThree'][$item['uid']]);

        return $singleElementHTML;
    }

    /**
     * Draw the footer for a single tt_content element
     *
     * @param PageLayoutView $parentObject : The parent object that triggered this hook
     * @param array $row Record array
     * @return string HTML of the footer
     * @throws \UnexpectedValueException
     */
    protected function tt_content_drawFooter(PageLayoutView $parentObject, array $row)
    {
        $content = '';
        // Get processed values:
        $info = [];
        $parentObject->getProcessedValue('tt_content', 'starttime,endtime,fe_group,spaceBefore,spaceAfter', $row,
            $info);

        // Content element annotation
        if (!empty($GLOBALS['TCA']['tt_content']['ctrl']['descriptionColumn'])) {
            $info[] = htmlspecialchars($row[$GLOBALS['TCA']['tt_content']['ctrl']['descriptionColumn']]);
        }

        // Call drawFooter hooks
        $drawFooterHooks = &$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawFooter'];
        if (is_array($drawFooterHooks)) {
            foreach ($drawFooterHooks as $hookClass) {
                $hookObject = GeneralUtility::getUserObj($hookClass);
                if (!$hookObject instanceof PageLayoutViewDrawFooterHookInterface) {
                    throw new \UnexpectedValueException($hookClass . ' must implement interface ' . PageLayoutViewDrawFooterHookInterface::class,
                        1404378171);
                }
                $hookObject->preProcess($parentObject, $info, $row);
            }
        }

        // Display info from records fields:
        if (!empty($info)) {
            $content = '<div class="t3-page-ce-info">
				' . implode('<br>', $info) . '
				</div>';
        }
        // Wrap it
        if (!empty($content)) {
            $content = '<div class="t3-page-ce-footer">' . $content . '</div>';
        }
        return $content;
    }

    /**
     * Sets the headers for a grid before content and headers are put together
     *
     * @param PageLayoutView $parentObject : The parent object that triggered this hook
     * @param array $head : The collected item data rows
     * @param int $colPos : The column position we want to get a header for
     * @param string $name : The name of the header
     * @param array $editUidList : determines if we will get edit icons or not
     * @param bool $expanded
     *
     * @internal param array $row : The current data row for the container item
     */
    protected function setColumnHeader(
        PageLayoutView $parentObject,
        &$head,
        &$colPos,
        &$name,
        &$editUidList,
        $expanded = true
    ) {
        $head[$colPos] = $this->tt_content_drawColHeader($name,
            ($parentObject->doEdit && $editUidList[$colPos]) ? '&edit[tt_content][' . $editUidList[$colPos] . ']=edit' : '',
            $parentObject, $expanded);
    }

    /**
     * Draw header for a content element column:
     *
     * @param string $colName Column name
     * @param string $editParams Edit params (Syntax: &edit[...] for FormEngine)
     * @param \TYPO3\CMS\Backend\View\PageLayoutView $parentObject
     * @param boolean $expanded
     *
     * @return string HTML table
     */
    protected function tt_content_drawColHeader($colName, $editParams, PageLayoutView $parentObject, $expanded = true)
    {
        $iconsArr = [];
        // Create command links:
        if ($parentObject->tt_contentConfig['showCommands']) {
            // Edit whole of column:
            if ($editParams) {
                $iconsArr['edit'] = '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick($editParams)) . '" title="' . $this->getLanguageService()->getLL('editColumn',
                        true) . '">' . $this->iconFactory->getIcon('actions-document-open',
                        Icon::SIZE_SMALL)->render() . '</a>';
            }
        }

        if ($expanded) {
            $state = 'expanded';
            $title = $this->languageService->sL('LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tx_gridelements_collapsecontent');
            $toggleTitle = $this->languageService->sL('LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tx_gridelements_expandcontent');
        } else {
            $state = 'collapsed';
            $title = $this->languageService->sL('LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tx_gridelements_expandcontent');
            $toggleTitle = $this->languageService->sL('LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tx_gridelements_collapsecontent');
        }

        $iconsArr['toggleContent'] = '<a href="#" class="btn btn-default t3js-toggle-gridelements-column toggle-content" title="' . $title . '" data-toggle-title="' . $toggleTitle . '" data-state="' . $state . '">' . $this->iconFactory->getIcon('actions-view-list-collapse',
                'small') . $this->iconFactory->getIcon('actions-view-list-expand',
                'small') . '</a>';
        $icons = '<div class="t3-page-column-header-icons btn-group btn-group-sm">' . implode('',
                $iconsArr) . '</div>';
        // Create header row:
        $out = '<div class="t3-page-column-header">
					' . $icons . '
					<div class="t3-page-column-header-label">' . htmlspecialchars($colName) . '</div>
				</div>';

        return $out;
    }

    /**
     * Renders the grid layout table after the HTML content for the single elements has been rendered
     *
     * @param array $layout : The setup of the layout that is selected for the grid we are going to render
     * @param array $row : The current data row for the container item
     * @param array $head : The data for the column headers of the grid we are going to render
     * @param array $gridContent : The content data of the grid we are going to render
     *
     * @return string
     */
    protected function renderGridLayoutTable($layout, $row, $head, $gridContent)
    {
        $specificIds = $this->helper->getSpecificIds($row);
        $grid = '<div class="t3-grid-container t3-grid-element-container' . ($layout['frame'] ? ' t3-grid-container-framed t3-grid-container-' . htmlspecialchars($layout['frame']) : '') . ($layout['top_level_layout'] ? ' t3-grid-tl-container' : '') . '">';
        if ($layout['frame'] || $this->helper->getBackendUser()->uc['showGridInformation'] === 1) {
            $grid .= '<h4 class="t3-grid-container-title-' . htmlspecialchars($layout['frame']) . '">' .
                BackendUtility::wrapInHelp(
                    'tx_gridelements_backend_layouts',
                    'title',
                    $this->languageService->sL($layout['title']), [
                        'title'       => $this->languageService->sL($layout['title']),
                        'description' => $this->languageService->sL($layout['description']),
                    ]
                ) . '</h4>';
        }
        $grid .= '<table border="0" cellspacing="0" cellpadding="0" width="100%" height="100%" class="t3-page-columns t3-grid-table">';
        // add colgroups
        $colCount = 0;
        $rowCount = 0;
        if (isset($layout['config'])) {
            if (isset($layout['config']['colCount'])) {
                $colCount = (int)$layout['config']['colCount'];
            }
            if (isset($layout['config']['rowCount'])) {
                $rowCount = (int)$layout['config']['rowCount'];
            }
        }
        $grid .= '<colgroup>';
        for ($i = 0; $i < $colCount; $i++) {
            $grid .= '<col style="width:' . (100 / $colCount) . '%"></col>';
        }
        $grid .= '</colgroup>';
        // cycle through rows
        for ($layoutRow = 1; $layoutRow <= $rowCount; $layoutRow++) {
            $rowConfig = $layout['config']['rows.'][$layoutRow . '.'];
            if (!isset($rowConfig) || !isset($rowConfig['columns.'])) {
                continue;
            }
            $grid .= '<tr>';
            foreach ($rowConfig['columns.'] as $column => $columnConfig) {
                if (!isset($columnConfig)) {
                    continue;
                }
                // which column should be displayed inside this cell
                $columnKey = isset($columnConfig['colPos']) && $columnConfig['colPos'] !== '' ? (int)$columnConfig['colPos'] : 32768;
                // first get disallowed CTypes
                $disallowedContentTypes = $layout['disallowed'][$columnKey]['CType'];
                if (!isset($disallowedContentTypes['*']) && !empty($disallowedContentTypes)) {
                    foreach ($disallowedContentTypes as $key => &$ctype) {
                        $ctype = $key;
                    }
                } else {
                    if (isset($disallowedContentTypes['*'])) {
                        $disallowedGridTypes['*'] = '*';
                    } else {
                        $disallowedContentTypes = [];
                    }
                }
                // when everything is disallowed, no further checks are necessary
                if (!isset($disallowedContentTypes['*'])) {
                    $allowedContentTypes = $layout['allowed'][$columnKey]['CType'];
                    if (!isset($allowedContentTypes['*']) && !empty($allowedContentTypes)) {
                        // set allowed CTypes unless they are disallowed
                        foreach ($allowedContentTypes as $key => &$ctype) {
                            if (isset($disallowedContentTypes[$key])) {
                                unset($allowedContentTypes[$key]);
                                unset($disallowedContentTypes[$key]);
                            } else {
                                $ctype = $key;
                            }
                        }
                    } else {
                        $allowedContentTypes = [];
                    }
                    // get disallowed list types
                    $disallowedListTypes = $layout['disallowed'][$columnKey]['list_type'];
                    if (!isset($disallowedListTypes['*']) && !empty($disallowedListTypes)) {
                        foreach ($disallowedListTypes as $key => &$ctype) {
                            $ctype = $key;
                        }
                    } else {
                        if (isset($disallowedListTypes['*'])) {
                            // when each list type is disallowed, no CType list is necessary anymore
                            $disallowedListTypes['*'] = '*';
                            unset($allowedContentTypes['list']);
                        } else {
                            $disallowedListTypes = [];
                        }
                    }
                    // when each list type is disallowed, no further list type checks are necessary
                    if (!isset($disallowedListTypes['*'])) {
                        $allowedListTypes = $layout['allowed'][$columnKey]['list_type'];
                        if (!isset($allowedListTypes['*']) && !empty($allowedListTypes)) {
                            foreach ($allowedListTypes as $listType => &$listTypeData) {
                                // set allowed list types unless they are disallowed
                                if (isset($disallowedListTypes[$listType])) {
                                    unset($allowedListTypes[$listType]);
                                    unset($disallowedListTypes[$listType]);
                                } else {
                                    $listTypeData = $listType;
                                }
                            }
                        } else {
                            if (!empty($allowedContentTypes) && !empty($allowedListTypes)) {
                                $allowedContentTypes['list'] = 'list';
                            }
                            unset($allowedListTypes);
                        }
                    } else {
                        $allowedListTypes = [];
                    }
                    // get disallowed grid types
                    $disallowedGridTypes = $layout['disallowed'][$columnKey]['tx_gridelements_backend_layout'];
                    if (!isset($disallowedGridTypes['*']) && !empty($disallowedGridTypes)) {
                        foreach ($disallowedGridTypes as $key => &$ctype) {
                            $ctype = $key;
                        }
                    } else {
                        if (isset($disallowedGridTypes['*'])) {
                            // when each list type is disallowed, no CType gridelements_pi1 is necessary anymore
                            $disallowedGridTypes['*'] = '*';
                            unset($allowedContentTypes['gridelements_pi1']);
                        } else {
                            $disallowedGridTypes = [];
                        }
                    }
                    // when each list type is disallowed, no further grid types checks are necessary
                    if (!isset($disallowedGridTypes['*'])) {
                        $allowedGridTypes = $layout['allowed'][$columnKey]['tx_gridelements_backend_layout'];
                        if (!isset($allowedGridTypes['*']) && !empty($allowedGridTypes)) {
                            foreach ($allowedGridTypes as $gridType => &$gridTypeData) {
                                // set allowed grid types unless they are disallowed
                                if (isset($disallowedGridTypes[$gridType])) {
                                    unset($allowedGridTypes[$gridType]);
                                    unset($disallowedGridTypes[$gridType]);
                                } else {
                                    $gridTypeData = $gridType;
                                }
                            }
                        } else {
                            if (!empty($allowedContentTypes) && !empty($allowedGridTypes)) {
                                $allowedContentTypes['gridelements_pi1'] = 'gridelements_pi1';
                            }
                            unset($allowedGridTypes);
                        }
                    } else {
                        $allowedGridTypes = [];
                    }
                } else {
                    $allowedContentTypes = [];
                }
                // render the grid cell
                $colSpan = (int)$columnConfig['colspan'];
                $rowSpan = (int)$columnConfig['rowspan'];
                $maxItems = (int)$columnConfig['maxitems'];
                $disableNewContent = $gridContent['numberOfItems'][$columnKey] >= $maxItems && $maxItems > 0;
                $tooManyItems = $gridContent['numberOfItems'][$columnKey] > $maxItems && $maxItems > 0;
                $expanded = $this->helper->getBackendUser()->uc['moduleData']['page']['gridelementsCollapsedColumns'][$row['uid'] . '_' . $columnKey] ? 'collapsed' : 'expanded';
                $grid .= '<td valign="top"' .
                    (isset($columnConfig['colspan']) ? ' colspan="' . $colSpan . '"' : '') .
                    (isset($columnConfig['rowspan']) ? ' rowspan="' . $rowSpan . '"' : '') .
                    'data-colpos="' . $columnKey . '" data-columnkey="' . $specificIds['uid'] . '_' . $columnKey . '"
					class="t3-grid-cell t3js-page-column t3-page-column t3-page-column-' . $columnKey .
                    (!isset($columnConfig['colPos']) || $columnConfig['colPos'] === '' ? ' t3-grid-cell-unassigned' : '') .
                    (isset($columnConfig['colspan']) && $columnConfig['colPos'] !== '' ? ' t3-grid-cell-width' . $colSpan : '') .
                    (isset($columnConfig['rowspan']) && $columnConfig['colPos'] !== '' ? ' t3-grid-cell-height' . $rowSpan : '') .
                    ($disableNewContent ? ' t3-page-ce-disable-new-ce' : '') .
                    ($layout['horizontal'] ? ' t3-grid-cell-horizontal' : '') . ' ' . $expanded . '"' .
                    ' data-allowed-ctype="' . (!empty($allowedContentTypes) ? join(',',
                        $allowedContentTypes) : '*') . '"' .
                    (!empty($disallowedContentTypes) ? ' data-disallowed-ctype="' . join(',',
                            $disallowedContentTypes) . '"' : '') .
                    (!empty($allowedListTypes) ? ' data-allowed-list_type="' . join(',',
                            $allowedListTypes) . '"' : '') .
                    (!empty($disallowedListTypes) ? ' data-disallowed-list_type="' . join(',',
                            $disallowedListTypes) . '"' : '') .
                    (!empty($allowedGridTypes) ? ' data-allowed-tx_gridelements_backend_layout="' . join(',',
                            $allowedGridTypes) . '"' : '') .
                    (!empty($disallowedGridTypes) ? ' data-disallowed-tx_gridelements_backend_layout="' . join(',',
                            $disallowedGridTypes) . '"' : '') .
                    (!empty($maxItems) ? ' data-maxitems="' . $maxItems . '"' : '') .
                    ' data-state="' . $expanded . '">';
                $grid .= ($this->helper->getBackendUser()->uc['hideColumnHeaders'] ? '' : $head[$columnKey]);
                if ($maxItems > 0) {
                    $maxItemsClass = ($disableNewContent ? ' warning' : ' success');
                    $maxItemsClass = ($tooManyItems ? ' danger' : $maxItemsClass);
                    $grid .= '<span class="t3-grid-cell-number-of-items' . $maxItemsClass . '">' .
                        $gridContent['numberOfItems'][$columnKey] . '/' . $maxItems . ($maxItemsClass === ' danger' ? '!' : '') .
                        '</span>';
                }
                $grid .= $gridContent[$columnKey];
                $grid .= '</td>';
            }
            $grid .= '</tr>';
        }
        $grid .= '</table></div>';

        return $grid;
    }

    /**
     * renders the HTML output for elements of the CType shortcut
     *
     * @param PageLayoutView $parentObject : The parent object that triggered this hook
     * @param array $row : The current data row for this item
     *
     * @return string $shortcutContent: The HTML output for elements of the CType shortcut
     */
    protected function renderCTypeShortcut(PageLayoutView $parentObject, &$row)
    {
        $shortcutContent = '';
        if ($row['records']) {
            $shortcutItems = explode(',', $row['records']);
            $collectedItems = [];
            foreach ($shortcutItems as $shortcutItem) {
                $shortcutItem = trim($shortcutItem);
                if (strpos($shortcutItem, 'pages_') !== false) {
                    $this->collectContentDataFromPages($shortcutItem, $collectedItems, $row['recursive'], $row['uid'], $row['sys_language_uid']);
                } else {
                    if (strpos($shortcutItem, '_') === false || strpos($shortcutItem, 'tt_content_') !== false) {
                        $this->collectContentData($shortcutItem, $collectedItems, $row['uid'], $row['sys_language_uid']);
                    }
                }
            }
            if (!empty($collectedItems)) {
                foreach ($collectedItems as $item) {
                    if ($item) {
                        $className = $item['tx_gridelements_reference_container'] ? 'reference container_reference' : 'reference';
                        $shortcutContent .= '<div class="' . $className . '">';
                        $shortcutContent .= $this->renderSingleElementHTML($parentObject, $item);
                        // NOTE: this is the end tag for <div class="t3-page-ce-body">
                        // because of bad (historic) conception, starting tag has to be placed inside tt_content_drawHeader()
                        $shortcutContent .= '<div class="reference-overlay"></div></div></div>';
                    }
                }
            }
        }

        return $shortcutContent;
    }

    /**
     * Collects tt_content data from a single page or a page tree starting at a given page
     *
     * @param string $shortcutItem : The single page to be used as the tree root
     * @param array $collectedItems : The collected item data rows ordered by parent position, column position and sorting
     * @param int $recursive : The number of levels for the recursion
     * @param int $parentUid : uid of the referencing tt_content record
     * @param int $language : sys_language_uid of the referencing tt_content record
     *
     * @return void
     */
    protected function collectContentDataFromPages(
        $shortcutItem,
        &$collectedItems,
        $recursive = 0,
        $parentUid,
        $language = 0
    ) {
        $itemList = str_replace('pages_', '', $shortcutItem);
        if ($recursive) {
            if (!$this->tree instanceof QueryGenerator) {
                $this->tree = GeneralUtility::makeInstance(QueryGenerator::class);
            }
            $itemList = $this->tree->getTreeList($itemList, (int)$recursive, 0, 1);
        }
        $itemList = GeneralUtility::intExplode(',', $itemList);

        $queryBuilder = $this->getQueryBuilder();

        $items = $queryBuilder
            ->select('*')
            ->addSelectLiteral($queryBuilder->expr()->inSet('pid',
                    $queryBuilder->createNamedParameter($itemList, Connection::PARAM_INT_ARRAY)) . ' AS inSet')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->neq('uid',
                    $queryBuilder->createNamedParameter((int)$parentUid, \PDO::PARAM_INT)),
                $queryBuilder->expr()->in('pid',
                    $queryBuilder->createNamedParameter($itemList, Connection::PARAM_INT_ARRAY)),
                $queryBuilder->expr()->gte('colPos', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)),
                $queryBuilder->expr()->in('sys_language_uid', $queryBuilder->createNamedParameter([0, -1], Connection::PARAM_INT_ARRAY))
            )
            ->orderBy('inSet')
            ->addOrderBy('colPos')
            ->addOrderBy('sorting')
            ->execute()
            ->fetchAll();

        foreach ($items as $item) {
            if (!empty($this->extentensionConfiguration['overlayShortcutTranslation']) && $language > 0) {
                $translatedItem = BackendUtility::getRecordLocalization('tt_content', $item['uid'], $language);
                if (!empty($translatedItem)) {
                    $item = array_shift($translatedItem);
                }
            }
            if ($this->helper->getBackendUser()->workspace > 0) {
                BackendUtility::workspaceOL('tt_content', $item, $this->helper->getBackendUser()->workspace);
            }
            $item['tx_gridelements_reference_container'] = $item['pid'];
            $collectedItems[] = $item;
        }
    }

    /**
     * Collects tt_content data from a single tt_content element
     *
     * @param string $shortcutItem : The tt_content element to fetch the data from
     * @param array $collectedItems : The collected item data row
     * @param int $parentUid : uid of the referencing tt_content record
     * @param int $language : sys_language_uid of the referencing tt_content record
     *
     * @return void
     */
    protected function collectContentData($shortcutItem, &$collectedItems, $parentUid, $language)
    {
        $shortcutItem = str_replace('tt_content_', '', $shortcutItem);
        if ((int)$shortcutItem !== (int)$parentUid) {
            $queryBuilder = $this->getQueryBuilder();
            if ($this->showHidden) {
                $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
            }
            $item = $queryBuilder
                ->select('*')
                ->from('tt_content')
                ->where(
                    $queryBuilder->expr()->eq('uid',
                        $queryBuilder->createNamedParameter((int)$shortcutItem, \PDO::PARAM_INT))
                )
                ->setMaxResults(1)
                ->execute()
                ->fetch();

            if (!empty($this->extentensionConfiguration['overlayShortcutTranslation']) && $language > 0) {
                $translatedItem = BackendUtility::getRecordLocalization('tt_content', $item['uid'], $language);
                if (!empty($translatedItem)) {
                    $item = array_shift($translatedItem);
                }
            }

            if ($this->helper->getBackendUser()->workspace > 0) {
                BackendUtility::workspaceOL(
                    'tt_content',
                    $item,
                    $this->helper->getBackendUser()->workspace
                );
            }
            $collectedItems[] = $item;
        }
    }

    /**
     * getter for Icon Factory
     *
     * @return IconFactory iconFactory
     */
    public function getIconFactory()
    {
        return $this->iconFactory;
    }

}
