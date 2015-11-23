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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\PageLayoutView;
use TYPO3\CMS\Backend\View\PageLayoutViewDrawItemHookInterface;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Database\QueryGenerator;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Class/Function which manipulates the rendering of item example content and replaces it with a grid of child elements.
 *
 * @author Jo Hasenau <info@cybercraft.de>
 * @package TYPO3
 * @subpackage tx_gridelements
 */
class DrawItem implements PageLayoutViewDrawItemHookInterface
{

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * @var DatabaseConnection
     */
    protected $databaseConnection;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * @var LanguageService
     */
    protected $languageService;

    /**
     * @var QueryGenerator
     */
    protected $tree;

    /**
     * @var string
     */
    protected $backPath = '';

    public function __construct()
    {
        $this->setDatabaseConnection($GLOBALS['TYPO3_DB']);
        $this->setLanguageService($GLOBALS['LANG']);
        $this->helper = Helper::getInstance();
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
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
            $showHidden = $parentObject->tt_contentConfig['showHidden'] ? '' : BackendUtility::BEenableFields('tt_content');
            $deleteClause = BackendUtility::deleteClause('tt_content');

            if ($this->helper->getBackendUser()->uc['hideContentPreview']) {
                $itemContent = '';
                $drawItem = false;
            }

            switch ($row['CType']) {
                case 'gridelements_pi1':
                    $drawItem = false;
                    $itemContent .= $this->renderCTypeGridelements($parentObject, $row, $showHidden, $deleteClause);
                    $refIndexObj = GeneralUtility::makeInstance(ReferenceIndex::class);
                    /* @var $refIndexObj \TYPO3\CMS\Core\Database\ReferenceIndex */
                    $refIndexObj->updateRefIndexTable('tt_content', $row['uid']);
                    break;
                case 'shortcut':
                    $drawItem = false;
                    $itemContent .= $this->renderCTypeShortcut($parentObject, $row, $showHidden, $deleteClause);
                    break;
            }
        }
        $gridType = $row['tx_gridelements_backend_layout'] ? ' data-gridtype="' . $row['tx_gridelements_backend_layout'] . '"' : '';
        $headerContent = '<div id="ce' . $row['uid'] . '" class="t3-ctype-identifier " data-ctype="' . $row['CType'] . '"' . $gridType . '>' . $headerContent . '</div>';
    }

    /**
     * renders the HTML output for elements of the CType gridelements_pi1
     *
     * @param PageLayoutView $parentObject : The parent object that triggered this hook
     * @param array $row : The current data row for this item
     * @param string $showHidden : query String containing enable fields
     * @param string $deleteClause : query String to check for deleted items
     *
     * @return string $itemContent: The HTML output for elements of the CType gridelements_pi1
     */
    public function renderCTypeGridelements(PageLayoutView $parentObject, &$row, &$showHidden, &$deleteClause)
    {
        $head = array();
        $gridContent = array();
        $editUidList = array();
        $colPosValues = array();
        $singleColumn = false;

        // get the layout record for the selected backend layout if any
        $gridContainerId = $row['uid'];
        /** @var $layoutSetup LayoutSetup */
        $layoutSetup = GeneralUtility::makeInstance(LayoutSetup::class);
        if ($row['pid'] < 0) {
            $originalRecord = BackendUtility::getRecord('tt_content', $row['t3ver_oid']);
        } else {
            $originalRecord = $row;
        }
        $gridElement = $layoutSetup->init($originalRecord['pid'])->cacheCurrentParent($gridContainerId, true);
        $layoutUid = $gridElement['tx_gridelements_backend_layout'];
        $layout = $layoutSetup->getLayoutSetup($layoutUid);
        $parserRows = null;
        if (isset($layout['config']) && isset($layout['config']['rows.'])) {
            $parserRows = $layout['config']['rows.'];
        }

        // if there is anything to parse, lets check for existing columns in the layout

        if (is_array($parserRows) && !empty($parserRows)) {
            $this->setMultipleColPosValues($parserRows, $colPosValues);
        } else {
            $singleColumn = true;
            $this->setSingleColPosItems($parentObject, $colPosValues, $gridElement, $showHidden, $deleteClause);
        }

        // if there are any columns, lets build the content for them
        $outerTtContentDataArray = $parentObject->tt_contentData['nextThree'];
        if (!empty($colPosValues)) {
            $this->renderGridColumns($parentObject, $colPosValues, $gridContent, $gridElement, $editUidList,
                $singleColumn, $head, $showHidden, $deleteClause);
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
     * renders the HTML output for elements of the CType shortcut
     *
     * @param PageLayoutView $parentObject : The parent object that triggered this hook
     * @param array $row : The current data row for this item
     * @param string $showHidden : query String containing enable fields
     * @param string $deleteClause : query String to check for deleted items
     *
     * @return string $shortcutContent: The HTML output for elements of the CType shortcut
     */
    public function renderCTypeShortcut(PageLayoutView $parentObject, &$row, &$showHidden, &$deleteClause)
    {
        $shortcutContent = '';
        if ($row['records']) {
            $shortcutItems = explode(',', $row['records']);
            $collectedItems = array();
            foreach ($shortcutItems as $shortcutItem) {
                $shortcutItem = trim($shortcutItem);
                if (strpos($shortcutItem, 'pages_') !== false) {
                    $this->collectContentDataFromPages($shortcutItem, $collectedItems, $row['recursive'], $showHidden,
                        $deleteClaus, $row['uid']);
                } else if (strpos($shortcutItem, '_') === false || strpos($shortcutItem, 'tt_content_') !== false) {
                    $this->collectContentData($shortcutItem, $collectedItems, $showHidden, $deleteClause, $row['uid']);
                }
            }
            if (!empty($collectedItems)) {
                foreach ($collectedItems as $itemRow) {
                    if ($itemRow) {
                        $className = $itemRow['tx_gridelements_reference_container'] ? 'reference container_reference' : 'reference';
                        $shortcutContent .= '<div class="' . $className . '">';
                        $shortcutContent .= $this->renderSingleElementHTML($parentObject, $itemRow);
                        // NOTE: this is the end tag for <div class="t3-page-ce-body">
                        // because of bad (historic) conception, starting tag has to be placed inside tt_content_drawHeader()
                        $shortcutContent .= '<div class="reference-overlay"></div></div></div><br />';
                    }
                }
            }
        }

        return $shortcutContent;
    }

    /**
     * Sets column positions based on a selected gridelement layout
     *
     * @param array $parserRows : The parsed rows of the gridelement layout
     * @param array $colPosValues : The column positions that have been found for that layout
     *
     * @return void
     */
    public function setMultipleColPosValues($parserRows, &$colPosValues)
    {
        if (is_array($parserRows)) {
            foreach ($parserRows as $parserRow) {
                if (is_array($parserRow['columns.']) && !empty($parserRow['columns.'])) {
                    foreach ($parserRow['columns.'] as $parserColumns) {
                        $name = $this->languageService->sL($parserColumns['name'], true);
                        if ($parserColumns['colPos'] !== '') {
                            $colPosValues[(int)$parserColumns['colPos']] = array(
                                'name' => $name,
                                'allowed' => $parserColumns['allowed'],
                                'allowedGridTypes' => $parserColumns['allowedGridTypes']
                            );
                        } else {
                            $colPosValues[32768] = array(
                                'name' => $this->languageService->getLL('notAssigned'),
                                'allowed' => ''
                            );
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
     * @param string $showHidden : query String containing enable fields
     * @param string $deleteClause : query String to check for deleted items
     *
     * @return array collected items for this column
     */
    public function setSingleColPosItems(
        PageLayoutView $parentObject,
        &$colPosValues,
        &$row,
        $showHidden,
        $deleteClause
    ) {
        // Due to the pid being "NOT USED" in makeQueryArray we have to set pidSelect here
        $originalPidSelect = $parentObject->pidSelect;
        $specificIds = $this->helper->getSpecificIds($row);
        $parentObject->pidSelect = 'pid = ' . $specificIds['pid'];

        // @todo $parentObject->showLanguage was appended in this where clause, but this property does not exist anymore
        $queryParts = $parentObject->makeQueryArray('tt_content', $specificIds['pid'],
            'AND colPos = -1 AND tx_gridelements_container IN (' . $row['uid'] . ',' . $specificIds['uid'] . ') ' . $showHidden . $deleteClause);

        // Due to the pid being "NOT USED" in makeQueryArray we have to reset pidSelect here
        $parentObject->pidSelect = $originalPidSelect;

        $result = $this->databaseConnection->exec_SELECT_queryArray($queryParts);
        $colPosValues[] = array(0, '');

        return $parentObject->getResult($result);
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
     * @param string $showHidden : query String containing enable fields
     * @param string $deleteClause : query String to check for deleted items
     *
     * @return void
     */
    public function renderGridColumns(
        PageLayoutView $parentObject,
        &$colPosValues,
        &$gridContent,
        &$row,
        &$editUidList,
        &$singleColumn,
        &$head,
        $showHidden,
        $deleteClause
    ) {
        foreach ($colPosValues as $colPos => $values) {
            // first we have to create the column content separately for each column
            // so we can check for the first and the last element to provide proper sorting
            if ($singleColumn === false) {
                $items = $this->collectItemsForColumn($parentObject, $colPos, $row, $showHidden, $deleteClause);
            } else {
                $items = array();
            }
            // if there are any items, we can create the HTML for them just like in the original TCEform
            $this->renderSingleGridColumn($parentObject, $items, $colPos, $values, $gridContent, $row, $editUidList);
            // we will need a header for each of the columns to activate mass editing for elements of that column
            $this->setColumnHeader($parentObject, $head, $colPos, $values['name'], $editUidList);
        }
    }

    /**
     * Collects tt_content data from a single tt_content element
     *
     * @param PageLayoutView $parentObject : The paren object that triggered this hook
     * @param int $colPos : The column position to collect the items for
     * @param array $row : The current data row for the container item
     * @param string $showHidden : query String containing enable fields
     * @param string $deleteClause : query String to check for deleted items
     *
     * @return array collected items for the given column
     */
    public function collectItemsForColumn(PageLayoutView $parentObject, &$colPos, &$row, &$showHidden, &$deleteClause)
    {
        // Due to the pid being "NOT USED" in makeQueryArray we have to set pidSelect here
        $originalPidSelect = $parentObject->pidSelect;
        $specificIds = $this->helper->getSpecificIds($row);

        $parentObject->pidSelect = 'pid = ' . $row['pid'];

        if (!$parentObject->tt_contentConfig['languageMode']) {
            $showLanguage = ' AND (sys_language_uid = -1 OR sys_language_uid=' . $parentObject->tt_contentConfig['sys_language_uid'] . ')';
        } else if ($row['sys_language_uid'] > 0) {
            $showLanguage = ' AND sys_language_uid=' . $row['sys_language_uid'];
        } else {
            $showLanguage = '';
        }

        $where = '';
        if ($this->helper->getBackendUser()->workspace > 0 && $row['t3ver_wsid'] > 0) {
            $where .= 'AND t3ver_wsid = ' . $row['t3ver_wsid'];
        }
        $where .= ' AND colPos = -1 AND tx_gridelements_container IN (' . $row['uid'] . ',' . $specificIds['uid'] . ') AND tx_gridelements_columns=' . $colPos . $showHidden . $deleteClause . $showLanguage;

        $queryParts = $parentObject->makeQueryArray('tt_content', $row['pid'], $where);

        // Due to the pid being "NOT USED" in makeQueryArray we have to reset pidSelect here
        $parentObject->pidSelect = $originalPidSelect;

        $result = $this->databaseConnection->exec_SELECT_queryArray($queryParts);

        return $parentObject->getResult($result);
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

        $newParams = '';
        if ($colPos < 32768) {
            $newParams = $parentObject->newContentElementOnClick($parentObject->id,
                '-1' . '&tx_gridelements_allowed=' . $values['allowed'] . '&tx_gridelements_allowed_grid_types=' . $values['allowedGridTypes'] . '&tx_gridelements_container=' . $specificIds['uid'] . '&tx_gridelements_columns=' . $colPos,
                $row['sys_language_uid']);
        }

        $gridContent[$colPos] .= '
			<div data-colpos="' . $colPos . '" data-language-uid="' . $row['sys_language_uid'] . '" class="t3js-sortable t3js-sortable-lang t3js-sortable-lang-' . $row['sys_language_uid'] . ' t3-page-ce-wrapper ui-sortable">
				<div class="t3-page-ce t3js-page-ce" data-container="' . $row['uid'] . '" id="' . str_replace('.', '',
                uniqid('', true)) . '">
					<div class="t3js-page-new-ce t3js-page-new-ce-allowed t3-page-ce-wrapper-new-ce" id="colpos-' . $colPos . '-' . str_replace('.',
                '', uniqid('', true)) . '">
						<a href="#" onclick="' . htmlspecialchars($newParams) . '" title="' . $this->languageService->getLL('newContentElement',
                true) . '" class="btn btn-default btn-sm">' . $this->iconFactory->getIcon('actions-document-new',
                'small') . ' ' . $this->languageService->getLL('content', true) . '</a>
					</div>
					<div class="t3-page-ce-dropzone-available t3js-page-ce-dropzone-available"></div>
				</div>';

        if (!empty($items)) {
            foreach ($items as $itemRow) {
                if (is_array($itemRow)) {
                    $statusHidden = $parentObject->isDisabled('tt_content', $itemRow) ? ' t3-page-ce-hidden' : '';
                    $gridContent[$colPos] .= '
				<div class="t3-page-ce t3js-page-ce t3js-page-ce-sortable' . $statusHidden . '" data-table="tt_content" data-uid="' . $itemRow['uid'] . '" data-container="' . $itemRow['tx_gridelements_container'] . '" data-ctype="' . $itemRow['CType'] . '"><div class="t3-page-ce-dragitem" id="' . str_replace('.',
                            '', uniqid('', true)) . '">' . $this->renderSingleElementHTML($parentObject,
                            $itemRow) . '</div></div>';
                    // New content element:
                    if ($parentObject->option_newWizard) {
                        $onClick = 'window.location.href=' . GeneralUtility::quoteJSvalue(BackendUtility::getModuleUrl('new_content_element') . '&id=' . $itemRow['pid'] . '&colPos=-1&sys_language_uid=' . $itemRow['sys_language_uid'] . '&tx_gridelements_allowed=' . $values['allowed'] . '&tx_gridelements_allowed_grid_types=' . $values['allowedGridTypes'] . '&uid_pid=-' . $itemRow['uid'] . '&returnUrl=' . rawurlencode(GeneralUtility::getIndpEnv('REQUEST_URI'))) . ';';
                    } else {
                        $onClick = BackendUtility::editOnClick('&edit[tt_content][' . $itemRow['uid'] . ']=new&defVals[tt_content][colPos]=' . $colPos . '&defVals[tt_content][sys_language_uid]=' . $itemRow['sys_language_uid']);
                    }
                    $gridContent[$colPos] .= '
				<div class="t3js-page-new-ce t3js-page-new-ce-allowed t3-page-ce-wrapper-new-ce" id="colpos-' . $itemRow['tx_gridelements_columns'] . '-page-' . $itemRow['pid'] . '-gridcontainer-' . $itemRow['tx_gridelements_container'] . '-' . str_replace('.',
                            '', uniqid('', true)) . '">
					<a href="#" onclick="' . htmlspecialchars($onClick) . '" title="' . $this->languageService->getLL('newContentElement',
                            true) . '" class="btn btn-default btn-sm">' . $this->iconFactory->getIcon('actions-document-new',
                            'small') . ' ' . $this->languageService->getLL('content', true) . '</a>
				</div>
				<div class="t3-page-ce-dropzone-available t3js-page-ce-dropzone-available"></div>
				</div>
					';
                    $editUidList[$colPos] .= $editUidList[$colPos] ? ',' . $itemRow['uid'] : $itemRow['uid'];
                }
            }
        }

        $gridContent[$colPos] .= '</div>';
    }

    /**
     * Sets the headers for a grid before content and headers are put together
     *
     * @param PageLayoutView $parentObject : The parent object that triggered this hook
     * @param array $head : The collected item data rows
     * @param int $colPos : The column position we want to get a header for
     * @param string $name : The name of the header
     * @param array $editUidList : determines if we will get edit icons or not
     *
     * @internal param array $row : The current data row for the container item
     * @return void
     */
    public function setColumnHeader(PageLayoutView $parentObject, &$head, &$colPos, &$name, &$editUidList)
    {
        // @todo $parentObject->pageTitleParamForAltDoc was appended to =edit but this property is gone
        $head[$colPos] = $this->tt_content_drawColHeader($name,
            ($parentObject->doEdit && $editUidList[$colPos]) ? '&edit[tt_content][' . $editUidList[$colPos] . ']=edit' : '',
            $parentObject);
    }

    /**
     * Draw header for a content element column:
     *
     * @param string $colName Column name
     * @param string $editParams Edit params (Syntax: &edit[...] for alt_doc.php)
     * @param PageLayoutView $parentObject
     *
     * @return string HTML table
     */
    function tt_content_drawColHeader($colName, $editParams, PageLayoutView $parentObject)
    {

        $icons = '';
        // Create command links:
        if ($parentObject->tt_contentConfig['showCommands']) {
            // Edit whole of column:
            if ($editParams) {
                $icons .= '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick($editParams)) . '" title="' . $this->languageService->getLL('editColumn',
                        true) . '">' . $this->iconFactory->getIcon('actions-document-open', 'small') . '</a>';
            }
            $icons .= '<a href="#" class="btn btn-default toggle-content toggle-up" title="' . $this->languageService->sL('LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tx_gridelements_togglecontent') . '">' . $this->iconFactory->getIcon('actions-view-list-collapse',
                    'small') . '</a>';
            $icons .= '<a href="#" class="btn btn-default toggle-content toggle-down" title="' . $this->languageService->sL('LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tx_gridelements_togglecontent') . '">' . $this->iconFactory->getIcon('actions-view-list-expand',
                    'small') . '</a>';
        }
        if (strlen($icons)) {
            $icons = '<div class="t3-page-column-header-icons btn-group btn-group-sm">' . $icons . '</div>';
        }

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
     * @param array $layoutSetup : The setup of the layout that is selected for the grid we are going to render
     * @param array $row : The current data row for the container item
     * @param array $head : The data for the column headers of the grid we are going to render
     * @param array $gridContent : The content data of the grid we are going to render
     *
     * @return string
     */
    public function renderGridLayoutTable($layoutSetup, $row, $head, $gridContent)
    {
        $specificIds = $this->helper->getSpecificIds($row);

        $grid = '<div class="t3-grid-container t3-grid-element-container' . ($layoutSetup['frame'] ? ' t3-grid-container-framed t3-grid-container-' . $layoutSetup['frame'] : '') . ($layoutSetup['top_level_layout'] ? ' t3-grid-tl-container' : '') . '">';
        if ($layoutSetup['frame'] || $this->helper->getBackendUser()->uc['showGridInformation'] === 1) {
            $grid .= '<h4 class="t3-grid-container-title-' . (int)$layoutSetup['frame'] . '">' . BackendUtility::wrapInHelp('tx_gridelements_backend_layouts',
                    'title', $this->languageService->sL($layoutSetup['title']), array(
                        'title' => $this->languageService->sL($layoutSetup['title']),
                        'description' => $this->languageService->sL($layoutSetup['description'])
                    )) . '</h4>';
        }
        $grid .= '<table border="0" cellspacing="0" cellpadding="0" width="100%" height="100%" class="t3-page-columns t3-grid-table">';
        // add colgroups
        $colCount = 0;
        $rowCount = 0;
        if (isset($layoutSetup['config'])) {
            if (isset($layoutSetup['config']['colCount'])) {
                $colCount = (int)$layoutSetup['config']['colCount'];
            }
            if (isset($layoutSetup['config']['rowCount'])) {
                $rowCount = (int)$layoutSetup['config']['rowCount'];
            }
        }
        $grid .= '<colgroup>';
        for ($i = 0; $i < $colCount; $i++) {
            $grid .= '<col style="width:' . (100 / $colCount) . '%"></col>';
        }
        $grid .= '</colgroup>';
        // cycle through rows
        for ($layoutRow = 1; $layoutRow <= $rowCount; $layoutRow++) {
            $rowConfig = $layoutSetup['config']['rows.'][$layoutRow . '.'];
            if (!isset($rowConfig)) {
                continue;
            }
            $grid .= '<tr>';
            for ($col = 1; $col <= $colCount; $col++) {
                $columnConfig = $rowConfig['columns.'][$col . '.'];
                if (!isset($columnConfig)) {
                    continue;
                }
                // which column should be displayed inside this cell
                $columnKey = $columnConfig['colPos'] !== '' ? (int)$columnConfig['colPos'] : 32768;
                // allowed CTypes
                $allowedContentTypes = array();
                if (!empty($columnConfig['allowed'])) {
                    $allowedContentTypes = array_flip(GeneralUtility::trimExplode(',', $columnConfig['allowed']));
                    if (!isset($allowedContentTypes['*'])) {
                        foreach ($allowedContentTypes as $key => &$ctype) {
                            $ctype = 't3-allow-' . $key;
                        }
                    } else {
                        unset($allowedContentTypes);
                    }
                }
                if (!empty($columnConfig['allowedGridTypes'])) {
                    $allowedGridTypes = array_flip(GeneralUtility::trimExplode(',', $columnConfig['allowedGridTypes']));
                    if (!isset($allowedGridTypes['*'])) {
                        foreach ($allowedGridTypes as $gridType => &$gridTypeClass) {
                            $gridTypeClass = 't3-allow-gridtype-' . $gridType;
                        }
                        $allowedContentTypes['gridelements_pi1'] = 't3-allow-gridelements_pi1';
                    } else {
                        if (isset($allowedContentTypes)) {
                            $allowedContentTypes['gridelements_pi1'] = 't3-allow-gridelements_pi1';
                        }
                        unset($allowedGridTypes);
                    }
                }
                // render the grid cell
                $colSpan = (int)$columnConfig['colspan'];
                $rowSpan = (int)$columnConfig['rowspan'];
                $grid .= '<td valign="top"' . (isset($columnConfig['colspan']) ? ' colspan="' . $colSpan . '"' : '') . (isset($columnConfig['rowspan']) ? ' rowspan="' . $rowSpan . '"' : '') . 'data-colpos="' . $columnKey . '" id="column-' . $specificIds['uid'] . 'x' . $columnKey . '"
					class="t3-grid-cell t3js-page-column t3-page-column t3-page-column-' . $columnKey . (!isset($columnConfig['colPos']) || $columnConfig['colPos'] === '' ? ' t3-grid-cell-unassigned' : '') . (isset($columnConfig['colspan']) && $columnConfig['colPos'] !== '' ? ' t3-grid-cell-width' . $colSpan : '') . (isset($columnConfig['rowspan']) && $columnConfig['colPos'] !== '' ? ' t3-grid-cell-height' . $rowSpan : '') . ' ' . ($layoutSetup['horizontal'] ? ' t3-grid-cell-horizontal' : '') . (!empty($allowedContentTypes) ? ' ' . join(' ',
                            $allowedContentTypes) : ' t3-allow-all') . (!empty($allowedGridTypes) ? ' ' . join(' ',
                            $allowedGridTypes) : '') . '">';

                $grid .= ($this->helper->getBackendUser()->uc['hideColumnHeaders'] ? '' : $head[$columnKey]) . $gridContent[$columnKey];
                $grid .= '</td>';
            }
            $grid .= '</tr>';
        }
        $grid .= '</table></div>';

        return $grid;
    }

    /**
     * Collects tt_content data from a single page or a page tree starting at a given page
     *
     * @param string $shortcutItem : The single page to be used as the tree root
     * @param array $collectedItems : The collected item data rows ordered by parent position, column position and sorting
     * @param int $recursive : The number of levels for the recursion
     * @param string $showHidden : query String containing enable fields
     * @param string $deleteClause : query String to check for deleted items
     * @param int $parentUid : uid of the referencing tt_content record
     *
     * @return void
     */
    public function collectContentDataFromPages(
        $shortcutItem,
        &$collectedItems,
        $recursive = 0,
        &$showHidden,
        &$deleteClause,
        $parentUid
    ) {
        $itemList = str_replace('pages_', '', $shortcutItem);
        if ($recursive) {
            if (!$this->tree instanceof QueryGenerator) {
                $this->tree = GeneralUtility::makeInstance(QueryGenerator::class);
            }
            $itemList = $this->tree->getTreeList($itemList, (int)$recursive, 0, 1);
        }
        $itemRows = $this->databaseConnection->exec_SELECTgetRows('*', 'tt_content',
            'uid != ' . (int)$parentUid . ' AND pid IN (' . $itemList . ') AND colPos >= 0 ' . $showHidden . $deleteClause,
            '', 'FIND_IN_SET(pid, \'' . $itemList . '\'),colPos,sorting');
        foreach ($itemRows as $itemRow) {
            if ($this->helper->getBackendUser()->workspace > 0) {
                BackendUtility::workspaceOL('tt_content', $itemRow, $this->helper->getBackendUser()->workspace);
            }
            $itemRow['tx_gridelements_reference_container'] = $itemRow['pid'];
            $collectedItems[] = $itemRow;
        }
    }

    /**
     * Collects tt_content data from a single tt_content element
     *
     * @param string $shortcutItem : The tt_content element to fetch the data from
     * @param array $collectedItems : The collected item data row
     * @param string $showHidden : query String containing enable fields
     * @param string $deleteClause : query String to check for deleted items
     * @param int $parentUid : uid of the referencing tt_content record
     *
     * @return void
     */
    public function collectContentData($shortcutItem, &$collectedItems, &$showHidden, &$deleteClause, $parentUid)
    {
        $shortcutItem = str_replace('tt_content_', '', $shortcutItem);
        if ((int)$shortcutItem !== (int)$parentUid) {
            $itemRow = $this->databaseConnection->exec_SELECTgetSingleRow('*', 'tt_content',
                'uid=' . $shortcutItem . $showHidden . $deleteClause);
            if ($this->helper->getBackendUser()->workspace > 0) {
                BackendUtility::workspaceOL('tt_content', $itemRow, $this->helper->getBackendUser()->workspace);
            }
            $collectedItems[] = $itemRow;
        }
    }

    /**
     * Renders the HTML code for a single tt_content element
     *
     * @param PageLayoutView $parentObject : The parent object that triggered this hook
     * @param array $itemRow : The data row to be rendered as HTML
     *
     * @return string
     */
    public function renderSingleElementHTML(PageLayoutView $parentObject, $itemRow)
    {
        // @todo $parentObject->lP is gone, defLangBinding is proably not enough for the third param to act correctly
        $singleElementHTML = $parentObject->tt_content_drawHeader($itemRow,
            $parentObject->tt_contentConfig['showInfo'] ? 15 : 5, $parentObject->defLangBinding, true, true);
        $singleElementHTML .= '<div ' . (!empty($itemRow['_ORIG_uid']) ? ' class="ver-element"' : '') . '><div class="t3-page-ce-body-inner t3-page-ce-body-inner-' . $itemRow['CType'] . '">' . $parentObject->tt_content_drawItem($itemRow) . '</div></div>';
        $footerContent = '';
        // Get processed values:
        $info = array();
        $parentObject->getProcessedValue('tt_content', 'starttime,endtime,fe_group,spaceBefore,spaceAfter', $itemRow,
            $info);
        // Display info from records fields:
        if (!empty($info)) {
            $footerContent = '<div class="t3-page-ce-info">
				' . implode('<br />', $info) . '
				</div>';
        }
        // Wrap it
        if (!empty($footerContent)) {
            $singleElementHTML .= '<div class="t3-page-ce-footer">' . $footerContent . '</div>';
        }

        return $singleElementHTML;
    }

    /**
     * setter for databaseConnection object
     *
     * @param DatabaseConnection $databaseConnection
     *
     * @return void
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

    /**
     * getter for databaseConnection
     *
     * @return LanguageService $languageService
     */
    public function getLanguageService()
    {
        return $this->languageService;
    }

    /**
     * setter for databaseConnection object
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
     * getter for Icon Factory
     *
     * @return IconFactory iconFactory
     */
    public function getIconFactory()
    {
        return $this->iconFactory;
    }

}
