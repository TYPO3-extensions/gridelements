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

/**
 * Module: TYPO3/CMS/Gridelements/ContextMenuActions
 *
 * JavaScript to handle gridelements related actions for Contextmenu
 * @exports TYPO3/CMS/Gridelements/ContextMenuActions
 */
define(['jquery', 'TYPO3/CMS/Backend/Modal', 'TYPO3/CMS/Backend/Severity'], function ($, Modal, Severity) {
    'use strict';

    /**
     * @exports TYPO3/CMS/Gridelements/ContextMenuActions
     */
    var ContextMenuActions = {};

    ContextMenuActions.pasteAfter = function (table, uid) {
        //the only difference between this pasteReference and PasteAfter is in the action url
        //which is already taken care of on the PHP side
        ContextMenuActions.pasteReference.bind($(this))(table, uid);
    };

    /**
     * Paste record as a reference
     *
     * @param {string} table
     * @param {int} uid of the record after which record from the cliboard will be pasted
     */
    ContextMenuActions.pasteReference = function (table, uid) {
        var $anchorElement = $(this);
        var actionUrl = $anchorElement.data('action-url');
        var performPaste = function () {
            var url = actionUrl + '&redirect=' + top.rawurlencode(top.list_frame.document.location.pathname + top.list_frame.document.location.search);

            top.TYPO3.Backend.ContentContainer.setUrl(url);
            if (table === 'pages' && top.TYPO3.Backend.NavigationContainer.PageTree) {
                top.TYPO3.Backend.NavigationContainer.PageTree.refreshTree.defer(500);
            }
        };
        if (!$anchorElement.data('title')) {
            performPaste();
            return;
        }
        var $modal = Modal.confirm(
            $anchorElement.data('title'),
            $anchorElement.data('message'),
            Severity.warning, [
                {
                    text: $(this).data('button-close-text') || TYPO3.lang['button.cancel'] || 'Cancel',
                    active: true,
                    btnClass: 'btn-default',
                    name: 'cancel'
                },
                {
                    text: $(this).data('button-ok-text') || TYPO3.lang['button.ok'] || 'OK',
                    btnClass: 'btn-warning',
                    name: 'ok'
                }
            ]);

        $modal.on('button.clicked', function (e) {
            if (e.target.name === 'ok') {
                performPaste();
            }
            Modal.dismiss();
        });

    };

    return ContextMenuActions;
});
