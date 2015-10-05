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
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class/Function which adds the necessary ExtJS and pure JS stuff for the grid elements.
 * @author Jo Hasenau <info@cybercraft.de>, Tobias Ferger <tobi@tt36.de>
 * @package TYPO3
 * @subpackage tx_gridelements
 */
class PageRenderer {

	/**
	 * wrapper function called by hook (\TYPO3\CMS\Core\Page\PageRenderer->render-preProcess)
	 * @param array $parameters : An array of available parameters
	 * @param \TYPO3\CMS\Core\Page\PageRenderer $pageRenderer : The parent object that triggered this hook
	 * @return void
	 */
	public function addJSCSS($parameters, &$pageRenderer) {
		$pageRenderer->loadRequireJsModule('TYPO3/CMS/Gridelements/GridElementsOnReady');
		$pageRenderer->loadRequireJsModule('TYPO3/CMS/Gridelements/GridElementsDragDrop');
		$pageRenderer->loadRequireJsModule('TYPO3/CMS/Gridelements/GridElementsDragInWizard');

		if (!empty($parameters['jsFiles'])) {

			if (method_exists($GLOBALS['SOBE']->doc, 'issueCommand')) {
				/** @var Clipboard $clipObj */
				$clipObj = GeneralUtility::makeInstance(Clipboard::class); // Start clipboard
				$clipObj->initializeClipboard();

				$pasteURL = '';
				if (isset($clipObj->clipData['normal']['el']) && strpos(key($clipObj->clipData['normal']['el']), 'tt_content') !== false) {
					$pasteURL = str_replace('&amp;', '&', $clipObj->pasteUrl('tt_content', 'DD_PASTE_UID', 0));
				}

				// add JavaScript library
				// $pageRenderer->addJsFile($GLOBALS['BACK_PATH'] . ExtensionManagementUtility::extRelPath('gridelements') . 'Resources/Public/Backend/JavaScript/dbNewContentElWizardFixDTM.js', $type = 'text/javascript', $compress = TRUE, $forceOnTop = FALSE, $allWrap = '');

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
				$layoutSetup = GeneralUtility::callUserFunction('TYPO3\\CMS\\Backend\\View\\BackendLayoutView->getSelectedBackendLayout', intval(GeneralUtility::_GP('id')), $this);
				if (is_array($layoutSetup) && !empty($layoutSetup['__config']['backend_layout.']['rows.'])) {
					foreach ($layoutSetup['__config']['backend_layout.']['rows.'] as $rows) {
						foreach ($rows as $row) {
							if (!empty($layoutSetup['__config']['backend_layout.']['rows.'])) {
								foreach ($row as $col) {
									$classes = '';
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
									$allowedContentTypesClassesByColPos[$col['colPos']] .= ' ' . trim($classes);
								}
							}
						}
					}
				}

				// add Ext.onReady() code from file
				$pAddExtOnReadyCode .= "
				top.pageColumnsAllowedCTypes = " . json_encode($allowedContentTypesClassesByColPos) . ";
				top.skipDraggableDetails = " . ($GLOBALS['BE_USER']->uc['dragAndDropHideNewElementWizardInfoOverlay'] ? 'true;' : 'false;') . ";
				top.geSprites = {
				copyfrompage: '" . IconUtility::getSpriteIconClasses('extensions-gridelements-copyfrompage') . "',
					pastecopy: '" . IconUtility::getSpriteIconClasses('extensions-gridelements-pastecopy') . "',
					pasteref: '" . IconUtility::getSpriteIconClasses('extensions-gridelements-pasteref') . "'
				};
				top.backPath = '" . $GLOBALS['BACK_PATH'] . "'";

				$pageRenderer->addJsInlineCode(// add some more JS here
					'gridelementsExtOnReady', $pAddExtOnReadyCode);
			}
		}
	}

	/**
	 * method that adds CSS files within the page renderer
	 * @param array $parameters : An array of available parameters while adding CSS to the page renderer
	 * @param \TYPO3\CMS\Core\Page\PageRenderer $pageRenderer : The parent object that triggered this hook
	 * @return void
	 */
	protected function addCSS($parameters, &$pageRenderer) {
		if (count($parameters['cssFiles'])) {
			// get configuration
			$configurationArray = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['gridelements']);
			$filename = $configurationArray['additionalStylesheet'];
			if ($filename) {
				// evaluate filename
				if (substr($filename, 0, 4) === 'EXT:') { // extension
					list($extKey, $local) = explode('/', substr($filename, 4), 2);
					$filename = '';
					if (strcmp($extKey, '') && ExtensionManagementUtility::isLoaded($extKey) && strcmp($local, '')) {
						$filename = ExtensionManagementUtility::extRelPath($extKey) . $local;
					}
				}
				$pageRenderer->addCssFile($filename, 'stylesheet', 'screen');
			}
		}
	}
}
