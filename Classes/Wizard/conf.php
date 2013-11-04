<?php
if(strpos($_SERVER['SCRIPT_NAME'], 'typo3conf') === FALSE) {
	$BACK_PATH = '../../../../';
	define('TYPO3_MOD_PATH', 'ext/gridelements/Classes/Wizard/');
} else {
	$BACK_PATH = '../../../../../typo3/';
	define('TYPO3_MOD_PATH', '../typo3conf/ext/gridelements/Classes/Wizard/');
}

// symlink fix - set (and later use for require()) $BACK_PATH_ABS if $BACK_PATH is wrong
$BACK_PATH_ABS = FALSE;
if(!file_exists($BACK_PATH . 'init.php')) {
	// init.php cannot be found in $BACK_PATH, so we use the absolute path
	$BACK_PATH_ABS = substr($_SERVER['SCRIPT_FILENAME'], 0, -1 * strlen($_SERVER['SCRIPT_NAME'])) . '/typo3/';
}

$MLANG['default']['tabs_images']['tab'] = 'layout.gif';
$MLANG['default']['ll_ref']='LLL:EXT:cms/layout/locallang_mod.php';

$MCONF['script'] = 'db_layout.php';
$MCONF['access'] = 'user,group';
$MCONF['name']   = 'web_layout';
