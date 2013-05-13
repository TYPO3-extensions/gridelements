<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "gridelements".
 *
 * Auto generated 12-05-2013 11:50
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Grid Elements',
	'description' => 'This extension integrates the grid layout concept also to regular content elements - the grid elements.
It offers a lot of new features like advanced drag & drop or real references, that improve the usability of the page and list module to speed up the daily work with the backend.',
	'category' => 'be',
	'shy' => 0,
	'version' => '2.0.0',
	'dependencies' => 'cms,version',
	'conflicts' => 'templavoila,jfmulticontent',
	'priority' => 'bottom',
	'loadOrder' => '',
	'module' => '',
	'state' => 'stable',
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
			'version' => '',
			'php' => '5.3.3-0.0.0',
			'typo3' => '6.0.0-0.0.0',
		),
		'conflicts' => array(
			'templavoila' => '',
			'jfmulticontent' => '',
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:93:{s:9:"ChangeLog";s:4:"9f42";s:21:"ext_conf_template.txt";s:4:"7981";s:12:"ext_icon.gif";s:4:"8275";s:17:"ext_localconf.php";s:4:"380e";s:14:"ext_tables.php";s:4:"8807";s:14:"ext_tables.sql";s:4:"40db";s:9:"README.md";s:4:"ce80";s:10:"README.txt";s:4:"2db6";s:29:"Classes/Backend/CmOptions.php";s:4:"fb3a";s:31:"Classes/Backend/LayoutSetup.php";s:4:"eb7e";s:29:"Classes/Backend/TtContent.php";s:4:"ba25";s:41:"Classes/Backend/WrapperForT3libBeFunc.php";s:4:"32a0";s:56:"Classes/Backend/ItemsProcFuncs/AbstractItemsProcFunc.php";s:4:"d683";s:45:"Classes/Backend/ItemsProcFuncs/ColPosList.php";s:4:"2bb0";s:44:"Classes/Backend/ItemsProcFuncs/CTypeList.php";s:4:"0d98";s:43:"Classes/DataHandler/AbstractDataHandler.php";s:4:"53ea";s:34:"Classes/DataHandler/MoveRecord.php";s:4:"b811";s:44:"Classes/DataHandler/PreProcessFieldArray.php";s:4:"7738";s:37:"Classes/DataHandler/ProcessCmdmap.php";s:4:"c0d7";s:25:"Classes/Helper/Helper.php";s:4:"8c2f";s:44:"Classes/Hooks/AbstractDatabaseRecordList.php";s:4:"8184";s:36:"Classes/Hooks/DatabaseRecordList.php";s:4:"b1be";s:29:"Classes/Hooks/DataHandler.php";s:4:"44e2";s:26:"Classes/Hooks/DrawItem.php";s:4:"6c23";s:28:"Classes/Hooks/FormEngine.php";s:4:"bb5d";s:30:"Classes/Hooks/PageRenderer.php";s:4:"64f3";s:29:"Classes/Hooks/WizardItems.php";s:4:"8ea2";s:31:"Classes/Plugin/Gridelements.php";s:4:"2de8";s:31:"Classes/View/AjaxRecordList.php";s:4:"d87f";s:32:"Classes/Wizard/BackendLayout.php";s:4:"8298";s:23:"Classes/Wizard/conf.php";s:4:"327b";s:37:"Classes/Xclass/DatabaseRecordList.php";s:4:"c448";s:58:"Configuration/FlexForms/default_flexform_configuration.xml";s:4:"7799";s:35:"Configuration/Tca/BackendLayout.php";s:4:"ced7";s:38:"Configuration/TypoScript/constants.txt";s:4:"d41d";s:34:"Configuration/TypoScript/setup.txt";s:4:"1f75";s:24:"Documentation/manual.pdf";s:4:"92d8";s:24:"Documentation/manual.sxw";s:4:"1e98";s:29:"Documentation/wizard_form.dat";s:4:"63f3";s:30:"Documentation/wizard_form.html";s:4:"4c87";s:43:"Resources/Private/Language/locallang_db.xml";s:4:"5c0a";s:43:"Resources/Public/Backend/Css/grideditor.css";s:4:"33d0";s:52:"Resources/Public/Backend/Css/Skin/GridElementsDD.css";s:4:"2574";s:53:"Resources/Public/Backend/Css/Skin/t3skin_override.css";s:4:"8732";s:64:"Resources/Public/Backend/Images/add copy (from another page).png";s:4:"acc4";s:41:"Resources/Public/Backend/Images/clear.gif";s:4:"cc11";s:55:"Resources/Public/Backend/Images/gridelements_sprite.png";s:4:"dae9";s:50:"Resources/Public/Backend/Images/new_content_el.gif";s:4:"efa2";s:46:"Resources/Public/Backend/Images/paste copy.png";s:4:"1a13";s:54:"Resources/Public/Backend/Images/paste reference to.png";s:4:"1712";s:45:"Resources/Public/Backend/Images/reference.png";s:4:"b409";s:58:"Resources/Public/Backend/Images/t3grid-edit-inactive-1.png";s:4:"728d";s:56:"Resources/Public/Backend/Images/t3grid-edit-inactive.png";s:4:"728d";s:47:"Resources/Public/Backend/Images/t3grid-edit.png";s:4:"f677";s:63:"Resources/Public/Backend/Images/t3grid-editor-down-inactive.png";s:4:"33d4";s:54:"Resources/Public/Backend/Images/t3grid-editor-down.png";s:4:"083d";s:63:"Resources/Public/Backend/Images/t3grid-editor-left-inactive.png";s:4:"bc52";s:54:"Resources/Public/Backend/Images/t3grid-editor-left.png";s:4:"12ff";s:64:"Resources/Public/Backend/Images/t3grid-editor-right-inactive.png";s:4:"328a";s:55:"Resources/Public/Backend/Images/t3grid-editor-right.png";s:4:"a01f";s:61:"Resources/Public/Backend/Images/t3grid-editor-up-inactive.png";s:4:"3ae1";s:52:"Resources/Public/Backend/Images/t3grid-editor-up.png";s:4:"736a";s:59:"Resources/Public/Backend/Images/t3grid-layer-icon-close.png";s:4:"c0aa";s:58:"Resources/Public/Backend/Images/t3grid-layer-icon-help.png";s:4:"8fa0";s:58:"Resources/Public/Backend/Images/t3grid-layer-icon-save.png";s:4:"fc92";s:52:"Resources/Public/Backend/Images/t3grid-tabledown.png";s:4:"24a5";s:52:"Resources/Public/Backend/Images/t3grid-tableleft.png";s:4:"e4d9";s:53:"Resources/Public/Backend/Images/t3grid-tableright.png";s:4:"592e";s:50:"Resources/Public/Backend/Images/t3grid-tableup.png";s:4:"6cf2";s:65:"Resources/Public/Backend/JavaScript/dbNewContentElWizardFixDTM.js";s:4:"79e9";s:49:"Resources/Public/Backend/JavaScript/grideditor.js";s:4:"618c";s:53:"Resources/Public/Backend/JavaScript/GridElementsDD.js";s:4:"514c";s:61:"Resources/Public/Backend/JavaScript/GridElementsDD_onReady.js";s:4:"2320";s:59:"Resources/Public/Backend/JavaScript/GridElementsListView.js";s:4:"6614";s:41:"Resources/Public/Icons/backend_layout.gif";s:4:"8275";s:31:"Tests/wrapperForT3libBeFunc.php";s:4:"aac3";s:59:"Tests/hooks/class.tx_gridelements_db_list_extrahookTest.php";s:4:"312e";s:53:"Tests/hooks/class.tx_gridelements_db_listhookTest.php";s:4:"fd4a";s:49:"Tests/lib/class.tx_gridelements_cmoptionsTest.php";s:4:"43ef";s:52:"Tests/lib/class.tx_gridelements_drawitemhookTest.php";s:4:"d63e";s:51:"Tests/lib/class.tx_gridelements_layoutsetupTest.php";s:4:"fd5a";s:56:"Tests/lib/class.tx_gridelements_pagerendererhookTest.php";s:4:"f7d3";s:52:"Tests/lib/class.tx_gridelements_tceformshookTest.php";s:4:"8fff";s:51:"Tests/lib/class.tx_gridelements_tcemainhookTest.php";s:4:"39a4";s:49:"Tests/lib/class.tx_gridelements_ttContentTest.php";s:4:"bac9";s:55:"Tests/lib/class.tx_gridelements_wizarditemshookTest.php";s:4:"bb54";s:77:"Tests/lib/itemsprocfuncs/class.tx_gridelements_itemsprocfunc_abstractTest.php";s:4:"76e1";s:79:"Tests/lib/itemsprocfuncs/class.tx_gridelements_itemsprocfunc_colposlistTest.php";s:4:"51f3";s:78:"Tests/lib/itemsprocfuncs/class.tx_gridelements_itemsprocfunc_ctypelistTest.php";s:4:"082a";s:64:"Tests/lib/tcemain/class.tx_gridelements_tcemain_abstractTest.php";s:4:"fe28";s:66:"Tests/lib/tcemain/class.tx_gridelements_tcemain_moveRecordTest.php";s:4:"0b81";s:76:"Tests/lib/tcemain/class.tx_gridelements_tcemain_preProcessFieldArrayTest.php";s:4:"973f";s:43:"Tests/pi1/class.tx_gridelements_pi1Test.php";s:4:"7a0f";}',
	'suggests' => array(
	),
);

?>