<?php

namespace GridElementsTeam\Gridelements\Xclass;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Backend\Module\BaseScriptClass;
use TYPO3\CMS\Backend\RecordList\RecordListGetTableHookInterface;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayoutView;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\CsvUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Frontend\Page\PageRepository;
use TYPO3\CMS\Recordlist\RecordList\RecordListHookInterface;

/**
 * Class for rendering of Web>List module
 */
class DatabaseRecordList extends \TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList
{
    // *********
    // External:
    // *********

    /**
     * Used to indicate which tables (values in the array) that can have a
     * create-new-record link. If the array is empty, all tables are allowed.
     *
     * @var string[]
     */
    public $allowedNewTables = [];
    /**
     * Used to indicate which tables (values in the array) that cannot have a
     * create-new-record link. If the array is empty, all tables are allowed.
     *
     * @var string[]
     */
    public $deniedNewTables = [];
    /**
     * If TRUE, the control panel will contain links to the create-new wizards for
     * pages and tt_content elements (normally, the link goes to just creatinga new
     * element without the wizards!).
     *
     * @var bool
     */
    public $newWizards = false;
    /**
     * If TRUE, will disable the rendering of clipboard + control panels.
     *
     * @var bool
     */
    public $dontShowClipControlPanels = false;
    /**
     * If TRUE, will show the clipboard in the field list.
     *
     * @var bool
     */
    public $showClipboard = false;
    /**
     * If TRUE, will DISABLE all control panels in lists. (Takes precedence)
     *
     * @var bool
     */
    public $noControlPanels = false;
    /**
     * If TRUE, clickmenus will be rendered
     *
     * @var bool
     */
    public $clickMenuEnabled = true;
    /**
     * Count of record rows in view
     *
     * @var int
     */
    public $totalRowCount;
    /**
     * Count of record rows in view
     *
     * @var int
     */
    public $totalColumnCount;
    /**
     * Space icon used for alignment
     *
     * @var string
     */
    public $spaceIcon;
    /**
     * Disable single table view
     *
     * @var bool
     */
    public $disableSingleTableView = false;

    // *********
    // Internal:
    // *********

    /**
     * Set to the page record (see writeTop())
     *
     * @var string[]
     */
    public $pageRow = [];
    /**
     * If set, the listing is returned as CSV instead.
     *
     * @var bool
     */
    public $csvOutput = false;
    /**
     * Clipboard object
     *
     * @var \TYPO3\CMS\Backend\Clipboard\Clipboard
     */
    public $clipObj;
    /**
     * Tracking names of elements (for clipboard use)
     *
     * @var string[]
     */
    public $CBnames = [];
    /**
     * Translations of the current record
     *
     * @var string[]
     */
    public $translations;
    /**
     * select fields for the query which fetches the translations of the current
     * record
     *
     * @var string
     */
    public $selFieldList;
    /**
     * @var mixed[]
     */
    public $pageinfo;
    /**
     * Injected by RecordList
     *
     * @var string[]
     */
    public $MOD_MENU = [];
    /**
     * @var int
     */
    protected $maxDepth = 10;

    // *********
    // Internal:
    // *********
    /**
     * @var int[]
     */
    protected $expandedGridelements = [];
    /**
     * @var int[]
     */
    protected $currentIdList = [];
    /**
     * @var int[]
     */
    protected $currentContainerIdList = [];
    /**
     * @var bool
     */
    protected $showMoveUp;
    /**
     * @var bool
     */
    protected $showMoveDown;
    /**
     * @var string
     */
    protected $lastMoveDownParams;
    /**
     * @var BackendUserAuthentication
     */
    protected $backendUser;
    /**
     * @var bool
     */
    protected $l10nEnabled;
    /**
     * Used to accumulate CSV lines for CSV export.
     *
     * @var string[]
     */
    protected $csvLines = [];
    /**
     * [$tablename][$uid] = number of references to this record
     *
     * @var int[][]
     */
    protected $referenceCount = [];
    /**
     * If defined the records are editable
     *
     * @var bool
     */
    protected $editable = true;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise perform
     * operations.
     *
     * @return string[] All available buttons as an assoc. array
     */
    public function getButtons()
    {
        $module = $this->getModule();
        $backendUser = $this->getBackendUserAuthentication();
        $lang = $this->getLanguageService();
        $buttons = [
            'csh'         => '',
            'view'        => '',
            'edit'        => '',
            'hide_unhide' => '',
            'move'        => '',
            'new_record'  => '',
            'paste'       => '',
            'level_up'    => '',
            'cache'       => '',
            'reload'      => '',
            'shortcut'    => '',
            'back'        => '',
            'csv'         => '',
            'export'      => '',
        ];
        // Get users permissions for this page record:
        $localCalcPerms = $backendUser->calcPerms($this->pageRow);
        // CSH
        if ((string)$this->id === '') {
            $buttons['csh'] = BackendUtility::cshItem('xMOD_csh_corebe', 'list_module_noId');
        } elseif (!$this->id) {
            $buttons['csh'] = BackendUtility::cshItem('xMOD_csh_corebe', 'list_module_root');
        } else {
            $buttons['csh'] = BackendUtility::cshItem('xMOD_csh_corebe', 'list_module');
        }
        if (isset($this->id)) {
            // View Exclude doktypes 254,255 Configuration:
            // mod.web_list.noViewWithDokTypes = 254,255
            if (isset($module->modTSconfig['properties']['noViewWithDokTypes'])) {
                $noViewDokTypes = GeneralUtility::trimExplode(',',
                    $module->modTSconfig['properties']['noViewWithDokTypes'], true);
            } else {
                //default exclusion: doktype 254 (folder), 255 (recycler)
                $noViewDokTypes = [
                    PageRepository::DOKTYPE_SYSFOLDER,
                    PageRepository::DOKTYPE_RECYCLER,
                ];
            }
            if (!in_array($this->pageRow['doktype'], $noViewDokTypes)) {
                $onClick = htmlspecialchars(BackendUtility::viewOnClick($this->id, '',
                    BackendUtility::BEgetRootLine($this->id)));
                $buttons['view'] = '<a href="#" onclick="' . $onClick . '" title="'
                    . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage')) . '">'
                    . $this->iconFactory->getIcon('actions-document-view', Icon::SIZE_SMALL)->render() . '</a>';
            }
            // New record on pages that are not locked by editlock
            if (!$module->modTSconfig['properties']['noCreateRecordsLink'] && $this->editLockPermissions()) {
                $onClick = htmlspecialchars('return jumpExt(' . GeneralUtility::quoteJSvalue(BackendUtility::getModuleUrl('db_new',
                        ['id' => $this->id])) . ');');
                $buttons['new_record'] = '<a href="#" onclick="' . $onClick . '" title="'
                    . htmlspecialchars($lang->getLL('newRecordGeneral')) . '">'
                    . $this->iconFactory->getIcon('actions-add', Icon::SIZE_SMALL)->render() . '</a>';
            }
            // If edit permissions are set, see
            // \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
            if ($localCalcPerms & Permission::PAGE_EDIT && !empty($this->id) && $this->editLockPermissions() && $this->getBackendUserAuthentication()->checkLanguageAccess(0)) {
                // Edit
                $params = '&edit[pages][' . $this->pageRow['uid'] . ']=edit';
                $onClick = htmlspecialchars(BackendUtility::editOnClick($params, '', -1));
                $buttons['edit'] = '<a href="#" onclick="' . $onClick . '" title="' . htmlspecialchars($lang->getLL('editPage')) . '">'
                    . $this->iconFactory->getIcon('actions-page-open', Icon::SIZE_SMALL)->render()
                    . '</a>';
            }
            // Paste
            if ($this->showClipboard && ($localCalcPerms & Permission::PAGE_NEW || $localCalcPerms & Permission::CONTENT_EDIT) && $this->editLockPermissions()) {
                $elFromTable = $this->clipObj->elFromTable('');
                if (!empty($elFromTable)) {
                    $confirmText = $this->clipObj->confirmMsgText('pages', $this->pageRow, 'into', $elFromTable);
                    $buttons['paste'] = '<a'
                        . ' href="' . htmlspecialchars($this->clipObj->pasteUrl('', $this->id)) . '"'
                        . ' title="' . htmlspecialchars($lang->getLL('clip_paste')) . '"'
                        . ' class="t3js-modal-trigger"'
                        . ' data-severity="warning"'
                        . ' data-title="' . htmlspecialchars($lang->getLL('clip_paste')) . '"'
                        . ' data-content="' . htmlspecialchars($confirmText) . '"'
                        . '>'
                        . $this->iconFactory->getIcon('actions-document-paste-into', Icon::SIZE_SMALL)->render()
                        . '</a>';
                }
            }
            // Cache
            $buttons['cache'] = '<a href="' . htmlspecialchars(($this->listURL() . '&clear_cache=1')) . '" title="'
                . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.clear_cache')) . '">'
                . $this->iconFactory->getIcon('actions-system-cache-clear', Icon::SIZE_SMALL)->render() . '</a>';
            if ($this->table && (!isset($module->modTSconfig['properties']['noExportRecordsLinks'])
                    || (isset($module->modTSconfig['properties']['noExportRecordsLinks'])
                        && !$module->modTSconfig['properties']['noExportRecordsLinks']))
            ) {
                // CSV
                $buttons['csv'] = '<a href="' . htmlspecialchars(($this->listURL() . '&csv=1')) . '" title="'
                    . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.csv')) . '">'
                    . $this->iconFactory->getIcon('actions-document-export-csv', Icon::SIZE_SMALL)->render() . '</a>';
                // Export
                if (ExtensionManagementUtility::isLoaded('impexp')) {
                    $url = BackendUtility::getModuleUrl('xMOD_tximpexp', ['tx_impexp[action]' => 'export']);
                    $buttons['export'] = '<a href="' . htmlspecialchars($url . '&tx_impexp[list][]='
                            . rawurlencode($this->table . ':' . $this->id)) . '" title="'
                        . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.export')) . '">'
                        . $this->iconFactory->getIcon('actions-document-export-t3d',
                            Icon::SIZE_SMALL)->render() . '</a>';
                }
            }
            // Reload
            $buttons['reload'] = '<a href="' . htmlspecialchars($this->listURL()) . '" title="'
                . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.reload')) . '">'
                . $this->iconFactory->getIcon('actions-refresh', Icon::SIZE_SMALL)->render() . '</a>';
            // Shortcut
            if ($backendUser->mayMakeShortcut()) {
                $buttons['shortcut'] = $this->getDocumentTemplate()->makeShortcutIcon(
                    'id, M, imagemode, pointer, table, search_field, search_levels, showLimit, sortField, sortRev',
                    implode(',', array_keys($this->MOD_MENU)),
                    'web_list'
                );
            }
            // Back
            if ($this->returnUrl) {
                $href = htmlspecialchars(GeneralUtility::linkThisUrl($this->returnUrl, ['id' => $this->id]));
                $buttons['back'] = '<a href="' . $href . '" class="typo3-goBack" title="'
                    . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.goBack')) . '">'
                    . $this->iconFactory->getIcon('actions-view-go-back', Icon::SIZE_SMALL)->render() . '</a>';
            }
        }
        return $buttons;
    }

    /**
     * @return BaseScriptClass
     */
    protected function getModule()
    {
        return $GLOBALS['SOBE'];
    }

    /**
     * Check whether or not the current backend user is an admin or the current page is
     * locked by editlock.
     *
     * @return bool
     */
    protected function editLockPermissions()
    {
        return $this->getBackendUserAuthentication()->isAdmin() || !$this->pageRow['editlock'];
    }

    /**
     * @return DocumentTemplate
     */
    protected function getDocumentTemplate()
    {
        return $GLOBALS['TBE_TEMPLATE'];
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise perform
     * operations.
     *
     * @param ModuleTemplate $moduleTemplate
     */
    public function getDocHeaderButtons(ModuleTemplate $moduleTemplate)
    {
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $module = $this->getModule();
        $backendUser = $this->getBackendUserAuthentication();
        $lang = $this->getLanguageService();
        // Get users permissions for this page record:
        $localCalcPerms = $backendUser->calcPerms($this->pageRow);
        // CSH
        if ((string)$this->id === '') {
            $fieldName = 'list_module_noId';
        } elseif (!$this->id) {
            $fieldName = 'list_module_root';
        } else {
            $fieldName = 'list_module';
        }
        $cshButton = $buttonBar->makeHelpButton()
            ->setModuleName('xMOD_csh_corebe')
            ->setFieldName($fieldName);
        $buttonBar->addButton($cshButton);
        if (isset($this->id)) {
            // View Exclude doktypes 254,255 Configuration:
            // mod.web_list.noViewWithDokTypes = 254,255
            if (isset($module->modTSconfig['properties']['noViewWithDokTypes'])) {
                $noViewDokTypes = GeneralUtility::trimExplode(',',
                    $module->modTSconfig['properties']['noViewWithDokTypes'], true);
            } else {
                //default exclusion: doktype 254 (folder), 255 (recycler)
                $noViewDokTypes = [
                    PageRepository::DOKTYPE_SYSFOLDER,
                    PageRepository::DOKTYPE_RECYCLER,
                ];
            }
            // New record on pages that are not locked by editlock
            if (!$module->modTSconfig['properties']['noCreateRecordsLink'] && $this->editLockPermissions()) {
                $onClick = 'return jumpExt(' . GeneralUtility::quoteJSvalue(BackendUtility::getModuleUrl('db_new',
                        ['id' => $this->id])) . ');';
                $newRecordButton = $buttonBar->makeLinkButton()
                    ->setHref('#')
                    ->setOnClick($onClick)
                    ->setTitle($lang->getLL('newRecordGeneral'))
                    ->setIcon($this->iconFactory->getIcon('actions-document-new', Icon::SIZE_SMALL));
                $buttonBar->addButton($newRecordButton, ButtonBar::BUTTON_POSITION_LEFT, 10);
            }
            if (!in_array($this->pageRow['doktype'], $noViewDokTypes)) {
                $onClick = BackendUtility::viewOnClick($this->id, '', BackendUtility::BEgetRootLine($this->id));
                $viewButton = $buttonBar->makeLinkButton()
                    ->setHref('#')
                    ->setOnClick($onClick)
                    ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage'))
                    ->setIcon($this->iconFactory->getIcon('actions-document-view', Icon::SIZE_SMALL));
                $buttonBar->addButton($viewButton, ButtonBar::BUTTON_POSITION_LEFT, 20);
            }
            // If edit permissions are set, see
            // \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
            if ($localCalcPerms & Permission::PAGE_EDIT && !empty($this->id) && $this->editLockPermissions()) {
                // Edit
                $params = '&edit[pages][' . $this->pageRow['uid'] . ']=edit';
                $onClick = BackendUtility::editOnClick($params, '', -1);
                $editButton = $buttonBar->makeLinkButton()
                    ->setHref('#')
                    ->setOnClick($onClick)
                    ->setTitle($lang->getLL('editPage'))
                    ->setIcon($this->iconFactory->getIcon('actions-page-open', Icon::SIZE_SMALL));
                $buttonBar->addButton($editButton, ButtonBar::BUTTON_POSITION_LEFT, 20);
            }
            // Paste
            if ($this->showClipboard && ($localCalcPerms & Permission::PAGE_NEW || $localCalcPerms & Permission::CONTENT_EDIT) && $this->editLockPermissions()) {
                $elFromTable = $this->clipObj->elFromTable('');
                if (!empty($elFromTable)) {
                    $confirmMessage = $this->clipObj->confirmMsgText('pages', $this->pageRow, 'into', $elFromTable);
                    $pasteButton = $buttonBar->makeLinkButton()
                        ->setHref($this->clipObj->pasteUrl('', $this->id))
                        ->setTitle($lang->getLL('clip_paste'))
                        ->setClasses('t3js-modal-trigger')
                        ->setDataAttributes([
                            'severity' => 'warning',
                            'content'  => $confirmMessage,
                            'title'    => $lang->getLL('clip_paste'),
                        ])
                        ->setIcon($this->iconFactory->getIcon('actions-document-paste-into', Icon::SIZE_SMALL));
                    $buttonBar->addButton($pasteButton, ButtonBar::BUTTON_POSITION_LEFT, 40);
                }
            }
            // Cache
            $clearCacheButton = $buttonBar->makeLinkButton()
                ->setHref($this->listURL() . '&clear_cache=1')
                ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.clear_cache'))
                ->setIcon($this->iconFactory->getIcon('actions-system-cache-clear', Icon::SIZE_SMALL));
            $buttonBar->addButton($clearCacheButton, ButtonBar::BUTTON_POSITION_RIGHT);
            if ($this->table && (!isset($module->modTSconfig['properties']['noExportRecordsLinks'])
                    || (isset($module->modTSconfig['properties']['noExportRecordsLinks'])
                        && !$module->modTSconfig['properties']['noExportRecordsLinks']))
            ) {
                // CSV
                $csvButton = $buttonBar->makeLinkButton()
                    ->setHref($this->listURL() . '&csv=1')
                    ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.csv'))
                    ->setIcon($this->iconFactory->getIcon('actions-document-export-csv', Icon::SIZE_SMALL));
                $buttonBar->addButton($csvButton, ButtonBar::BUTTON_POSITION_LEFT, 40);
                // Export
                if (ExtensionManagementUtility::isLoaded('impexp')) {
                    $url = BackendUtility::getModuleUrl('xMOD_tximpexp', ['tx_impexp[action]' => 'export']);
                    $exportButton = $buttonBar->makeLinkButton()
                        ->setHref($url . '&tx_impexp[list][]=' . rawurlencode($this->table . ':' . $this->id))
                        ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.export'))
                        ->setIcon($this->iconFactory->getIcon('actions-document-export-t3d', Icon::SIZE_SMALL));
                    $buttonBar->addButton($exportButton, ButtonBar::BUTTON_POSITION_LEFT, 40);
                }
            }
            // Reload
            $reloadButton = $buttonBar->makeLinkButton()
                ->setHref($this->listURL())
                ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.reload'))
                ->setIcon($this->iconFactory->getIcon('actions-refresh', Icon::SIZE_SMALL));
            $buttonBar->addButton($reloadButton, ButtonBar::BUTTON_POSITION_RIGHT);
            // Shortcut
            if ($backendUser->mayMakeShortcut()) {
                $shortCutButton = $buttonBar->makeShortcutButton()
                    ->setModuleName('web_list')
                    ->setGetVariables([
                        'id',
                        'M',
                        'imagemode',
                        'pointer',
                        'table',
                        'search_field',
                        'search_levels',
                        'showLimit',
                        'sortField',
                        'sortRev',
                    ])
                    ->setSetVariables(array_keys($this->MOD_MENU));
                $buttonBar->addButton($shortCutButton, ButtonBar::BUTTON_POSITION_RIGHT);
            }
            // Back
            if ($this->returnUrl) {
                $href = htmlspecialchars(GeneralUtility::linkThisUrl($this->returnUrl, ['id' => $this->id]));
                $buttons['back'] = '<a href="' . $href . '" class="typo3-goBack" title="'
                    . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.goBack')) . '">'
                    . $this->iconFactory->getIcon('actions-view-go-back', Icon::SIZE_SMALL) . '</a>';
            }
        }
    }

    /**
     * Creates the listing of records from a single table
     *
     * @param string $table Table name
     * @param int $id Page id
     * @param string $rowList List of fields to show in the listing. Pseudo fields will be added including the record header.
     * @throws \UnexpectedValueException
     * @return string HTML table with the listing for the record.
     */
    public function getTable($table, $id, $rowList = '')
    {
        $backendLayout = $this->getBackendLayoutView()->getSelectedBackendLayout($id);
        $backendLayoutColumns = [];
        if (is_array($backendLayout['__items'])) {
            foreach ($backendLayout['__items'] as $backendLayoutItem) {
                $backendLayoutColumns[$backendLayoutItem[1]] = htmlspecialchars($backendLayoutItem[0]);
            }
        }
        $rowListArray = GeneralUtility::trimExplode(',', $rowList, true);
        // if no columns have been specified, show description (if configured)
        if (!empty($GLOBALS['TCA'][$table]['ctrl']['descriptionColumn']) && empty($rowListArray)) {
            $rowListArray[] = $GLOBALS['TCA'][$table]['ctrl']['descriptionColumn'];
        }
        $backendUser = $this->getBackendUserAuthentication();
        $lang = $this->getLanguageService();
        // Init
        $addWhere = '';
        /** @var $queryBuilder QueryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $titleCol = $GLOBALS['TCA'][$table]['ctrl']['label'];
        $thumbsCol = $GLOBALS['TCA'][$table]['ctrl']['thumbnail'];
        $this->l10nEnabled = $GLOBALS['TCA'][$table]['ctrl']['languageField']
            && $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']
            && $table !== 'pages_language_overlay';
        $tableCollapsed = (bool)$this->tablesCollapsed[$table];
        // prepare space icon
        $this->spaceIcon = '<span class="btn btn-default disabled">' . $this->iconFactory->getIcon('empty-empty',
                Icon::SIZE_SMALL)->render() . '</span>';
        // Cleaning rowlist for duplicates and place the $titleCol as the first column always!
        $this->fieldArray = [];
        // title Column
        // Add title column
        $this->fieldArray[] = $titleCol;
        // Control-Panel
        if (!GeneralUtility::inList($rowList, '_CONTROL_')) {
            $this->fieldArray[] = '_CONTROL_';
        }
        // Clipboard
        if ($this->showClipboard) {
            $this->fieldArray[] = '_CLIPBOARD_';
        }
        // Ref
        if (!$this->dontShowClipControlPanels) {
            $this->fieldArray[] = '_REF_';
        }
        // Path
        if ($this->searchLevels) {
            $this->fieldArray[] = '_PATH_';
        }
        // Localization
        if ($this->localizationView && $this->l10nEnabled) {
            $this->fieldArray[] = '_LOCALIZATION_';
            $this->fieldArray[] = '_LOCALIZATION_b';
            // Only restrict to the default language if no search request is in place
            if ($this->searchString === '') {
                $addWhere = (string)$queryBuilder->expr()->orX(
                    $queryBuilder->expr()->lte($GLOBALS['TCA'][$table]['ctrl']['languageField'], 0),
                    $queryBuilder->expr()->eq($GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'], 0)
                );
            }
        }
        // Cleaning up:
        $this->fieldArray = array_unique(array_merge($this->fieldArray, $rowListArray));
        if ($this->noControlPanels) {
            $tempArray = array_flip($this->fieldArray);
            unset($tempArray['_CONTROL_']);
            unset($tempArray['_CLIPBOARD_']);
            $this->fieldArray = array_keys($tempArray);
        }
        // Creating the list of fields to include in the SQL query:
        $selectFields = $this->fieldArray;
        $selectFields[] = 'uid';
        $selectFields[] = 'pid';
        // adding column for thumbnails
        if ($thumbsCol) {
            $selectFields[] = $thumbsCol;
        }
        if ($table === 'pages') {
            $selectFields[] = 'module';
            $selectFields[] = 'extendToSubpages';
            $selectFields[] = 'nav_hide';
            $selectFields[] = 'doktype';
            $selectFields[] = 'shortcut';
            $selectFields[] = 'shortcut_mode';
            $selectFields[] = 'mount_pid';
        }
        if ($table === 'tt_content') {
            $selectFields[] = 'CType';
            $selectFields[] = 'colPos';
            $selectFields[] = 'tx_gridelements_container';
            $selectFields[] = 'tx_gridelements_columns';
        }
        if (is_array($GLOBALS['TCA'][$table]['ctrl']['enablecolumns'])) {
            $selectFields = array_merge($selectFields, $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']);
        }
        foreach (['type', 'typeicon_column', 'editlock'] as $field) {
            if ($GLOBALS['TCA'][$table]['ctrl'][$field]) {
                $selectFields[] = $GLOBALS['TCA'][$table]['ctrl'][$field];
            }
        }
        if ($GLOBALS['TCA'][$table]['ctrl']['versioningWS']) {
            $selectFields[] = 't3ver_id';
            $selectFields[] = 't3ver_state';
            $selectFields[] = 't3ver_wsid';
        }
        if ($this->l10nEnabled) {
            $selectFields[] = $GLOBALS['TCA'][$table]['ctrl']['languageField'];
            $selectFields[] = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'];
        }
        if ($GLOBALS['TCA'][$table]['ctrl']['label_alt']) {
            $selectFields = array_merge(
                $selectFields,
                GeneralUtility::trimExplode(',', $GLOBALS['TCA'][$table]['ctrl']['label_alt'], true)
            );
        }
        // Unique list!
        $selectFields = array_unique($selectFields);
        $fieldListFields = $this->makeFieldList($table, 1);
        if (empty($fieldListFields) && $GLOBALS['TYPO3_CONF_VARS']['BE']['debug']) {
            $message = sprintf($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:missingTcaColumnsMessage'),
                $table, $table);
            $messageTitle = $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:missingTcaColumnsMessageTitle');
            /** @var FlashMessage $flashMessage */
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                $message,
                $messageTitle,
                FlashMessage::WARNING,
                true
            );
            /** @var $flashMessageService FlashMessageService */
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            /** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
            $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
            $defaultFlashMessageQueue->enqueue($flashMessage);
        }
        // Making sure that the fields in the field-list ARE in the field-list from TCA!
        $selectFields = array_intersect($selectFields, $fieldListFields);
        // Implode it into a list of fields for the SQL-statement.
        $selFieldList = implode(',', $selectFields);
        $this->selFieldList = $selFieldList;
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['getTable'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['getTable'] as $classData) {
                $hookObject = GeneralUtility::getUserObj($classData);
                if (!$hookObject instanceof RecordListGetTableHookInterface) {
                    throw new \UnexpectedValueException($classData . ' must implement interface ' . RecordListGetTableHookInterface::class,
                        1195114460);
                }
                $hookObject->getDBlistQuery($table, $id, $addWhere, $selFieldList, $this);
            }
        }
        $additionalConstraints = empty($addWhere) ? [] : [QueryHelper::stripLogicalOperatorPrefix($addWhere)];
        if ($table === 'tt_content') {
            $additionalConstraints[] = (string)$queryBuilder->expr()->andX(
                $queryBuilder->expr()->neq('colPos', -1)
            );
        }
        $selFieldList = GeneralUtility::trimExplode(',', $selFieldList, true);

        // Create the SQL query for selecting the elements in the listing:
        // do not do paging when outputting as CSV
        if ($this->csvOutput) {
            $this->iLimit = 0;
        }
        if ($this->firstElementNumber > 2 && $this->iLimit > 0) {
            // Get the two previous rows for sorting if displaying page > 1
            $this->firstElementNumber = $this->firstElementNumber - 2;
            $this->iLimit = $this->iLimit + 2;
            // (API function from TYPO3\CMS\Recordlist\RecordList\AbstractDatabaseRecordList)
            $queryBuilder = $this->getQueryBuilder($table, $id, $additionalConstraints);
            $this->firstElementNumber = $this->firstElementNumber + 2;
            $this->iLimit = $this->iLimit - 2;
        } else {
            // (API function from TYPO3\CMS\Recordlist\RecordList\AbstractDatabaseRecordList)
            $queryBuilder = $this->getQueryBuilder($table, $id, $additionalConstraints);
        }

        if ($table === 'tt_content') {
            array_pop($additionalConstraints);
        }

        // Finding the total amount of records on the page
        // (API function from TYPO3\CMS\Recordlist\RecordList\AbstractDatabaseRecordList)
        $this->setTotalItems($table, $id, $additionalConstraints);

        // Init:
        $queryResult = $queryBuilder->execute();
        $dbCount = 0;
        $out = '';
        $tableHeader = '';
        $listOnlyInSingleTableMode = $this->listOnlyInSingleTableMode && !$this->table;
        // If the count query returned any number of records, we perform the real query,
        // selecting records.
        if ($this->totalItems) {
            // Fetch records only if not in single table mode
            if ($listOnlyInSingleTableMode) {
                $dbCount = $this->totalItems;
            } else {
                // Set the showLimit to the number of records when outputting as CSV
                if ($this->csvOutput) {
                    $this->showLimit = $this->totalItems;
                    $this->iLimit = $this->totalItems;
                }
                $dbCount = $queryResult->rowCount();
            }
        }
        // If any records was selected, render the list:
        if ($dbCount) {
            $tableTitle = htmlspecialchars($lang->sL($GLOBALS['TCA'][$table]['ctrl']['title']));
            if ($tableTitle === '') {
                $tableTitle = $table;
            }
            // Header line is drawn
            $theData = [];
            if ($this->disableSingleTableView) {
                $theData[$titleCol] = '<span class="c-table">' . BackendUtility::wrapInHelp($table, '', $tableTitle)
                    . '</span> (<span class="t3js-table-total-items">' . $this->totalItems . '</span>)';
            } else {
                $icon = $this->table
                    ? '<span title="' . htmlspecialchars($lang->getLL('contractView')) . '">' . $this->iconFactory->getIcon('actions-view-table-collapse',
                        Icon::SIZE_SMALL)->render() . '</span>'
                    : '<span title="' . htmlspecialchars($lang->getLL('expandView')) . '">' . $this->iconFactory->getIcon('actions-view-table-expand',
                        Icon::SIZE_SMALL)->render() . '</span>';
                $theData[$titleCol] = $this->linkWrapTable($table,
                    $tableTitle . ' (<span class="t3js-table-total-items">' . $this->totalItems . '</span>) ' . $icon);
            }
            if ($listOnlyInSingleTableMode) {
                $tableHeader .= BackendUtility::wrapInHelp($table, '', $theData[$titleCol]);
            } else {
                // Render collapse button if in multi table mode
                $collapseIcon = '';
                if (!$this->table) {
                    $href = htmlspecialchars(($this->listURL() . '&collapse[' . $table . ']=' . ($tableCollapsed ? '0' : '1')));
                    $title = $tableCollapsed
                        ? htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.expandTable'))
                        : htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.collapseTable'));
                    $icon = '<span class="collapseIcon">' . $this->iconFactory->getIcon(($tableCollapsed ? 'actions-view-list-expand' : 'actions-view-list-collapse'),
                            Icon::SIZE_SMALL)->render() . '</span>';
                    $collapseIcon = '<a href="' . $href . '" title="' . $title . '" class="pull-right t3js-toggle-recordlist" data-table="' . htmlspecialchars($table) . '" data-toggle="collapse" data-target="#recordlist-' . htmlspecialchars($table) . '">' . $icon . '</a>';
                }
                $tableHeader .= $theData[$titleCol] . $collapseIcon;
            }
            // Check if gridelements containers are expanded or collapsed
            if ($table === 'tt_content') {
                $this->expandedGridelements = [];
                $backendUser = $this->getBackendUserAuthentication();
                if (is_array($backendUser->uc['moduleData']['list']['gridelementsExpanded'])) {
                    $this->expandedGridelements = $backendUser->uc['moduleData']['list']['gridelementsExpanded'];
                }
                $expandOverride = GeneralUtility::_GP('gridelementsExpand');
                if (is_array($expandOverride)) {
                    foreach ($expandOverride as $expandContainer => $expandValue) {
                        if ($expandValue) {
                            $this->expandedGridelements[$expandContainer] = 1;
                        } else {
                            unset($this->expandedGridelements[$expandContainer]);
                        }
                    }
                    $backendUser->uc['moduleData']['list']['gridelementsExpanded'] = $this->expandedGridelements;
                    // Save modified user uc
                    $backendUser->writeUC($backendUser->uc);
                    $returnUrl = GeneralUtility::sanitizeLocalUrl(GeneralUtility::_GP('returnUrl'));
                    if ($returnUrl !== '') {
                        HttpUtility::redirect($returnUrl);
                    }
                }
            }
            // Render table rows only if in multi table view or if in single table view
            $rowOutput = '';
            if (!$listOnlyInSingleTableMode || $this->table) {
                // Fixing an order table for sortby tables
                $this->currentTable = [];
                $this->currentIdList = [];
                $doSort = $GLOBALS['TCA'][$table]['ctrl']['sortby'] && !$this->sortField;
                $prevUid = 0;
                $prevPrevUid = 0;
                // Get first two rows and initialize prevPrevUid and prevUid if on page > 1
                if ($this->firstElementNumber > 2 && $this->iLimit > 0) {
                    $row = $queryResult->fetch();
                    $prevPrevUid = -((int)$row['uid']);
                    $row = $queryResult->fetch();
                    $prevUid = $row['uid'];
                }
                $accRows = [];
                // Accumulate rows here
                while ($row = $queryResult->fetch()) {
                    if (!$this->isRowListingConditionFulfilled($table, $row)) {
                        continue;
                    }
                    // In offline workspace, look for alternative record:
                    BackendUtility::workspaceOL($table, $row, $backendUser->workspace, true);
                    if (is_array($row)) {
                        $accRows[] = $row;
                        $this->currentIdList[] = $row['uid'];
                        if ($row['CType'] === 'gridelements_pi1') {
                            $this->currentContainerIdList[] = $row['uid'];
                        }
                        if ($doSort) {
                            if ($prevUid) {
                                $this->currentTable['prev'][$row['uid']] = $prevPrevUid;
                                $this->currentTable['next'][$prevUid] = '-' . $row['uid'];
                                $this->currentTable['prevUid'][$row['uid']] = $prevUid;
                            }
                            $prevPrevUid = isset($this->currentTable['prev'][$row['uid']]) ? -$prevUid : $row['pid'];
                            $prevUid = $row['uid'];
                        }
                    }
                }
                $this->totalRowCount = count($accRows);
                // CSV initiated
                if ($this->csvOutput) {
                    $this->initCSV();
                }
                // Render items:
                $this->CBnames = [];
                $this->duplicateStack = [];
                $this->eCounter = $this->firstElementNumber;
                $cc = 0;
                $lastColPos = 0;
                foreach ($accRows as $key => $row) {
                    // Render item row if counter < limit
                    if ($cc < $this->iLimit) {
                        $cc++;
                        $this->translations = false;
                        if (isset($row['colPos']) && ($row['colPos'] != $lastColPos)) {
                            $lastColPos = $row['colPos'];
                            $this->showMoveUp = false;
                            $rowOutput .= '<tr>
                                    <td colspan="2"></td>
                                    <td colspan="' . (count($this->fieldArray) - 1 + $this->maxDepth) . '" style="padding:5px;">
                                        <br />
                                        <strong>'
                                . $this->getLanguageService()->sL('LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:list.columnName')
                                . ' ' . ($backendLayoutColumns[$row['colPos']] ? $backendLayoutColumns[$row['colPos']] : (int)$row['colPos']) . '</strong>
                                    </td>
                                </tr>';
                        } else {
                            $this->showMoveUp = true;
                        }
                        $this->showMoveDown = !isset($row['colPos']) || !isset($accRows[$key + 1])
                            || $row['colPos'] == $accRows[$key + 1]['colPos'];
                        $rowOutput .= $this->renderListRow($table, $row, $cc, $titleCol, $thumbsCol);
                        // If localization view is enabled and no search happened it means that the selected
                        // records are either default or All language and here we will not select translations
                        // which point to the main record:
                        if ($this->localizationView && $this->l10nEnabled && $this->searchString === '') {
                            // For each available translation, render the record:
                            if (is_array($this->translations)) {
                                foreach ($this->translations as $lRow) {
                                    // $lRow isn't always what we want - if record was moved we've to work with the
                                    // placeholder records otherwise the list is messed up a bit
                                    if ($row['_MOVE_PLH_uid'] && $row['_MOVE_PLH_pid']) {
                                        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                                            ->getQueryBuilderForTable($table);
                                        $queryBuilder->getRestrictions()
                                            ->removeAll()
                                            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
                                        $predicates = [
                                            $queryBuilder->expr()->eq(
                                                't3ver_move_id',
                                                $queryBuilder->createNamedParameter((int)$lRow['uid'], \PDO::PARAM_INT)
                                            ),
                                            $queryBuilder->expr()->eq(
                                                'pid',
                                                $queryBuilder->createNamedParameter((int)$row['_MOVE_PLH_pid'],
                                                    \PDO::PARAM_INT)
                                            ),
                                            $queryBuilder->expr()->eq(
                                                't3ver_wsid',
                                                $queryBuilder->createNamedParameter((int)$row['t3ver_wsid'],
                                                    \PDO::PARAM_INT)
                                            ),
                                        ];

                                        $tmpRow = $queryBuilder
                                            ->select(...$selFieldList)
                                            ->from($table)
                                            ->andWhere(...$predicates)
                                            ->execute()
                                            ->fetch();

                                        $lRow = is_array($tmpRow) ? $tmpRow : $lRow;
                                    }
                                    // In offline workspace, look for alternative record:
                                    BackendUtility::workspaceOL($table, $lRow, $backendUser->workspace, true);
                                    if (is_array($lRow) && $backendUser->checkLanguageAccess($lRow[$GLOBALS['TCA'][$table]['ctrl']['languageField']])) {
                                        $this->currentIdList[] = $lRow['uid'];
                                        $rowOutput .= $this->renderListRow($table, $lRow, $cc, $titleCol, $thumbsCol,
                                            18);
                                    }
                                }
                            }
                        }
                    }
                    // Counter of total rows incremented:
                    $this->eCounter++;
                }
                // The header row for the table is now created:
                $out .= $this->renderListHeader($table, $this->currentIdList);
                // Record navigation is added to the beginning and end of the table if in single
                // table mode
                if ($this->table) {
                    $rowOutput = $this->renderListNavigation('top') . $rowOutput . $this->renderListNavigation('bottom');
                } else {
                    // Show that there are more records than shown
                    if ($this->totalItems > $this->itemsLimitPerTable) {
                        $countOnFirstPage = $this->totalItems > $this->itemsLimitSingleTable
                            ? $this->itemsLimitSingleTable
                            : $this->totalItems;
                        $hasMore = $this->totalItems > $this->itemsLimitSingleTable;
                        $colspan = $this->showIcon
                            ? count($this->fieldArray) + 1 + $this->maxDepth
                            : count($this->fieldArray);
                        $rowOutput .= '<tr><td colspan="' . $colspan . '">
								<a href="' . htmlspecialchars(($this->listURL() . '&table=' . rawurlencode($table)))
                            . '" class="btn btn-default">'
                            . '<span class="t3-icon fa fa-chevron-down"></span> <i>[1 - '
                            . $countOnFirstPage . ($hasMore ? '+' : '') . ']</i></a>
								</td></tr>';
                    }
                }
            }

            $collapseClass = $tableCollapsed && !$this->table ? 'collapse' : 'collapse in';
            $dataState = $tableCollapsed && !$this->table ? 'collapsed' : 'expanded';

            // The list of records is added after the header:
            $out .= $rowOutput;
            // ... and it is all wrapped in a table:
            $out = '



			<!--
				DB listing of elements:	"' . htmlspecialchars($table) . '"
			-->
				<div class="panel panel-space panel-default recordlist">
					<div class="panel-heading">
					' . $tableHeader . '
					</div>
					<div class="' . $collapseClass . '" data-state="' . $dataState . '" id="recordlist-' . htmlspecialchars($table) . '">
						<div class="table-fit">
							<table data-table="' . htmlspecialchars($table) . '" class="table table-striped table-hover' . ($listOnlyInSingleTableMode ? ' typo3-dblist-overview' : '') . '">
								' . $out . '
							</table>
						</div>
					</div>
				</div>
			';
            // Output csv if...
            // This ends the page with exit.
            if ($this->csvOutput) {
                $this->outputCSV($table);
            }
        }
        // Return content:
        return $out;
    }

    /**
     * @return BackendLayoutView
     */
    protected function getBackendLayoutView()
    {
        return GeneralUtility::makeInstance(BackendLayoutView::class);
    }

    /*********************************
     *
     * Rendering of various elements
     *
     *********************************/

    /**
     * Check if all row listing conditions are fulfilled.
     *
     * This function serves as a dummy method to be overridden in extending classes.
     *
     * @param string $table Table name
     * @param string[] $row Record
     * @return bool True, if all conditions are fulfilled.
     */
    protected function isRowListingConditionFulfilled($table, $row)
    {
        return true;
    }

    /**
     * Initializes internal csvLines array with the header of field names
     */
    protected function initCSV()
    {
        $this->addHeaderRowToCSV();
    }

    /**
     * Add header line with field names as CSV line
     */
    protected function addHeaderRowToCSV()
    {
        // Add header row, control fields will be reduced inside addToCSV()
        $this->addToCSV(array_combine($this->fieldArray, $this->fieldArray));
    }

    /*********************************
     *
     * Rendering of various elements
     *
     *********************************/

    /**
     * Adds selected columns of one table row as CSV line.
     *
     * @param mixed[] $row Record array, from which the values of fields found in $this->fieldArray will be listed in the CSV output.
     */
    protected function addToCSV(array $row = [])
    {
        $rowReducedByControlFields = self::removeControlFieldsFromFieldRow($row);
        // Get an field array without control fields but in the expected order
        $fieldArray = array_intersect_key(array_flip($this->fieldArray), $rowReducedByControlFields);
        // Overwrite fieldArray to keep the order with an array of needed fields
        $rowReducedToSelectedColumns = array_replace($fieldArray,
            array_intersect_key($rowReducedByControlFields, $fieldArray));
        $this->setCsvRow($rowReducedToSelectedColumns);
    }

    /**
     * Remove control fields from row for CSV export
     *
     * @param mixed[] $row fieldNames => fieldValues
     * @return mixed[] Input array reduces by control fields
     */
    protected static function removeControlFieldsFromFieldRow(array $row = [])
    {
        // Possible control fields in a list row
        $controlFields = [
            '_PATH_',
            '_REF_',
            '_CONTROL_',
            '_CLIPBOARD_',
            '_LOCALIZATION_',
            '_LOCALIZATION_b',
        ];
        return array_diff_key($row, array_flip($controlFields));
    }

    /**
     * Adds input row of values to the internal csvLines array as a CSV formatted line
     *
     * @param mixed[] $csvRow Array with values to be listed.
     */
    public function setCsvRow($csvRow)
    {
        $this->csvLines[] = CsvUtility::csvValues($csvRow);
    }

    /**
     * Rendering a single row for the list
     *
     * @param string $table Table name
     * @param mixed[] $row Current record
     * @param int $cc Counter, counting for each time an element is rendered (used for alternating colors)
     * @param string $titleCol Table field (column) where header value is found
     * @param string $thumbsCol Table field (column) where (possible) thumbnails can be found
     * @param int $indent Indent from left.
     * @param int $level
     * @param int $triggerContainer
     * @param string $expanded
     * @return string Table row for the element
     * @internal param string $expanded
     * @access private
     * @see getTable()
     */
    public function renderListRow($table, $row, $cc, $titleCol, $thumbsCol, $indent = 0, $level = 0, $triggerContainer = 0, $expanded = '')
    {
        if (!is_array($row)) {
            return '';
        }
        $rowOutput = '';
        $id_orig = null;
        // If in search mode, make sure the preview will show the correct page
        if ((string)$this->searchString !== '') {
            $id_orig = $this->id;
            $this->id = $row['pid'];
        }

        $tagAttributes = [
            'class'      => ['t3js-entity'],
            'data-table' => $table,
        ];

        // Add special classes for first and last row
        if ($cc == 1 && $indent == 0) {
            $tagAttributes['class'][] = 'firstcol';
        }
        if ($cc == $this->totalRowCount || $cc == $this->iLimit) {
            $tagAttributes['class'][] = 'lastcol';
        }
        // Overriding with versions background color if any:
        if (!empty($row['_CSSCLASS'])) {
            $tagAttributes['class'] = [$row['_CSSCLASS']];
        }
        // Incr. counter.
        $this->counter++;
        // The icon with link
        $toolTip = BackendUtility::getRecordToolTip($row, $table);
        $additionalStyle = $indent ? ' style="margin-left: ' . $indent . 'px;"' : '';
        $iconImg = '<span ' . $toolTip . ' ' . $additionalStyle . '>'
            . $this->iconFactory->getIconForRecord($table, $row, Icon::SIZE_SMALL)->render()
            . '</span>';
        $theIcon = $this->clickMenuEnabled ? BackendUtility::wrapClickMenuOnIcon($iconImg, $table,
            $row['uid']) : $iconImg;
        // Preparing and getting the data-array
        $theData = [];
        $localizationMarkerClass = '';
        $lC2 = '';
        foreach ($this->fieldArray as $fCol) {
            if ($fCol == $titleCol) {
                $recTitle = BackendUtility::getRecordTitle($table, $row, false, true);
                $warning = '';
                // If the record is edit-locked	by another user, we will show a little warning sign:
                $lockInfo = BackendUtility::isRecordLocked($table, $row['uid']);
                if ($lockInfo) {
                    $warning = '<span data-toggle="tooltip" data-placement="right" data-title="' . htmlspecialchars($lockInfo['msg']) . '">'
                        . $this->iconFactory->getIcon('status-warning-in-use', Icon::SIZE_SMALL)->render() . '</span>';
                }
                $theData[$fCol] = $theData['__label'] = $warning . $this->linkWrapItems($table, $row['uid'], $recTitle,
                        $row);
                // Render thumbnails, if:
                // - a thumbnail column exists
                // - there is content in it
                // - the thumbnail column is visible for the current type
                $type = 0;
                if (isset($GLOBALS['TCA'][$table]['ctrl']['type'])) {
                    $typeColumn = $GLOBALS['TCA'][$table]['ctrl']['type'];
                    $type = $row[$typeColumn];
                }
                // If current type doesn't exist, set it to 0 (or to 1 for historical reasons,
                // if 0 doesn't exist)
                if (!isset($GLOBALS['TCA'][$table]['types'][$type])) {
                    $type = isset($GLOBALS['TCA'][$table]['types'][0]) ? 0 : 1;
                }
                $visibleColumns = $GLOBALS['TCA'][$table]['types'][$type]['showitem'];

                if ($this->thumbs &&
                    trim($row[$thumbsCol]) &&
                    preg_match('/(^|(.*(;|,)?))' . $thumbsCol . '(((;|,).*)|$)/', $visibleColumns) === 1
                ) {
                    $thumbCode = '<br />' . $this->thumbCode($row, $table, $thumbsCol);
                    $theData[$fCol] .= $thumbCode;
                    $theData['__label'] .= $thumbCode;
                }
                if (isset($GLOBALS['TCA'][$table]['ctrl']['languageField'])
                    && $row[$GLOBALS['TCA'][$table]['ctrl']['languageField']] != 0
                    && $row[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']] != 0
                ) {
                    // It's a translated record with a language parent
                    $localizationMarkerClass = ' localization';
                }
            } elseif ($fCol === 'pid') {
                $theData[$fCol] = $row[$fCol];
            } elseif ($fCol === '_PATH_') {
                $theData[$fCol] = $this->recPath($row['pid']);
            } elseif ($fCol === '_REF_') {
                $theData[$fCol] = $this->createReferenceHtml($table, $row['uid']);
            } elseif ($fCol === '_CONTROL_') {
                $theData[$fCol] = $this->makeControl($table, $row);
            } elseif ($fCol === '_CLIPBOARD_') {
                $theData[$fCol] = $this->makeClip($table, $row);
            } elseif ($fCol === '_LOCALIZATION_') {
                list($lC1, $lC2) = $this->makeLocalizationPanel($table, $row);
                $theData[$fCol] = $lC1;
            } elseif ($fCol !== '_LOCALIZATION_b') {
                $tmpProc = BackendUtility::getProcessedValueExtra($table, $fCol, $row[$fCol], 100, $row['uid']);
                $theData[$fCol] = $this->linkUrlMail(htmlspecialchars($tmpProc), $row[$fCol]);
                if ($this->csvOutput) {
                    $row[$fCol] = BackendUtility::getProcessedValueExtra($table, $fCol, $row[$fCol], 0, $row['uid']);
                }
            } elseif ($fCol === '_LOCALIZATION_b') {
                $theData[$fCol] = $lC2;
            } else {
                $theData[$fCol] = htmlspecialchars(BackendUtility::getProcessedValueExtra($table, $fCol, $row[$fCol], 0,
                    $row['uid']));
            }
        }
        // Reset the ID if it was overwritten
        if ((string)$this->searchString !== '') {
            $this->id = $id_orig;
        }
        // Add row to CSV list:
        if ($this->csvOutput) {
            $this->addToCSV($row);
        }
        // Add classes to table cells
        $this->addElement_tdCssClass[$titleCol] = 'col-title col-responsive' . $localizationMarkerClass;
        $this->addElement_tdCssClass['__label'] = $this->addElement_tdCssClass[$titleCol];
        $this->addElement_tdCssClass['_CONTROL_'] = 'col-control';
        if ($this->getModule()->MOD_SETTINGS['clipBoard']) {
            $this->addElement_tdCssClass['_CLIPBOARD_'] = 'col-clipboard';
        }
        $this->addElement_tdCssClass['_PATH_'] = 'col-path';
        $this->addElement_tdCssClass['_LOCALIZATION_'] = 'col-localizationa';
        $this->addElement_tdCssClass['_LOCALIZATION_b'] = 'col-localizationb';
        /**
         * @hook checkChildren
         * @date 2014-02-11
         * @request Alexander Grein <alexander.grein@in2code.de>
         */
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'] as $classData) {
                $hookObject = GeneralUtility::getUserObj($classData);
                if (is_object($hookObject) && method_exists($hookObject, 'checkChildren')) {
                    $hookObject->checkChildren($table, $row, $level, $theData, $this);
                }
            }
        }
        // Create element in table cells:
        $theData['uid'] = $row['uid'];
        if ($table === 'tt_content') {
            $theData['tx_gridelements_container'] = (int)$row['tx_gridelements_container'];
        }
        if (isset($GLOBALS['TCA'][$table]['ctrl']['languageField'])
            && isset($GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'])
            && !isset($GLOBALS['TCA'][$table]['ctrl']['transOrigPointerTable'])
        ) {
            $theData['parent'] = $row[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']];
        }
        $tagAttributes = array_map(
            function ($attributeValue) {
                if (is_array($attributeValue)) {
                    return implode(' ', $attributeValue);
                }
                return $attributeValue;
            },
            $tagAttributes
        );

        if ($triggerContainer) {
            $theData['_triggerContainer'] = $triggerContainer;
        };

        $rowOutput .= $this->addElement(1, $theIcon, $theData, GeneralUtility::implodeAttributes($tagAttributes, true));

        if ($this->localizationView && $this->l10nEnabled) {
            // For each available translation, render the record:
            if (is_array($this->translations)) {
                foreach ($this->translations as $lRow) {
                    // $lRow isn't always what we want - if record was moved we've to work with the
                    // placeholder records otherwise the list is messed up a bit
                    if ($row['_MOVE_PLH_uid'] && $row['_MOVE_PLH_pid']) {
                        $where = 't3ver_move_id="' . (int)$lRow['uid'] . '" AND pid="' . $row['_MOVE_PLH_pid']
                            . '" AND t3ver_wsid=' . $row['t3ver_wsid'] . BackendUtility::deleteClause($table);
                        $tmpRow = BackendUtility::getRecordRaw($table, $where, $this->selFieldList);
                        $lRow = is_array($tmpRow) ? $tmpRow : $lRow;
                    }
                    // In offline workspace, look for alternative record:
                    BackendUtility::workspaceOL($table, $lRow, $this->backendUser->workspace, true);
                    if (is_array($lRow) && $this->getBackendUserAuthentication()->checkLanguageAccess($lRow[$GLOBALS['TCA'][$table]['ctrl']['languageField']])) {
                        $this->currentIdList[] = $lRow['uid'];
                        if ($row['tx_gridelements_container']) {
                            $lRow['_CSSCLASS'] = 't3-gridelements-child' . $expanded;
                        }
                        $rowOutput .= $this->renderListRow($table, $lRow, $cc, $titleCol, $thumbsCol, 20, $level, $row['tx_gridelements_container'], $expanded);
                    }
                }
            }
        }

        if ($theData['_EXPANDABLE_'] && $level < 8 && ($row['l18n_parent'] == 0 || !$this->localizationView) && !empty($theData['_CHILDREN_'])) {
            $expanded = $this->expandedGridelements[$row['uid']] ? ' expanded' : '';
            $previousGridColumn = '';
            $originalMoveUp = $this->showMoveUp;
            $originalMoveDown = $this->showMoveDown;
            foreach ($theData['_CHILDREN_'] as $key => $child) {
                if (isset($child['tx_gridelements_columns']) && ($child['tx_gridelements_columns'] !== $previousGridColumn)) {
                    $previousGridColumn = $child['tx_gridelements_columns'];
                    $this->currentTable['prev'][$child['uid']] = (int)$row['pid'];
                } else {
                    if (isset($theData['_CHILDREN_'][$key - 2]) && $theData['_CHILDREN_'][$key - 2]['tx_gridelements_columns'] === $child['tx_gridelements_columns']) {
                        $this->currentTable['prev'][$child['uid']] = -(int)$theData['_CHILDREN_'][$key - 2]['uid'];
                    } else {
                        $this->currentTable['prev'][$child['uid']] = (int)$row['pid'];
                    }
                }
                if (isset($theData['_CHILDREN_'][$key + 1]) && $theData['_CHILDREN_'][$key + 1]['tx_gridelements_columns'] === $child['tx_gridelements_columns']) {
                    $this->currentTable['next'][$child['uid']] = -(int)$theData['_CHILDREN_'][$key + 1]['uid'];
                }
            }
            $previousGridColumn = '';
            foreach ($theData['_CHILDREN_'] as $key => $child) {
                if (isset($child['tx_gridelements_columns']) && ($child['tx_gridelements_columns'] !== $previousGridColumn)) {
                    $previousGridColumn = $child['tx_gridelements_columns'];
                    $this->showMoveUp = false;
                    $rowOutput .= '<tr class="t3-gridelements-child'. $expanded . '" data-trigger-container="'
                        . ($this->localizationView && $row['l18n_parent'] ? $row['l18n_parent'] : $row['uid'])
                        . '" data-grid-container="' . $row['uid'] . '">
                                <td colspan="' . ($level + 2) . '"></td>
                                <td colspan="' . (count($this->fieldArray) - $level - 2 + $this->maxDepth) . '" style="padding:5px;">
                                    <br />
                                    <strong>' . $this->getLanguageService()->sL('LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:list.columnName')
                        . ' ' . (int)$child['tx_gridelements_columns'] . '</strong>
                                </td>
                            </tr>';
                } else {
                    $this->showMoveUp = true;
                }
                $this->showMoveDown = !isset($child['tx_gridelements_columns']) || !isset($theData['_CHILDREN_'][$key + 1])
                    || (int)$child['tx_gridelements_columns'] === (int)$theData['_CHILDREN_'][$key + 1]['tx_gridelements_columns'];
                $this->currentIdList[] = $child['uid'];
                if ($row['CType'] === 'gridelements_pi1') {
                    $this->currentContainerIdList[] = $row['uid'];
                }
                $child['_CSSCLASS'] = 't3-gridelements-child' . $expanded;
                $rowOutput .= $this->renderListRow($table, $child, $cc, $titleCol, $thumbsCol, 0, $level + 1, $row['uid'], $expanded);
            }
            $this->showMoveUp = $originalMoveUp;
            $this->showMoveDown = $originalMoveDown;
        }

        // Finally, return table row element:
        return $rowOutput;
    }

    /**
     * Returns the path for a certain pid
     * The result is cached internally for the session, thus you can call
     * this function as much as you like without performance problems.
     *
     * @param int $pid The page id for which to get the path
     * @return mixed[] The path.
     */
    public function recPath($pid)
    {
        if (!isset($this->recPath_cache[$pid])) {
            $this->recPath_cache[$pid] = BackendUtility::getRecordPath($pid, $this->perms_clause, 20);
        }
        return $this->recPath_cache[$pid];
    }

    /*********************************
     *
     * Helper functions
     *
     *********************************/

    /**
     * Creates the HTML for a reference count for the record with the UID $uid
     * in the table $tableName.
     *
     * @param string $tableName
     * @param int $uid
     * @return string HTML of reference a link, will be empty if there are no
     */
    protected function createReferenceHtml($tableName, $uid)
    {
        $referenceCount = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('sys_refindex')
            ->count(
                '*',
                'sys_refindex',
                [
                    'ref_table' => $tableName,
                    'ref_uid'   => (int)$uid,
                    'deleted'   => 0,
                ]
            );

        return $this->generateReferenceToolTip(
            $referenceCount,
            GeneralUtility::quoteJSvalue($tableName) . ', ' . GeneralUtility::quoteJSvalue($uid)
        );
    }

    /**
     * Creates the control panel for a single record in the listing.
     *
     * @param string $table The table
     * @param mixed[] $row The record for which to make the control panel.
     * @throws \UnexpectedValueException
     * @return string HTML table with the control panel (unless disabled)
     */
    public function makeControl($table, $row)
    {
        $module = $this->getModule();
        $rowUid = $row['uid'];
        if (ExtensionManagementUtility::isLoaded('version') && isset($row['_ORIG_uid'])) {
            $rowUid = $row['_ORIG_uid'];
        }
        $cells = [
            'primary'   => [],
            'secondary' => [],
        ];
        // If the listed table is 'pages' we have to request the permission settings for each page:
        $localCalcPerms = 0;
        if ($table === 'pages') {
            $localCalcPerms = $this->getBackendUserAuthentication()->calcPerms(BackendUtility::getRecord('pages',
                $row['uid']));
        }
        $permsEdit = $table === 'pages'
            && $this->getBackendUserAuthentication()->checkLanguageAccess(0)
            && $localCalcPerms & Permission::PAGE_EDIT
            || $table !== 'pages'
            && $this->calcPerms & Permission::CONTENT_EDIT
            && $this->getBackendUserAuthentication()->recordEditAccessInternals($table, $row);
        $permsEdit = $this->overlayEditLockPermissions($table, $row, $permsEdit);
        // hides the move, copy, cut and paste icons for localized records - doesn't make much sense to perform these options for them
        $isL10nOverlay = $table !== 'pages_language_overlay' && $row[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']] != 0;
        // "Show" link (only pages and tt_content elements)
        if ($table === 'pages' || $table === 'tt_content') {
            $viewAction = '<a class="btn btn-default" href="#" onclick="'
                . htmlspecialchars(
                    BackendUtility::viewOnClick(
                        ($table === 'tt_content' ? $this->id : $row['uid']),
                        '',
                        '',
                        ($table === 'tt_content' ? '#c' . $row['uid'] : '')
                    )
                ) . '" title="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage')) . '">'
                . $this->iconFactory->getIcon('actions-view', Icon::SIZE_SMALL)->render() . '</a>';
            $this->addActionToCellGroup($cells, $viewAction, 'view');
        }
        // "Edit" link: ( Only if permissions to edit the page-record of the content of the parent page ($this->id)
        if ($permsEdit) {
            $params = '&edit[' . $table . '][' . $row['uid'] . ']=edit';
            $iconIdentifier = 'actions-open';
            if ($table === 'pages') {
                $iconIdentifier = 'actions-page-open';
            }
            $overlayIdentifier = !$this->isEditable($table) ? 'overlay-readonly' : null;
            $editAction = '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick($params,
                    '', -1))
                . '" title="' . htmlspecialchars($this->getLanguageService()->getLL('edit')) . '">' . $this->iconFactory->getIcon($iconIdentifier,
                    Icon::SIZE_SMALL, $overlayIdentifier)->render() . '</a>';
        } else {
            $editAction = $this->spaceIcon;
        }
        $this->addActionToCellGroup($cells, $editAction, 'edit');
        // "Info": (All records)
        $onClick = 'top.launchView(' . GeneralUtility::quoteJSvalue($table) . ', ' . (int)$row['uid'] . '); return false;';
        $viewBigAction = '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars($onClick) . '" title="' . htmlspecialchars($this->getLanguageService()->getLL('showInfo')) . '">'
            . $this->iconFactory->getIcon('actions-document-info', Icon::SIZE_SMALL)->render() . '</a>';
        $this->addActionToCellGroup($cells, $viewBigAction, 'viewBig');
        // "Move" wizard link for pages/tt_content elements:
        if ($permsEdit && ($table === 'tt_content' || $table === 'pages')) {
            $onClick = 'return jumpExt(' . GeneralUtility::quoteJSvalue(BackendUtility::getModuleUrl('move_element') . '&table=' . $table . '&uid=' . $row['uid']) . ');';
            $linkTitleLL = htmlspecialchars($this->getLanguageService()->getLL('move_' . ($table === 'tt_content' ? 'record' : 'page')));
            $icon = ($table === 'pages' ? $this->iconFactory->getIcon('actions-page-move',
                Icon::SIZE_SMALL) : $this->iconFactory->getIcon('actions-document-move', Icon::SIZE_SMALL));
            $moveAction = '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars($onClick) . '" title="' . $linkTitleLL . '">' . $icon->render() . '</a>';
            $this->addActionToCellGroup($cells, $moveAction, 'move');
        }
        // If the table is NOT a read-only table, then show these links:
        if ($this->isEditable($table)) {
            // "Revert" link (history/undo)
            $moduleUrl = BackendUtility::getModuleUrl('record_history', ['element' => $table . ':' . $row['uid']]);
            $onClick = 'return jumpExt(' . GeneralUtility::quoteJSvalue($moduleUrl) . ',\'#latest\');';
            $historyAction = '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars($onClick) . '" title="'
                . htmlspecialchars($this->getLanguageService()->getLL('history')) . '">'
                . $this->iconFactory->getIcon('actions-document-history-open', Icon::SIZE_SMALL)->render() . '</a>';
            $this->addActionToCellGroup($cells, $historyAction, 'history');
            // Versioning:
            if (ExtensionManagementUtility::isLoaded('version') && !ExtensionManagementUtility::isLoaded('workspaces')) {
                $vers = BackendUtility::selectVersionsOfRecord($table, $row['uid'], 'uid',
                    $this->getBackendUserAuthentication()->workspace, false, $row);
                // If table can be versionized.
                if (is_array($vers)) {
                    $href = BackendUtility::getModuleUrl('web_txversionM1', [
                        'table' => $table,
                        'uid'   => $row['uid'],
                    ]);
                    $versionAction = '<a class="btn btn-default" href="' . htmlspecialchars($href) . '" title="'
                        . htmlspecialchars($this->getLanguageService()->getLL('displayVersions')) . '">'
                        . $this->iconFactory->getIcon('actions-version-page-open', Icon::SIZE_SMALL)->render() . '</a>';
                    $this->addActionToCellGroup($cells, $versionAction, 'version');
                }
            }
            // "Edit Perms" link:
            if ($table === 'pages' && $this->getBackendUserAuthentication()->check('modules',
                    'system_BeuserTxPermission') && ExtensionManagementUtility::isLoaded('beuser')) {
                $href = BackendUtility::getModuleUrl('system_BeuserTxPermission') . '&id=' . $row['uid'] . '&returnId=' . $row['uid'] . '&tx_beuser_system_beusertxpermission[action]=edit';
                $permsAction = '<a class="btn btn-default" href="' . htmlspecialchars($href) . '" title="'
                    . htmlspecialchars($this->getLanguageService()->getLL('permissions')) . '">'
                    . $this->iconFactory->getIcon('actions-lock', Icon::SIZE_SMALL)->render() . '</a>';
                $this->addActionToCellGroup($cells, $permsAction, 'perms');
            }
            // "New record after" link (ONLY if the records in the table are sorted by a "sortby"-row
            // or if default values can depend on previous record):
            if (($GLOBALS['TCA'][$table]['ctrl']['sortby'] || $GLOBALS['TCA'][$table]['ctrl']['useColumnsForDefaultValues']) && $permsEdit) {
                if ($table !== 'pages' && $this->calcPerms & Permission::CONTENT_EDIT || $table === 'pages' && $this->calcPerms & Permission::PAGE_NEW) {
                    if ($this->showNewRecLink($table)) {
                        $params = '&edit[' . $table . '][' . -($row['_MOVE_PLH'] ? $row['_MOVE_PLH_uid'] : $row['uid']) . ']=new';
                        $icon = ($table === 'pages' ? $this->iconFactory->getIcon('actions-page-new',
                            Icon::SIZE_SMALL) : $this->iconFactory->getIcon('actions-add', Icon::SIZE_SMALL));
                        $titleLabel = 'new';
                        if ($GLOBALS['TCA'][$table]['ctrl']['sortby']) {
                            $titleLabel .= ($table === 'pages' ? 'Page' : 'Record');
                        }
                        $newAction = '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick($params,
                                '', -1))
                            . '" title="' . htmlspecialchars($this->getLanguageService()->getLL($titleLabel)) . '">'
                            . $icon->render() . '</a>';
                        $this->addActionToCellGroup($cells, $newAction, 'new');
                    }
                }
            }
            // "Up/Down" links
            if ($permsEdit && $GLOBALS['TCA'][$table]['ctrl']['sortby'] && !$this->sortField && !$this->searchLevels) {
                if (isset($this->currentTable['prev'][$row['uid']]) && $this->showMoveUp === true && !$isL10nOverlay) {
                    // Up
                    $params = '&cmd[' . $table . '][' . $row['uid'] . '][move]=' . $this->currentTable['prev'][$row['uid']];
                    $moveUpAction = '<a class="btn btn-default" href="#" onclick="'
                        . htmlspecialchars('return jumpToUrl(' . BackendUtility::getLinkToDataHandlerAction($params,
                                -1) . ');')
                        . '" title="' . htmlspecialchars($this->getLanguageService()->getLL('moveUp')) . '">'
                        . $this->iconFactory->getIcon('actions-move-up', Icon::SIZE_SMALL)->render() . '</a>';
                } else {
                    $moveUpAction = $this->spaceIcon;
                }
                $this->addActionToCellGroup($cells, $moveUpAction, 'moveUp');

                if ($this->currentTable['next'][$row['uid']] && $this->showMoveDown === true && !$isL10nOverlay) {
                    // Down
                    $params = '&cmd[' . $table . '][' . $row['uid'] . '][move]=' . $this->currentTable['next'][$row['uid']];
                    $moveDownAction = '<a class="btn btn-default" href="#" onclick="'
                        . htmlspecialchars('return jumpToUrl(' . BackendUtility::getLinkToDataHandlerAction($params,
                                -1) . ');')
                        . '" title="' . htmlspecialchars($this->getLanguageService()->getLL('moveDown')) . '">'
                        . $this->iconFactory->getIcon('actions-move-down', Icon::SIZE_SMALL)->render() . '</a>';
                } else {
                    $moveDownAction = $this->spaceIcon;
                }
                $this->addActionToCellGroup($cells, $moveDownAction, 'moveDown');
            }
            // "Hide/Unhide" links:
            $hiddenField = $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled'];

            if (
                $permsEdit && $hiddenField && $GLOBALS['TCA'][$table]['columns'][$hiddenField]
                && (!$GLOBALS['TCA'][$table]['columns'][$hiddenField]['exclude']
                    || $this->getBackendUserAuthentication()->check('non_exclude_fields', $table . ':' . $hiddenField))
            ) {
                if ($this->isRecordCurrentBackendUser($table, $row)) {
                    $hideAction = $this->spaceIcon;
                } else {
                    $hideTitle = htmlspecialchars($this->getLanguageService()->getLL('hide' . ($table === 'pages' ? 'Page' : '')));
                    $unhideTitle = htmlspecialchars($this->getLanguageService()->getLL('unHide' . ($table === 'pages' ? 'Page' : '')));
                    if ($row[$hiddenField]) {
                        $params = 'data[' . $table . '][' . $rowUid . '][' . $hiddenField . ']=0';
                        $hideAction = '<a class="btn btn-default t3js-record-hide" data-state="hidden" href="#"'
                            . ' data-params="' . htmlspecialchars($params) . '"'
                            . ' title="' . $unhideTitle . '"'
                            . ' data-toggle-title="' . $hideTitle . '">'
                            . $this->iconFactory->getIcon('actions-edit-unhide', Icon::SIZE_SMALL)->render() . '</a>';
                    } else {
                        $params = 'data[' . $table . '][' . $rowUid . '][' . $hiddenField . ']=1';
                        $hideAction = '<a class="btn btn-default t3js-record-hide" data-state="visible" href="#"'
                            . ' data-params="' . htmlspecialchars($params) . '"'
                            . ' title="' . $hideTitle . '"'
                            . ' data-toggle-title="' . $unhideTitle . '">'
                            . $this->iconFactory->getIcon('actions-edit-hide', Icon::SIZE_SMALL)->render() . '</a>';
                    }
                }
                $this->addActionToCellGroup($cells, $hideAction, 'hide');
            }
            // "Delete" link:
            $disableDeleteTS = $this->getBackendUserAuthentication()->getTSConfig('options.disableDelete');
            $disableDelete = (bool)trim(isset($disableDeleteTS['properties'][$table]) ? $disableDeleteTS['properties'][$table] : $disableDeleteTS['value']);
            if ($permsEdit && !$disableDelete && ($table === 'pages' && $localCalcPerms & Permission::PAGE_DELETE || $table !== 'pages' && $this->calcPerms & Permission::CONTENT_EDIT)) {
                // Check if the record version is in "deleted" state, because that will switch the action to "restore"
                if ($this->getBackendUserAuthentication()->workspace > 0 && isset($row['t3ver_state']) && (int)$row['t3ver_state'] === 2) {
                    $actionName = 'restore';
                    $refCountMsg = '';
                } else {
                    $actionName = 'delete';
                    $refCountMsg = BackendUtility::referenceCount(
                            $table,
                            $row['uid'],
                            ' ' . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.referencesToRecord'),
                            $this->getReferenceCount($table, $row['uid'])) . BackendUtility::translationCount($table,
                            $row['uid'],
                            ' ' . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.translationsOfRecord')
                        );
                }

                if ($this->isRecordCurrentBackendUser($table, $row)) {
                    $deleteAction = $this->spaceIcon;
                } else {
                    $title = BackendUtility::getRecordTitle($table, $row);
                    $warningText = $this->getLanguageService()->getLL($actionName . 'Warning') . ' "' . $title . '" ' . '[' . $table . ':' . $row['uid'] . ']' . $refCountMsg;

                    $params = 'cmd[' . $table . '][' . $row['uid'] . '][delete]=1';
                    $icon = $this->iconFactory->getIcon('actions-edit-' . $actionName, Icon::SIZE_SMALL)->render();
                    $linkTitle = htmlspecialchars($this->getLanguageService()->getLL($actionName));
                    $deleteAction = '<a class="btn btn-default t3js-record-delete" href="#" '
                        . ' data-l10parent="' . htmlspecialchars($row['l10n_parent']) . '"'
                        . ' data-params="' . htmlspecialchars($params) . '" data-title="' . htmlspecialchars($title) . '"'
                        . ' data-message="' . htmlspecialchars($warningText) . '" title="' . $linkTitle . '"'
                        . '>' . $icon . '</a>';
                }
            } else {
                $deleteAction = $this->spaceIcon;
            }
            $this->addActionToCellGroup($cells, $deleteAction, 'delete');
            // "Levels" links: Moving pages into new levels...
            if ($permsEdit && $table === 'pages' && !$this->searchLevels) {
                // Up (Paste as the page right after the current parent page)
                if ($this->calcPerms & Permission::PAGE_NEW) {
                    $params = '&cmd[' . $table . '][' . $row['uid'] . '][move]=' . -$this->id;
                    $moveLeftAction = '<a class="btn btn-default" href="#" onclick="'
                        . htmlspecialchars('return jumpToUrl(' . BackendUtility::getLinkToDataHandlerAction($params,
                                -1) . ');')
                        . '" title="' . htmlspecialchars($this->getLanguageService()->getLL('prevLevel')) . '">'
                        . $this->iconFactory->getIcon('actions-move-left', Icon::SIZE_SMALL)->render() . '</a>';
                    $this->addActionToCellGroup($cells, $moveLeftAction, 'moveLeft');
                }
                // Down (Paste as subpage to the page right above)
                if ($this->currentTable['prevUid'][$row['uid']]) {
                    $localCalcPerms = $this->getBackendUserAuthentication()->calcPerms(BackendUtility::getRecord('pages',
                        $this->currentTable['prevUid'][$row['uid']]));
                    if ($localCalcPerms & Permission::PAGE_NEW) {
                        $params = '&cmd[' . $table . '][' . $row['uid'] . '][move]=' . $this->currentTable['prevUid'][$row['uid']];
                        $moveRightAction = '<a class="btn btn-default" href="#" onclick="'
                            . htmlspecialchars('return jumpToUrl(' . BackendUtility::getLinkToDataHandlerAction($params,
                                    -1) . ');')
                            . '" title="' . htmlspecialchars($this->getLanguageService()->getLL('nextLevel')) . '">'
                            . $this->iconFactory->getIcon('actions-move-right', Icon::SIZE_SMALL)->render() . '</a>';
                    } else {
                        $moveRightAction = $this->spaceIcon;
                    }
                } else {
                    $moveRightAction = $this->spaceIcon;
                }
                $this->addActionToCellGroup($cells, $moveRightAction, 'moveRight');
            }
        }
        /**
         * @hook recStatInfoHooks: Allows to insert HTML before record icons on various places
         */
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['recStatInfoHooks'])) {
            $stat = '';
            $_params = [$table, $row['uid']];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['recStatInfoHooks'] as $_funcRef) {
                $stat .= GeneralUtility::callUserFunction($_funcRef, $_params, $this);
            }
            $this->addActionToCellGroup($cells, $stat, 'stat');
        }
        /**
         * @hook makeControl: Allows to change control icons of records in list-module
         * @usage This hook method gets passed the current $cells array as third parameter.
         *        This array contains values for the icons/actions generated for each record in Web>List.
         *        Each array entry is accessible by an index-key.
         *        The order of the icons is depending on the order of those array entries.
         */
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'])) {
            // for compatibility reason, we move all icons to the rootlevel
            // before calling the hooks
            foreach ($cells as $section => $actions) {
                foreach ($actions as $actionKey => $action) {
                    $cells[$actionKey] = $action;
                }
            }
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'] as $classData) {
                $hookObject = GeneralUtility::getUserObj($classData);
                if (!$hookObject instanceof RecordListHookInterface) {
                    throw new \UnexpectedValueException($classData . ' must implement interface ' . RecordListHookInterface::class,
                        1195567840);
                }
                $cells = $hookObject->makeControl($table, $row, $cells, $this);
            }
            // now sort icons again into primary and secondary sections
            // after all hooks are processed
            $hookCells = $cells;
            foreach ($hookCells as $key => $value) {
                if ($key === 'primary' || $key === 'secondary') {
                    continue;
                }
                $this->addActionToCellGroup($cells, $value, $key);
            }
        }
        $output = '<!-- CONTROL PANEL: ' . $table . ':' . $row['uid'] . ' -->';
        foreach ($cells as $classification => $actions) {
            $visibilityClass = ($classification !== 'primary' && !$module->MOD_SETTINGS['bigControlPanel'] ? 'collapsed' : 'expanded');
            if ($visibilityClass === 'collapsed') {
                $cellOutput = '';
                foreach ($actions as $action) {
                    $cellOutput .= $action;
                }
                $output .= ' <div class="btn-group">' .
                    '<span id="actions_' . $table . '_' . $row['uid'] . '" class="btn-group collapse collapse-horizontal width">' . $cellOutput . '</span>' .
                    '<a href="#actions_' . $table . '_' . $row['uid'] . '" class="btn btn-default collapsed" data-toggle="collapse" aria-expanded="false"><span class="t3-icon fa fa-ellipsis-h"></span></a>' .
                    '</div>';
            } else {
                $output .= ' <div class="btn-group" role="group">' . implode('', $actions) . '</div>';
            }
        }
        return $output;
    }

    /**
     * Check if the current record is locked by editlock. Pages are locked if their editlock flag is set,
     * records are if they are locked themselves or if the page they are on is locked (a page’s editlock
     * is transitive for its content elements).
     *
     * @param string $table
     * @param array $row
     * @param bool $editPermission
     * @return bool
     */
    protected function overlayEditLockPermissions($table, $row = [], $editPermission = true)
    {
        if ($editPermission && !$this->getBackendUserAuthentication()->isAdmin()) {
            // If no $row is submitted we only check for general edit lock of current page (except for table "pages")
            if (empty($row)) {
                return $table === 'pages' ? true : !$this->pageRow['editlock'];
            }
            if (($table === 'pages' && $row['editlock']) || ($table !== 'pages' && $this->pageRow['editlock'])) {
                $editPermission = false;
            } elseif (isset($GLOBALS['TCA'][$table]['ctrl']['editlock']) && $row[$GLOBALS['TCA'][$table]['ctrl']['editlock']]) {
                $editPermission = false;
            }
        }
        return $editPermission;
    }

    /**
     * add action into correct section
     *
     * @param array $cells
     * @param string $action
     * @param string $actionKey
     */
    public function addActionToCellGroup(&$cells, $action, $actionKey)
    {
        $cellsMap = [
            'primary'   => [
                'view',
                'edit',
                'hide',
                'delete',
                'stat',
            ],
            'secondary' => [
                'viewBig',
                'history',
                'perms',
                'new',
                'move',
                'moveUp',
                'moveDown',
                'moveLeft',
                'moveRight',
                'version',
            ],
        ];
        $classification = in_array($actionKey, $cellsMap['primary']) ? 'primary' : 'secondary';
        $cells[$classification][$actionKey] = $action;
        unset($cells[$actionKey]);
    }

    /**
     * Check if the table is readonly or editable
     * @param string $table
     * @return bool
     */
    public function isEditable($table)
    {
        return $GLOBALS['TCA'][$table]['ctrl']['readOnly'] || $this->editable;
    }

    /**
     * Returns TRUE if a link for creating new records should be displayed for $table
     *
     * @param string $table Table name
     * @return bool Returns TRUE if a link for creating new records should be displayed for $table
     * @see \TYPO3\CMS\Backend\Controller\NewRecordController::showNewRecLink
     */
    public function showNewRecLink($table)
    {
        // No deny/allow tables are set:
        if (empty($this->allowedNewTables) && empty($this->deniedNewTables)) {
            return true;
        }
        return !in_array($table, $this->deniedNewTables)
            && (empty($this->allowedNewTables) || in_array($table, $this->allowedNewTables));
    }

    /************************************
     *
     * CSV related functions
     *
     ************************************/

    /**
     * Check if the record represents the current backend user
     *
     * @param string $table
     * @param array $row
     * @return bool
     */
    protected function isRecordCurrentBackendUser($table, $row)
    {
        return $table === 'be_users' && (int)$row['uid'] === $this->getBackendUserAuthentication()->user['uid'];
    }

    /**
     * Gets the number of records referencing the record with the UID $uid in
     * the table $tableName.
     *
     * @param string $tableName
     * @param int $uid
     * @return int The number of references to record $uid in table
     */
    protected function getReferenceCount($tableName, $uid)
    {
        if (!isset($this->referenceCount[$tableName][$uid])) {
            $numberOfReferences = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable('sys_refindex')
                ->count(
                    '*',
                    'sys_refindex',
                    [
                        'ref_table' => $tableName,
                        'ref_uid'   => (int)$uid,
                        'deleted'   => 0,
                    ]
                );
            $this->referenceCount[$tableName][$uid] = $numberOfReferences;
        }
        return $this->referenceCount[$tableName][$uid];
    }

    /**
     * Creates the clipboard panel for a single record in the listing.
     *
     * @param string $table The table
     * @param mixed[] $row The record for which to make the clipboard panel.
     * @throws \UnexpectedValueException
     * @return string HTML table with the clipboard panel (unless disabled)
     */
    public function makeClip($table, $row)
    {
        // Return blank, if disabled:
        if (!$this->getModule()->MOD_SETTINGS['clipBoard']) {
            return '';
        }
        $cells = [];
        $cells['pasteAfter'] = ($cells['pasteInto'] = $this->spaceIcon);
        //enables to hide the copy, cut and paste icons for localized records - doesn't make much sense to perform these options for them
        $isL10nOverlay = $this->localizationView && $table !== 'pages_language_overlay' && $row[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']] != 0;
        // Return blank, if disabled:
        // Whether a numeric clipboard pad is active or the normal pad we will see different content of the panel:
        // For the "Normal" pad:
        if ($this->clipObj->current === 'normal') {
            // Show copy/cut icons:
            $isSel = (string)$this->clipObj->isSelected($table, $row['uid']);
            if ($isL10nOverlay || !$this->overlayEditLockPermissions($table, $row)) {
                $cells['copy'] = $this->spaceIcon;
                $cells['cut'] = $this->spaceIcon;
            } else {
                $copyIcon = $this->iconFactory->getIcon('actions-edit-copy', Icon::SIZE_SMALL);
                $cutIcon = $this->iconFactory->getIcon('actions-edit-cut', Icon::SIZE_SMALL);

                if ($isSel === 'copy') {
                    $copyIcon = $this->iconFactory->getIcon('actions-edit-copy-release', Icon::SIZE_SMALL);
                } elseif ($isSel === 'cut') {
                    $cutIcon = $this->iconFactory->getIcon('actions-edit-cut-release', Icon::SIZE_SMALL);
                }

                $cells['copy'] = '<a class="btn btn-default" href="#" onclick="'
                    . htmlspecialchars('return jumpSelf(' . GeneralUtility::quoteJSvalue($this->clipObj->selUrlDB($table,
                            $row['uid'], 1, ($isSel === 'copy'), ['returnUrl' => ''])) . ');')
                    . '" title="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.copy')) . '">'
                    . $copyIcon->render() . '</a>';

                // Check permission to cut page or content
                if ($table === 'pages') {
                    $localCalcPerms = $this->getBackendUserAuthentication()->calcPerms(BackendUtility::getRecord('pages',
                        $row['uid']));
                    $permsEdit = $localCalcPerms & Permission::PAGE_EDIT;
                } else {
                    $permsEdit = $this->calcPerms & Permission::CONTENT_EDIT;
                }
                $permsEdit = $this->overlayEditLockPermissions($table, $row, $permsEdit);

                // If the listed table is 'pages' we have to request the permission settings for each page:
                if ($table === 'pages') {
                    if ($permsEdit) {
                        $cells['cut'] = '<a class="btn btn-default" href="#" onclick="'
                            . htmlspecialchars('return jumpSelf(' . GeneralUtility::quoteJSvalue($this->clipObj->selUrlDB($table,
                                    $row['uid'], 0, ($isSel === 'cut'), ['returnUrl' => ''])) . ');')
                            . '" title="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.cut')) . '">'
                            . $cutIcon->render() . '</a>';
                    } else {
                        $cells['cut'] = $this->spaceIcon;
                    }
                } else {
                    if ($table !== 'pages' && $this->calcPerms & Permission::CONTENT_EDIT) {
                        $cells['cut'] = '<a class="btn btn-default" href="#" onclick="'
                            . htmlspecialchars('return jumpSelf(' . GeneralUtility::quoteJSvalue($this->clipObj->selUrlDB($table,
                                    $row['uid'], 0, ($isSel === 'cut'), ['returnUrl' => ''])) . ');')
                            . '" title="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.cut')) . '">'
                            . $cutIcon->render() . '</a>';
                    } else {
                        $cells['cut'] = $this->spaceIcon;
                    }
                }
            }
        } else {
            // For the numeric clipboard pads (showing checkboxes where one can select elements on/off)
            // Setting name of the element in ->CBnames array:
            $n = $table . '|' . $row['uid'];
            $this->CBnames[] = $n;
            // Check if the current element is selected and if so, prepare to set the checkbox as selected:
            $checked = $this->clipObj->isSelected($table, $row['uid']) ? 'checked="checked" ' : '';
            // If the "duplicateField" value is set then select all elements which are duplicates...
            if ($this->duplicateField && isset($row[$this->duplicateField])) {
                $checked = '';
                if (in_array($row[$this->duplicateField], $this->duplicateStack)) {
                    $checked = 'checked="checked" ';
                }
                $this->duplicateStack[] = $row[$this->duplicateField];
            }
            // Adding the checkbox to the panel:
            $cells['select'] = $isL10nOverlay
                ? $this->spaceIcon
                : '<input type="hidden" name="CBH[' . $n . ']" value="0" /><label class="btn btn-default btn-checkbox"><input type="checkbox"'
                . ' name="CBC[' . $n . ']" value="1" ' . $checked . '/><span class="t3-icon fa"></span></label>';
        }
        // Now, looking for selected elements from the current table:
        $elFromTable = $this->clipObj->elFromTable($table);
        if (!empty($elFromTable) && $GLOBALS['TCA'][$table]['ctrl']['sortby']) {
            // IF elements are found, they can be individually ordered and are not locked by editlock, then add a "paste after" icon:
            $cells['pasteAfter'] = $isL10nOverlay || !$this->overlayEditLockPermissions($table, $row)
                ? $this->spaceIcon
                : '<a class="btn btn-default t3js-modal-trigger"'
                . ' href="' . htmlspecialchars($this->clipObj->pasteUrl($table, -$row['uid'])) . '"'
                . ' title="' . htmlspecialchars($this->getLanguageService()->getLL('clip_pasteAfter')) . '"'
                . ' data-title="' . htmlspecialchars($this->getLanguageService()->getLL('clip_pasteAfter')) . '"'
                . ' data-content="' . htmlspecialchars($this->clipObj->confirmMsgText($table, $row, 'after',
                    $elFromTable)) . '"'
                . ' data-severity="warning">'
                . $this->iconFactory->getIcon('actions-document-paste-after', Icon::SIZE_SMALL)->render() . '</a>';
        }
        // Now, looking for elements in general:
        $elFromTable = $this->clipObj->elFromTable('');
        if ($table === 'pages' && !empty($elFromTable)) {
            $cells['pasteInto'] = '<a class="btn btn-default t3js-modal-trigger"'
                . ' href="' . htmlspecialchars($this->clipObj->pasteUrl('', $row['uid'])) . '"'
                . ' title="' . htmlspecialchars($this->getLanguageService()->getLL('clip_pasteInto')) . '"'
                . ' data-title="' . htmlspecialchars($this->getLanguageService()->getLL('clip_pasteInto')) . '"'
                . ' data-content="' . htmlspecialchars($this->clipObj->confirmMsgText($table, $row, 'into',
                    $elFromTable)) . '"'
                . ' data-severity="warning">'
                . $this->iconFactory->getIcon('actions-document-paste-into', Icon::SIZE_SMALL)->render() . '</a>';
        }
        /**
         * @hook makeClip: Allows to change clip-icons of records in list-module
         * @usage This hook method gets passed the current $cells array as third parameter.
         *        This array contains values for the clipboard icons generated for each record in Web>List.
         *        Each array entry is accessible by an index-key.
         *        The order of the icons is depending on the order of those array entries.
         */
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'] as $classData) {
                $hookObject = GeneralUtility::getUserObj($classData);
                if (!$hookObject instanceof RecordListHookInterface) {
                    throw new \UnexpectedValueException($classData . ' must implement interface ' . RecordListHookInterface::class,
                        1195567845);
                }
                $cells = $hookObject->makeClip($table, $row, $cells, $this);
            }
        }
        // Compile items into a DIV-element:
        return '<!-- CLIPBOARD PANEL: ' . $table . ':' . $row['uid'] . ' -->
			<div class="btn-group" role="group">' . implode('', $cells) . '</div>';
    }

    /**
     * Creates the localization panel
     *
     * @param string $table The table
     * @param mixed[] $row The record for which to make the localization panel.
     * @return string[] Array with key 0/1 with content for column 1 and 2
     */
    public function makeLocalizationPanel($table, $row)
    {
        $out = [
            0 => '',
            1 => '',
        ];
        // Reset translations
        $this->translations = [];

        // Language title and icon:
        $out[0] = $this->languageFlag($row[$GLOBALS['TCA'][$table]['ctrl']['languageField']]);
        // Guard clause so we can quickly return if a record is localized to "all languages"
        // It should only be possible to localize a record off default (uid 0)
        // Reasoning: The Parent is for ALL languages... why overlay with a localization?
        if ((int)$row[$GLOBALS['TCA'][$table]['ctrl']['languageField']] === -1) {
            return $out;
        }
        $translations = $this->translateTools->translationInfo($table, $row['uid'], 0, $row, $this->selFieldList);
        if (is_array($translations)) {
            $this->translations = $translations['translations'];
            // Traverse page translations and add icon for each language that does NOT yet exist:
            $lNew = '';
            foreach ($this->pageOverlays as $lUid_OnPage => $lsysRec) {
                if ($this->isEditable($table) && !isset($translations['translations'][$lUid_OnPage]) && $this->getBackendUserAuthentication()->checkLanguageAccess($lUid_OnPage)) {
                    $url = $this->listURL();
                    $href = BackendUtility::getLinkToDataHandlerAction(
                        '&cmd[' . $table . '][' . $row['uid'] . '][localize]=' . $lUid_OnPage,
                        $url . '&justLocalized=' . rawurlencode($table . ':' . $row['uid'] . ':' . $lUid_OnPage)
                    );
                    $language = BackendUtility::getRecord('sys_language', $lUid_OnPage, 'title');
                    if ($this->languageIconTitles[$lUid_OnPage]['flagIcon']) {
                        $lC = $this->iconFactory->getIcon($this->languageIconTitles[$lUid_OnPage]['flagIcon'],
                            Icon::SIZE_SMALL)->render();
                    } else {
                        $lC = $this->languageIconTitles[$lUid_OnPage]['title'];
                    }
                    $lC = '<a href="' . htmlspecialchars($href) . '" title="'
                        . htmlspecialchars($language['title']) . '" class="btn btn-default">' . $lC . '</a> ';
                    $lNew .= $lC;
                }
            }
            if ($lNew) {
                $out[1] .= $lNew;
            }
        } elseif ($row['l18n_parent']) {
            $out[0] = '&nbsp;&nbsp;&nbsp;&nbsp;' . $out[0];
        }
        return $out;
    }

    /**
     * Returns a table-row with the content from the fields in the input data array.
     * OBS: $this->fieldArray MUST be set! (represents the list of fields to display)
     *
     * @param int $h Is an int >=0 and denotes how tall an element is. Set to '0' makes a halv line, -1 = full line, set to 1 makes a 'join' and above makes 'line'
     * @param string $icon Is the <img>+<a> of the record. If not supplied the first 'join'-icon will be a 'line' instead
     * @param array $data Is the data array, record with the fields. Notice: These fields are (currently) NOT htmlspecialchar'ed before being wrapped in <td>-tags
     * @param string $rowParams Is insert in the <tr>-tags. Must carry a ' ' as first character
     * @param string $_ OBSOLETE - NOT USED ANYMORE. $lMargin is the leftMargin (int)
     * @param string $_2 OBSOLETE - NOT USED ANYMORE. Is the HTML <img>-tag for an alternative 'gfx/ol/line.gif'-icon (used in the top)
     * @param string $colType Defines the tag being used for the columns. Default is td.
     * @param int $level
     *
     * @return string HTML content for the table row
     */
    public function addElement($h, $icon, $data, $rowParams = '', $_ = '', $_2 = '', $colType = 'td', $level = 0)
    {
        if ($colType === 'pagination') {
            $colType = 'td';
            $pagination = true;
        } else {
            $colType = ($colType === 'th') ? 'th' : 'td';
            $pagination = false;
        }
        $noWrap = $this->no_noWrap ? '' : ' nowrap="nowrap"';
        // Start up:
        $parent = isset($data['parent']) ? (int)$data['parent'] : 0;
        $out = '
		<!-- Element, begin: -->
		<tr ' . $rowParams . ' data-uid="' . (int)$data['uid'] . '" data-l10nparent="' . $parent . '"' .
            ((int)$data['tx_gridelements_container'] > 0 ? ' data-grid-container="' . $data['tx_gridelements_container'] . '"' : '') .
            ((int)$data['_triggerContainer'] > 0 ? ' data-trigger-container="' . $data['_triggerContainer'] . '"' : '') . '>';
        if (count($data) > 1) {
            for ($i = 0; $i < $level; $i++) {
                $out .= '<' . $colType . '></' . $colType . '>';
            }

            if ($data['_EXPANDABLE_'] && (!$this->localizationView || !$parent)) {
                $sortField = GeneralUtility::_GP('sortField') ? GeneralUtility::_GP('sortField') . ':' . (int)GeneralUtility::_GP('sortRev') : '';
                $contentCollapseIcon = '';
                /**
                 * @hook contentCollapseIcon
                 * @date 2014-02-11
                 * @request Alexander Grein <alexander.grein@in2code.de>
                 */
                if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'])) {
                    foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'] as $classData) {
                        $hookObject = GeneralUtility::getUserObj($classData);
                        if (is_object($hookObject) && method_exists($hookObject, 'contentCollapseIcon')) {
                            $hookObject->contentCollapseIcon($data, $sortField, $level, $contentCollapseIcon, $this);
                        }
                    }
                }
                $out .= '<' . $colType . ' nowrap="nowrap" class="col-icon">' . $contentCollapseIcon . '</' . $colType . '>';
            } else {
                if ($colType === 'td') {
                    $out .= '<' . $colType . '></' . $colType . '>';
                }
            }

        }
        // Show icon and lines
        if ($this->showIcon) {
            $out .= '
			<' . $colType . ' nowrap="nowrap" class="col-icon"' . ($colType === 'th' ? ' colspan="' . ($this->maxDepth - $level) . '"' : '') . '>';
            if (!$h) {
                $out .= '&nbsp;';
            } else {
                for ($a = 0; $a < $h; $a++) {
                    if (!$a) {
                        if ($icon) {
                            $out .= $icon;
                        }
                    }
                }
            }
            $out .= '</' . $colType . '>
			';
        }
        // Init rendering.
        $colsp = '';
        $lastKey = '';
        $c = 0;
        $ccount = 0;
        // __label is used as the label key to circumvent problems with uid used as label (see #67756)
        // as it was introduced later on, check if it really exists before using it
        $fields = $this->fieldArray;
        if ($colType === 'td' && array_key_exists('__label', $data)) {
            $fields[0] = '__label';
        }
        // Traverse field array which contains the data to present:
        foreach ($fields as $vKey) {
            if (isset($data[$vKey])) {
                if ($lastKey) {
                    $cssClass = $this->addElement_tdCssClass[$lastKey];
                    if ($this->oddColumnsCssClass && $ccount % 2 == 0) {
                        $cssClass = implode(' ',
                            [$this->addElement_tdCssClass[$lastKey], $this->oddColumnsCssClass]);
                    }
                    $out .= '
						<' . $colType . $noWrap . ' class="' . $cssClass . '"' . $colsp
                        . $this->addElement_tdParams[$lastKey] . '>' . $data[$lastKey] . '</' . $colType . '>';
                }
                $lastKey = $vKey;
                $c = 1;
                $ccount++;
            } else {
                if (!$lastKey) {
                    $lastKey = $vKey;
                }
                $c++;
            }
            if (count($data) == 1) {
                $c++;
            }
            if ($pagination) {
                $colsp = ' colspan="' . ($this->totalColumnCount - 1) . '"';
            } else if ($c > 1) {
                $colsp = ' colspan="2"';
            } else if ($ccount === 1 && $colType === 'td') {
                $colsp = ' colspan="' . ($this->maxDepth - $level - 1) . '"';
            } else {
                $colsp = '';
            }
        }
        if ($lastKey) {
            $cssClass = $this->addElement_tdCssClass[$lastKey];
            if ($this->oddColumnsCssClass) {
                $cssClass = implode(' ', [$this->addElement_tdCssClass[$lastKey], $this->oddColumnsCssClass]);
            }
            $out .= '
				<' . $colType . $noWrap . ' class="' . $cssClass . '"' . $colsp
                . $this->addElement_tdParams[$lastKey] . '>' . $data[$lastKey] . '</' . $colType . '>';
        }
        // End row
        $out .= '
		</tr>';
        // Return row.
        return $out;
    }


    /**
     * Creates a page browser for tables with many records
     *
     * @param string $renderPart Distinguish between 'top' and 'bottom' part of the navigation (above or below the records)
     * @return string Navigation HTML
     */
    protected function renderListNavigation($renderPart = 'top')
    {
        $totalPages = ceil($this->totalItems / $this->iLimit);
        // Show page selector if not all records fit into one page
        if ($totalPages <= 1) {
            return '';
        }
        $content = '';
        $listURL = $this->listURL('', $this->table);
        // 1 = first page
        // 0 = first element
        $currentPage = floor($this->firstElementNumber / $this->iLimit) + 1;
        // Compile first, previous, next, last and refresh buttons
        if ($currentPage > 1) {
            $labelFirst = htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:first'));
            $labelPrevious = htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:previous'));
            $first = '<li><a href="' . $listURL . '&pointer=' . $this->getPointerForPage(1) . '" title="' . $labelFirst . '">'
                . $this->iconFactory->getIcon('actions-view-paging-first', Icon::SIZE_SMALL)->render() . '</a></li>';
            $previous = '<li><a href="' . $listURL . '&pointer=' . $this->getPointerForPage($currentPage - 1) . '" title="' . $labelPrevious . '">'
                . $this->iconFactory->getIcon('actions-view-paging-previous', Icon::SIZE_SMALL)->render() . '</a></li>';
        } else {
            $first = '<li class="disabled"><span>' . $this->iconFactory->getIcon('actions-view-paging-first',
                    Icon::SIZE_SMALL)->render() . '</span></li>';
            $previous = '<li class="disabled"><span>' . $this->iconFactory->getIcon('actions-view-paging-previous',
                    Icon::SIZE_SMALL)->render() . '</span></li>';
        }
        if ($currentPage < $totalPages) {
            $labelNext = htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:next'));
            $labelLast = htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:last'));
            $next = '<li><a href="' . $listURL . '&pointer=' . $this->getPointerForPage($currentPage + 1) . '" title="' . $labelNext . '">'
                . $this->iconFactory->getIcon('actions-view-paging-next', Icon::SIZE_SMALL)->render() . '</a></li>';
            $last = '<li><a href="' . $listURL . '&pointer=' . $this->getPointerForPage($totalPages) . '" title="' . $labelLast . '">'
                . $this->iconFactory->getIcon('actions-view-paging-last', Icon::SIZE_SMALL)->render() . '</a></li>';
        } else {
            $next = '<li class="disabled"><span>' . $this->iconFactory->getIcon('actions-view-paging-next',
                    Icon::SIZE_SMALL)->render() . '</span></li>';
            $last = '<li class="disabled"><span>' . $this->iconFactory->getIcon('actions-view-paging-last',
                    Icon::SIZE_SMALL)->render() . '</span></li>';
        }
        $reload = '<li><a href="#" onclick="document.dblistForm.action=' . GeneralUtility::quoteJSvalue($listURL
                . '&pointer=') . '+calculatePointer(document.getElementById(' . GeneralUtility::quoteJSvalue('jumpPage-' . $renderPart)
            . ').value); document.dblistForm.submit(); return true;" title="'
            . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:reload')) . '">'
            . $this->iconFactory->getIcon('actions-refresh', Icon::SIZE_SMALL)->render() . '</a></li>';
        if ($renderPart === 'top') {
            // Add js to traverse a page select input to a pointer value
            $content = '
<script type="text/javascript">
/*<![CDATA[*/
	function calculatePointer(page) {
		if (page > ' . $totalPages . ') {
			page = ' . $totalPages . ';
		}
		if (page < 1) {
			page = 1;
		}
		return (page - 1) * ' . $this->iLimit . ';
	}
/*]]>*/
</script>
';
        }
        $pageNumberInput = '
			<input type="number" min="1" max="' . $totalPages . '" value="' . $currentPage . '" size="3" class="form-control input-sm paginator-input" id="jumpPage-' . $renderPart . '" name="jumpPage-'
            . $renderPart . '" onkeyup="if (event.keyCode == 13) { document.dblistForm.action=' . GeneralUtility::quoteJSvalue($listURL
                . '&pointer=') . '+calculatePointer(this.value); document.dblistForm.submit(); } return true;" />
			';
        $pageIndicatorText = sprintf(
            $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:pageIndicator'),
            $pageNumberInput,
            $totalPages
        );
        $pageIndicator = '<li><span>' . $pageIndicatorText . '</span></li>';
        if ($this->totalItems > $this->firstElementNumber + $this->iLimit) {
            $lastElementNumber = $this->firstElementNumber + $this->iLimit;
        } else {
            $lastElementNumber = $this->totalItems;
        }
        $rangeIndicator = '<li><span>' . sprintf($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:rangeIndicator'),
                ($this->firstElementNumber + 1), $lastElementNumber) . '</span></li>';

        $titleColumn = $this->fieldArray[0];
        $data = [
            $titleColumn => $content . '
				<nav class="pagination-wrap">
					<ul class="pagination pagination-block">
						' . $first . '
						' . $previous . '
						' . $rangeIndicator . '
						' . $pageIndicator . '
						' . $next . '
						' . $last . '
						' . $reload . '
					</ul>
				</nav>
			'
        ];
        return $this->addElement(1, '', $data, '', '', '', 'pagination');
    }

    /**
     * Get pointer for first element on the page
     *
     * @param int $page Page number starting with 1
     * @return int Pointer to first element on the page (starting with 0)
     */
    protected function getPointerForPage($page)
    {
        return ($page - 1) * $this->iLimit;
    }

    /**
     * Rendering the header row for a table
     *
     * @param string $table Table name
     * @param int[] $currentIdList Array of the currently displayed uids of the table
     * @throws \UnexpectedValueException
     * @return string Header table row
     * @access private
     * @see getTable()
     */
    public function renderListHeader($table, $currentIdList)
    {
        $lang = $this->getLanguageService();
        // Init:
        $theData = [];
        $icon = '';
        // Traverse the fields:
        foreach ($this->fieldArray as $fCol) {
            // Calculate users permissions to edit records in the table:
            $permsEdit = $this->calcPerms & ($table === 'pages' ? 2 : 16) && $this->overlayEditLockPermissions($table);
            switch ((string)$fCol) {
                case '_PATH_':
                    // Path
                    $theData[$fCol] = '<i>[' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels._PATH_')) . ']</i>';
                    break;
                case '_REF_':
                    // References
                    $theData[$fCol] = '<i>[' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_file_list.xlf:c__REF_')) . ']</i>';
                    break;
                case '_LOCALIZATION_':
                    // Path
                    $theData[$fCol] = '<i>[' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels._LOCALIZATION_')) . ']</i>';
                    break;
                case '_LOCALIZATION_b':
                    // Path
                    $theData[$fCol] = htmlspecialchars($lang->getLL('Localize'));
                    break;
                case '_CLIPBOARD_':
                    if (!$this->getModule()->MOD_SETTINGS['clipBoard']) {
                        break;
                    }
                    // Clipboard:
                    $cells = [];
                    // If there are elements on the clipboard for this table, and the parent page is not locked by editlock
                    // then display the "paste into" icon:
                    $elFromTable = $this->clipObj->elFromTable($table);
                    if (!empty($elFromTable) && $this->overlayEditLockPermissions($table)) {
                        $href = htmlspecialchars($this->clipObj->pasteUrl($table, $this->id));
                        $confirmMessage = $this->clipObj->confirmMsgText('pages', $this->pageRow, 'into', $elFromTable);
                        $cells['pasteAfter'] = '<a class="btn btn-default t3js-modal-trigger"'
                            . ' href="' . $href . '"'
                            . ' title="' . htmlspecialchars($lang->getLL('clip_paste')) . '"'
                            . ' data-title="' . htmlspecialchars($lang->getLL('clip_paste')) . '"'
                            . ' data-content="' . htmlspecialchars($confirmMessage) . '"'
                            . ' data-severity="warning">'
                            . $this->iconFactory->getIcon('actions-document-paste-into', Icon::SIZE_SMALL)->render()
                            . '</a>';
                    }
                    // If the numeric clipboard pads are enabled, display the control icons for that:
                    if ($this->clipObj->current !== 'normal') {
                        // The "select" link:
                        $spriteIcon = $this->iconFactory->getIcon('actions-edit-copy', Icon::SIZE_SMALL)->render();
                        $cells['copyMarked'] = $this->linkClipboardHeaderIcon($spriteIcon, $table, 'setCB', '',
                            $lang->getLL('clip_selectMarked'));
                        // The "edit marked" link:
                        $editIdList = implode(',', $currentIdList);
                        $editIdList = '\'+editList(' . GeneralUtility::quoteJSvalue($table) . ',' . GeneralUtility::quoteJSvalue($editIdList) . ')+\'';
                        $params = 'edit[' . $table . '][' . $editIdList . ']=edit';
                        $onClick = BackendUtility::editOnClick('', '', -1);
                        $onClickArray = explode('?', $onClick, 2);
                        $lastElement = array_pop($onClickArray);
                        $onClickArray[] = $params . '&' . $lastElement;
                        $onClick = implode('?', $onClickArray);
                        $cells['edit'] = '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars($onClick) . '" title="'
                            . htmlspecialchars($lang->getLL('clip_editMarked')) . '">'
                            . $this->iconFactory->getIcon('actions-document-open', Icon::SIZE_SMALL)->render() . '</a>';
                        // The "Delete marked" link:
                        $cells['delete'] = $this->linkClipboardHeaderIcon(
                            $this->iconFactory->getIcon('actions-edit-delete', Icon::SIZE_SMALL)->render(),
                            $table,
                            'delete',
                            sprintf($lang->getLL('clip_deleteMarkedWarning'),
                                $lang->sL($GLOBALS['TCA'][$table]['ctrl']['title'])),
                            $lang->getLL('clip_deleteMarked')
                        );
                        // The "Select all" link:
                        $onClick = htmlspecialchars(('checkOffCB(' . GeneralUtility::quoteJSvalue(implode(',',
                                $this->CBnames)) . ', this); return false;'));
                        $cells['markAll'] = '<a class="btn btn-default" rel="" href="#" onclick="' . $onClick . '" title="'
                            . htmlspecialchars($lang->getLL('clip_markRecords')) . '">'
                            . $this->iconFactory->getIcon('actions-document-select',
                                Icon::SIZE_SMALL)->render() . '</a>';
                    } else {
                        $cells['empty'] = '';
                    }
                    /**
                     * @hook renderListHeaderActions: Allows to change the clipboard icons of the Web>List table headers
                     * @usage Above each listed table in Web>List a header row is shown.
                     *        This hook allows to modify the icons responsible for the clipboard functions
                     *        (shown above the clipboard checkboxes when a clipboard other than "Normal" is selected),
                     *        or other "Action" functions which perform operations on the listed records.
                     */
                    if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'])) {
                        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'] as $classData) {
                            $hookObject = GeneralUtility::getUserObj($classData);
                            if (!$hookObject instanceof RecordListHookInterface) {
                                throw new \UnexpectedValueException($classData . ' must implement interface ' . RecordListHookInterface::class,
                                    1195567850);
                            }
                            $cells = $hookObject->renderListHeaderActions($table, $currentIdList, $cells, $this);
                        }
                    }
                    $theData[$fCol] = '';
                    if (isset($cells['edit']) && isset($cells['delete'])) {
                        $theData[$fCol] .= '<div class="btn-group" role="group">' . $cells['edit'] . $cells['delete'] . '</div>';
                        unset($cells['edit'], $cells['delete']);
                    }
                    $theData[$fCol] .= '<div class="btn-group" role="group">' . implode('', $cells) . '</div>';
                    break;
                case '_CONTROL_':
                    // Control panel:
                    if ($this->isEditable($table)) {
                        // If new records can be created on this page, add links:
                        $permsAdditional = $table === 'pages' ? 8 : 16;
                        if ($table === 'tt_content') {
                            $expandTitle = $lang->sL('LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xlf:list.expandAllElements');
                            $collapseTitle = $lang->sL('LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xlf:list.collapseAllElements');
                            $containerIds = implode(',', array_flip(array_flip($this->currentContainerIdList)));
                            $icon = '<a
                class="btn btn-default t3js-toggle-gridelements-all" href="#t3-gridelements-collapse-all" id="t3-gridelements-collapse-all"
                title="' . $collapseTitle . '" data-container-ids="' . $containerIds . '">' . $this->iconFactory->getIcon('actions-view-list-collapse',
                                    'small')->render() . '</a><a
                class="btn btn-default t3js-toggle-gridelements-all" href="#t3-gridelements-expand-all" id="t3-gridelements-expand-all"
                title="' . $expandTitle . '" data-container-ids="' . $containerIds . '">' . $this->iconFactory->getIcon('actions-view-list-expand',
                                    'small')->render() . '</a>';

                        }
                        if ($this->calcPerms & $permsAdditional && $this->showNewRecLink($table)) {
                            $spriteIcon = $table === 'pages'
                                ? $this->iconFactory->getIcon('actions-page-new', Icon::SIZE_SMALL)
                                : $this->iconFactory->getIcon('actions-add', Icon::SIZE_SMALL);
                            if ($table === 'tt_content' && $this->newWizards) {
                                // If mod.newContentElementWizard.override is set, use that extension's create new content wizard instead:
                                $tmpTSc = BackendUtility::getModTSconfig($this->pageinfo['uid'], 'mod');
                                $newContentElementWizard = isset($tmpTSc['properties']['newContentElementWizard.']['override'])
                                    ? $tmpTSc['properties']['newContentElementWizard.']['override']
                                    : 'new_content_element';
                                $newContentWizScriptPath = BackendUtility::getModuleUrl($newContentElementWizard,
                                    ['id' => $this->id]);

                                $onClick = 'return jumpExt(' . GeneralUtility::quoteJSvalue($newContentWizScriptPath) . ');';
                                $icon = '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars($onClick) . '" title="'
                                    . htmlspecialchars($lang->getLL('new')) . '">' . $spriteIcon->render() . '</a>';
                            } elseif ($table === 'pages' && $this->newWizards) {
                                $parameters = [
                                    'id'        => $this->id,
                                    'pagesOnly' => 1,
                                    'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI'),
                                ];
                                $href = BackendUtility::getModuleUrl('db_new', $parameters);
                                $icon = '<a class="btn btn-default" href="' . htmlspecialchars($href) . '" title="' . htmlspecialchars($lang->getLL('new')) . '">'
                                    . $spriteIcon->render() . '</a>';
                            } else {
                                $params = '&edit[' . $table . '][' . $this->id . ']=new';
                                if ($table === 'pages_language_overlay') {
                                    $params .= '&overrideVals[pages_language_overlay][doktype]=' . (int)$this->pageRow['doktype'];
                                }
                                $icon = '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick($params,
                                        '', -1))
                                    . '" title="' . htmlspecialchars($lang->getLL('new')) . '">' . $spriteIcon->render() . '</a>';
                            }
                        }
                        // If the table can be edited, add link for editing ALL SHOWN fields for all listed records:
                        if ($permsEdit && $this->table && is_array($currentIdList)) {
                            $editIdList = implode(',', $currentIdList);
                            if ($this->clipNumPane()) {
                                $editIdList = '\'+editList(' . GeneralUtility::quoteJSvalue($table) . ',' . GeneralUtility::quoteJSvalue($editIdList) . ')+\'';
                            }
                            $params = 'edit[' . $table . '][' . $editIdList . ']=edit&columnsOnly=' . implode(',',
                                    $this->fieldArray);
                            // we need to build this uri differently, otherwise GeneralUtility::quoteJSvalue messes up the edit list function
                            $onClick = BackendUtility::editOnClick('', '', -1);
                            $onClickArray = explode('?', $onClick, 2);
                            $lastElement = array_pop($onClickArray);
                            $onClickArray[] = $params . '&' . $lastElement;
                            $onClick = implode('?', $onClickArray);
                            $icon .= '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars($onClick)
                                . '" title="' . htmlspecialchars($lang->getLL('editShownColumns')) . '">'
                                . $this->iconFactory->getIcon('actions-document-open',
                                    Icon::SIZE_SMALL)->render() . '</a>';
                            $icon = '<div class="btn-group" role="group">' . $icon . '</div>';
                        }
                        // Add an empty entry, so column count fits again after moving this into $icon
                        $theData[$fCol] = '&nbsp;';
                    }
                    break;
                default:
                    // Regular fields header:
                    $theData[$fCol] = '';

                    // Check if $fCol is really a field and get the label and remove the colons
                    // at the end
                    $sortLabel = BackendUtility::getItemLabel($table, $fCol);
                    if ($sortLabel !== null) {
                        $sortLabel = htmlspecialchars($lang->sL($sortLabel));
                        $sortLabel = rtrim(trim($sortLabel), ':');
                    } else {
                        // No TCA field, only output the $fCol variable with square brackets []
                        $sortLabel = htmlspecialchars($fCol);
                        $sortLabel = '<i>[' . rtrim(trim($sortLabel), ':') . ']</i>';
                    }

                    if ($this->table && is_array($currentIdList)) {
                        // If the numeric clipboard pads are selected, show duplicate sorting link:
                        if ($this->clipNumPane()) {
                            $theData[$fCol] .= '<a class="btn btn-default" href="' . htmlspecialchars($this->listURL('',
                                        '-1') . '&duplicateField=' . $fCol)
                                . '" title="' . htmlspecialchars($lang->getLL('clip_duplicates')) . '">'
                                . $this->iconFactory->getIcon('actions-document-duplicates-select',
                                    Icon::SIZE_SMALL)->render() . '</a>';
                        }
                        // If the table can be edited, add link for editing THIS field for all
                        // listed records:
                        if ($this->isEditable($table) && $permsEdit && $GLOBALS['TCA'][$table]['columns'][$fCol]) {
                            $editIdList = implode(',', $currentIdList);
                            if ($this->clipNumPane()) {
                                $editIdList = '\'+editList(' . GeneralUtility::quoteJSvalue($table) . ',' . GeneralUtility::quoteJSvalue($editIdList) . ')+\'';
                            }
                            $params = 'edit[' . $table . '][' . $editIdList . ']=edit&columnsOnly=' . $fCol;
                            // we need to build this uri differently, otherwise GeneralUtility::quoteJSvalue messes up the edit list function
                            $onClick = BackendUtility::editOnClick('', '', -1);
                            $onClickArray = explode('?', $onClick, 2);
                            $lastElement = array_pop($onClickArray);
                            $onClickArray[] = $params . '&' . $lastElement;
                            $onClick = implode('?', $onClickArray);
                            $iTitle = sprintf($lang->getLL('editThisColumn'), $sortLabel);
                            $theData[$fCol] .= '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars($onClick)
                                . '" title="' . htmlspecialchars($iTitle) . '">'
                                . $this->iconFactory->getIcon('actions-document-open',
                                    Icon::SIZE_SMALL)->render() . '</a>';
                        }
                        if (strlen($theData[$fCol]) > 0) {
                            $theData[$fCol] = '<div class="btn-group" role="group">' . $theData[$fCol] . '</div> ';
                        }
                    }
                    $theData[$fCol] .= $this->addSortLink($sortLabel, $fCol, $table);
            }
        }
        $this->totalColumnCount = 10 + count($theData);
        $headerOutput = '<colgroup>';
        for ($i = -10; $i < count($theData); $i++) {
            if ($i < -1) {
                $headerOutput .= '<col class="col-icon" width="40" />';
            } else {
                $headerOutput .= '<col width="auto" />';
            }
        }
        $headerOutput .= '</colgroup>';
        /**
         * @hook renderListHeader: Allows to change the contents of columns/cells of the Web>List table headers
         * @usage Above each listed table in Web>List a header row is shown.
         *        Containing the labels of all shown fields and additional icons to create new records for this
         *        table or perform special clipboard tasks like mark and copy all listed records to clipboard, etc.
         */
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'] as $classData) {
                $hookObject = GeneralUtility::getUserObj($classData);
                if (!$hookObject instanceof RecordListHookInterface) {
                    throw new \UnexpectedValueException($classData . ' must implement interface ' . RecordListHookInterface::class,
                        1195567855);
                }
                $theData = $hookObject->renderListHeader($table, $currentIdList, $theData, $this);
            }
        }

        // Create and return header table row:
        return $headerOutput . '<thead>' . $this->addElement(1, $icon, $theData, '', '', '', 'th') . '</thead>';
    }

    /**
     * Creates a link around $string. The link contains an onclick action
     * which submits the script with some clipboard action.
     * Currently, this is used for setting elements / delete elements.
     *
     * @param string $string The HTML content to link (image/text)
     * @param string $table Table name
     * @param string $cmd Clipboard command (eg. "setCB" or "delete")
     * @param string $warning Warning text, if any ("delete" uses this for confirmation
     * @param string $title title attribute for the anchor
     * @return string <a> tag wrapped link.
     */
    public function linkClipboardHeaderIcon($string, $table, $cmd, $warning = '', $title = '')
    {
        $jsCode = 'document.dblistForm.cmd.value=' . GeneralUtility::quoteJSvalue($cmd)
            . ';document.dblistForm.cmd_table.value='
            . GeneralUtility::quoteJSvalue($table)
            . ';document.dblistForm.submit();';

        $attributes = [];
        if ($title !== '') {
            $attributes['title'] = $title;
        }
        if ($warning) {
            $attributes['class'] = 'btn btn-default t3js-modal-trigger';
            $attributes['data-href'] = 'javascript:' . $jsCode;
            $attributes['data-severity'] = 'warning';
            $attributes['data-title'] = $title;
            $attributes['data-content'] = $warning;
        } else {
            $attributes['class'] = 'btn btn-default';
            $attributes['onclick'] = $jsCode . 'return false;';
        }

        $attributesString = '';
        foreach ($attributes as $key => $value) {
            $attributesString .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
        }
        return '<a href="#" ' . $attributesString . '>' . $string . '</a>';
    }

    /**
     * Returns TRUE if a numeric clipboard pad is selected/active
     *
     * @return bool
     */
    public function clipNumPane()
    {
        return in_array('_CLIPBOARD_', $this->fieldArray) && $this->clipObj->current !== 'normal';
    }

    /**
     * Creates a sort-by link on the input string ($code).
     * It will automatically detect if sorting should be ascending or descending depending on $this->sortRev.
     * Also some fields will not be possible to sort (including if single-table-view is disabled).
     *
     * @param string $code The string to link (text)
     * @param string $field The fieldname represented by the title ($code)
     * @param string $table Table name
     * @return string Linked $code variable
     */
    public function addSortLink($code, $field, $table)
    {
        // Certain circumstances just return string right away (no links):
        if ($field === '_CONTROL_' || $field === '_LOCALIZATION_' || $field === '_CLIPBOARD_' || $field === '_REF_' || $this->disableSingleTableView) {
            return $code;
        }
        // If "_PATH_" (showing record path) is selected, force sorting by pid field (will at least group the records!)
        if ($field === '_PATH_') {
            $field = 'pid';
        }
        //	 Create the sort link:
        $sortUrl = $this->listURL('', '-1', 'sortField,sortRev,table,firstElementNumber') . '&table=' . $table
            . '&sortField=' . $field . '&sortRev=' . ($this->sortRev || $this->sortField != $field ? 0 : 1);
        $sortArrow = $this->sortField === $field
            ? $this->iconFactory->getIcon('status-status-sorting-' . ($this->sortRev ? 'desc' : 'asc'),
                Icon::SIZE_SMALL)->render()
            : '';
        // Return linked field:
        return '<a href="' . htmlspecialchars($sortUrl) . '">' . $code . $sortArrow . '</a>';
    }

    /**
     * Compiles the internal csvLines array to a csv-string and outputs it to the browser.
     * This function exits!
     *
     * @param string $prefix Filename prefix:
     */
    public function outputCSV($prefix)
    {
        // Setting filename:
        $filename = $prefix . '_' . date('dmy-Hi') . '.csv';
        // Creating output header:
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . $filename);
        // Cache-Control header is needed here to solve an issue with browser IE and
        // versions lower than 9. See for more information: http://support.microsoft.com/kb/323308
        header("Cache-Control: ''");
        // Printing the content of the CSV lines:
        echo implode(CRLF, $this->csvLines);
        // Exits:
        die;
    }

    /**
     * pasteUrl of the element (database and file)
     * For the meaning of $table and $uid, please read from ->makePasteCmdArray!!!
     * The URL will point to tce_file or tce_db depending in $table
     *
     * @param string $table Tablename (_FILE for files)
     * @param mixed $uid "destination": can be positive or negative indicating how the paste is done (paste into / paste after)
     * @param bool $setRedirect If set, then the redirect URL will point back to the current script, but with CB reset.
     * @param array|NULL $update Additional key/value pairs which should get set in the moved/copied record (via DataHandler)
     *
     * @return string
     */
    public function pasteUrl($table, $uid, $setRedirect = true, array $update = null)
    {
        $formProtection = FormProtectionFactory::get();
        return ($table === '_FILE' ? BackendUtility::getModuleUrl('tce_file',
                []) : BackendUtility::getModuleUrl('tce_db', []))
            . ($setRedirect ? '&redirect=' . rawurlencode(GeneralUtility::linkThisScript(['CB' => ''])) : '')
            . '&vC=' . $this->getBackendUserAuthentication()->veriCode() . '&prErr=1&uPT=1' . '&CB[paste]='
            . rawurlencode($table . '|' . $uid) . '&CB[pad]=' . $this->clipObj->current
            . (is_array($update) ? GeneralUtility::implodeArrayForUrl('CB[update]', $update) : '')
            . '&formToken=' . $formProtection->generateToken('tceAction');
    }

    /**
     * Returns the select-url for database elements
     *
     * @param string $table Table name
     * @param int $uid Uid of record
     * @param bool $copy If set, copymode will be enabled
     * @param bool $deselect If set, the link will deselect, otherwise select.
     * @param array $baseArray The base array of GET vars to be sent in addition. Notice that current GET vars WILL automatically be included.
     *
     * @return string URL linking to the current script but with the CB array set to select the element with table/uid
     */
    public function selUrlDB($table, $uid, $copy = false, $deselect = false, $baseArray = [])
    {
        $CB = ['el' => [rawurlencode($table . '|' . $uid) => $deselect ? 0 : 1]];
        if ($copy) {
            $CB['setCopyMode'] = 1;
        }
        $baseArray['CB'] = $CB;

        return GeneralUtility::linkThisScript($baseArray);
    }

    /**
     * Creates a checkbox list for selecting fields to display from a table:
     *
     * @param string $table Table name
     * @param bool $formFields If TRUE, form-fields will be wrapped around the table.
     * @return string HTML table with the selector check box (name: displayFields['.$table.'][])
     */
    public function fieldSelectBox($table, $formFields = true)
    {
        $lang = $this->getLanguageService();
        // Init:
        $formElements = ['', ''];
        if ($formFields) {
            $formElements = [
                '<form action="' . htmlspecialchars($this->listURL()) . '" method="post" name="fieldSelectBox">',
                '</form>',
            ];
        }
        // Load already selected fields, if any:
        $setFields = is_array($this->setFields[$table]) ? $this->setFields[$table] : [];
        // Request fields from table:
        $fields = $this->makeFieldList($table, false, true);
        // Add pseudo "control" fields
        $fields[] = '_PATH_';
        $fields[] = '_REF_';
        $fields[] = '_LOCALIZATION_';
        $fields[] = '_CONTROL_';
        $fields[] = '_CLIPBOARD_';
        // Create a checkbox for each field:
        $checkboxes = [];
        $checkAllChecked = true;
        $tsConfig = BackendUtility::getPagesTSconfig($this->id);
        $tsConfigOfTable = is_array($tsConfig['TCEFORM.'][$table . '.']) ? $tsConfig['TCEFORM.'][$table . '.'] : null;
        foreach ($fields as $fieldName) {
            // Hide field if hidden
            if ($tsConfigOfTable && is_array($tsConfigOfTable[$fieldName . '.']) && isset($tsConfigOfTable[$fieldName . '.']['disabled']) && (int)$tsConfigOfTable[$fieldName . '.']['disabled'] === 1) {
                continue;
            }
            // Determine, if checkbox should be checked
            if (in_array($fieldName, $setFields, true) || $fieldName === $this->fieldArray[0]) {
                $checked = ' checked="checked"';
            } else {
                $checkAllChecked = false;
                $checked = '';
            }
            // Field label
            $fieldLabel = is_array($GLOBALS['TCA'][$table]['columns'][$fieldName])
                ? rtrim($lang->sL($GLOBALS['TCA'][$table]['columns'][$fieldName]['label']), ':')
                : '';
            $checkboxes[] = '<tr><td class="col-checkbox"><input type="checkbox" id="check-' . $fieldName . '" name="displayFields['
                . $table . '][]" value="' . $fieldName . '" ' . $checked
                . ($fieldName === $this->fieldArray[0] ? ' disabled="disabled"' : '') . '></td><td class="col-title">'
                . '<label class="label-block" for="check-' . $fieldName . '">' . htmlspecialchars($fieldLabel) . ' <span class="text-muted text-monospace">[' . htmlspecialchars($fieldName) . ']</span></label></td></tr>';
        }
        // Table with the field selector::
        $content = $formElements[0] . '
			<input type="hidden" name="displayFields[' . $table . '][]" value="">
			<div class="table-fit table-scrollable">
				<table border="0" cellpadding="0" cellspacing="0" class="table table-transparent table-hover">
					<thead>
						<tr>
							<th class="col-checkbox checkbox" colspan="2">
								<label><input type="checkbox" class="checkbox checkAll" ' . ($checkAllChecked ? ' checked="checked"' : '') . '>
								' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.toggleall')) . '</label>
							</th>
						</tr>
					</thead>
					<tbody>
					' . implode('', $checkboxes) . '
					</tbody>
				</table>
			</div>
			<input type="submit" name="search" class="btn btn-default" value="'
            . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.setFields')) . '"/>
			' . $formElements[1];
        return '<div class="fieldSelectBox">' . $content . '</div>';
    }

    /**
     * Creates the "&returnUrl" parameter for links - this is used when the script links
     * to other scripts and passes its own URL with the link so other scripts can return to the listing again.
     * Uses REQUEST_URI as value.
     *
     * @return string
     */
    public function makeReturnUrl()
    {
        return '&returnUrl=' . rawurlencode(GeneralUtility::getIndpEnv('REQUEST_URI'));
    }

    /**
     * @param bool $isEditable
     */
    public function setIsEditable($isEditable)
    {
        $this->editable = $isEditable;
    }

    /**
     * @return \int[]
     */
    public function getExpandedGridelements()
    {
        return $this->expandedGridelements;
    }
}
