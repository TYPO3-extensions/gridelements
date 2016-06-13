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
use GridElementsTeam\Gridelements\DataHandler\MoveRecord;
use GridElementsTeam\Gridelements\DataHandler\PreProcessFieldArray;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Class/Function which offers TCE main hook functions.
 *
 * @author Jo Hasenau <info@cybercraft.de>
 * @package TYPO3
 * @subpackage tx_gridelements
 */
class DataHandler implements SingletonInterface
{

    /**
     * @var DatabaseConnection
     */
    protected $databaseConnection;

    public function __construct()
    {
        $this->setDatabaseConnection($GLOBALS['TYPO3_DB']);
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
        if (($table === 'tt_content' || $table === 'pages') && $status === 'update' && !$parentObj->isImporting) {
            /** @var $hook AfterDatabaseOperations */
            $hook = GeneralUtility::makeInstance(AfterDatabaseOperations::class);
            $hook->execute_afterDatabaseOperations($fieldArray, $table, $id, $parentObj);
        }
    }

    /**
     * Function to process the drag & drop copy action
     *
     * @param string $command : The command to be handled by the command map
     * @param string $table : The name of the table we are working on
     * @param int $id : The id of the record that is going to be copied
     * @param string $value : The value that has been sent with the copy command
     * @param boolean $commandIsProcessed : A switch to tell the parent object, if the record has been copied
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $parentObj : The parent object that triggered this hook
     * @param array|boolean $pasteUpdate : Values to be updated after the record is pasted
     *
     * @return    void
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
        /** @var $hook \GridElementsTeam\Gridelements\DataHandler\ProcessCmdmap */
        if (!$parentObj->isImporting) {
            $hook = GeneralUtility::makeInstance('GridElementsTeam\\Gridelements\\DataHandler\\ProcessCmdmap');
            $hook->execute_processCmdmap($command, $table, $id, $value, $commandIsProcessed, $parentObj, $pasteUpdate);
        }
    }

    /**
     * setter for databaseConnection object
     *
     * @param DatabaseConnection $databaseConnection
     *
     * @return void
     */
    public function setDatabaseConnection(DatabaseConnection $databaseConnection)
    {
        $this->databaseConnection = $databaseConnection;
    }

    /**
     * getter for databaseConnection
     *
     * @return DatabaseConnection databaseConnection
     */
    public function getDatabaseConnection()
    {
        return $this->databaseConnection;
    }

    /**
     * fixing translation bug at grid elements
     *
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $tcemainObj
     */
    public function processCmdmap_afterFinish	(\TYPO3\CMS\Core\DataHandling\DataHandler $tcemainObj){

	$getArray = GeneralUtility::_GET();
	// we need only action 'copyFromLanguage' and 'localize' (not from default language)
	if ($getArray['action'] == "copyFromLanguage" ||
		($getArray['action'] == "localize" && $getArray['srcLanguageId'] > 0)){

	    if (count($getArray['uidList']) > 0){

		// check all CE at list, that were localized
		foreach ($getArray['uidList'] as $origCeUid){

		    // get uid of localized records
		    $localizedCeUid = $tcemainObj->copyMappingArray_merged['tt_content'][$origCeUid];
		    if (empty($localizedCeUid)){
			continue;
		    }
		    $this->localizeChildrenRecordsForGridElement($getArray['action'],
			    $origCeUid,
			    $localizedCeUid,
			    $tcemainObj->copyMappingArray_merged['tt_content'],
			    $getArray['srcLanguageId']);
		}
	    }
	}
    }

    /**
     * localize children records for grid element and fix headers for other CE
     *
     * @param string	$action - name of action, like 'localize' or 'copyFromLanguage'
     * @param integer	$origCeUid - uid of original CE
     * @param integer	$localizedCeUid - uid of localized CE
     * @param array	$localizeMapping - array with relations between CE original --> localized
     * @param integer	$sourceLanguageUid - uid of original language
     *
     * @return void
     */
    private function localizeChildrenRecordsForGridElement (
	    $action,
	    $origCeUid,
	    $localizedCeUid,
	    $localizeMapping,
	    $sourceLanguageUid){

	$localizedRecord = BackendUtility::getRecord('tt_content', $localizedCeUid,
		'uid,pid,Ctype,sys_language_uid,tx_gridelements_children,tx_gridelements_container,l18n_parent');

	// check if this record is gridelement and has related records (childrens)
	if ($localizedRecord['Ctype'] == "gridelements_pi1" && $localizedRecord['tx_gridelements_children'] > 0){

	    // try to find childrens of this record
	    $childrens = BackendUtility::getRecordsByField('tt_content',
		    'tx_gridelements_container',
		    $localizedRecord['uid'],'','','',
		    $localizedRecord['tx_gridelements_children']);

	    if (count($childrens) > 0){
		foreach($childrens as $record){
		    // $key - 'original' record, for children
		    $key = array_search($record['uid'], $localizeMapping);
		    if ($key == FALSE){
			continue;
		    }

		    // define l18n_parent for children records
		    $l18nparent = 0;
		    if ($action == 'localize'){
			if ($sourceLanguageUid > 0){
			    $tmpRecord = BackendUtility::getRecord('tt_content', $record['uid'], 'l18n_parent');
			    $l18nparent = $tmpRecord['l18n_parent'];
			} else {
			    $l18nparent = $key;
			}
		    }

		    // update children records
		    $this->getDatabaseConnection()->sql_query('UPDATE tt_content '
			    . 'SET sys_language_uid = '. $localizedRecord['sys_language_uid']. ', t3_origuid = '. $key
			    . ', l18n_parent = '. $l18nparent
			    . ' WHERE uid = '. $record['uid']
			);

		    $this->localizeChildrenRecordsForGridElement($action,
			    $key,
			    $record['uid'],
			    $localizeMapping,
			    $sourceLanguageUid);
		}
	    }

	    // define l18n_parent for grid container record
	    $l18nparent = 0;
	    if ($action == 'localize'){
		if ($sourceLanguageUid > 0){
		    $tmpRecord = BackendUtility::getRecord('tt_content', $origCeUid, 'l18n_parent');
		    $l18nparent = $tmpRecord['l18n_parent'];
		} else {
		    $l18nparent = $origCeUid;
		}
	    }

	    // update grid container record
	    $this->getDatabaseConnection()->sql_query('UPDATE tt_content SET '
		    . 'l18n_parent = '. $l18nparent . ', t3_origuid = '. $origCeUid
		    . ' WHERE uid = '. $localizedRecord['uid']
		);
	}
    }

}