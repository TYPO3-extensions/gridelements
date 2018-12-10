<?php

$l10n = 'LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml';

return [
    'ctrl' => [
        'title' => $l10n . ':tx_gridelements_backend_layout',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'sortby' => 'sorting',
        'delete' => 'deleted',
        'rootLevel' => -1,
        'thumbnail' => 'resources',
        'dividers2tabs' => true,
        'selicon_field' => 'icon',
        'selicon_field_path' => 'uploads/tx_gridelements',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'iconfile' => 'EXT:gridelements/Resources/Public/Icons/gridelements.svg',
    ],
    'interface' => [
        'showRecordFieldList' => 'title,frame,config,pi_flexform_ds,description,hidden,icon'
    ],
    'columns' => [
        't3ver_label' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.versionLabel',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'max' => '30',
            ]
        ],
        'hidden' => [
            'exclude' => 1,
            'label' => $l10n . ':tx_gridelements_backend_layout',
            'config' => [
                'type' => 'check',
                'items' => [
                    '1' => [
                        '0' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:hidden.I.0',
                    ],
                ],
            ],
        ],
        'title' => [
            'exclude' => 1,
            'label' => $l10n . ':tx_gridelements_backend_layout.title',
            'config' => [
                'type' => 'input',
                'size' => '25',
                'max' => '256',
                'eval' => 'required'
            ]
        ],
        'alias' => [
            'exclude' => 1,
            'label' => $l10n . ':tx_gridelements_backend_layout.alias',
            'config' => [
                'type' => 'input',
                'size' => '25',
                'max' => '256',
                'eval' => 'nospace,alphanum_x,lower',
            ]
        ],
        'description' => [
            'exclude' => 1,
            'label' => $l10n . ':tx_gridelements_backend_layout.description',
            'config' => [
                'type' => 'text',
                'rows' => '5',
            ]
        ],
        'horizontal' => [
            'exclude' => 1,
            'label' => $l10n . ':tx_gridelements_backend_layout.horizontal',
            'config' => [
                'type' => 'check',
                'items' => [
                    '1' => [
                        '0' => $l10n . ':tx_gridelements_backend_layout.horizontal.I.0',
                    ],
                ],
            ],
        ],
        'icon' => [
            'exclude' => 1,
            'label' => $l10n . ':tx_gridelements_backend_layout.icon',
            'config' => [
                'type' => 'group',
                'internal_type' => 'file',
                'allowed' => 'jpg,gif,png,svg',
                'uploadfolder' => 'uploads/tx_gridelements',
                'size' => 5,
                'minitems' => 0,
                'maxitems' => 2,
            ]
        ],
        'frame' => [
            'exclude' => 1,
            'label' => $l10n . ':tx_gridelements_backend_layout.frame',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        $l10n . ':tx_gridelements_backend_layout.frame.I.0',
                        '0'
                    ],
                    [
                        $l10n . ':tx_gridelements_backend_layout.frame.I.-1',
                        '-1'
                    ],
                    [
                        $l10n . ':tx_gridelements_backend_layout.frame.I.1',
                        '1'
                    ],
                    [
                        $l10n . ':tx_gridelements_backend_layout.frame.I.2',
                        '2'
                    ],
                    [
                        $l10n . ':tx_gridelements_backend_layout.frame.I.3',
                        '3'
                    ],
                ],
                'size' => 1,
                'maxitems' => 1,
            ]
        ],
        'top_level_layout' => [
            'exclude' => 1,
            'label' => $l10n . ':tx_gridelements_backend_layout.top_level_layout',
            'config' => [
                'type' => 'check',
                'items' => [
                    '1' => [
                        '0' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.enabled',
                    ],
                ],
            ],
        ],
        'config' => [
            'exclude' => 1,
            'label' => $l10n . ':tx_gridelements_backend_layout.config',
            'config' => [
                'type' => 'text',
                'renderType' => 'belayoutwizard',
                'cols' => '25',
                'rows' => '5',
                'fixedFont' => true
            ]
        ],
        'pi_flexform_ds' => [
            'exclude' => 1,
            'label' => $l10n . ':tx_gridelements_backend_layout.pi_flexform_ds',
            'config' => [
                'type' => 'text',
                'cols' => '35',
                'rows' => '10',
            ],
        ],
        'pi_flexform_ds_file' => [
            'exclude' => 1,
            'label' => $l10n . ':tx_gridelements_backend_layout.pi_flexform_ds_file',
            'config' => [
                'type' => 'group',
                'internal_type' => 'file',
                'allowed' => 'xml',
                'maxitems' => 1,
                'size' => 1,
            ],
        ],
    ],
    'palettes' => [
        'general' => [
            'canNotCollapse' => 1,
            'showitem' => 'title, --linebreak--, description'
        ],
        'appearance' => [
            'canNotCollapse' => 1,
            'showitem' => 'icon, horizontal, frame'
        ],
        'visibility' => [
            'canNotCollapse' => 1,
            'showitem' => 'hidden'
        ],
        'flexform' => [
            'canNotCollapse' => 1,
            'showitem' => 'pi_flexform_ds, --linebreak--, pi_flexform_ds_file'
        ],
    ],
    'types' => [
        '1' => [
            'showitem' => '--palette--;' . $l10n . ':tx_gridelements_backend_layout;general,
					--palette--;' . $l10n . ':tx_gridelements_backend_layout.palette.appearance;appearance,
					--div--;' . $l10n . ':tx_gridelements_backend_layout.div.configuration, top_level_layout, alias, config,
					--palette--;' . $l10n . ':tx_gridelements_backend_layout.ce_configuration;flexform,
					--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.visibility;visibility'
        ],
    ],

];
