<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

t3lib_extMgm::allowTableOnStandardPages('tx_gridelements_backend_layout');

$TCA['tx_gridelements_backend_layout'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:gridelements/locallang_db.xml:tx_gridelements_backend_layout',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'versioningWS' => TRUE,
		'origUid' => 't3_origuid',
		'sortby' => 'sorting',
		'delete' => 'deleted',
		'thumbnail' => 'resources',
		'dividers2tabs' => TRUE,
		'selicon_field' => 'icon',
		'selicon_field_path' => 'uploads/tx_gridelements',
		'enablecolumns' => array(
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY) . 'tca.php',
		'iconfile' => 'backend_layout.gif',
	),
);

if (TYPO3_MODE == 'BE') {

	include_once(t3lib_extMgm::extPath('gridelements') . 'lib/class.tx_gridelements_tt_content.php');

}

$tempColumns = array(
	'tx_gridelements_backend_layout' => array(
		'exclude' => 1,
		'label' => 'LLL:EXT:gridelements/locallang_db.xml:tt_content.tx_gridelements_backend_layout',
		'config' => array(
			'type' => 'select',
/*			'items' => array(
				array('LLL:EXT:gridelements/locallang_db.xml:tt_content.tx_gridelements_backend_layout.I.none', 0),
//				array('LLL:EXT:gridelements/locallang_db.xml:tt_content.tx_gridelements_backend_layout.I.vertical', -1),
//				array('LLL:EXT:gridelements/locallang_db.xml:tt_content.tx_gridelements_backend_layout.I.horizontal', -2),
			),*/
#			'foreign_table' => 'tx_gridelements_backend_layout',
#			'foreign_table_where' => 'AND (
#					( ###PAGE_TSCONFIG_ID### = 0 AND ###STORAGE_PID### = 0 ) OR
#					(tx_gridelements_backend_layout.pid = ###PAGE_TSCONFIG_ID### OR tx_gridelements_backend_layout.pid = ###STORAGE_PID### ) OR
#					( ###PAGE_TSCONFIG_ID### = 0 AND tx_gridelements_backend_layout.pid = ###THIS_UID### )
#				) AND tx_gridelements_backend_layout.hidden = 0 ORDER BY tx_gridelements_backend_layout.title',
			'itemsProcFunc' => 'tx_gridelements_tt_content->layoutItemsProcFunc',
			'size' => 1,
			'selicon_cols' => 9,
			'maxitems' => 1,
			'default' => '',
		)
	),
	'tx_gridelements_children' => array(
		'exclude' => 1,
		'label' => 'LLL:EXT:gridelements/locallang_db.xml:tt_content.tx_gridelements_children',
		'config' => array(
			'type' => 'inline',
			'appearance' => array(
				'levelLinksPosition' => 'none',
				'showPossibleLocalizationRecords' => true,
				'showRemovedLocalizationRecords' => true,
				'showAllLocalizationLink' => true,
				'showSynchronizationLink' => true,
				'enabledControls' => array(
					'info' => true,
					'new' => false,
					'dragdrop' => false,
					'sort' => false,
					'hide' => true,
					'delete' => true,
					'localize' => true,
				)
			),
			'inline' => array(
				'inlineNewButtonStyle' => 'display: inline-block;',
			),
			'behaviour' => array(
				'localizationMode' => 'select',
				'localizeChildrenAtParentLocalization' => true,
			),
			'foreign_table' => 'tt_content',
			'foreign_field' => 'tx_gridelements_container',
			'foreign_sortby' => 'sorting',
			'size' => 5,
			'autoSizeMax' => 20,
		)
	),
	'tx_gridelements_container' => array(
		'exclude' => 1,
		'label' => 'LLL:EXT:gridelements/locallang_db.xml:tt_content.tx_gridelements_container',
		'config' => array(
			'type' => 'select',
			'items' => array(
				array('', 0),
			),
			'foreign_table' => 'tt_content',
			'foreign_table_where' => '
				AND tt_content.sys_language_uid = ###REC_FIELD_sys_language_uid###
				AND tt_content.pid=###CURRENT_PID###
				AND tt_content.CType=\'gridelements_pi1\'
				AND NOT tt_content.uid=###THIS_UID###
				AND (
					NOT tt_content.tx_gridelements_container=###THIS_UID###
					OR tt_content.tx_gridelements_container=0
				)
				ORDER BY tt_content.header, tt_content.uid
			',
			'itemsProcFunc' => 'tx_gridelements_tt_content->containerItemsProcFunc',
			'size' => 1,
			'minitems' => 0,
			'maxitems' => 1,
		)
	),
	'tx_gridelements_columns' => array(
		'exclude' => 1,
		'label' => 'LLL:EXT:gridelements/locallang_db.xml:tt_content.tx_gridelements_columns',
		'config' => array(
			'type' => 'select',
			'itemsProcFunc' => 'tx_gridelements_tt_content->columnsItemsProcFunc',
			'size' => 1,
			'maxitems' => 1,
		)
	),
);


t3lib_div::loadTCA('tt_content');
t3lib_extMgm::addTCAcolumns('tt_content', $tempColumns, 1);
t3lib_extMgm::addStaticFile($_EXTKEY, 'static/gridelements/', 'gridelements');

$TCA['tt_content']['ctrl']['requestUpdate'] .= ',tx_gridelements_container,tx_gridelements_columns,colPos';
$TCA['tt_content']['ctrl']['typeicons']['gridelements_pi1'] = 'backend_layout.gif';
$TCA['tt_content']['ctrl']['useColumnsForDefaultValues'] .= ',tx_gridelements_container,tx_gridelements_columns';
$TCA['tt_content']['ctrl']['shadowColumnsForNewPlaceholders'] .= ',tx_gridelements_container,tx_gridelements_columns';
$TCA['tt_content']['ctrl']['typeicon_classes']['gridelements_pi1'] = 'tcarecords-tx_gridelements_backend_layout-default';
// $TCA['tt_content']['ctrl']['keepFields'] .= 'tx_gridelements_backend_layout,tx_gridelements_children,tx_gridelements_container,tx_gridelements_columns';

$TCA['tt_content']['types'][$_EXTKEY . '_pi1']['showitem'] = $TCA['tt_content']['types']['text']['showitem'];

$TCA['tt_content']['columns']['colPos']['config']['itemsProcFunc'] = 'EXT:gridelements/lib/itemsprocfuncs/class.tx_gridelements_itemsprocfunc_colposlist.php:tx_gridelements_itemsprocfunc_colposlist->itemsProcFunc';
$TCA['tt_content']['columns']['CType']['config']['itemsProcFunc'] = 'EXT:gridelements/lib/itemsprocfuncs/class.tx_gridelements_itemsprocfunc_ctypelist.php:tx_gridelements_itemsprocfunc_ctypelist->itemsProcFunc';
$TCA['tt_content']['columns']['pi_flexform']['config']['ds']['*,gridelements_pi1'] = '';
$TCA['tt_content']['columns']['records']['config']['allowed'] .= ',pages';

t3lib_extMgm::addToAllTCAtypes('tt_content', 'recursive', 'shortcut', 'after:records');
t3lib_extMgm::addToAllTCAtypes('tt_content', 'tx_gridelements_backend_layout', $_EXTKEY . '_pi1', 'replace:bodytext');
t3lib_extMgm::addToAllTCAtypes('tt_content', 'pi_flexform, tx_gridelements_children', $_EXTKEY . '_pi1', 'replace:rte_enabled');
t3lib_extMgm::addToAllTCAtypes('tt_content', 'tx_gridelements_container, tx_gridelements_columns');

t3lib_div::loadTCA('backend_layout');
$TCA['backend_layout']['columns']['config']['config']['wizards']['0']['script'] = t3lib_extMgm::extRelPath($_EXTKEY) . 'lib/wizard_gridelements_backend_layout.php';

t3lib_extMgm::addPlugin(
	array(
		'LLL:EXT:gridelements/locallang_db.xml:tt_content.CType_pi1',
        $_EXTKEY . '_pi1',
        t3lib_extMgm::extRelPath($_EXTKEY) . 'ext_icon.gif'
	),
	'CType'
);

// Hooks
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem'][] = 'EXT:gridelements/lib/class.tx_gridelements_drawitemhook.php:tx_gridelements_drawItemHook';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms']['db_new_content_el']['wizardItemsHook'][] = 'EXT:gridelements/lib/class.tx_gridelements_wizarditemshook.php:tx_gridelements_wizardItemsHook';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][] = 'EXT:gridelements/lib/class.tx_gridelements_tcemainhook.php:tx_gridelements_TCEmainHook';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 'EXT:gridelements/lib/class.tx_gridelements_tcemainhook.php:tx_gridelements_TCEmainHook';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['moveRecordClass'][] = 'EXT:gridelements/lib/class.tx_gridelements_tcemainhook.php:tx_gridelements_TCEmainHook';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['getSingleFieldClass'][] = 'EXT:gridelements/lib/class.tx_gridelements_tceformshook.php:tx_gridelements_TCEformsHook';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_templavoila_api']['apiIsRunningTCEmain'] = TRUE;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess'][] = 'EXT:gridelements/hooks/class.tx_gridelements_pagerendererhook.php:tx_gridelements_pagerendererhook->addJSCSS';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list.inc']['makeQueryArray'][] = 'EXT:gridelements/hooks/class.tx_gridelements_db_listhook.php:tx_gridelements_db_listHook';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'][] = 'EXT:gridelements/hooks/class.tx_gridelements_db_list_extrahook.php:tx_gridelements_db_list_extraHook';

// XCLASS
$GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/class.db_list_extra.inc'] = t3lib_extMgm::extPath('gridelements').'xclass/class.ux_db_list_extra.php';


$GLOBALS['TYPO3_USER_SETTINGS']['columns']['dragAndDropHideNewElementWizardInfoOverlay'] = array(
	'type' => 'check',
	'label' => 'LLL:EXT:gridelements/locallang_db.xml:dragAndDropHideNewElementWizardInfoOverlay'
);

$GLOBALS['TYPO3_USER_SETTINGS']['columns']['hideColumnHeaders'] = array(
	'type' => 'check',
	'label' => 'LLL:EXT:gridelements/locallang_db.xml:hideColumnHeaders'
);

$GLOBALS['TYPO3_USER_SETTINGS']['columns']['hideContentPreview'] = array(
	'type' => 'check',
	'label' => 'LLL:EXT:gridelements/locallang_db.xml:hideContentPreview'
);

$GLOBALS['TYPO3_USER_SETTINGS']['showitem'] .= ',--div--;LLL:EXT:gridelements/locallang_db.xml:gridElements,dragAndDropHideNewElementWizardInfoOverlay,hideColumnHeaders,hideContentPreview';

$TBE_STYLES['skins']['gridelements']['name'] = 'gridelements';
$TBE_STYLES['skins']['gridelements']['stylesheetDirectories']['structure'] = 'EXT:' . ($_EXTKEY) . '/res/css/skin/';

$GLOBALS['TBE_MODULES_EXT']['xMOD_alt_clickmenu']['extendCMclasses'][]=array(
	'name' => 'tx_gridelements_cmoptions',
	'path' => t3lib_extMgm::extPath($_EXTKEY).'lib/class.tx_gridelements_cmoptions.php'
);

// add custom sprite icons
$geIcons = array(
	'extensions-gridelements-copyfrompage',
	'extensions-gridelements-pastecopy',
	'extensions-gridelements-pasteref',
);

t3lib_SpriteManager::addIconSprite(
	$geIcons,
	t3lib_extMgm::siteRelPath('gridelements') . 'res/css/skin/t3skin_override.css'
);

?>