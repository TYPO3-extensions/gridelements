<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Stefan Froemken <froemken@gmail.com>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
class tx_gridelements_cmoptionsTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var language
	 */

	protected $lang;


	public function setUp() {
		$this->lang = $GLOBALS['LANG'];
	}

	public function tearDown() {
		$GLOBALS['LANG'] = $this->lang;
	}


	/**
	 * test main method
	 */
	public function testMain() {
		$cmOptions = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('GridElementsTeam\\Gridelements\\Backend\\CmOptions');

		//$backRef = new clickMenu;
		$backRef = new \TYPO3\CMS\Backend\ClickMenu\ClickMenu;
		$menuItems['copy'][0] = '<span class="t3-icon t3-icon-empty t3-icon-empty-empty t3-icon-empty c-roimg" id="roimg_5">&nbsp;</span><a href="#" onclick="showClickmenu_raw(\'/typo3/alt_clickmenu.php?table=tt_content&amp;uid=254&amp;listFr=1&amp;enDisItems=&amp;backPath=..%2F..%2F..%2F%7C5aadecccad&amp;addParams=&amp;ajax=1&amp;reloadListFrame=1&amp;CB[el][tt_content%7C254]=1&amp;CB[setCopyMode]=1\');return false;" onmouseover="mo(5);" onmouseout="mout(5);">Kopieren <span class="t3-icon t3-icon-actions t3-icon-actions-edit t3-icon-edit-copy">&nbsp;</span></a>';
		$menuItems['copy'][1] = 'Copy';
		$menuItems['copy'][2] = ' <span class="t3-icon t3-icon-actions t3-icon-actions-edit t3-icon-edit-copy">&nbsp;</span>';
		$menuItems['copy'][3] = 'showClickmenu_raw(\'/typo3/alt_clickmenu.php?table=tt_content&uid=254&listFr=1&enDisItems=&backPath=..%2F..%2F..%2F%7C5aadecccad&addParams=&ajax=1&reloadListFrame=1&CB[el][tt_content%7C254]=1&CB[setCopyMode]=1\');return false;';
		$menuItems['copy'][4] = 0;
		$menuItems['copy'][5] = 0;
		$expectedMenuItems = $tempMenuItems = $menuItems;
		$expectedMenuItems['copy'][3] = 'showClickmenu_raw(\'/typo3/alt_clickmenu.php?table=tt_content&uid=254&listFr=1&enDisItems=&backPath=..%2F..%2F..%2F%7C5aadecccad&addParams=&ajax=1&reloadListFrame=1&CB[el][tt_content%7C254]=1&CB[setCopyMode]=1\'); GridElementsDD.listenForCopyItem(254); return false;';
		$table = 'tt_content';
		$uid = 254;
		$result = $cmOptions->main($backRef, $menuItems, $table, $uid);
		$this->assertEquals($expectedMenuItems, $result);

		$clipObj = new \TYPO3\CMS\Backend\Clipboard\Clipboard();
		$clipObj->current = 'normal';
		$clipObj->clipData['normal']['mode'] = 'copy';
		$backRef->clipObj = $clipObj;
		$language = $this->getMock('language', array('sL'));
		$language
				->expects($this->once())
				->method('sL')
				->with('LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tx_gridelements_clickmenu_pastereference')
				->will($this->returnValue('past shortcut after'));
		$GLOBALS['LANG'] = $language;
		$menuItems['pasteafter'][0] = '<span class="t3-icon t3-icon-empty t3-icon-empty-empty t3-icon-empty c-roimg" id="roimg_7">&nbsp;</span><a href="#" onclick="if (top.content.list_frame &amp;&amp; confirm(String.fromCharCode(34,73,99,104,32,98,105,110,32,101,105,110,101,32,220,98,101,114,115,99,104,114,105,102,116,34,32,104,105,110,116,101,114,32,34,73,99,104,32,98,105,110,32,101,105,110,101,32,220,98,101,114,115,99,104,114,105,102,116,34,32,107,111,112,105,101,114,101,110,63))){top.content.list_frame.location.href=top.TS.PATH_typo3+\'tce_db.php?&amp;vC=02d761752c&amp;prErr=1&amp;uPT=1&amp;CB[paste]=tt_content%7C-254&amp;CB[pad]=normal&amp;formToken=566b4dcb108368a7f10d26116a59d19fac028852&amp;redirect=\'+top.rawurlencode(top.content.list_frame.document.location.pathname+top.content.list_frame.document.location.search); hideCM();}return false;" onmouseover="mo(7);" onmouseout="mout(7);">Einf&#xfc;gen nach <span class="t3-icon t3-icon-actions t3-icon-actions-document t3-icon-document-paste-after">&nbsp;</span></a>';
		$menuItems['pasteafter'][1] = 'Paste after';
		$menuItems['pasteafter'][2] = ' <span class="t3-icon t3-icon-actions t3-icon-actions-document t3-icon-document-paste-after">&nbsp;</span>';
		$menuItems['pasteafter'][3] = 'if (top.content.list_frame && confirm(String.fromCharCode(34,73,99,104,32,98,105,110,32,101,105,110,101,32,220,98,101,114,115,99,104,114,105,102,116,34,32,104,105,110,116,101,114,32,34,73,99,104,32,98,105,110,32,101,105,110,101,32,220,98,101,114,115,99,104,114,105,102,116,34,32,107,111,112,105,101,114,101,110,63))){top.content.list_frame.location.href=top.TS.PATH_typo3+\'tce_db.php?&vC=02d761752c&prErr=1&uPT=1&CB[paste]=tt_content%7C-254&CB[pad]=normal&formToken=566b4dcb108368a7f10d26116a59d19fac028852&redirect=\'+top.rawurlencode(top.content.list_frame.document.location.pathname+top.content.list_frame.document.location.search); hideCM();}return false;';
		$menuItems['pasteafter'][4] = 0;
		$menuItems['pasteafter'][5] = 0;
		$expectedMenuItems = $tempMenuItems = $menuItems;
		$expectedMenuItems['copy'][3] = 'showClickmenu_raw(\'/typo3/alt_clickmenu.php?table=tt_content&uid=254&listFr=1&enDisItems=&backPath=..%2F..%2F..%2F%7C5aadecccad&addParams=&ajax=1&reloadListFrame=1&CB[el][tt_content%7C254]=1&CB[setCopyMode]=1\'); GridElementsDD.listenForCopyItem(254); return false;';
		$expectedMenuItems['pastereference'][0] = '<span class="t3-icon t3-icon-empty t3-icon-empty-empty t3-icon-empty c-roimg" id="roimg_7">&nbsp;</span><a href="#" onclick="if (top.content.list_frame &amp;&amp; confirm(String.fromCharCode(34,73,99,104,32,98,105,110,32,101,105,110,101,32,220,98,101,114,115,99,104,114,105,102,116,34,32,104,105,110,116,101,114,32,34,73,99,104,32,98,105,110,32,101,105,110,101,32,220,98,101,114,115,99,104,114,105,102,116,34,32,107,111,112,105,101,114,101,110,63))){top.content.list_frame.location.href=top.TS.PATH_typo3+\'tce_db.php?&amp;vC=02d761752c&amp;prErr=1&amp;uPT=1&amp;CB[paste]=tt_content%7C-254&amp;CB[pad]=normal&amp;formToken=566b4dcb108368a7f10d26116a59d19fac028852&amp;redirect=\'+top.rawurlencode(top.content.list_frame.document.location.pathname+top.content.list_frame.document.location.search); hideCM();}return false;" onmouseover="mo(7);" onmouseout="mout(7);">Einf&#xfc;gen nach <span class="t3-icon t3-icon-actions t3-icon-actions-document t3-icon-document-paste-after">&nbsp;</span></a>';
		$expectedMenuItems['pastereference'][1] = 'past shortcut after';
		$expectedMenuItems['pastereference'][2] = ' <span class="t3-icon t3-icon-actions t3-icon-actions-document t3-icon-document-paste-after">&nbsp;</span>';
		$expectedMenuItems['pastereference'][3] = 'if (top.content.list_frame && confirm(String.fromCharCode(34,73,99,104,32,98,105,110,32,101,105,110,101,32,220,98,101,114,115,99,104,114,105,102,116,34,32,104,105,110,116,101,114,32,34,73,99,104,32,98,105,110,32,101,105,110,101,32,220,98,101,114,115,99,104,114,105,102,116,34,32,107,111,112,105,101,114,101,110,63))){top.content.list_frame.location.href=top.TS.PATH_typo3+\'tce_db.php?&vC=02d761752c&prErr=1&uPT=1&CB[paste]=tt_content%7C-254&CB[pad]=normal&reference=1&formToken=566b4dcb108368a7f10d26116a59d19fac028852&redirect=\'+top.rawurlencode(top.content.list_frame.document.location.pathname+top.content.list_frame.document.location.search); hideCM();}return false;';
		$expectedMenuItems['pastereference'][4] = 0;
		$expectedMenuItems['pastereference'][5] = 0;
		$result = $cmOptions->main($backRef, $menuItems, $table, $uid);
		$this->assertEquals($expectedMenuItems, $result);
	}
}
