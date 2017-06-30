<?php
namespace GridElementsTeam\Gridelements\Hooks;

/***************************************************************
 *  Copyright notice
 *  (c) 2013 Jo Hasenau <info@cybercraft.de>, Tobias Ferger <tobi@tt36.de>
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

use TYPO3\CMS\Backend\Clipboard\Clipboard;
use TYPO3\CMS\Backend\Controller\PageLayoutController;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayoutView;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Recordlist\RecordList;

/**
 * Class/Function which adds the necessary ExtJS and pure JS stuff for the grid elements.
 *
 * @author Jo Hasenau <info@cybercraft.de>, Tobias Ferger <tobi@tt36.de>
 * @package TYPO3
 * @subpackage tx_gridelements
 */
class PageRenderer implements SingletonInterface
{
    /**
     * wrapper function called by hook (\TYPO3\CMS\Core\Page\PageRenderer->render-preProcess)
     *
     * @param array $parameters An array of available parameters
     * @param \TYPO3\CMS\Core\Page\PageRenderer $pageRenderer The parent object that triggered this hook
     */
    public function addJSCSS(array $parameters, \TYPO3\CMS\Core\Page\PageRenderer $pageRenderer)
    {
        $pageRenderer->addCssFile(ExtensionManagementUtility::siteRelPath('gridelements') . 'Resources/Public/Backend/Css/Skin/t3skin_override.css');
        if (get_class($GLOBALS['SOBE']) === RecordList::class || is_subclass_of($GLOBALS['SOBE'], RecordList::class)) {
            $pageRenderer->loadRequireJsModule('TYPO3/CMS/Gridelements/GridElementsOnReady');
            return;
        }
        if (get_class($GLOBALS['SOBE']) === PageLayoutController::class || is_subclass_of($GLOBALS['SOBE'], PageLayoutController::class)) {
            $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
            // $pageRenderer->loadRequireJsModule('TYPO3/CMS/Gridelements/GridElementsOnReady');
            $pageRenderer->loadRequireJsModule('TYPO3/CMS/Gridelements/GridElementsDragDrop');
            // $pageRenderer->loadRequireJsModule('TYPO3/CMS/Gridelements/GridElementsDragInWizard');

            /** @var Clipboard $clipObj */
            $clipObj = GeneralUtility::makeInstance(Clipboard::class); // Start clipboard
            $clipObj->initializeClipboard();
            $clipObj->lockToNormal();

            if (!$pageRenderer->getCharSet()) {
                $pageRenderer->setCharSet($GLOBALS['LANG']->charSet ? $GLOBALS['LANG']->charSet : 'utf-8');
            }

            // pull locallang_db.xml to JS side - only the tx_gridelements_js-prefixed keys
            $pageRenderer->addInlineLanguageLabelFile('EXT:gridelements/Resources/Private/Language/locallang_db.xml', 'tx_gridelements_js');

            $pAddExtOnReadyCode = '
                TYPO3.l10n = {
                    localize: function(langKey){
                        return TYPO3.lang[langKey];
                    }
                }
            ';

            $allowedContentTypesClassesByColPos = array();
            $allowedGridTypesClassesByColPos = array();
            $id = (int)GeneralUtility::_GP('id');
            $layoutSetup = GeneralUtility::callUserFunction(BackendLayoutView::class . '->getSelectedBackendLayout', $id, $this);
            if (is_array($layoutSetup) && !empty($layoutSetup['__config']['backend_layout.']['rows.'])) {
                foreach ($layoutSetup['__config']['backend_layout.']['rows.'] as $rows) {
                    foreach ($rows as $row) {
                        if (!empty($layoutSetup['__config']['backend_layout.']['rows.'])) {
                            foreach ($row as $col) {
                                $classes = '';
                                $gridClasses = '';
                                if ($col['allowed']) {
                                    $allowed = explode(',', $col['allowed']);
                                    foreach ($allowed as $contentTypes) {
                                        $contentTypes = trim($contentTypes);
                                        if ($contentTypes === '*') {
                                            $classes = 't3-allow-all';
                                            break;
                                        } else {
                                            $contentTypes = explode(',', $contentTypes);
                                            foreach ($contentTypes as $contentType) {
                                                $classes .= 't3-allow-' . $contentType . ' ';
                                            }
                                        }
                                    }
                                } else {
                                    $classes = 't3-allow-all';
                                }
                                if ($col['allowedGridTypes']) {
                                    $allowedGridTypes = explode(',', $col['allowedGridTypes']);
                                    $classes .= 't3-allow-gridelements_pi1 ';
                                    foreach ($allowedGridTypes as $gridTypes) {
                                        $gridTypes = trim($gridTypes);
                                        if ($gridTypes !== '*') {
                                            if (empty($gridClasses)) {
                                                $gridClasses .= 't3-allow-gridtype ';
                                            }
                                            $gridTypes = explode(',', $gridTypes);
                                            foreach ($gridTypes as $gridType) {
                                                $gridClasses .= 't3-allow-gridtype-' . $gridType . ' ';
                                            }
                                        }
                                    }
                                    if ($classes !== 't3-allow-all' && !empty($gridClasses)) {
                                        $classes .= 't3-allow-gridelements_pi1 ';
                                    }
                                }
                                $allowedContentTypesClassesByColPos[$col['colPos']] .= ' ' . trim($classes);
                                $allowedGridTypesClassesByColPos[$col['colPos']] .= ' ' . trim($gridClasses);
                            }
                        }
                    }
                }
            }

            // add Ext.onReady() code from file
            $pAddExtOnReadyCode .= "
            top.pageColumnsAllowedCTypes = " . json_encode($allowedContentTypesClassesByColPos) . ";
            top.pageColumnsAllowedGridTypes = " . json_encode($allowedGridTypesClassesByColPos) . ";
            top.pasteReferenceAllowed = " . ($this->getBackendUser()->checkAuthMode('tt_content', 'CType', 'shortcut', $GLOBALS['TYPO3_CONF_VARS']['BE']['explicitADmode']) ? 'true' : 'false') . ";
            top.skipDraggableDetails = " . ($this->getBackendUser()->uc['dragAndDropHideNewElementWizardInfoOverlay'] ? 'true' : 'false') . ";
            top.backPath = '" . $GLOBALS['BACK_PATH'] . "';
            top.browserUrl = '" . BackendUtility::getModuleUrl('wizard_element_browser') . "'";

            $pAddExtOnReadyCode .= "
                    top.copyFromAnotherPageLinkTemplate = " . json_encode('<a class="t3js-paste-new btn btn-default" title="' . $this->getLanguageService()->sL('LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tx_gridelements_js.copyfrompage') . '">' . $iconFactory->getIcon('actions-insert-reference', Icon::SIZE_SMALL)->render() . '</a>') . ";";

            $pageRenderer->addJsInlineCode('gridelementsExtOnReady', $pAddExtOnReadyCode);
        }
    }

    /**
     * Gets the current backend user.
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
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    public function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
