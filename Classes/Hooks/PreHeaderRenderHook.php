<?php
namespace GridElementsTeam\Gridelements\Hooks;

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

class PreHeaderRenderHook {

	function main($arg) {
		/** @var $pagerenderer \TYPO3\CMS\Core\Page\PageRenderer */
		$pagerenderer = $arg['pageRenderer'];
		$pagerenderer->addCssFile(ExtensionManagementUtility::extRelPath('gridelements') . 'Resources/Public/Backend/Css/Skin/t3skin_override.css');
	}
}
