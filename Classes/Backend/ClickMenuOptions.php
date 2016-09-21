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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Type\Bitmask\JsConfirmation;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Class/Function which
 *
 * @author Jo Hasenau <info@cybercraft.de>
 * @package TYPO3
 * @subpackage tx_gridelements
 */
class ClickMenuOptions implements SingletonInterface
{
    /**
     * @var LanguageService
     */
    protected $languageService;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * Main method
     *
     * @param ClickMenu $backRef
     * @param array $menuItems
     * @param string $table
     * @param int $uid
     *
     * @return array
     */
    public function main(ClickMenu $backRef, array $menuItems, $table = '', $uid = 0)
    {
        if ($table !== 'tt_content') {
            return $menuItems;
        }

        $this->setLanguageService($GLOBALS['LANG']);

        // add "paste reference after" if user is allowed to use CType shortcut
        if ($menuItems['pasteafter'] && $this->getBackendUser()->checkAuthMode('tt_content', 'CType', 'shortcut', $GLOBALS['TYPO3_CONF_VARS']['BE']['explicitADmode'])) {
            unset($menuItems['pasteafter']);
            $selItem = $backRef->clipObj->getSelectedRecord();
            $targetItem = BackendUtility::getRecordRaw('tt_content', 'uid = ' . $uid, 'colPos,tx_gridelements_container,tx_gridelements_columns');
            $elInfo = array(
                GeneralUtility::fixed_lgd_cs($selItem['_RECORD_TITLE'], $this->getBackendUser()->uc['titleLen']),
                GeneralUtility::fixed_lgd_cs(BackendUtility::getRecordTitle($table, $backRef->rec), $this->getBackendUser()->uc['titleLen']),
                $backRef->clipObj->currentMode()
            );
            $menuItems['pasteafter'] = $this->DB_paste($backRef, $table, -$uid, 'after', $elInfo, $targetItem, false);
            if ($backRef->clipObj->currentMode() === 'copy') {
                $menuItems['pastereference'] = $this->DB_paste($backRef, $table, -$uid, 'after', $elInfo, $targetItem, true);
            }

        }

        return $menuItems;
    }

    /**
     * Adding CM element for Clipboard "paste into"/"paste after"
     * NOTICE: $table and $uid should follow the special syntax for paste, see clipboard-class :: pasteUrl();
     *
     * @param ClickMenu $backRef
     * @param string $table Table name
     * @param int $uid UID for the current record. NOTICE: Special syntax!
     * @param string $type Type "into" or "after
     * @param array $elInfo Contains instructions about whether to copy or cut an element.
     * @param array $targetItem
     * @param bool $reference
     *
     * @return array Item array, element in $menuItems
     */
    public function DB_paste(ClickMenu $backRef, $table, $uid, $type, array $elInfo, array  $targetItem, $reference)
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $updateArray = array(
            'colPos' => $targetItem['colPos'],
            'tx_gridelements_container' => (int)$targetItem['tx_gridelements_container'],
            'tx_gridelements_columns' => (int)$targetItem['tx_gridelements_columns']
        );
        $loc = 'top.content.list_frame';
        if ($this->getBackendUser()->jsConfirmation(JsConfirmation::COPY_MOVE_PASTE)) {
            $conf = $loc . ' && confirm(' . GeneralUtility::quoteJSvalue(
                sprintf($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:mess.' . ($elInfo[2] === 'copy' ? 'copy' : 'move') . '_' . $type), $elInfo[0], $elInfo[1])
                ) . ')';
        } else {
            $conf = $loc;
        }
        $editOnClick = 'if(' . $conf . '){' . $loc . '.location.href='
                       . GeneralUtility::quoteJSvalue($backRef->clipObj->pasteUrl($table, $uid, 0, $updateArray)
                       . ($reference ? '&reference=1' : '') . '&redirect=') . '+top.rawurlencode('
                       . $backRef->frameLocation(($loc . '.document')) . '.pathname+'
                       . $backRef->frameLocation(($loc . '.document')) . '.search);}';

        return $backRef->linkItem(
            $reference
                ? $this->getLanguageService()->sL('LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tx_gridelements_ClickMenu_pastereference')
                : $backRef->label('paste' . $type),
            $this->iconFactory->getIcon('actions-document-paste-' . $type, Icon::SIZE_SMALL)->render(),
            $editOnClick . 'return false;'
        );
    }

    /**
     * Gets the current backend user
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    public function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * getter for databaseConnection
     *
     * @return LanguageService $languageService
     */
    public function getLanguageService()
    {
        return $this->languageService;
    }

    /**
     * setter for databaseConnection object
     *
     * @param LanguageService $languageService
     */
    public function setLanguageService(LanguageService $languageService)
    {
        $this->languageService = $languageService;
    }
}
