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

use GridElementsTeam\Gridelements\DataHandler\AfterDatabaseOperations;
use GridElementsTeam\Gridelements\DataHandler\PreProcessFieldArray;
use GridElementsTeam\Gridelements\DataHandler\ProcessCmdmap;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class/Function which offers TCE main hook functions.
 *
 * @author Jo Hasenau <info@cybercraft.de>
 */
class DataHandler implements SingletonInterface
{
    public function __construct()
    {
    }

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
     * @param int $id : The uid of the page we are currently working on
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $parentObj : The parent object that triggered this hook
     *
     * @return void
     */
    public function processDatamap_preProcessFieldArray(
        &$fieldArray,
        $table,
        $id,
        \TYPO3\CMS\Core\DataHandling\DataHandler $parentObj
    ) {
        if (($table === 'tt_content' || $table === 'pages') && !$parentObj->isImporting) {
            /** @var $hook PreProcessFieldArray */
            $hook = GeneralUtility::makeInstance(PreProcessFieldArray::class);
            $hook->execute_preProcessFieldArray($fieldArray, $table, $id, $parentObj);
        }
    }

    /**
     * @param string $status
     * @param string $table : The name of the table the data should be saved to
     * @param int $id : The uid of the page we are currently working on
     * @param array $fieldArray : The array of fields and values that have been saved to the datamap
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $parentObj : The parent object that triggered this hook
     *
     * @return void
     */
    public function processDatamap_afterDatabaseOperations(
        &$status,
        &$table,
        &$id,
        &$fieldArray,
        \TYPO3\CMS\Core\DataHandling\DataHandler $parentObj
    ) {
        if (($table === 'tt_content' || $table === 'pages') && !$parentObj->isImporting) {
            /** @var AfterDatabaseOperations $hook */
            $hook = GeneralUtility::makeInstance(AfterDatabaseOperations::class);
            if (strpos($id, 'NEW') !== false) {
                $id = $parentObj->substNEWwithIDs[$id];
            } else {
                if ($table === 'tt_content' && $status === 'update') {
                    $hook->adjustValuesAfterWorkspaceOperations($fieldArray, $id, $parentObj);
                }
            }
            $hook->execute_afterDatabaseOperations($fieldArray, $table, $id, $parentObj);
        }
    }

    /**
     * Function to process the drag & drop copy action
     *
     * @param string $command The command to be handled by the command map
     * @param string $table The name of the table we are working on
     * @param int $id The id of the record that is going to be copied
     * @param string $value The value that has been sent with the copy command
     * @param bool $commandIsProcessed A switch to tell the parent object, if the record has been copied
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $parentObj The parent object that triggered this hook
     * @param array|bool $pasteUpdate Values to be updated after the record is pasted
     */
    public function processCmdmap(
        $command,
        $table,
        $id,
        $value,
        &$commandIsProcessed,
        \TYPO3\CMS\Core\DataHandling\DataHandler &$parentObj,
        $pasteUpdate
    ) {
        if (!$parentObj->isImporting) {
            /** @var ProcessCmdmap $hook */
            $hook = GeneralUtility::makeInstance(ProcessCmdmap::class);
            $hook->execute_processCmdmap($command, $table, $id, $value, $commandIsProcessed, $parentObj, $pasteUpdate);
        }
    }
}
