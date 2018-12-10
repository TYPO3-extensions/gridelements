<?php

namespace GridElementsTeam\Gridelements\Hooks;

/***************************************************************
 *  Copyright notice
 *  (c) 2013 Jo Hasenau <info@cybercraft.de>, Dirk Hoffmann <hoffmann@vmd-jena.de>
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

use GridElementsTeam\Gridelements\Helper\Helper;
use GridElementsTeam\Gridelements\Xclass\DatabaseRecordList as DatabaseRecordListXclass;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;
use TYPO3\CMS\Recordlist\RecordList\RecordListHookInterface;

/**
 * Class/Function which offers TCE main hook functions.
 *
 * @author Jo Hasenau <info@cybercraft.de>
 * @author Dirk Hoffmann <hoffmann@vmd-jena.de>
 */
class DatabaseRecordList implements RecordListHookInterface, SingletonInterface
{
    /**
     * @var Iconfactory
     */
    protected $iconFactory;

    /**
     * @var LanguageService
     */
    protected $languageService;

    /**
     * DatabaseRecordList constructor.
     */
    public function __construct()
    {
        $this->setLanguageService($GLOBALS['LANG']);
    }

    /**
     * modifies Web>List clip icons (copy, cut, paste, etc.) of a displayed row
     *
     * @param string $table the current database table
     * @param array $row the current record row
     * @param array $cells the default clip-icons to get modified
     * @param DatabaseRecordList $parentObject Instance of calling object (by ref due to interface)
     *
     * @return array the modified clip-icons
     */
    public function makeClip($table, $row, $cells, &$parentObject)
    {
        if ($table === 'tt_content') {
            $cells['moveUp'] = '';
        }

        return $cells;
    }

    /**
     * modifies Web>List control icons of a displayed row
     *
     * @param string $table the current database table
     * @param array $row the current record row
     * @param array $cells the default control-icons to get modified
     * @param DatabaseRecordList $parentObject Instance of calling object (by ref due to interface)
     *
     * @return array the modified control-icons
     */
    public function makeControl($table, $row, $cells, &$parentObject)
    {
        return $cells;
    }

    /**
     * modifies Web>List header row columns/cells
     *
     * @param string $table the current database table
     * @param array $currentIdList Array of the currently displayed uids of the table
     * @param array $headerColumns An array of rendered cells/columns
     * @param DatabaseRecordList $parentObject Instance of calling object (by ref due to interface)
     *
     * @return array Array of modified cells/columns
     */
    public function renderListHeader($table, $currentIdList, $headerColumns, &$parentObject)
    {
        return $headerColumns;
    }

    /**
     * modifies Web>List header row clipboard/action icons
     *
     * @param string $table the current database table
     * @param array $currentIdList Array of the currently displayed uids of the table
     * @param array $cells An array of the current clipboard/action icons
     * @param DatabaseRecordList $parentObject Instance of calling object (by ref due to interface)
     *
     * @return array Array of modified clipboard/action icons
     */
    public function renderListHeaderActions($table, $currentIdList, $cells, &$parentObject)
    {
        return $cells;
    }

    /**
     * check if current row has child elements and add info to $theData array
     *
     * @param string $table
     * @param array $row
     * @param int $level
     * @param array $theData
     * @param DatabaseRecordListXclass $parentObj
     */
    public function checkChildren($table, array $row, $level, array &$theData, DatabaseRecordListXclass $parentObj)
    {
        if ($table === 'tt_content' && $row['CType'] === 'gridelements_pi1') {
            $elementChildren = Helper::getInstance()->getChildren(
                $table,
                $row['uid'],
                $row['pid'],
                '',
                0,
                $parentObj->selFieldList
            );
            if (!empty($elementChildren)) {
                $theData['_EXPANDABLE_'] = true;
                $theData['_EXPAND_ID_'] = $table . ':' . $row['uid'];
                $theData['_EXPAND_TABLE_'] = $table;
                $theData['_LEVEL_'] = $level;
                $theData['_CHILDREN_'] = $elementChildren;
            }
        }
    }

    /**
     * return content collapse icon
     *
     * @param array $data
     * @param string $sortField
     * @param int $level
     * @param string $contentCollapseIcon
     * @param DatabaseRecordListXclass $parentObj
     */
    public function contentCollapseIcon(
        array &$data,
        $sortField,
        $level,
        &$contentCollapseIcon,
        DatabaseRecordListXclass $parentObj
    ) {
        if ($data['_EXPAND_TABLE_'] === 'tt_content') {
            $expandTitle = $this->languageService->sL('LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xlf:list.expandElement');
            $collapseTitle = $this->languageService->sL('LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xlf:list.collapseElement');
            $expandedGridelements = $parentObj->getExpandedGridelements();
            if ($expandedGridelements[$data['uid']]) {
                $href = htmlspecialchars(($parentObj->listURL() . '&gridelementsExpand[' . (int)$data['uid'] . ']=0'));
                $contentCollapseIcon = '<a class="btn btn-default t3js-toggle-gridelements-list open-gridelements-container" data-state="expanded" href="' . $href .
                    '" id="t3-gridelements-' . $data['uid'] . '" title="' . $collapseTitle
                    . '" data-toggle-title="' . $expandTitle . '">'
                    . $this->getIconFactory()->getIcon('actions-view-list-expand', 'small')->render()
                    . $this->getIconFactory()->getIcon('actions-view-list-collapse', 'small')->render() . '</a>';
            } else {
                $href = htmlspecialchars(($parentObj->listURL() . '&gridelementsExpand[' . (int)$data['uid'] . ']=1'));
                $contentCollapseIcon = '<a class="btn btn-default t3js-toggle-gridelements-list" data-state="collapsed" href="' . $href .
                    '" id="t3-gridelements-' . $data['uid'] . '" title="' . $expandTitle
                    . '" data-toggle-title="' . $collapseTitle . '">'
                    . $this->getIconFactory()->getIcon('actions-view-list-expand', 'small')->render()
                    . $this->getIconFactory()->getIcon('actions-view-list-collapse', 'small')->render() . '</a>';
            }
        }
    }

    /**
     * @return IconFactory
     */
    public function getIconFactory()
    {
        if ($this->iconFactory === null) {
            $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        }

        return $this->iconFactory;
    }

    /**
     * getter for languageService
     *
     * @return LanguageService $languageService
     */
    public function getLanguageService()
    {
        return $this->languageService;
    }

    /**
     * setter for languageService object
     *
     * @param LanguageService $languageService
     */
    public function setLanguageService(LanguageService $languageService)
    {
        $this->languageService = $languageService;
    }

    /**
     * @return BackendUserAuthentication
     */
    public function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
