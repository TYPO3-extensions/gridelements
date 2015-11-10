<?php
namespace GridElementsTeam\Gridelements\Backend\ItemsProcFuncs;

use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Class/Function which manipulates the item-array for table/field tt_content CType.
 *
 * @author         Jo Hasenau <info@cybercraft.de>
 * @package        TYPO3
 * @subpackage     tx_gridelements
 */
class SysLanguageUidList extends AbstractItemsProcFunc {

	/**
	 * ItemProcFunc for CType items
	 *
	 * @param    array $params : The array of parameters that is used to render the item list
	 *
	 * @return    void
	 */
	public function itemsProcFunc(&$params) {
		if ((int)$params['row']['pid'] > 0 && (int)$params['row']['tx_gridelements_container'] > 0) {
			$this->checkForAllowedLanguages($params['items'], $params['row']['tx_gridelements_container']);
		}
	}

	/**
	 * Checks if a language is allowed in this particular container - only this one container defines the allowed languages regardless of any parent
	 *
	 * @param    array $items : The items of the current language list
	 * @param    integer $gridContainerId : The ID of the current container
	 *
	 * @return   void
	 */
	public function checkForAllowedLanguages(&$items, $gridContainerId) {
		if ((int)$gridContainerId > 0) {
			$parentContainer = BackendUtility::getRecordWSOL('tt_content', $gridContainerId);
			if (is_array($items) && count($items) && (int)$parentContainer['uid'] > 0) {
				foreach ($items as $item => $valueArray) {
					if ((int)$parentContainer['sys_language_uid'] > -1 && (int)$valueArray[1] !== (int)$parentContainer['sys_language_uid']) {
						unset($items[$item]);
					}
				}
			};
		}
	}
}
