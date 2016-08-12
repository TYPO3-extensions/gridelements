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

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList;

/**
 * Class/Function which manipulates the query parts while fetching tt_content records within the list module.
 *
 * @author Jo Hasenau <info@cybercraft.de>
 * @author Dirk Hoffmann <hoffmann@vmd-jena.de>
 * @package TYPO3
 * @subpackage tx_gridelements
 */
class AbstractDatabaseRecordList implements SingletonInterface
{
    /**
     * ItemProcFunc for columns items
     *
     * @param array $queryParts The array containing the parts to build the query from
     * @param $parent The parent object that triggered this hook
     * @param string $table The name of the table we are currently working on
     */
    public function makeQueryArray_post(array &$queryParts, $parent, $table)
    {
        if ($table === 'tt_content' && $parent instanceof \GridElementsTeam\Gridelements\Xclass\DatabaseRecordList) {
            $queryParts['ORDERBY'] = $this->addValueToList($queryParts['ORDERBY'], 'colPos');
            if (!$parent->searchString) {
                $queryParts['WHERE'] .= ' AND colPos != -1';
            }

            if ($queryParts['SELECT'] != '*') {
                $queryParts['SELECT'] = $this->addValueToList($queryParts['SELECT'], 'colPos');
            }
        }
    }

    /**
     * adds a new value to the given list
     *
     * @param string $list comma seperated list of values
     * @param string $value
     *
     * @return string
     */
    public function addValueToList($list, $value)
    {
        $parts = GeneralUtility::trimExplode(',', $value . ',' . $list, true);

        return implode(',', array_flip(array_flip($parts)));
    }
}
