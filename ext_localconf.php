<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

$_EXTCONF = unserialize($_EXTCONF);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('
	options.saveDocNew.tx_gridelements_backend_layout=1
');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugin/Gridelements.php', '_pi1',
    'CType', 1);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['extTablesInclusion-PostProcessing'][] = 'GridElementsTeam\\Gridelements\\Hooks\\ExtTablesInclusionPostProcessing';

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1488914437] = [
    'nodeName' => 'belayoutwizard',
    'priority' => 50,
    'class' => \GridElementsTeam\Gridelements\Wizard\GridelementsBackendLayoutWizardElement::class,
];

// XCLASS
if ($_EXTCONF['nestingInListModule']) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList::class] = array('className' => \GridElementsTeam\Gridelements\Xclass\DatabaseRecordList::class);
}
