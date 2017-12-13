<?php

use GridElementsTeam\Gridelements\Slots\ExtTablesInclusionPostProcessing;
use GridElementsTeam\Gridelements\Wizard\GridelementsBackendLayoutWizardElement;
use GridElementsTeam\Gridelements\Xclass\DatabaseRecordList;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

$_EXTCONF = unserialize($_EXTCONF);

if (TYPO3_MODE === 'BE') {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1488914437] = [
        'nodeName' => 'belayoutwizard',
        'priority' => 50,
        'class'    => GridelementsBackendLayoutWizardElement::class,
    ];

    // XCLASS
    if ($_EXTCONF['nestingInListModule']) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList::class] = ['className' => DatabaseRecordList::class];
    }
}

ExtensionManagementUtility::addUserTSConfig('
	options.saveDocNew.tx_gridelements_backend_layout=1
');

ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugin/Gridelements.php', '_pi1',
    'CType', 1);

$signalSlotDispatcher = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
$signalSlotDispatcher->connect(ExtensionManagementUtility::class, 'tcaIsBeingBuilt', ExtTablesInclusionPostProcessing::class, 'processData');
