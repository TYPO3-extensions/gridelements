<?php

########################################################################
# Extension Manager/Repository config file for ext "gridelements".
#
# Auto generated 27-04-2012 23:34
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Grid Elements',
	'description' => 'This extension integrates the grid layout concept also to regular content elements - the grid elements.
It offers a lot of new features like advanced drag & drop or real references, that improve the usability of the page and list module to speed up the daily work with the backend.',
	'category' => 'be',
	'shy' => 0,
	'version' => '2.0.0-dev',
	'dependencies' => 'cms',
	'conflicts' => 'templavoila,jfmulticontent',
	'priority' => 'bottom',
	'loadOrder' => '',
	'module' => '',
	'state' => 'beta',
	'uploadfolder' => 1,
	'createDirs' => '',
	'modify_tables' => 'tt_content',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'Grid Elements Team',
	'author_email' => 'info@cybercraft.de',
	'author_company' => '',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'cms' => '',
			'php' => '5.3.0-0.0.0',
			'typo3' => '6.0.0-0.0.0',
		),
		'conflicts' => array(
			'templavoila' => '',
			'jfmulticontent' => '',
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:56:{s:9:"ChangeLog";s:4:"7f9d";s:10:"README.txt";s:4:"65ef";s:16:"ext_autoload.php";s:4:"2f72";s:12:"ext_icon.gif";s:4:"8275";s:17:"ext_localconf.php";s:4:"c34d";s:14:"ext_tables.php";s:4:"b57c";s:14:"ext_tables.sql";s:4:"859b";s:39:"icon_tx_gridelements_backend_layout.gif";s:4:"475a";s:16:"locallang_db.xml";s:4:"515e";s:7:"tca.php";s:4:"1097";s:14:"doc/manual.pdf";s:4:"759e";s:14:"doc/manual.sxw";s:4:"6cca";s:19:"doc/wizard_form.dat";s:4:"63f3";s:20:"doc/wizard_form.html";s:4:"0ac9";s:43:"lib/class.tx_gridelements_addjavascript.php";s:4:"96a2";s:39:"lib/class.tx_gridelements_cmoptions.php";s:4:"ee66";s:40:"lib/class.tx_gridelements_itemsprocfunc_colposlist.php";s:4:"d559";s:47:"lib/class.tx_gridelements_dblistactionshook.php";s:4:"d817";s:40:"lib/class.tx_gridelements_dblisthook.php";s:4:"2822";s:42:"lib/class.tx_gridelements_drawitemhook.php";s:4:"ddde";s:41:"lib/class.tx_gridelements_layoutsetup.php";s:4:"f003";s:42:"lib/class.tx_gridelements_tceformshook.php";s:4:"ab16";s:41:"lib/class.tx_gridelements_tcemainhook.php";s:4:"fb1c";s:40:"lib/class.tx_gridelements_tt_content.php";s:4:"15e4";s:45:"lib/class.tx_gridelements_wizarditemshook.php";s:4:"fcf9";s:12:"lib/conf.php";s:4:"fb7b";s:24:"lib/getclipboarditem.php";s:4:"e24a";s:42:"lib/wizard_gridelements_backend_layout.php";s:4:"3e71";s:33:"pi1/class.tx_gridelements_pi1.php";s:4:"03a7";s:22:"res/css/grideditor.css";s:4:"03d3";s:31:"res/css/skin/GridElementsDD.css";s:4:"d0e5";s:32:"res/css/skin/t3skin_override.css";s:4:"2823";s:26:"res/img/new_content_el.gif";s:4:"efa2";s:34:"res/img/t3grid-edit-inactive-1.png";s:4:"728d";s:32:"res/img/t3grid-edit-inactive.png";s:4:"728d";s:23:"res/img/t3grid-edit.png";s:4:"f677";s:39:"res/img/t3grid-editor-down-inactive.png";s:4:"33d4";s:30:"res/img/t3grid-editor-down.png";s:4:"083d";s:39:"res/img/t3grid-editor-left-inactive.png";s:4:"bc52";s:30:"res/img/t3grid-editor-left.png";s:4:"12ff";s:40:"res/img/t3grid-editor-right-inactive.png";s:4:"328a";s:31:"res/img/t3grid-editor-right.png";s:4:"a01f";s:37:"res/img/t3grid-editor-up-inactive.png";s:4:"3ae1";s:28:"res/img/t3grid-editor-up.png";s:4:"736a";s:35:"res/img/t3grid-layer-icon-close.png";s:4:"c0aa";s:34:"res/img/t3grid-layer-icon-help.png";s:4:"8fa0";s:34:"res/img/t3grid-layer-icon-save.png";s:4:"fc92";s:28:"res/img/t3grid-tabledown.png";s:4:"24a5";s:28:"res/img/t3grid-tableleft.png";s:4:"e4d9";s:29:"res/img/t3grid-tableright.png";s:4:"592e";s:26:"res/img/t3grid-tableup.png";s:4:"6cf2";s:24:"res/js/GridElementsDD.js";s:4:"456b";s:32:"res/js/GridElementsDD_onReady.js";s:4:"2851";s:20:"res/js/grideditor.js";s:4:"213c";s:33:"static/gridelements/constants.txt";s:4:"d41d";s:29:"static/gridelements/setup.txt";s:4:"3535";}',
	'suggests' => array(
	),
);

?>