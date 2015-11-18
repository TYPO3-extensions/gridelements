<?php
namespace GridElementsTeam\Gridelements\Backend\ItemsProcFuncs;

use GridElementsTeam\Gridelements\Backend\LayoutSetup;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class/Function which manipulates the item-array for table/field tt_content CType.
 *
 * @author         Jo Hasenau <info@cybercraft.de>
 * @package        TYPO3
 * @subpackage     tx_gridelements
 */
class CTypeList extends AbstractItemsProcFunc {

	/**
	 * @var LayoutSetup
	 */
	protected $layoutSetup;

	/**
	 * injects layout setup
	 *
	 * @param \GridElementsTeam\Gridelements\Backend\LayoutSetup $layoutSetup
	 */
	public function injectLayoutSetup(\GridElementsTeam\Gridelements\Backend\LayoutSetup $layoutSetup) {
		$this->layoutSetup = $layoutSetup;
	}

	/**
	 * initializes this class
	 *
	 * @param int $pageUid
	 */
	public function init($pageUid) {
		if (!$this->layoutSetup instanceof LayoutSetup) {
			$this->layoutSetup = GeneralUtility::makeInstance('GridElementsTeam\\Gridelements\\Backend\\LayoutSetup')
					->init($pageUid);
		}
	}

	/**
	 * ItemProcFunc for CType items
	 *
	 * @param    array $params : The array of parameters that is used to render the item list
	 *
	 * @return    void
	 */
	public function itemsProcFunc(&$params) {
		if ((int)$params['row']['pid'] > 0) {
			$this->checkForAllowedCTypes($params['items'], $params['row']['pid'], $params['row']['colPos'], $params['row']['tx_gridelements_container'], $params['row']['tx_gridelements_columns']);
		} else {
			// negative uid_pid values indicate that the element has been inserted after an existing element
			// so there is no pid to get the backendLayout for and we have to get that first
			$existingElement = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('pid, CType, colPos, tx_gridelements_container, tx_gridelements_columns', 'tt_content', 'uid=' . -((int)$params['row']['pid']));
			if ((int)$existingElement['pid'] > 0) {
				$this->checkForAllowedCTypes($params['items'], $existingElement['pid'], $existingElement['colPos'], $existingElement['tx_gridelements_container'], $existingElement['tx_gridelements_columns']);
			}
		}
	}

	/**
	 * Checks if a CType is allowed in this particular page or grid column - only this one column defines the allowed CTypes regardless of any parent column
	 *
	 * @param    array $items : The items of the current CType list
	 * @param    integer $pid : The id of the page we are currhently working on
	 * @param    integer $pageColumn : The page column the element is a child of
	 * @param    integer $gridContainerId : The ID of the current container
	 * @param    integer $gridColumn : The grid column the element is a child of
	 *
	 * @return    array|null    $backendLayout: An array containing the data of the selected backend layout as well as a parsed version of the layout configuration
	 */
	public function checkForAllowedCTypes(&$items, $pid, $pageColumn, $gridContainerId, $gridColumn) {
		if ((int)$pageColumn >= 0 || (int)$pageColumn === -2) {
			$column = $pageColumn ? $pageColumn : 0;
			$backendLayout = $this->getSelectedBackendLayout($pid);
		} else {
			$this->init($pid);
			$column = $gridColumn ? $gridColumn : 0;
			$gridElement = $this->layoutSetup->cacheCurrentParent($gridContainerId, TRUE);
			$backendLayout = $this->layoutSetup->getLayoutSetup($gridElement['tx_gridelements_backend_layout']);
		}
		if (isset($backendLayout)) {
			foreach ($items as $key => $item) {
				if (!(GeneralUtility::inList($backendLayout['columns'][$column], $item[1]) || GeneralUtility::inList($backendLayout['columns'][$column], '*'))) {
					unset($items[$key]);
				}
			}
		}
	}
}
