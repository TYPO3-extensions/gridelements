<?php

namespace GridElementsTeam\Gridelements\Hooks;

/***************************************************************
 *  Copyright notice
 *  (c) 2014 Jo Hasenau <info@cybercraft.de>, Dirk Hoffmann <hoffmann@vmd-jena.de>, Stephan Schuler <stephan.schuler@netlogix.de>
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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Manipulate and find flex forms for gridelements tt_content plugin
 *
 * @author Jo Hasenau <info@cybercraft.de>
 * @author Dirk Hoffmann <hoffmann@vmd-jena.de>
 * @author Stephan Schuler <stephan.schuler@netlogix.de>
 * @package TYPO3
 * @subpackage tx_gridelements
 */
class TtContentFlexForm
{
    /**
     * Method to find flex form configuration of a tt_content gridelements
     * content element.
     *
     * @param array $tca
     * @param $tableName
     * @param $fieldName
     * @param array $row
     * @return array
     */
    public function getDataStructureIdentifierPreProcess(array $tca, $tableName, $fieldName, array $row)
    {
        if ($tableName === 'tt_content' && $fieldName === 'pi_flexform' && $row['CType'] === 'gridelements_pi1') {
            if (!empty($row['tx_gridelements_backend_layout'])) {
                $pageUid = $row['pid'];
                $layoutId = $row['tx_gridelements_backend_layout'];
                $layoutSetupInstance = GeneralUtility::makeInstance(LayoutSetup::class)->init($pageUid);
                $layoutSetup = $layoutSetupInstance->getLayoutSetup($layoutId);
                if ($layoutSetup['pi_flexform_ds_file']) {
                    // Our data structure is in a record. Re-use core internal syntax to resolve that.
                    $identifier = [
                        'type'       => 'record',
                        'tableName'  => 'tx_gridelements_backend_layout',
                        'uid'        => $layoutId,
                        'fieldName'  => 'pi_flexform_ds_file',
                        'flexformDS' => 'FILE:' . $layoutSetup['pi_flexform_ds_file'],
                    ];
                } elseif ($layoutSetup['pi_flexform_ds']) {
                    $identifier = [
                        'type'       => 'record',
                        'tableName'  => 'tx_gridelements_backend_layout',
                        'uid'        => $layoutId,
                        'fieldName'  => 'pi_flexform_ds',
                        'flexformDS' => $layoutSetup['pi_flexform_ds'],
                    ];
                } else {
                    // This could be an additional core patch that allows referencing a DS file directly.
                    // If so, the second hook below would be obsolete.
                    $identifier = [
                        'type' => 'gridelements-dummy',
                    ];
                }
            } else {
                $identifier = [
                    'type' => 'gridelements-dummy',
                ];
            }
        } else {
            // Not my business
            $identifier = [];
        }
        return $identifier;
    }

    /**
     * Deliver a dummy flex form if identifier tells us to do so.
     *
     * @param array $identifier
     * @return string
     */
    public function parseDataStructureByIdentifierPreProcess(array $identifier)
    {
        if ($identifier['type'] === 'gridelements-dummy') {
            return 'FILE:EXT:gridelements/Configuration/FlexForms/default_flexform_configuration.xml';
        } elseif (!empty($identifier['flexformDS'])) {
            return $identifier['flexformDS'];
        } else {
            return '';
        }
    }
}
