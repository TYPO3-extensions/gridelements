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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class/Function which manipulates the rendering of items within the new content element wizard
 *
 * @author         Jo Hasenau <info@cybercraft.de>, Tobias Ferger <tobi@tt36.de>
 * @package        TYPO3
 * @subpackage     tx_gridelements
 */
class WizardItems implements \TYPO3\CMS\Backend\Wizard\NewContentElementWizardHookInterface {

	/**
	 * @var \GridElementsTeam\Gridelements\Backend\LayoutSetup
	 */
	protected $layoutSetup;

	/**
	 * inject layout setup
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
		if (!$this->layoutSetup instanceof \GridElementsTeam\Gridelements\Backend\LayoutSetup) {
			if ($pageUid < 0) {
				$triggerElement = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('pid', 'tt_content', 'uid = ' . -$pageUid);
				$pageUid = (int)$triggerElement['pid'];
			}
			$this->layoutSetup = GeneralUtility::makeInstance('GridElementsTeam\\Gridelements\\Backend\\LayoutSetup')
					->init($pageUid);
		}
	}

	/**
	 * Processes the items of the new content element wizard
	 * and inserts necessary default values for items created within a grid
	 *
	 * @param    array $wizardItems : The array containing the current status of the wizard item list before rendering
	 * @param    \db_new_content_el $parentObject : The parent object that triggered this hook
	 *
	 * @return void
	 */
	public function manipulateWizardItems(&$wizardItems, &$parentObject) {
		if (!GeneralUtility::inList($GLOBALS['BE_USER']->groupData['explicit_allowdeny'], 'tt_content:CType:gridelements_pi1:DENY')) {
			$pageID = $parentObject->pageinfo['uid'];
			$this->init($pageID);

			$container = (int)GeneralUtility::_GP('tx_gridelements_container');
			$column = (int)GeneralUtility::_GP('tx_gridelements_columns');
			$allowed_GP = GeneralUtility::_GP('tx_gridelements_allowed');
			if (!empty($allowed_GP)) {
				$allowed = array_flip(explode(',', $allowed_GP));
				$allowedGridTypes_GP = GeneralUtility::_GP('tx_gridelements_allowed_grid_types');
				if (!empty($allowedGridTypes_GP)) {
					$allowed['gridelements_pi1'] = 1;
				}
				$this->removeDisallowedWizardItems($allowed, $wizardItems);
			} else {
				$allowed = NULL;
			}

			if (empty($allowed) || isset($allowed['gridelements_pi1'])) {
				$allowedGridTypes = GeneralUtility::trimExplode(',', GeneralUtility::_GP('tx_gridelements_allowed_grid_types'), TRUE);
				$excludeLayouts = $this->getExcludeLayouts($container, $parentObject);

				$gridItems = $this->layoutSetup->getLayoutWizardItems($parentObject->colPos, $excludeLayouts, $allowedGridTypes);
				$this->addGridItemsToWizard($gridItems, $wizardItems);
			}

			$this->addGridValuesToWizardItems($wizardItems, $container, $column);

			$this->removeEmptyHeadersFromWizard($wizardItems);
		}
	}

	/**
	 * remove unneccessary headers from wizard items
	 *
	 * @param array $wizardItems
	 *
	 * @return void
	 */
	public function removeEmptyHeadersFromWizard(&$wizardItems) {
		$headersWithElements = array();
		foreach ($wizardItems as $key => $wizardItem) {
			$isElement = strpos($key, '_', 1);
			if ($isElement) {
				$headersWithElements[] = substr($key, 0, $isElement);
			}
		}
		foreach ($wizardItems as $key => $wizardItem) {
			if ($wizardItems[$key]['header']) {
				if (!in_array($key, $headersWithElements)) {
					unset($wizardItems[$key]);
				}
			}
		}
	}

	/**
	 * remove disallowed content elements from wizard items
	 *
	 * @param array $allowed
	 * @param array $wizardItems
	 *
	 * @return void
	 */
	public function removeDisallowedWizardItems($allowed, &$wizardItems) {
		if (!isset($allowed['*'])) {
			foreach ($wizardItems as $key => $wizardItem) {
				if (!$wizardItems[$key]['header']) {
					if (count($allowed) && !isset($allowed[$wizardItems[$key]['tt_content_defValues']['CType']])) {
						unset($wizardItems[$key]);
					}
				}
			}
		}
	}

	/**
	 * retrieve layouts to exclude from pagetsconfig
	 *
	 * @param integer $container
	 * @param    \db_new_content_el $parentObject : The parent object that triggered this hook
	 *
	 * @return array
	 */
	public function getExcludeLayouts($container, &$parentObject) {
		$excludeLayouts = 0;
		$excludeArray = array();

		$pageID = $parentObject->pageinfo['uid'];

		$TSconfig = BackendUtility::getPagesTSconfig($pageID);

		if ($container && $TSconfig['TCEFORM.']['tt_content.']['tx_gridelements_backend_layout.']['itemsProcFunc.']['topLevelLayouts']) {
			$excludeArray[] = trim($TSconfig['TCEFORM.']['tt_content.']['tx_gridelements_backend_layout.']['itemsProcFunc.']['topLevelLayouts']);
		}

		$excludeLayoutsTS = $TSconfig['TCEFORM.']['tt_content.']['tx_gridelements_backend_layout.']['itemsProcFunc.']['excludeLayouts'];

		if ($excludeLayoutsTS) {
			$excludeArray[] = trim($excludeLayoutsTS);
		}

		$userExcludeLayoutsTS = $TSconfig['TCEFORM.']['tt_content.']['tx_gridelements_backend_layout.']['itemsProcFunc.']['userExcludeLayouts'];

		if ($userExcludeLayoutsTS) {
			$excludeArray[] = trim($userExcludeLayoutsTS);
		}

		if (count($excludeArray)) {
			$excludeLayouts = implode(',', $excludeArray);
		}

		return $excludeLayouts;
	}

	/**
	 * add gridelements to wizard items
	 *
	 * @param array $gridItems
	 * @param array $wizardItems
	 *
	 * @return void
	 */
	public function addGridItemsToWizard(&$gridItems, &$wizardItems) {
		// we have grid elements to add
		if (count($gridItems)) {

			// create gridelements node
			$wizardItems['gridelements'] = array();

			// set header label
			$wizardItems['gridelements']['header'] = $GLOBALS['LANG']->sL('LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tx_gridelements_backend_layout_wizard_label');

			// traverse the gridelements and create wizard item for each gridelement
			foreach ($gridItems as $key => $item) {
				$wizardItems['gridelements_grid_' . $item['uid']] = array(
						'title' => $item['title'],
						'description' => $item['description'],
						'params' => ($item['icon'][1] ? '&largeIconImage=' . $item['icon'][1] : '') . '&defVals[tt_content][CType]=gridelements_pi1&defVals[tt_content][tx_gridelements_backend_layout]=' . $item['uid'] . ($item['tll'] ? '&isTopLevelLayout' : ''),
						'tt_content_defValues' => array(
								'CType' => 'gridelements_pi1',
								'tx_gridelements_backend_layout' => $item['uid']
						),
				);
				if ($item['icon'][0]) {
					$wizardItems['gridelements_grid_' . $item['uid']]['icon'] = $item['icon'][0];
				} else {
					$wizardItems['gridelements_grid_' . $item['uid']]['icon'] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('gridelements') . 'Resources/Public/Backend/Images/new_content_el.gif';
				}
				/*
				if ($container != 0) {
					$wizardItems['gridelements_grid_' . $item['uid']]['tx_gridelements_container'] = $container;
				}
				if ($column != 0) {
					$wizardItems['gridelements_grid_' . $item['uid']]['tx_gridelements_columns'] = $column;
				}
				*/
			}
		}
	}

	/**
	 * initializes wizard items
	 *
	 * @param array $wizardItems
	 * @param integer $container
	 * @param integer $column
	 *
	 * @return void
	 */
	public function addGridValuesToWizardItems(&$wizardItems, $container, $column) {
		foreach ($wizardItems as $key => $wizardItem) {
			if (!$wizardItems[$key]['header']) {
				if ($container !== 0) {
					$wizardItems[$key]['tt_content_defValues']['tx_gridelements_container'] = $container;
					$wizardItems[$key]['params'] .= '&defVals[tt_content][tx_gridelements_container]=' . $container;
				}
				if ($column !== 0) {
					$wizardItems[$key]['tt_content_defValues']['tx_gridelements_columns'] = $column;
					$wizardItems[$key]['params'] .= '&defVals[tt_content][tx_gridelements_columns]=' . $column;
				}
			}
		}
	}

}
