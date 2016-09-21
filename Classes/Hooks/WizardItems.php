<?php
namespace GridElementsTeam\Gridelements\Hooks;

/***************************************************************
 *  Copyright notice
 *  (c) 2013 Jo Hasenau <info@cybercraft.de>
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

use GridElementsTeam\Gridelements\Backend\LayoutSetup;
use GridElementsTeam\Gridelements\Helper\Helper;
use TYPO3\CMS\Backend\Controller\ContentElement\NewContentElementController;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Wizard\NewContentElementWizardHookInterface;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Class/Function which manipulates the rendering of items within the new content element wizard
 *
 * @author Jo Hasenau <info@cybercraft.de>, Tobias Ferger <tobi@tt36.de>
 * @package TYPO3
 * @subpackage tx_gridelements
 */
class WizardItems implements NewContentElementWizardHookInterface
{
    /**
     * @var LayoutSetup
     */
    protected $layoutSetup;

    /**
     * inject layout setup
     *
     * @param LayoutSetup $layoutSetup
     */
    public function injectLayoutSetup(LayoutSetup $layoutSetup)
    {
        $this->layoutSetup = $layoutSetup;
    }

    /**
     * initializes this class
     *
     * @param int $pageUid
     */
    public function init($pageUid)
    {
        if (!$this->layoutSetup instanceof LayoutSetup) {
            if ($pageUid < 0) {
                $pageUid = Helper::getInstance()->getPidFromNegativeUid($pageUid);
            }
            $this->layoutSetup = GeneralUtility::makeInstance(LayoutSetup::class)->init($pageUid);
        }
    }

    /**
     * Processes the items of the new content element wizard
     * and inserts necessary default values for items created within a grid
     *
     * @param array $wizardItems The array containing the current status of the wizard item list before rendering
     * @param NewContentElementController $parentObject The parent object that triggered this hook
     */
    public function manipulateWizardItems(&$wizardItems, &$parentObject)
    {
        if (GeneralUtility::inList($GLOBALS['BE_USER']->groupData['explicit_allowdeny'], 'tt_content:CType:gridelements_pi1:DENY')) {
            return;
        }
        $pageID = $parentObject->id;
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
            $allowed = null;
        }

        if (empty($allowed) || isset($allowed['gridelements_pi1'])) {
            $allowedGridTypes = array_flip(GeneralUtility::trimExplode(',', GeneralUtility::_GP('tx_gridelements_allowed_grid_types'), true));
            $excludeLayouts = $this->getExcludeLayouts($container, $parentObject);

            $gridItems = $this->layoutSetup->getLayoutWizardItems($parentObject->colPos, $excludeLayouts, $allowedGridTypes);
            $this->addGridItemsToWizard($gridItems, $wizardItems);
        }

        $this->addGridValuesToWizardItems($wizardItems, $container, $column);

        $this->removeEmptyHeadersFromWizard($wizardItems);
    }

    /**
     * remove unnecessary headers from wizard items
     *
     * @param array $wizardItems
     */
    public function removeEmptyHeadersFromWizard(array &$wizardItems)
    {
        $headersWithElements = array();
        foreach ($wizardItems as $key => $wizardItem) {
            $isElement = strpos($key, '_', 1);
            if ($isElement) {
                $headersWithElements[substr($key, 0, $isElement)] = true;
            }
        }
        foreach ($wizardItems as $key => $wizardItem) {
            if ($wizardItems[$key]['header']) {
                if (!isset($headersWithElements[$key])) {
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
     */
    public function removeDisallowedWizardItems(array $allowed, array &$wizardItems)
    {
        if (!isset($allowed['*'])) {
            foreach ($wizardItems as $key => $wizardItem) {
                if (!$wizardItems[$key]['header']) {
                    if (!empty($allowed) && !isset($allowed[$wizardItems[$key]['tt_content_defValues']['CType']])) {
                        unset($wizardItems[$key]);
                    }
                }
            }
        }
    }

    /**
     * retrieve layouts to exclude from pageTSconfig
     *
     * @param int $container
     * @param NewContentElementController $parentObject The parent object that triggered this hook
     *
     * @return array
     */
    public function getExcludeLayouts($container, NewContentElementController $parentObject)
    {
        $excludeLayouts = 0;
        $excludeArray = array();

        $pageID = $parentObject->id;

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

        if (!empty($excludeArray)) {
            $excludeLayouts = implode(',', $excludeArray);
        }

        return $excludeLayouts;
    }

    /**
     * add gridelements to wizard items
     *
     * @param array $gridItems
     * @param array $wizardItems
     */
    public function addGridItemsToWizard(array &$gridItems, array &$wizardItems)
    {
        if (empty($gridItems)) {
            return;
        }
        // create gridelements node
        $wizardItems['gridelements'] = array();

        // set header label
        $wizardItems['gridelements']['header'] = $this->getLanguageService()->sL(
            'LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xml:tx_gridelements_backend_layout_wizard_label'
        );

        $iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);

        // traverse the gridelements and create wizard item for each gridelement
        foreach ($gridItems as $key => $item) {
            $largeIcon = '';
            if (empty($item['iconIdentifierLarge'])) {
                if (is_array($item['icon']) && isset($item['icon'][1])) {
                    $item['iconIdentifierLarge'] = 'gridelements-large-' . $key;
                    $largeIcon = $item['icon'][1];
                    if (StringUtility::beginsWith($largeIcon, '../typo3conf/ext/')) {
                        $largeIcon = str_replace('../typo3conf/ext/', 'EXT:', $largeIcon);
                    }
                    if (StringUtility::beginsWith($largeIcon, '../uploads/tx_gridelements/')) {
                        $largeIcon = str_replace('../', '', $largeIcon);
                    } else if (!StringUtility::beginsWith($largeIcon, 'EXT:') && strpos($largeIcon,
                            '/') === false
                    ) {
                        $largeIcon = GeneralUtility::resolveBackPath($item['icon'][1]);
                    }
                    if (!empty($icon)) {
                        if (StringUtility::endsWith($largeIcon, '.svg')) {
                            $iconRegistry->registerIcon($item['iconIdentifierLarge'], SvgIconProvider::class, array(
                                'source' => $largeIcon
                            ));
                        } else {
                            $iconRegistry->registerIcon($item['iconIdentifierLarge'], BitmapIconProvider::class,
                                array(
                                    'source' => $largeIcon
                                ));
                        }
                    }
                } else {
                    $item['iconIdentifierLarge'] = 'gridelements-large-' . $key;
                    $iconRegistry->registerIcon($item['iconIdentifierLarge'], SvgIconProvider::class, array(
                        'source' => 'EXT:gridelements/Resources/Public/Icons/gridelements.svg'
                    ));
                }
            }
            $itemIdentifier = $item['alias'] ? $item['alias'] : $item['uid'];
            $wizardItems['gridelements_' . $itemIdentifier] = array(
                'title' => $item['title'],
                'description' => $item['description'],
                'params' => ($largeIcon ? '&largeIconImage=' . $largeIcon : '')
                            . '&defVals[tt_content][CType]=gridelements_pi1&defVals[tt_content][tx_gridelements_backend_layout]=' . $item['uid']
                            . ($item['tll'] ? '&isTopLevelLayout' : ''),
                'tt_content_defValues' => array(
                    'CType' => 'gridelements_pi1',
                    'tx_gridelements_backend_layout' => $item['uid']
                ),
            );
            $icon = '';
            if ($item['iconIdentifier']) {
                $wizardItems['gridelements_' . $itemIdentifier]['iconIdentifier'] = $item['iconIdentifier'];
            } elseif (is_array($item['icon']) && isset($item['icon'][0])) {
                $item['iconIdentifier'] = 'gridelements-' . $key;
                $icon = $item['icon'][0];
                if (StringUtility::beginsWith($icon, '../typo3conf/ext/')) {
                    $icon = str_replace('../typo3conf/ext/', 'EXT:', $icon);
                }
                if (StringUtility::beginsWith($icon, '../uploads/tx_gridelements/')) {
                    $icon = str_replace('../', '', $icon);
                } else if (!StringUtility::beginsWith($icon, 'EXT:') && strpos($icon, '/') !== false) {
                    $icon = GeneralUtility::resolveBackPath($item['icon'][0]);
                }
                if (StringUtility::endsWith($icon, '.svg')) {
                    $iconRegistry->registerIcon($item['iconIdentifier'], SvgIconProvider::class, array(
                        'source' => $icon
                    ));
                } else {
                    $iconRegistry->registerIcon($item['iconIdentifier'], BitmapIconProvider::class, array(
                        'source' => $icon
                    ));
                }
            } else {
                $item['iconIdentifier'] = 'gridelements-' . $key;
                $iconRegistry->registerIcon($item['iconIdentifier'], SvgIconProvider::class, array(
                    'source' => 'EXT:gridelements/Resources/Public/Icons/gridelements.svg'
                ));
            }
            if ($icon && !isset($wizardItems['gridelements_' . $itemIdentifier]['iconIdentifier'])) {
                $wizardItems['gridelements_' . $itemIdentifier]['iconIdentifier'] = 'gridelements-' . $key;
            } else if (!isset($wizardItems['gridelements_' . $itemIdentifier]['iconIdentifier'])) {
                $wizardItems['gridelements_' . $itemIdentifier]['iconIdentifier'] = 'gridelements-default';
            }
        }
    }

    /**
     * initializes wizard items
     *
     * @param array $wizardItems
     * @param int $container
     * @param int $column
     */
    public function addGridValuesToWizardItems(array &$wizardItems, $container, $column)
    {
        foreach ($wizardItems as $key => $wizardItem) {
            if (!$wizardItems[$key]['header']) {
                if ($container !== 0) {
                    $wizardItems[$key]['tt_content_defValues']['tx_gridelements_container'] = (int)$container;
                    $wizardItems[$key]['params'] .= '&defVals[tt_content][tx_gridelements_container]=' . (int)$container;
                }
                $wizardItems[$key]['tt_content_defValues']['tx_gridelements_columns'] = (int)$column;
                $wizardItems[$key]['params'] .= '&defVals[tt_content][tx_gridelements_columns]=' . (int)$column;
            }
        }
    }

    /**
     * @return LanguageService
     */
    public function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return DatabaseConnection
     */
    public function getDatabase()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
