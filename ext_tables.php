<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_gridelements_backend_layout');

$TCA['tx_gridelements_backend_layout'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tx_gridelements_backend_layout',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'versioningWS' => TRUE,
		'origUid' => 't3_origuid',
		'sortby' => 'sorting',
		'delete' => 'deleted',
		'rootLevel' => -1,
		'thumbnail' => 'resources',
		'dividers2tabs' => TRUE,
		'selicon_field' => 'icon',
		'selicon_field_path' => 'uploads/tx_gridelements',
		'enablecolumns' => array(
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Configuration/Tca/BackendLayout.php',
		'iconfile' => '../' . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('gridelements') . 'Resources/Public/Icons/backend_layout.gif',
	),
);

if (TYPO3_MODE == 'BE') {

	include_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('gridelements') . 'Classes/Backend/TtContent.php');

}

$tempColumns = array(
	'tx_gridelements_backend_layout' => array(
		'exclude' => 1,
		'label' => 'LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tt_content.tx_gridelements_backend_layout',
		'config' => array(
			'type' => 'select',
			'itemsProcFunc' => 'GridElementsTeam\Gridelements\Backend\TtContent->layoutItemsProcFunc',
			'size' => 1,
			'selicon_cols' => 9,
			'maxitems' => 1,
			'default' => '',
		)
	),
	'tx_gridelements_children' => array(
		'exclude' => 1,
		'label' => 'LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tt_content.tx_gridelements_children',
		'config' => array(
			'type' => 'inline',
			'appearance' => array(
				'levelLinksPosition' => 'top',
				'showPossibleLocalizationRecords' => TRUE,
				'showRemovedLocalizationRecords' => TRUE,
				'showAllLocalizationLink' => TRUE,
				'showSynchronizationLink' => TRUE,
				'enabledControls' => array(
					'info' => TRUE,
					'new' => FALSE,
					'dragdrop' => FALSE,
					'sort' => FALSE,
					'hide' => TRUE,
					'delete' => TRUE,
					'localize' => TRUE,
				)
			),
			'inline' => array(
				'inlineNewButtonStyle' => 'display: inline-block;',
			),
			'behaviour' => array(
				'localizationMode' => 'select',
				'localizeChildrenAtParentLocalization' => TRUE,
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
		'label' => 'LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tt_content.tx_gridelements_container',
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
				AND NOT (tt_content.uid=###THIS_UID###)
				AND (
					NOT (tt_content.tx_gridelements_container=###THIS_UID###)
					OR tt_content.tx_gridelements_container=0
				)
				ORDER BY tt_content.header, tt_content.uid
			',
			'itemsProcFunc' => 'GridElementsTeam\Gridelements\Backend\TtContent->containerItemsProcFunc',
			'size' => 1,
			'minitems' => 0,
			'maxitems' => 1,
		)
	),
	'tx_gridelements_columns' => array(
		'exclude' => 1,
		'label' => 'LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tt_content.tx_gridelements_columns',
		'config' => array(
			'type' => 'select',
			'itemsProcFunc' => 'GridElementsTeam\Gridelements\Backend\TtContent->columnsItemsProcFunc',
			'size' => 1,
			'maxitems' => 1,
		)
	),
);


\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('tt_content', $tempColumns);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript/', 'Gridelements');

$TCA['tt_content']['ctrl']['requestUpdate'] .= ',tx_gridelements_container,tx_gridelements_columns,colPos';
$TCA['tt_content']['ctrl']['typeicons']['gridelements_pi1'] = 'backend_layout.gif';
$TCA['tt_content']['ctrl']['useColumnsForDefaultValues'] .= ',tx_gridelements_container,tx_gridelements_columns';
$TCA['tt_content']['ctrl']['shadowColumnsForNewPlaceholders'] .= ',tx_gridelements_container,tx_gridelements_columns';
$TCA['tt_content']['ctrl']['typeicon_classes']['gridelements_pi1'] = 'tcarecords-tx_gridelements_backend_layout-default';
// $TCA['tt_content']['ctrl']['keepFields'] .= 'tx_gridelements_backend_layout,tx_gridelements_children,tx_gridelements_container,tx_gridelements_columns';

$TCA['tt_content']['types'][$_EXTKEY . '_pi1']['showitem'] = $TCA['tt_content']['types']['text']['showitem'];

$TCA['tt_content']['columns']['colPos']['config']['itemsProcFunc'] = 'GridElementsTeam\Gridelements\Backend\ItemsProcFuncs\ColPosList->itemsProcFunc';
$TCA['tt_content']['columns']['CType']['config']['itemsProcFunc'] = 'GridElementsTeam\Gridelements\Backend\ItemsProcFuncs\CTypeList->itemsProcFunc';
$TCA['tt_content']['columns']['pi_flexform']['config']['ds']['*,gridelements_pi1'] = '';
$TCA['tt_content']['columns']['records']['config']['allowed'] .= ',pages';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('tt_content', 'recursive', 'shortcut', 'after:records');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('tt_content', 'tx_gridelements_backend_layout', $_EXTKEY . '_pi1', 'replace:bodytext');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('tt_content', 'pi_flexform, tx_gridelements_children', $_EXTKEY . '_pi1', 'replace:rte_enabled');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('tt_content', 'tx_gridelements_container, tx_gridelements_columns');

$TCA['backend_layout']['columns']['config']['config']['wizards']['0']['script'] =
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'Classes/Wizard/BackendLayout.php';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
	array(
		'LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tt_content.CType_pi1',
		$_EXTKEY . '_pi1',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'ext_icon.gif'
	),
	'CType'
);

// Hooks
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem'][] =
    'EXT:gridelements/Classes/Hooks/DrawItem.php:GridElementsTeam\\Gridelements\\Hooks\\DrawItem';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms']['db_new_content_el']['wizardItemsHook'][] =
    'EXT:gridelements/Classes/Hooks/WizardItems.php:GridElementsTeam\\Gridelements\\Hooks\\WizardItems';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][] =
    'EXT:gridelements/Classes/Hooks/DataHandler.php:GridElementsTeam\\Gridelements\\Hooks\\DataHandler';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] =
    'EXT:gridelements/Classes/Hooks/DataHandler.php:GridElementsTeam\\Gridelements\\Hooks\\DataHandler';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['moveRecordClass'][] =
    'EXT:gridelements/Classes/Hooks/DataHandler.php:GridElementsTeam\\Gridelements\\Hooks\\DataHandler';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['getFlexFormDSClass'][] =
    'EXT:gridelements/Classes/Hooks/BackendUtility.php:GridElementsTeam\\Gridelements\\Hooks\\BackendUtility';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_templavoila_api']['apiIsRunningTCEmain'] = TRUE;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess'][] =
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Classes/Hooks/PageRenderer.php:GridElementsTeam\\Gridelements\\Hooks\\PageRenderer->addJSCSS';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list.inc']['makeQueryArray'][] =
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Classes/Hooks/AbstractDatabaseRecordList.php:GridElementsTeam\\Gridelements\\Hooks\\AbstractDatabaseRecordList';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'][] =
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Classes/Hooks/DatabaseRecordList.php:GridElementsTeam\\Gridelements\\Hooks\\DatabaseRecordList';

$GLOBALS['TYPO3_USER_SETTINGS']['columns']['dragAndDropHideNewElementWizardInfoOverlay'] = array(
	'type' => 'check',
	'label' => 'LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:dragAndDropHideNewElementWizardInfoOverlay'
);

$GLOBALS['TYPO3_USER_SETTINGS']['columns']['hideColumnHeaders'] = array(
	'type' => 'check',
	'label' => 'LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:hideColumnHeaders'
);

$GLOBALS['TYPO3_USER_SETTINGS']['columns']['hideContentPreview'] = array(
	'type' => 'check',
	'label' => 'LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:hideContentPreview'
);

$GLOBALS['TYPO3_USER_SETTINGS']['showitem'] .=
    ',--div--;LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:gridElements,dragAndDropHideNewElementWizardInfoOverlay,hideColumnHeaders,hideContentPreview';

$TBE_STYLES['skins']['gridelements']['name'] = 'gridelements';
$TBE_STYLES['skins']['gridelements']['stylesheetDirectories']['structure'] = 'EXT:' . ($_EXTKEY) . '/Resources/Public/Backend/Css/Skin/';

$GLOBALS['TBE_MODULES_EXT']['xMOD_alt_clickmenu']['extendCMclasses'][]=array(
	'name' => 'GridElementsTeam\\Gridelements\\Backend\\CmOptions',
);

// add custom sprite icons
$geIcons = array(
	'extensions-gridelements-copyfrompage',
	'extensions-gridelements-pastecopy',
	'extensions-gridelements-pasteref',
);

\TYPO3\CMS\Backend\Sprite\SpriteManager::addIconSprite(
	$geIcons,
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('gridelements') . 'Resources/Public/Backend/Css/Skin/t3skin_override.css'
);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/template.php']['preHeaderRenderHook'][] = 'EXT:gridelements/Classes/Hooks/PreHeaderRenderHook.php:Tx_Gridelements_Hooks_PreHeaderRenderHook->main';



