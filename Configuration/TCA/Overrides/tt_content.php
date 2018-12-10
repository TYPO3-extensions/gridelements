<?php

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('gridelements', 'Configuration/TypoScript/', 'Gridelements');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('gridelements', 'Configuration/TypoScript/DataProcessing', 'Gridelements w/DataProcessing');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin([
    'LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tt_content.CType_pi1',
    'gridelements_pi1',
    'gridelements-default'
], 'CType', 'gridelements');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
    'tt_content',
    [
        'tx_gridelements_backend_layout' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tt_content.tx_gridelements_backend_layout',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'itemsProcFunc' => 'GridElementsTeam\Gridelements\Backend\TtContent->layoutItemsProcFunc',
                'fieldWizard' => [
                    'selectIcons' => [
                        'disabled' => ''
                    ]
                ],
                'size' => 1,
                'maxitems' => 1,
                'default' => 0
            ],
            'onChange' => 'reload'
        ],
        'tx_gridelements_children' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tt_content.tx_gridelements_children',
            'config' => [
                'type' => 'inline',
                'appearance' => [
                    'levelLinksPosition' => 'top',
                    'showPossibleLocalizationRecords' => true,
                    'showRemovedLocalizationRecords' => true,
                    'showAllLocalizationLink' => true,
                    'showSynchronizationLink' => true,
                    'enabledControls' => [
                        'info' => true,
                        'new' => false,
                        'dragdrop' => false,
                        'sort' => false,
                        'hide' => true,
                        'delete' => true,
                        'localize' => true,
                    ]
                ],
                'inline' => [
                    'inlineNewButtonStyle' => 'display: inline-block;',
                ],
                'foreign_table' => 'tt_content',
                'foreign_field' => 'tx_gridelements_container',
                'overrideChildTca' => [
                    'columns' => [
                        'colPos' => [
                            'config' => [
                                'default' => -1
                            ]
                        ]
                    ]
                ],
                'foreign_sortby' => 'sorting',
                'size' => 5,
                'autoSizeMax' => 20,
            ]
        ],
        'tx_gridelements_container' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tt_content.tx_gridelements_container',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        '',
                        0
                    ],
                ],
                'default' => 0,
                'foreign_table' => 'tt_content',
                'foreign_table_where' => "AND (tt_content.sys_language_uid = ###REC_FIELD_sys_language_uid### OR tt_content.sys_language_uid = -1) AND tt_content.pid=###CURRENT_PID### AND tt_content.CType='gridelements_pi1' AND (tt_content.uid != ###THIS_UID###) AND (tt_content.tx_gridelements_container != ###THIS_UID### OR tt_content.tx_gridelements_container=0) ORDER BY tt_content.header, tt_content.uid",
                'dontRemapTablesOnCopy' => 'tt_content',
                'itemsProcFunc' => 'GridElementsTeam\Gridelements\Backend\TtContent->containerItemsProcFunc',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
            ],
            'onChange' => 'reload'
        ],
        'tx_gridelements_columns' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tt_content.tx_gridelements_columns',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'itemsProcFunc' => 'GridElementsTeam\Gridelements\Backend\TtContent->columnsItemsProcFunc',
                'size' => 1,
                'maxitems' => 1,
                'default' => 0,
            ],
            'onChange' => 'reload'
        ]
    ]
);

$GLOBALS['TCA']['tt_content']['ctrl']['useColumnsForDefaultValues'] .= ',tx_gridelements_container,tx_gridelements_columns';
$GLOBALS['TCA']['tt_content']['ctrl']['shadowColumnsForNewPlaceholders'] .= ',tx_gridelements_container,tx_gridelements_columns';
$GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes']['gridelements_pi1'] = 'gridelements-default';

$GLOBALS['TCA']['tt_content']['columns']['colPos']['config']['itemsProcFunc'] = 'GridElementsTeam\Gridelements\Backend\ItemsProcFuncs\ColPosList->itemsProcFunc';
$GLOBALS['TCA']['tt_content']['columns']['colPos']['onChange'] = 'reload';
$GLOBALS['TCA']['tt_content']['columns']['CType']['config']['itemsProcFunc'] = 'GridElementsTeam\Gridelements\Backend\ItemsProcFuncs\CTypeList->itemsProcFunc';
$GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['itemsProcFunc'] = 'GridElementsTeam\Gridelements\Backend\ItemsProcFuncs\ListTypeList->itemsProcFunc';
$GLOBALS['TCA']['tt_content']['columns']['sys_language_uid']['config']['itemsProcFunc'] = 'GridElementsTeam\Gridelements\Backend\ItemsProcFuncs\SysLanguageUidList->itemsProcFunc';
$GLOBALS['TCA']['tt_content']['columns']['pi_flexform']['config']['ds']['*,gridelements_pi1'] = '';
$GLOBALS['TCA']['tt_content']['columns']['records']['config']['allowed'] .= ',pages';

$GLOBALS['TCA']['tt_content']['types']['gridelements_pi1']['showitem'] = '
	--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general;general,
	--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.header;header,
	tx_gridelements_backend_layout,
	pi_flexform,
	tx_gridelements_children,
    --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.frames;frames,
    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.appearanceLinks;appearanceLinks,
    media,
    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,--palette--;;language,
    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
    --palette--;;hidden,
    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access;access,
    --div--;LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_category.tabs.category,
	categories,
    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,rowDescription
	';
