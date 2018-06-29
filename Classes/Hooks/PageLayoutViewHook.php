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

use TYPO3\CMS\Backend\View\PageLayoutView;

/**
 * Class/Function which manipulates the rendering of item example content and replaces it with a grid of child elements.
 *
 * @author Jo Hasenau <info@cybercraft.de>
 * @package TYPO3
 * @subpackage tx_gridelements
 */
class PageLayoutViewHook
{
    public function contentIsUsed(array $params, PageLayoutView $parentObject): bool
    {
        if ($params['used']) {
            return true;
        }
        $record = $params['record'];
        return $record['colPos'] === -1 && !empty($record['tx_gridelements_container']);
    }
}
