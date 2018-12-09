<?php

namespace GridElementsTeam\Gridelements\Slots;

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

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Class ExtTablesInclusionPostProcessing
 */
class ExtTablesInclusionPostProcessing
{
    /**
     * Function which may process data created / registered by extTables
     * scripts (f.e. modifying TCA data of all extensions)
     *
     * @param array $tca
     *
     * @return array
     */
    public function processData($tca)
    {
        // Move the local $tca to global variable to use general modification functions like addToAllTCAtypes
        $GLOBALS['TCA'] = $tca;

        ExtensionManagementUtility::addToAllTCAtypes('tt_content', 'recursive', 'shortcut', 'after:records');
        ExtensionManagementUtility::addToAllTCAtypes(
            'tt_content',
            '--div--;LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xlf:gridElements,tx_gridelements_container,tx_gridelements_columns'
        );

        // return the modified global TCA definition
        return [$GLOBALS['TCA']];
    }
}
