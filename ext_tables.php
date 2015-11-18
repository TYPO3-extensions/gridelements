<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_gridelements_backend_layout');

if (TYPO3_MODE === 'BE') {
	include_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('gridelements') . 'Classes/Backend/TtContent.php');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript/', 'Gridelements');

// Register backend_layout wizard
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'wizard_gridelements_backend_layout',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Classes/Wizard/'
);

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
		'EXT:gridelements/Classes/Hooks/BackendUtilityGridelements.php:GridElementsTeam\\Gridelements\\Hooks\\BackendUtilityGridelements';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_templavoila_api']['apiIsRunningTCEmain'] = TRUE;

if (TYPO3_MODE === 'BE') {
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess'][] =
			\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Classes/Hooks/PageRenderer.php:GridElementsTeam\\Gridelements\\Hooks\\PageRenderer->addJSCSS';
}

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

$GLOBALS['TYPO3_USER_SETTINGS']['columns']['showGridInformation'] = array(
		'type' => 'check',
		'label' => 'LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:showGridInformation'
);

$GLOBALS['TYPO3_USER_SETTINGS']['showitem'] .=
		',--div--;LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:gridElements,dragAndDropHideNewElementWizardInfoOverlay,hideColumnHeaders,hideContentPreview,showGridInformation';

$TBE_STYLES['skins']['gridelements']['name'] = 'gridelements';
$TBE_STYLES['skins']['gridelements']['stylesheetDirectories']['structure'] = 'EXT:' . ($_EXTKEY) . '/Resources/Public/Backend/Css/Skin/';

$GLOBALS['TBE_MODULES_EXT']['xMOD_alt_clickmenu']['extendCMclasses'][] = array(
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

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/template.php']['preHeaderRenderHook'][] = 'GridElementsTeam\\Gridelements\\Hooks\\PreHeaderRenderHook->main';



