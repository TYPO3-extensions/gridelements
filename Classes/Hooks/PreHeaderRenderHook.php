<?php
namespace GridElementsTeam\Gridelements\Hooks;

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Class PreHeaderRenderHook
 *
 * @package GridElementsTeam\Gridelements\Hooks
 */
class PreHeaderRenderHook {

	/**
	 * @param array $arg
	 */
	function main($arg) {
		/** @var $pagerenderer \TYPO3\CMS\Core\Page\PageRenderer */
		$pagerenderer = $arg['pageRenderer'];
		$pagerenderer->addCssFile($GLOBALS['BACK_PATH'] . ExtensionManagementUtility::extRelPath('gridelements') . 'Resources/Public/Backend/Css/Skin/t3skin_override.css');
	}
}
