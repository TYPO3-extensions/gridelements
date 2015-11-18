<?php
namespace GridElementsTeam\Gridelements\Backend\ItemsProcFuncs;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Jo Hasenau <info@cybercraft.de>
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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class/Function which manipulates the item-array for table/field tt_content colPos.
 *
 * @author         Jo Hasenau <info@cybercraft.de>
 * @package        TYPO3
 * @subpackage     tx_gridelements
 */
class ColPosList extends AbstractItemsProcFunc {

	/**
	 * ItemProcFunc for colpos items
	 *
	 * @param    array $params : The array of parameters that is used to render the item list
	 *
	 * @return    void
	 */
	public function itemsProcFunc(&$params) {
		if ($params['row']['pid'] > 0) {
			$params['items'] = $this->addColPosListLayoutItems($params['row']['pid'], $params['items'], $params['row']['CType']);
		} else {
			// negative uid_pid values indicate that the element has been inserted after an existing element
			// so there is no pid to get the backendLayout for and we have to get that first
			$existingElement = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('pid, CType', 'tt_content', 'uid=' . -((int)$params['row']['pid']));
			if ($existingElement['pid'] > 0) {
				$params['items'] = $this->addColPosListLayoutItems($existingElement['pid'], $params['items'], $existingElement['CType']);
			}
		}
	}

	/**
	 * Adds items to a colpos list
	 *
	 * @param   integer $pageId : The uid of the page we are currently working on
	 * @param   array $items : The array of items before the action
	 * @param   string $CType : The content type of the item holding the colPosList
	 *
	 * @return  array   $items: The ready made array of items
	 */
	protected function addColPosListLayoutItems($pageId, array $items, $CType = '') {
		$layout = $this->getSelectedBackendLayout($pageId);

		if ($layout) {
			if ($CType !== '' && count($layout['__items']) > 0) {
				foreach ($layout['__items'] as $itemKey => $itemArray) {
					if ($itemArray[3] !== '' && !GeneralUtility::inList($itemArray[3], $CType) && !GeneralUtility::inList($itemArray[3], '*')) {
						unset($layout['__items'][$itemKey]);
					}
				}
			}
			if ($layout['__items'] && count($layout['__items']) > 0) {
				$items = $layout['__items'];
			}
		}

		$items[] = array(
				$GLOBALS['LANG']->sL('LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tt_content.tx_gridelements_container'),
				'-1',
				NULL,
				NULL
		);
		return $items;
	}
}
