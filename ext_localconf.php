<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

$_EXTCONF = unserialize($_EXTCONF);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('
	options.saveDocNew.tx_gridelements_backend_layout=1
');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('
	mod.wizards.newContentElement.renderMode = tabs
');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'Classes/Plugin/Gridelements.php', '_pi1', 'CType', 1);

// XCLASS
if ($_EXTCONF['nestingInListModule']) {
// register BE AJAX controller
	$TYPO3_CONF_VARS['BE']['AJAX']['tx_gridelements::controller'] =
			\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Classes/View/AjaxRecordList.php:GridElementsTeam\\Gridelements\\View\\AjaxRecordList->init';

	$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Recordlist\\RecordList\\DatabaseRecordList'] = array(
			'className' => 'GridElementsTeam\\Gridelements\\Xclass\\DatabaseRecordList',
	);
}
