<?php

namespace GridElementsTeam\Gridelements\DataHandler;

/***************************************************************
 *  Copyright notice
 *  (c) 2013 Jo Hasenau <info@cybercraft.de>
 *  (c) 2013 Stefan Froemken <froemken@gmail.com>
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

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class/Function which offers TCE main hook functions.
 *
 * @author         Jo Hasenau <info@cybercraft.de>
 */
class ProcessCmdmap extends AbstractDataHandler
{
    /**
     * Function to process the drag & drop copy action
     *
     * @param string $command The command to be handled by the command map
     * @param string $table The name of the table we are working on
     * @param int $id The id of the record that is going to be copied
     * @param string $value The value that has been sent with the copy command
     * @param bool $commandIsProcessed A switch to tell the parent object, if the record has been copied
     * @param DataHandler $parentObj The parent object that triggered this hook
     * @param array|bool $pasteUpdate Values to be updated after the record is pasted
     */
    public function execute_processCmdmap(
        $command,
        $table,
        $id,
        $value,
        &$commandIsProcessed,
        DataHandler $parentObj = null,
        $pasteUpdate = false
    ) {
        $this->init($table, $id, $parentObj);
        $reference = (int)GeneralUtility::_GET('reference');

        if ($command === 'copy' && $reference === 1 && !$commandIsProcessed && $table === 'tt_content' && !$this->getTceMain()->isImporting) {
            $dataArray = [
                'pid'     => $value,
                'CType'   => 'shortcut',
                'records' => $id,
                'header'  => 'Reference',
            ];

            // used for overriding container and column with real target values
            if (is_array($pasteUpdate) && !empty($pasteUpdate)) {
                $dataArray = array_merge($dataArray, $pasteUpdate);
            }

            $clipBoard = GeneralUtility::_GET('CB');
            if (!empty($clipBoard)) {
                $updateArray = $clipBoard['update'];
                if (!empty($updateArray)) {
                    $dataArray = array_merge($dataArray, $updateArray);
                }
            }

            $data = [];
            $data['tt_content']['NEW234134'] = $dataArray;

            $this->getTceMain()->start($data, []);
            $this->getTceMain()->process_datamap();

            $parentObj->registerDBList = null;
            $parentObj->remapStack = null;
            $commandIsProcessed = true;
        }

        if ($command === 'delete' && $table === 'tt_content') {
            $containerUpdateArray = [];
            $queryBuilder = $this->getQueryBuilder();
            $originalContainer = $queryBuilder
                ->select('tx_gridelements_container', 'sys_language_uid')
                ->from('tt_content')
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter((int)$id, \PDO::PARAM_INT)
                    )
                )
                ->execute()
                ->fetch();

            if (!empty($originalContainer)) {
                $containerUpdateArray[$originalContainer['tx_gridelements_container']] = -1;
                $this->doGridContainerUpdate($containerUpdateArray);
            }
        }
        if ($table === 'tt_content') {
            $this->cleanupWorkspacesAfterFinalizing();
        }
    }
}
