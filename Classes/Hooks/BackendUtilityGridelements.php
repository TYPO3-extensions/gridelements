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
 * Manipulate a given tt_content::pi_flexforms data structure
 *
 * @author Jo Hasenau <info@cybercraft.de>
 * @author Dirk Hoffmann <hoffmann@vmd-jena.de>
 * @author Stephan Schuler <stephan.schuler@netlogix.de>
 * @package TYPO3
 * @subpackage tx_gridelements
 */
class BackendUtilityGridelements
{

    /**
     * @var LayoutSetup
     */
    protected $layoutSetup;

    /**
     * Overwrites the data structure of a given tt_content::pi_flexform by
     * by the one matching the gridelements layout.
     *
     * @param array|string $dataStructureArray The incoming data structure. This might be the default one.
     * @param array $conf
     * @param array $row
     * @param string $table
     * @param string $fieldName
     */
    public function getFlexFormDS_postProcessDS(&$dataStructureArray, array $conf, array $row, $table, $fieldName)
    {
        if ($table === 'tt_content' && $fieldName === 'pi_flexform' && $row['CType'] === 'gridelements_pi1' && $row['tx_gridelements_backend_layout']) {
            $this->init($row['pid']);
            $dataStructureArray = GeneralUtility::xml2array($this->layoutSetup->getFlexformConfiguration($row['tx_gridelements_backend_layout']));
        }
    }

    /**
     * initializes this class
     *
     * @param int $pageUid
     */
    public function init($pageUid)
    {
        if (!$this->layoutSetup instanceof LayoutSetup) {
            $this->injectLayoutSetup(GeneralUtility::makeInstance(LayoutSetup::class)->init($pageUid));
        }
    }

    /**
     * inject layout setup
     *
     * @param LayoutSetup $layoutSetup
     */
    public function injectLayoutSetup(LayoutSetup $layoutSetup)
    {
        $this->layoutSetup = $layoutSetup;
    }

}
