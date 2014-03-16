<?php

class Tx_Gridelements_Hooks_PreHeaderRenderHook {
	function main($arg) {
		/** @var $pagerenderer t3lib_PageRenderer */
		$pagerenderer = $arg['pageRenderer'];
		$pagerenderer->addCssFile(t3lib_extMgm::extRelPath('gridelements') . 'Resources/Public/Backend/Css/Skin/t3skin_override.css');
	}
}
