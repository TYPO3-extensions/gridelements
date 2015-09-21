<?php
namespace GridElementsTeam\Gridelements\Backend;

/***************************************************************
 *  Copyright notice
 *  (c) 2013 Jo Hasenau <info@cybercraft.de>
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use TYPO3\CMS\Backend\ClickMenu\ClickMenu;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

/**
 * Class/Function which
 * @author Jo Hasenau <info@cybercraft.de>
 * @package TYPO3
 * @subpackage tx_gridelements
 */
class CmOptions {

	/**
	 * Main method
	 * @param ClickMenu $backRef
	 * @param array $menuItems
	 * @param string $table
	 * @param integer $uid
	 * @return array
	 */
	public function main(ClickMenu &$backRef, array $menuItems, $table, $uid) {

		// add copied item handler to "(un)copy" link in clickmenu
		if (strpos($menuItems['copy'][0], 't3-icon-edit-copy-release') === false) {
			preg_match('@&uid=(?P<digit>\d+)&@', $menuItems['copy'][3], $arrMatches);
			$strUidInLink = $arrMatches[1];
			$menuItems['copy'][3] = str_replace('return false;', ' GridElementsDD.listenForCopyItem(' . $strUidInLink . '); return false;', $menuItems['copy'][3]);
		}

		// add "paste reference after" if user is allowed to use CType shortcut
		if ($GLOBALS['BE_USER']->checkAuthMode('tt_content', 'CType', 11, 'explicitAllow')) {
			$parkItem = $menuItems['pasteafter'];
			if ($parkItem) {
				unset($menuItems['pasteafter']);
				$menuItems['pasteafter'] = $parkItem;
				if ($backRef->clipObj->currentMode() === 'copy') {
					$parkItem[1] = $GLOBALS['LANG']->sL('LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tx_gridelements_clickmenu_pastereference');
					$parkItem[3] = preg_replace('/formToken/', 'reference=1&formToken', $parkItem[3]);
					$menuItems['pastereference'] = $parkItem;
				}
			}
		}

		return $menuItems;
	}

}
