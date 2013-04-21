<?php
namespace GridElementsTeam\Gridelements\Hooks;

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

/**
 * Class/Function which offers TCE main hook functions.
 *
 * @author		Jo Hasenau <info@cybercraft.de>
 * @package		TYPO3
 * @subpackage	tx_gridelements
 */

class FormEngine {

	/**
	 * @var \GridElementsTeam\Gridelements\Backend\LayoutSetup
	 */
	protected $layoutSetup;

	/**
	 * inject layout setup
	 *
	 * @param \GridElementsTeam\Gridelements\Backend\LayoutSetup $layoutSetup
	 * @return void
	 */
	public function injectLayoutSetup(\GridElementsTeam\Gridelements\Backend\LayoutSetup $layoutSetup) {
		$this->layoutSetup = $layoutSetup;
	}

	/**
	 * initializes this class
	 *
	 * @param integer $pageUid
	 * @return void
	 */
	public function init($pageUid) {
		if (!$this->layoutSetup instanceof \GridElementsTeam\Gridelements\Backend\LayoutSetup) {
			$this->injectLayoutSetup(
				\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('GridElementsTeam\Gridelements\Backend\LayoutSetup')
					->init($pageUid)
			);
		}
	}

	/**
	 * Function to set the colPos of an element depending on
	 * whether it is a child of a parent container or not
	 * changes are applied to the FieldArray of the PA by reference
	 *
	 * @param	string Table name of record
	 * @param	string Fieldname to render
	 * @param	array The record
	 * @param	array parameters array containing a lot of stuff. Value by Reference!
	 * @return void
	 *
	 */
	public function getSingleField_beforeRender($table, $field, $row, &$PA) {
		if ($field == 'pi_flexform' && $row['CType'] == 'gridelements_pi1' && $row['tx_gridelements_backend_layout']) {
			$this->init($row['pid']);
			$PA['fieldConf']['config']['ds']['*,gridelements_pi1'] = $this->layoutSetup->getFlexformConfiguration($row['tx_gridelements_backend_layout']);
		}
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/Classes/Hooks/FormEngine.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/gridelements/Classes/Hooks/FormEngine.php']);
}
?>