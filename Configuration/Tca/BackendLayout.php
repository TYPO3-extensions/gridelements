<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

$TCA['tx_gridelements_backend_layout'] = array(
	'ctrl' => $TCA['tx_gridelements_backend_layout']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'title,frame,config,pi_flexform_ds,description,hidden,icon'
	),
	'feInterface' => $TCA['tx_gridelements_backend_layout']['feInterface'],
	'columns' => array(
		't3ver_label' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.versionLabel',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'max' => '30',
			)
		),
		'hidden' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tx_gridelements_backend_layout',
			'config' => array(
				'type' => 'check',
				'items' => array (
					'1'	=> array(
						'0' => 'LLL:EXT:cms/locallang_ttc.xml:hidden.I.0',
					),
				),
			),
		),
		'title' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tx_gridelements_backend_layout.title',
			'config' => array(
				'type' => 'input',
				'size' => '25',
				'max' => '256',
				'eval' => 'required'
			)
		),
		'alias' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tx_gridelements_backend_layout.alias',
			'config' => array(
				'type' => 'input',
				'size' => '25',
				'max' => '256',
				'eval' => 'nospace,alphanum_x,lower,uniqueInPid',
			)
		),
		'description' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tx_gridelements_backend_layout.description',
			'config' => array(
				'type' => 'text',
				'rows' => '5',
			)
		),
		'icon' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tx_gridelements_backend_layout.icon',
			'config' => array(
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => 'jpg,gif,png',
				'uploadfolder' => 'uploads/tx_gridelements',
				'show_thumbs' => 1,
				'size' => 5,
				'minitems' => 0,
				'maxitems' => 2,
			)
		),
		'frame' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tx_gridelements_backend_layout.frame',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tx_gridelements_backend_layout.frame.I.0', '0'),
					array('LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tx_gridelements_backend_layout.frame.I.-1', '-1'),
					array('LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tx_gridelements_backend_layout.frame.I.1', '1'),
					array('LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tx_gridelements_backend_layout.frame.I.2', '2'),
					array('LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tx_gridelements_backend_layout.frame.I.3', '3'),
				),
				'size' => 1,
				'maxitems' => 1,
			)
		),
		'top_level_layout' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tx_gridelements_backend_layout.top_level_layout',
			'config' => array(
				'type' => 'check',
				'items' => array (
					'1'	=> array(
						'0' => 'LLL:EXT:lang/locallang_core.xml:labels.enabled',
					),
				),
			),
		),
		'config' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tx_gridelements_backend_layout.config',
			'config' => array(
				'type' => 'text',
				'cols' => '25',
				'rows' => '5',
				'wizards' => array(
					'_PADDING' => 4,
					'0' => array(
						'title' => 'LLL:EXT:cms/locallang_tca.xml:backend_layout.wizard',
						'type' => 'popup',
						'icon' => 'sysext/cms/layout/wizard_backend_layout.png',
						'script' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('gridelements') . 'Classes/Wizard/BackendLayout.php',
						'JSopenParams' => 'height=800,width=800,status=0,menubar=0,scrollbars=0',
					),
				),
			),
		),
		'pi_flexform_ds' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tx_gridelements_backend_layout.pi_flexform_ds',
			'config' => array(
				'type' => 'text',
				'cols' => '35',
				'rows' => '10',
			),
		),
		'pi_flexform_ds_file' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tx_gridelements_backend_layout.pi_flexform_ds_file',
			'config' => array(
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => 'xml',
				'maxitems' => 1,
				'size' => 1,
			),
		),
	),
	'palettes' => array(
		'general' => array('canNotCollapse' => 1, 'showitem' => 'title, --linebreak--, description'),
		'appearance' => array('canNotCollapse' => 1, 'showitem' => 'icon, frame'),
		'visibility' => array('canNotCollapse' => 1, 'showitem' => 'hidden'),
		'flexform' => array('canNotCollapse' => 1, 'showitem' => 'pi_flexform_ds, --linebreak--, pi_flexform_ds_file'),
	),
	'types' => array(
		'1' => array(
			'showitem' =>
				'--palette--;LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tx_gridelements_backend_layout;general,
				--palette--;LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tx_gridelements_backend_layout.palette.appearance;appearance,
				--div--;LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tx_gridelements_backend_layout.div.configuration, top_level_layout, alias, config, --palette--;LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tx_gridelements_backend_layout.ce_configuration;flexform,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.access,--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.visibility;visibility'
			),
		),
);
