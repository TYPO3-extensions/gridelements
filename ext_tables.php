<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

$_EXTCONF = unserialize($_EXTCONF);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_gridelements_backend_layout');

if (TYPO3_MODE === 'BE') {
    include_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('gridelements') . 'Classes/Backend/TtContent.php');
}

// Hooks
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem'][] = \GridElementsTeam\Gridelements\Hooks\DrawItem::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms']['db_new_content_el']['wizardItemsHook'][] = \GridElementsTeam\Gridelements\Hooks\WizardItems::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][] = \GridElementsTeam\Gridelements\Hooks\DataHandler::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = \GridElementsTeam\Gridelements\Hooks\DataHandler::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['moveRecordClass'][] = \GridElementsTeam\Gridelements\Hooks\DataHandler::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['getFlexFormDSClass'][] = \GridElementsTeam\Gridelements\Hooks\BackendUtilityGridelements::class;
// Inject an own class to find grid element flex forms in tt_content plugins since core 8.5
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][\TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools::class]['flexParsing']['gridelements'] =
    \GridElementsTeam\Gridelements\Hooks\TtContentFlexForm::class;


$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_templavoila_api']['apiIsRunningTCEmain'] = true;

if (TYPO3_MODE == 'BE') {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess'][] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Classes/Hooks/PageRenderer.php:GridElementsTeam\\Gridelements\\Hooks\\PageRenderer->addJSCSS';
}

if ($_EXTCONF['nestingInListModule']) {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'][] = \GridElementsTeam\Gridelements\Hooks\DatabaseRecordList::class;
}

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

$GLOBALS['TYPO3_USER_SETTINGS']['showitem'] .= ',--div--;LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:gridElements,dragAndDropHideNewElementWizardInfoOverlay,hideColumnHeaders,hideContentPreview,showGridInformation';

$TBE_STYLES['skins']['gridelements']['name'] = 'gridelements';
$TBE_STYLES['skins']['gridelements']['stylesheetDirectories']['structure'] = 'EXT:' . ($_EXTKEY) . '/Resources/Public/Backend/Css/Skin/';

$GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1487270751] =  \GridElementsTeam\Gridelements\ContextMenu\ItemProvider::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/template.php']['preHeaderRenderHook'][] = \GridElementsTeam\Gridelements\Hooks\PreHeaderRenderHook::class . '->main';

$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
$iconRegistry->registerIcon('gridelements-default', \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class, array(
    'source' => 'EXT:gridelements/Resources/Public/Icons/gridelements.svg'
));
