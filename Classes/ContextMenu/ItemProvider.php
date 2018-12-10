<?php

namespace GridElementsTeam\Gridelements\ContextMenu;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Backend\ContextMenu\ItemProviders\RecordProvider;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ItemProvider extends RecordProvider
{
    protected $itemsConfiguration = [
        'pastereference' => [
            'type'           => 'item',
            'label'          => 'LLL:EXT:gridelements/Resources/Private/Language/locallang_db.xlf:tx_gridelements_clickmenu_pastereference',
            'iconIdentifier' => 'actions-document-paste-after',
            'callbackAction' => 'pasteReference',
        ],
    ];

    /**
     * @param array $items
     * @return array
     */
    public function addItems(array $items): array
    {
        $this->initialize();

        if (isset($items['pasteAfter'])) {
            $localItems = $this->prepareItems($this->itemsConfiguration);
            $position = array_search('pasteAfter', array_keys($items), true);

            $beginning = array_slice($items, 0, $position + 1, true);
            $end = array_slice($items, $position + 1, null, true);

            $items = $beginning + $localItems + $end;
            $items['pasteAfter']['additioanlAttributes'] = $this->getAdditionalAttributes('pasteAfter');
        }
        return $items;
    }

    protected function getAdditionalAttributes(string $itemName): array
    {
        $urlParameters = [
            'prErr'      => 1,
            'uPT'        => 1,
            'CB[paste]'  => $this->table . '|' . -$this->record['uid'],
            'CB[pad]'    => 'normal',
            'CB[update]' => [
                'colPos'                    => $this->record['colPos'],
                'tx_gridelements_container' => (int)$this->record['tx_gridelements_container'],
                'tx_gridelements_columns'   => (int)$this->record['tx_gridelements_columns'],
            ],
        ];
        if ($itemName === 'pastereference') {
            $urlParameters['reference'] = 1;
        }

        $attributes = $this->getPasteAdditionalAttributes('after');
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $attributes += [
            'data-callback-module' => 'TYPO3/CMS/Gridelements/ClickMenuActions',
            'data-action-url'      => htmlspecialchars($uriBuilder->buildUriFromRoute('tce_db', $urlParameters)),
        ];
        return $attributes;
    }

    public function canHandle(): bool
    {
        return $this->table === 'tt_content';
    }

    public function getPriority(): int
    {
        return 45;
    }

    /**
     * @param string $itemName
     * @param string $type
     * @return bool
     */
    protected function canRender(string $itemName, string $type): bool
    {
        $canRender = false;
        if ($itemName === 'pastereference') {
            $canRender = $this->canBePastedAfter() && $this->clipboard->currentMode() === 'copy' && $this->backendUser->checkAuthMode(
                'tt_content',
                    'CType',
                'shortcut',
                $GLOBALS['TYPO3_CONF_VARS']['BE']['explicitADmode']
            );
        }
        return $canRender;
    }
}
