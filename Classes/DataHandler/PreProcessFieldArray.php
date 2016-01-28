<?php
namespace GridElementsTeam\Gridelements\DataHandler;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class/Function which offers TCE main hook functions.
 *
 * @author Jo Hasenau <info@cybercraft.de>
 * @package TYPO3
 * @subpackage tx_gridelements
 */
class PreProcessFieldArray extends AbstractDataHandler
{

    protected $definitionValues;

    protected $overrideValues;

    /**
     * Function to set the colPos of an element depending on
     * whether it is a child of a parent container or not
     * will set colPos according to availability of the current grid column of an element
     * 0 = no column at all
     * -1 = grid element column
     * -2 = non used elements column
     * changes are applied to the field array of the parent object by reference
     *
     * @param array $fieldArray : The array of fields and values that have been saved to the datamap
     * @param string $table : The name of the table the data should be saved to
     * @param integer $id : The parent uid of either the page or the container we are currently working on
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $parentObj : The parent object that triggered this hook
     *
     * @return void
     */
    public function execute_preProcessFieldArray(&$fieldArray, $table, $id, &$parentObj)
    {
        if ($table === 'tt_content') {
            $this->init($table, $id, $parentObj);
            if (!$this->getTceMain()->isImporting) {
                $this->processFieldArrayForTtContent($fieldArray);
            }
        }
    }

    /**
     * process field array for table tt_content
     *
     * @param array $fieldArray
     *
     * @return void
     */
    public function processFieldArrayForTtContent(array &$fieldArray)
    {
        $pid = 0;
        if ($this->getTable() === 'tt_content') {
            $pid = (int)GeneralUtility::_GET('DDinsertNew');

            if (abs($pid) > 0) {
                $this->setDefaultFieldValues($fieldArray, $pid);
                $this->getDefaultFlexformValues($fieldArray);
            }
        }
        $this->setFieldEntries($fieldArray);
    }

    /**
     * set default field values for new records
     *
     * @param array $fieldArray
     * @param int $pid
     *
     * @return void
     */

    public function setDefaultFieldValues(&$fieldArray, $pid = 0)
    {
        // Default values:
        $newRow = array(); // Used to store default values as found here:

        // Default values as set in userTS:
        $TCAdefaultOverride = $this->getBackendUser()->getTSConfigProp('TCAdefaults');
        if (is_array($TCAdefaultOverride['tt_content.'])) {
            foreach ($TCAdefaultOverride['tt_content.'] as $theF => $theV) {
                if (isset($GLOBALS['TCA']['tt_content']['columns'][$theF])) {
                    $newRow[$theF] = $theV;
                }
            }
        }

        if ($pid < 0) {
            $record = BackendUtility::getRecord('tt_content', abs($pid), 'pid');
            $id = $record['pid'];
            unset($record);
        } else {
            $id = (int)$pid;
        }

        $pageTS = BackendUtility::getPagesTSconfig($id);

        if (isset($pageTS['TCAdefaults.'])) {
            $TCAPageTSOverride = $pageTS['TCAdefaults.'];
            if (is_array($TCAPageTSOverride['tt_content.'])) {
                foreach ($TCAPageTSOverride['tt_content.'] as $theF => $theV) {
                    if (isset($GLOBALS['TCA']['tt_content']['columns'][$theF])) {
                        $newRow[$theF] = $theV;
                    }
                }
            }
        }

        // Default values as submitted:
        $this->definitionValues = GeneralUtility::_GP('defVals');
        $this->overrideValues = GeneralUtility::_GP('overrideVals');
        if (!is_array($this->definitionValues) && is_array($this->overrideValues)) {
            $this->definitionValues = $this->overrideValues;
        }
        if (is_array($this->definitionValues['tt_content'])) {
            foreach ($this->definitionValues['tt_content'] as $theF => $theV) {
                if (isset($GLOBALS['TCA']['tt_content']['columns'][$theF])) {
                    $newRow[$theF] = $theV;
                }
            }
        }

        // Fetch default values if a previous record exists
        if ($pid < 0 && $GLOBALS['TCA']['tt_content']['ctrl']['useColumnsForDefaultValues']) {
            // Fetches the previous record:
            $res = $this->databaseConnection->exec_SELECTquery('*', 'tt_content',
                'uid=' . abs($id) . BackendUtility::deleteClause('tt_content'));
            if ($row = $this->databaseConnection->sql_fetch_assoc($res)) {
                // Gets the list of fields to copy from the previous record.
                $fArr = explode(',', $GLOBALS['TCA']['tt_content']['ctrl']['useColumnsForDefaultValues']);
                foreach ($fArr as $theF) {
                    $theF = trim($theF);
                    if ($theF === '') {
                        continue;
                    }
                    if (isset($GLOBALS['TCA']['tt_content']['columns'][$theF])) {
                        $newRow[$theF] = $row[$theF];
                    }
                }
            }
        }
        $fieldArray = array_merge($newRow, $fieldArray);
    }

    /**
     * checks for default flexform values for new records and sets them accordingly
     *
     * @param array $fieldArray
     *
     * @return void
     */

    public function getDefaultFlexformValues(&$fieldArray)
    {
        foreach ($GLOBALS['TCA']['tt_content']['columns']['pi_flexform']['config']['ds'] as $key => $dataStructure) {
            $types = GeneralUtility::trimExplode(',', $key);
            if (($types[0] === $fieldArray['list_type'] || $types[0] === '*') && ($types[1] === $fieldArray['CType'] || $types[1] === '*')) {
                $fieldArray['pi_flexform'] = $this->extractDefaultDataFromDatastructure($dataStructure);
            }
        }
    }

    /**
     * extracts the default data out of a given XML data structure
     *
     * @param string $dataStructure
     *
     * @return string $defaultData
     */

    public function extractDefaultDataFromDataStructure($dataStructure)
    {
        $returnXML = '';
        $sheetArray = array();
        if ($dataStructure) {
            $structureArray = GeneralUtility::xml2array($dataStructure);
            if (!isset($structureArray['sheets']) && isset($structureArray['ROOT'])) {
                $structureArray['sheets']['sDEF']['ROOT'] = $structureArray['ROOT'];
                unset($structureArray['ROOT']);
            }
            if (isset($structureArray['sheets']) && !empty($structureArray['sheets'])) {
                foreach ($structureArray['sheets'] as $sheetName => $sheet) {
                    if (is_array($sheet['ROOT']['el']) && !empty($sheet['ROOT']['el'])) {
                        $elArray = array();
                        foreach ($sheet['ROOT']['el'] as $elName => $elConf) {
                            $config = $elConf['TCEforms']['config'];
                            $elArray[$elName]['vDEF'] = $config['default'];
                            if (!$elArray[$elName]['vDEF'] && $config['type'] === 'select' && !empty($config['items'])) {
                                $elArray[$elName]['vDEF'] = $config['items'][0][1];
                            }
                        }
                        $sheetArray['data'][$sheetName]['lDEF'] = $elArray;
                    }
                };
            }
            if (!empty($sheetArray)) {
                $flexformTools = GeneralUtility::makeInstance(FlexFormTools::class);
                $returnXML = $flexformTools->flexArray2Xml($sheetArray, true);
            }
        }

        return $returnXML;
    }

    /**
     * set initial entries to field array
     *
     * @param array $fieldArray
     *
     * @return void
     */
    public function setFieldEntries(array &$fieldArray)
    {
        if ((int)$fieldArray['tx_gridelements_container'] > 0 && strpos(key($this->getTceMain()->datamap['tt_content']),
                'NEW') !== false
        ) {
            $containerUpdateArray[(int)$fieldArray['tx_gridelements_container']] = 1;
            $this->doGridContainerUpdate($containerUpdateArray);
        }
        $this->setFieldEntriesForGridContainers($fieldArray);
    }

    /**
     * set/override entries to gridelements container
     *
     * @param array $fieldArray
     *
     * @return void
     */
    public function setFieldEntriesForGridContainers(array &$fieldArray)
    {
        if ((int)$fieldArray['tx_gridelements_container'] > 0 && isset($fieldArray['colPos']) && (int)$fieldArray['colPos'] !== -1) {
            $fieldArray['colPos'] = -1;
            $fieldArray['tx_gridelements_columns'] = 0;
        } else if (isset($fieldArray['tx_gridelements_container']) && (int)$fieldArray['tx_gridelements_container'] === 0 && (int)$fieldArray['colPos'] === -1) {
            $originalContainer = $this->databaseConnection->exec_SELECTgetSingleRow('tx_gridelements_container, sys_language_uid',
                'tt_content', 'uid=' . $this->getPageUid());
            $containerUpdateArray[$originalContainer['tx_gridelements_container']] = -1;
            $this->doGridContainerUpdate($containerUpdateArray);
            $fieldArray['sys_language_uid'] = $originalContainer['sys_language_uid'];
            $fieldArray['colPos'] = $this->checkForRootColumn((int)$this->getPageUid());
            $fieldArray['tx_gridelements_columns'] = 0;
        } else if (!isset($fieldArray['sys_language_uid']) && isset($fieldArray['tx_gridelements_container']) && (int)$fieldArray['tx_gridelements_container'] > 0 && (int)$fieldArray['colPos'] === -1) {
            $originalContainer = $this->databaseConnection->exec_SELECTgetSingleRow('sys_language_uid', 'tt_content',
                'uid=' . (int)$fieldArray['tx_gridelements_container']);
            $fieldArray['sys_language_uid'] = (int)$originalContainer['sys_language_uid'];
        }
    }

    /**
     * Function to recursively determine the colPos of the root container
     * so that an element that has been removed from any container
     * will still remain in the same major page column
     *
     * @param integer $contentId : The uid of the current content element
     *
     * @return integer $colPos: The new column of this content element
     */
    public function checkForRootColumn($contentId)
    {
        $parent = $this->databaseConnection->exec_SELECTgetSingleRow('t1.colPos, t1.tx_gridelements_container',
            'tt_content AS t1, tt_content AS t2', 't1.uid=t2.tx_gridelements_container AND t2.uid=' . (int)$contentId);
        if (!empty($parent) && $parent['tx_gridelements_container'] > 0) {
            $colPos = $this->checkForRootColumn($parent['tx_gridelements_container']);
        } else {
            $colPos = (int)$parent['colPos'];
        }

        return $colPos;
    }

    /**
     * @return BackendUserAuthentication
     */
    public function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
