<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}
t3lib_extMgm::addUserTSConfig('
	options.saveDocNew.tx_gridelements_backend_layout=1
');

t3lib_extMgm::addPageTSConfig('
	mod.wizards.newContentElement.renderMode = tabs
');

t3lib_extMgm::addPItoST43($_EXTKEY, 'view/class.tx_gridelements_view.php', '_pi1', 'CType', 1);

// register BE AJAX controller
$TYPO3_CONF_VARS['BE']['AJAX']['tx_gridelements::controller'] =
	t3lib_extMgm::extPath($_EXTKEY) . 'lib/class.tx_gridelements_ajax.php:tx_gridelements_ajax->init';
