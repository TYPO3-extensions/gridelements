<?php

use GridElementsTeam\Gridelements\ContextMenu\ItemProvider;
use GridElementsTeam\Gridelements\Hooks\BackendUtilityGridelements;
use GridElementsTeam\Gridelements\Hooks\DatabaseRecordList;
use GridElementsTeam\Gridelements\Hooks\DataHandler;
use GridElementsTeam\Gridelements\Hooks\DrawItem;
use GridElementsTeam\Gridelements\Hooks\PreHeaderRenderHook;
use GridElementsTeam\Gridelements\Hooks\TtContentFlexForm;
use GridElementsTeam\Gridelements\Hooks\WizardItems;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

$_EXTCONF = unserialize($_EXTCONF);

if (TYPO3_MODE === 'BE') {
    ExtensionManagementUtility::allowTableOnStandardPages('tx_gridelements_backend_layout');

    include_once(ExtensionManagementUtility::extPath('gridelements') . 'Classes/Backend/TtContent.php');

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess'][] = 'GridElementsTeam\\Gridelements\\Hooks\\PageRenderer->addJSCSS';
    $GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1487270751] = ItemProvider::class;

    // Hooks
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem'][] = DrawItem::class;

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms']['db_new_content_el']['wizardItemsHook'][] = WizardItems::class;

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][] = DataHandler::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = DataHandler::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['moveRecordClass'][] = DataHandler::class;

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['getFlexFormDSClass'][] = BackendUtilityGridelements::class;

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/template.php']['preHeaderRenderHook'][] = PreHeaderRenderHook::class . '->main';

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tx_templavoila_api']['apiIsRunningTCEmain'] = true;

    $GLOBALS['TYPO3_USER_SETTINGS']['columns']['dragAndDropHideNewElementWizardInfoOverlay'] = [
        'type'  => 'check',
        'label' => 'LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:dragAndDropHideNewElementWizardInfoOverlay',
    ];

    $GLOBALS['TYPO3_USER_SETTINGS']['columns']['hideColumnHeaders'] = [
        'type'  => 'check',
        'label' => 'LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:hideColumnHeaders',
    ];

    $GLOBALS['TYPO3_USER_SETTINGS']['columns']['hideContentPreview'] = [
        'type'  => 'check',
        'label' => 'LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:hideContentPreview',
    ];

    $GLOBALS['TYPO3_USER_SETTINGS']['columns']['showGridInformation'] = [
        'type'  => 'check',
        'label' => 'LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:showGridInformation',
    ];

    $GLOBALS['TYPO3_USER_SETTINGS']['showitem'] .= ',
        --div--;LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:gridElements,
            dragAndDropHideNewElementWizardInfoOverlay,
            hideColumnHeaders,
            hideContentPreview,
            showGridInformation
            ';

    $TBE_STYLES['skins']['gridelements']['name'] = 'gridelements';
    $TBE_STYLES['skins']['gridelements']['stylesheetDirectories']['structure'] = 'EXT:' . ($_EXTKEY) . '/Resources/Public/Backend/Css/Skin/';
}

// Hooks
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][FlexFormTools::class]['flexParsing']['gridelements'] = TtContentFlexForm::class;

if ($_EXTCONF['nestingInListModule']) {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'][] = DatabaseRecordList::class;
}

$iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);
$iconRegistry->registerIcon('gridelements-default', SvgIconProvider::class, [
    'source' => 'EXT:gridelements/Resources/Public/Icons/gridelements.svg',
]);
