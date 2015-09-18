<?php
namespace GridElementsTeam\Gridelements\Helper;

/**
 * Gridelements helper class
 *
 * @author      Dirk Hoffmann <dirk-hoffmann@telekom.de>
 * @package     TYPO3
 * @subpackage  tx_gridelements
 */

class Helper {

	/**
	 * Local instance of the helper
	 *
	 * @var Helper
	 */
	protected static $instance = NULL;

	/**
	 * Get instance from the class.
	 *
	 * @static
	 * @return    Helper
	 */
	public static function getInstance() {
		if (!self::$instance instanceof Helper) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @param string $table
	 * @param int $uid
	 * @param string $sortingField
	 * @param int $sortRev
	 *
	 * @return array
	 */
	public function getChildren($table = '', $uid = 0, $sortingField = '', $sortRev = 0) {
		$retVal = array();

		if (trim($table) === 'tt_content' && $uid > 0) {

			$children = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'tt_content', 'tx_gridelements_container = ' . $uid . ' AND deleted = 0');

			foreach ($children as $child) {
				if (trim($sortingField) && isset($child[$sortingField]) && $sortingField !== 'sorting') {
					$sortField = $child[$sortingField];
				} else {
					$sortField = sprintf('%1$011d', $child['sorting']);
				}
				$sortKey = sprintf('%1$011d', $child['tx_gridelements_columns']) . '.' . $sortField . ':' . sprintf('%1$011d', $child['uid']);

				$retVal[$sortKey] = $child;
			}
		}

		ksort($retVal);
		if ($sortRev) {
			$retVal = array_reverse($retVal);
		}

		return $retVal;
	}

	/**
	 * Gets the uid of a record depending on the current context.
	 * If in workspace mode, the overlay uid is used (if available),
	 * otherwise the regular uid is used.
	 *
	 * @param array $record Overlayed record data
	 *
	 * @return integer
	 */
	public function getSpecificIds(array $record) {
		$specificIds = array();
		$specificIds['uid'] = (int)$record['uid'];
		$specificIds['pid'] = (int)$record['pid'];

		if ($this->getBackendUser()->workspace > 0 && !empty($record['t3ver_oid'])) {
			$specificIds['uid'] = (int)$record['t3ver_oid'];
			$specificIds['pid'] = -1;
		}

		return $specificIds;
	}

	/**
	 * Gets the current backend user.
	 *
	 * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 */
	public function getBackendUser() {
		return $GLOBALS['BE_USER'];
	}
}
