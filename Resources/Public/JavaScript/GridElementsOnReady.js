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
 * this JS code initializes several settings for the Layout module (Web => Page)
 * based on jQuery UI
 */

define(['jquery', 'TYPO3/CMS/Backend/AjaxDataHandler', 'TYPO3/CMS/Backend/Storage', 'TYPO3/CMS/Gridelements/GridElementsDragDrop', 'TYPO3/CMS/Backend/LayoutModule/Paste', 'TYPO3/CMS/Backend/Modal', 'TYPO3/CMS/Backend/Severity'], function ($, AjaxDataHandler, Storage, DragDrop, Paste, Modal, Severity) {

    var OnReady = {
        openedPopupWindow: []
    };


    AjaxDataHandler.identifier.allGridelementsToggle = '.t3js-toggle-gridelements-all';
    AjaxDataHandler.identifier.gridelementToggle = '.t3js-toggle-gridelements-list';
    AjaxDataHandler.identifier.allGridelementsColumnsToggle = '.t3js-toggle-gridelements-columns-all';
    AjaxDataHandler.identifier.gridelementColumnToggle = '.t3js-toggle-gridelements-column';

    /**
     * initializes Drag+Drop for all content elements on the page
     */
    OnReady.initialize = function () {
        if ($('#recordlist-tt_content').length) {
            OnReady.activateAllGridExpander();
        }
        if ($('.t3js-page-columns').length) {
            if (top.pasteIntoLinkTemplate === '' && top.pasteAfterLinkTemplate === '') {
                OnReady.setAllowedData();
            }
            OnReady.activateAllCollapseIcons();
        }
    };

    /**
     * activates the paste into / paste after and fetch copy from another page icons outside of the context menus
     */
    Paste.activatePasteIcons = function () {
        OnReady.setAllowedData();
        $('.icon-actions-document-paste-into').parent().remove();
        $('.t3-page-ce-wrapper-new-ce').each(function () {
            if (!$(this).find('.icon-actions-document-new').length) {
                return true;
            }
            $(this).addClass('btn-group btn-group-sm');
            $('.t3js-page-lang-column .t3-page-ce > .t3-page-ce').removeClass('t3js-page-ce');
            var gridCell = $(this).closest('.t3-grid-cell');
            if (typeof(gridCell.data('allowed')) === 'undefined') {
                gridCell.data('allowedCType', false);
                gridCell.data('allowedTxGridelementsBackendLayout', false);
                gridCell.data('allowedListType', false);
                if (top.clipBoardElementCType) {
                    if (typeof(gridCell.data('allowed-ctype')) !== 'undefined') {
                        var allowedCTypes = gridCell.data('allowed-ctype').toString().split(',');
                        if (
                            allowedCTypes.indexOf(top.clipBoardElementCType) > -1
                            || allowedCTypes.indexOf('*') > -1
                            || gridCell.data('allowed-ctype') === '*'
                        ) {
                            gridCell.data('allowedCType', true);
                        }
                    } else {
                        gridCell.data('allowedCType', true);
                    }
                    if (typeof(gridCell.data('disallowed-ctype')) !== 'undefined') {
                        var disallowedCTypes = gridCell.data('disallowed-ctype').toString().split(',');
                        if (
                            disallowedCTypes.indexOf(top.clipBoardElementCType) > -1
                            || disallowedCTypes.indexOf('*') > -1
                            || gridCell.data('disallowed-ctype') === '*'
                        ) {
                            gridCell.data('allowedCType', false);
                        }
                    }
                } else {
                    gridCell.data('allowedCType', true);
                }
                if (top.clipBoardElementTxGridelementsBackendLayout) {
                    if (typeof(gridCell.data('allowed-tx_gridelements_backend_layout')) !== 'undefined') {
                        var allowedTxGridelementsBackendLayouts = gridCell.data('allowed-tx_gridelements_backend_layout').toString().split(',');
                        if (
                            allowedTxGridelementsBackendLayouts.indexOf(top.clipBoardElementTxGridelementsBackendLayout) > -1
                            || allowedTxGridelementsBackendLayouts.indexOf('*') > -1
                            || gridCell.data('allowed-tx_gridelements_backend_layout') === '*'
                        ) {
                            gridCell.data('allowedTxGridelementsBackendLayout', true);
                        }
                    } else {
                        gridCell.data('allowedTxGridelementsBackendLayout', true);
                    }
                    if (typeof(gridCell.data('disallowed-tx_gridelements_backend_layout')) !== 'undefined') {
                        var disallowedTxGridelementsBackendLayouts = gridCell.data('disallowed-tx_gridelements_backend_layout').toString().split(',');
                        if (
                            disallowedTxGridelementsBackendLayouts.indexOf(top.clipBoardElementTxGridelementsBackendLayout) > -1
                            || disallowedTxGridelementsBackendLayouts.indexOf('*') > -1
                            || gridCell.data('disallowed-tx_gridelements_backend_layout') === '*'
                        ) {
                            gridCell.data('allowedTxGridelementsBackendLayout', false);
                        }
                    }
                } else {
                    gridCell.data('allowedTxGridelementsBackendLayout', true);
                }
                if (top.clipBoardElementListType) {
                    if (typeof(gridCell.data('allowed-list_type')) !== 'undefined') {
                        var allowedListTypes = gridCell.data('allowed-list_type').toString().split(',');
                        if (
                            allowedListTypes.indexOf(top.clipBoardElementListType) > -1
                            || allowedListTypes.indexOf('*') > -1
                            || gridCell.data('allowed-list_type') === '*'
                        ) {
                            gridCell.data('allowedListType', true);
                        }
                    } else {
                        gridCell.data('allowedListType', true);
                    }
                    if (typeof(gridCell.data('disallowed-list_type')) !== 'undefined') {
                        var disallowedListTypes = gridCell.data('disallowed-list_type').toString().split(',');
                        if (
                            disallowedListTypes.indexOf(top.clipBoardElementListType) > -1
                            || disallowedListTypes.indexOf('*') > -1
                            || gridCell.data('disallowed-list_type') === '*'
                        ) {
                            gridCell.data('allowedListType', false);
                        }
                    }
                } else {
                    gridCell.data('allowedListType', true);
                }
                gridCell.data('allowed', (gridCell.data('allowedCType') && gridCell.data('allowedTxGridelementsBackendLayout') && gridCell.data('allowedListType')));
            }
            if (top.pasteAfterLinkTemplate && top.pasteIntoLinkTemplate && gridCell.data('allowed')) {
                var parent = $(this).parent();
                if (parent.data('page') || (parent.data('container') && !parent.data('uid'))) {
                    $(this).append(top.pasteIntoLinkTemplate);
                } else {
                    $(this).append(top.pasteAfterLinkTemplate);
                }
                $(this).find('.t3js-paste').on('click', function (evt) {
                    evt.preventDefault();
                    Paste.activatePasteModal($(this));
                });
            }
            $(this).append(top.copyFromAnotherPageLinkTemplate);
            $(this).find('.t3js-paste-new').on('click', function (evt) {
                evt.preventDefault();
                OnReady.copyFromAnotherPage($(this));
            });
        });
    };

    /**
     * generates the paste into / paste after modal
     */
    Paste.activatePasteModal = function (element) {
        var $element = $(element);
        var url = $element.data('url') || null;
        var title = (TYPO3.lang['paste.modal.title.paste'] || 'Paste record') + ': "' + $element.data('title') + '"';
        var severity = (typeof top.TYPO3.Severity[$element.data('severity')] !== 'undefined') ? top.TYPO3.Severity[$element.data('severity')] : top.TYPO3.Severity.info;
        if ($element.hasClass('t3js-paste-copy')) {
            var content = TYPO3.lang['tx_gridelements_js.modal.pastecopy'] || '1 How do you want to paste that clipboard content here?';
            var buttons = [
                {
                    text: TYPO3.lang['paste.modal.button.cancel'] || 'Cancel',
                    active: true,
                    btnClass: 'btn-default',
                    trigger: function () {
                        Modal.currentModal.trigger('modal-dismiss');
                    }
                },
                {
                    text: TYPO3.lang['tx_gridelements_js.modal.button.pastecopy'] || 'Paste as copy',
                    btnClass: 'btn-' + top.TYPO3.Severity.getCssClass(severity),
                    trigger: function (ev) {
                        Modal.currentModal.trigger('modal-dismiss');
                        DragDrop.onDrop($element.data('content'), $element, ev);
                    }
                },
                {
                    text: TYPO3.lang['tx_gridelements_js.modal.button.pastereference'] || 'Paste as reference',
                    btnClass: 'btn-' + top.TYPO3.Severity.getCssClass(severity),
                    trigger: function (ev) {
                        Modal.currentModal.trigger('modal-dismiss');
                        DragDrop.onDrop($element.data('content'), $element, ev, 'reference');
                    }
                }
            ];
            if (top.pasteReferenceAllowed !== true) {
                buttons.pop();
            }
        } else {
            var content = TYPO3.lang['paste.modal.paste'] || 'Do you want to move the record to this position?';
            var buttons = [
                {
                    text: TYPO3.lang['paste.modal.button.cancel'] || 'Cancel',
                    active: true,
                    btnClass: 'btn-default',
                    trigger: function () {
                        Modal.currentModal.trigger('modal-dismiss');
                    }
                },
                {
                    text: TYPO3.lang['paste.modal.button.paste'] || 'Move',
                    btnClass: 'btn-' + Severity.getCssClass(severity),
                    trigger: function () {
                        Modal.currentModal.trigger('modal-dismiss');
                        DragDrop.onDrop($element.data('content'), $element, null);
                    }
                }
            ];
        }
        if (url !== null) {
            var separator = (url.indexOf('?') > -1) ? '&' : '?';
            var params = $.param({data: $element.data()});
            Modal.loadUrl(title, severity, buttons, url + separator + params);
        } else {
            Modal.show(title, content, severity, buttons);
        }
    };

    /**
     * sets the classes for allowed element types to the cells of the original page module
     */
    OnReady.setAllowedData = function () {
        $('table.t3js-page-columns > tbody > tr > td').each(function () {
            var colPos = $(this).data('colpos') ? $(this).data('colpos') : $(this).find('> .t3-page-ce-wrapper').data('colpos');
            if (typeof colPos !== 'undefined') {
                if (typeof top.pageColumnsAllowed[colPos] !== 'undefined') {
                    $(this).attr('data-allowed-ctype', top.pageColumnsAllowed[colPos]['CType']);
                    $(this).attr('data-allowed-list_type', top.pageColumnsAllowed[colPos]['list_type']);
                    $(this).attr('data-allowed-tx_gridelements_backend_layout', top.pageColumnsAllowed[colPos]['tx_gridelements_backend_layout']);
                }
                if (typeof top.pageColumnsDisallowed[colPos] !== 'undefined') {
                    $(this).attr('data-disallowed-ctype', top.pageColumnsDisallowed[colPos]['CType']);
                    $(this).attr('data-disallowed-list_type', top.pageColumnsDisallowed[colPos]['list_type']);
                    $(this).attr('data-disallowed-tx_gridelements_backend_layout', top.pageColumnsDisallowed[colPos]['tx_gridelements_backend_layout']);
                }
                if (typeof top.pageColumnsMaxitems[colPos] !== 'undefined') {
                    var $children = $(this).find('> .t3js-sortable > .t3js-page-ce-sortable');
                    var itemsOfMax = $children.length + '/' + top.pageColumnsMaxitems[colPos];
                    $(this).attr('data-maxitems', top.pageColumnsMaxitems[colPos]);
                    $(this).find('> .t3-page-column-header').after('<span class="t3-grid-cell-number-of-items">' + itemsOfMax + '</span>');
                    if ($children.length > top.pageColumnsMaxitems[colPos]) {
                        $(this).find('> .t3-grid-cell-number-of-items').text(itemsOfMax + '!').addClass('danger');
                        $(this).addClass('t3-page-ce-disable-new-ce');
                        $children.each(function () {
                            if ($(this).index() > top.pageColumnsMaxitems[colPos]) {
                                $(this).addClass('t3-page-ce-danger');
                            }
                        });
                    } else if ($children.length === top.pageColumnsMaxitems[colPos]) {
                        $(this).find('> .t3-grid-cell-number-of-items').addClass('warning');
                    } else {
                        $(this).find('> .t3-grid-cell-number-of-items').addClass('success');
                    }
                }
                OnReady.setAllowedParameters($(this), colPos);
            }
        });
    };

    /**
     * sets the parameters for allowed element types to the add new content links of the original page module
     */
    OnReady.setAllowedParameters = function (pageColumn, colPos) {
        pageColumn.find('.t3js-page-new-ce:not(".t3js-page-new-ce-allowed") a').each(function () {
            if (typeof $(this).attr('href') !== 'undefined') {
                $(this).attr('href', $(this).attr('href').replace(
                    '&uid_pid',
                    (top.pageColumnsAllowed[colPos] ? ('&tx_gridelements_allowed=' + window.btoa(JSON.stringify(top.pageColumnsAllowed[colPos]))) : '') +
                    (top.pageColumnsDisallowed[colPos] ? ('&tx_gridelements_disallowed=' + window.btoa(JSON.stringify(top.pageColumnsDisallowed[colPos]))) : '') +
                    '&uid_pid'
                ));
            }
        });
    };

    /**
     * activates the arrow icons to show/hide content previews within a certain grid column
     */
    OnReady.activateAllCollapseIcons = function () {
        OnReady.activateCollapseIcons();
        var lastIcon = $('.module-docheader-bar-column-left .btn-group .icon').last().parent();
        var addNewIcon = $('.t3js-toggle-gridelements-column').first();
        var newIcon = addNewIcon.clone().attr('class', 'btn btn-default btn-sm t3js-gridcolumn-toggle t3js-gridcolumn-expand').insertAfter(lastIcon);
        newIcon.contents().filter(function () {
            return (this.nodeType === 3);
        }).remove();
        newIcon.find('.icon-actions-view-list-collapse').remove();
        newIcon.removeAttr('onclick').attr('title', 'Expand all grid columns');
        var newIcon = addNewIcon.clone().attr('class', 'btn btn-default btn-sm t3js-gridcolumn-toggle').insertAfter(lastIcon);
        newIcon.contents().filter(function () {
            return (this.nodeType === 3);
        }).remove();
        newIcon.find('.icon-actions-view-list-expand').remove();
        newIcon.removeAttr('onclick').attr('title', 'Collapse all grid columns');
        $(document).on('click', '.t3js-gridcolumn-toggle', function (evt) {
            evt.preventDefault();

            var $me = $(this),
                collapsed = $me.hasClass('t3js-gridcolumn-expand') ? 0 : 1;

            // Store collapse state in UC
            var storedModuleDataPage = {};

            if (Storage.Persistent.isset('moduleData.page.gridelementsCollapsedColumns')) {
                storedModuleDataPage = Storage.Persistent.get('moduleData.list.gridelementsExpanded');
            }

            var collapseConfig = {};
            $('[data-columnkey]').each(function () {
                collapseConfig[$(this).data('columnkey')] = collapsed;
                $(this).removeClass('collapsed', 'expanded');
                $(this).addClass(collapsed ? 'collapsed' : 'expanded');
            });

            storedModuleDataPage = $.extend(true, storedModuleDataPage, collapseConfig);
            Storage.Persistent.set('moduleData.page.gridelementsCollapsedColumns', storedModuleDataPage);

        });
    };

    /**
     * activates the arrow icons to show/hide content previews within a certain grid column
     */
    OnReady.activateCollapseIcons = function () {
        $(document).on('click', AjaxDataHandler.identifier.gridelementColumnToggle, function (evt) {
            evt.preventDefault();

            var $me = $(this),
                columnKey = $me.closest('.t3js-page-column').data('columnkey'),
                isExpanded = $me.data('state') === 'expanded';

            // Store collapse state in UC
            var storedModuleDataPage = {};

            if (Storage.Persistent.isset('moduleData.page.gridelementsCollapsedColumns')) {
                storedModuleDataPage = Storage.Persistent.get('moduleData.page.gridelementsCollapsedColumns');
            }

            var expandConfig = {};
            expandConfig[columnKey] = isExpanded ? 1 : 0;

            storedModuleDataPage = $.extend(true, storedModuleDataPage, expandConfig);
            Storage.Persistent.set('moduleData.page.gridelementsCollapsedColumns', storedModuleDataPage).done(function () {
                $me.data('state', isExpanded ? 'collapsed' : 'expanded');
            });

            $me.closest('.t3-grid-cell').toggleClass('collapsed', 'expanded');
            var originalTitle = $me.attr('title');
            $me.attr('title', $me.attr('data-toggle-title'));
            $me.attr('data-toggle-title', originalTitle);
            $me.blur();

        });

        $('.t3-page-column-header-icons').each(function () {
            $(this).addClass('btn-group btn-group-sm');
            $(this).find('a').addClass('btn btn-default');
        });
    };

    /**
     * generates the paste into / paste after modal
     */
    OnReady.copyFromAnotherPage = function (element) {
        var url = top.backPath + top.browserUrl + '&mode=db&bparams=' + element.parent().attr('id') + '|||tt_content|';
        OnReady.openedPopupWindow = window.open(url, 'Typo3WinBrowser', 'height=600,width=800,status=0,menubar=0,resizable=1,scrollbars=1');
        OnReady.openedPopupWindow.focus();
    };

    /**
     * gives back the data from the popup window to the copy action
     */
    if (!$('.typo3-TCEforms').length) {
        OnReady.setSelectOptionFromExternalSource = setFormValueFromBrowseWin = function (elementId, tableUid) {
            tableUid = tableUid.replace('tt_content_', '') * 1;
            DragDrop.onDrop(tableUid, $('#' + elementId).find('.t3js-paste-new'), 'copyFromAnotherPage');
        }
    }

    /**
     * activates the toggle icons to open listings of nested grid container structure in the list module
     */
    OnReady.activateAllGridExpander = function () {
        OnReady.activateGridExpander();
        $(document).on('click', AjaxDataHandler.identifier.allGridelementsToggle, function (evt) {
            evt.preventDefault();

            var $me = $(this),
                container = '0,' + $me.data('container-ids'),
                isExpanded = this.id === 't3-gridelements-expand-all' ? 1 : 0;

            // Store collapse state in UC
            var storedModuleDataList = {};

            if (Storage.Persistent.isset('moduleData.list.gridelementsExpanded')) {
                storedModuleDataList = Storage.Persistent.get('moduleData.list.gridelementsExpanded');
            }

            var expandConfig = {};
            $(container.toString().split(',')).each(function (el, id) {
                if (id > 0) {
                    expandConfig[id] = isExpanded;
                    if (isExpanded === 1) {
                        $('[data-uid=' + id + ']').find('.t3js-toggle-gridelements-list').addClass('open-gridelements-container');
                        $('[data-trigger-container=' + id + ']').addClass('expanded');
                    } else {
                        $('[data-uid=' + id + ']').find('.t3js-toggle-gridelements-list').removeClass('open-gridelements-container');
                        $('[data-trigger-container=' + id + ']').removeClass('expanded');
                    }
                }
            });

            storedModuleDataList = $.extend(true, storedModuleDataList, expandConfig);
            Storage.Persistent.set('moduleData.list.gridelementsExpanded', storedModuleDataList);

        });

    };

    /**
     * activates the toggle icons to open listings of nested grid container structure in the list module
     */
    OnReady.activateGridExpander = function () {
        $(document).on('click', AjaxDataHandler.identifier.gridelementToggle, function (evt) {
            evt.preventDefault();

            var $me = $(this),
                container = $me.closest('tr').data('uid'),
                isExpanded = $me.data('state') === 'expanded';

            // Store collapse state in UC
            var storedModuleDataList = {};

            if (Storage.Persistent.isset('moduleData.list.gridelementsExpanded')) {
                storedModuleDataList = Storage.Persistent.get('moduleData.list.gridelementsExpanded');
            }

            var expandConfig = {};
            expandConfig[container] = isExpanded ? 0 : 1;

            storedModuleDataList = $.extend(true, storedModuleDataList, expandConfig);
            Storage.Persistent.set('moduleData.list.gridelementsExpanded', storedModuleDataList).done(function () {
                $me.data('state', isExpanded ? 'collapsed' : 'expanded');
            });

            $(this).toggleClass('open-gridelements-container');
            var originalTitle = $(this).attr('data-original-title');
            $(this).attr('data-original-title', $(this).attr('data-toggle-title'));
            $(this).attr('data-toggle-title', originalTitle);
            $(this).blur();

            $('[data-trigger-container=' + $(this).closest('tr').data('uid') + ']').toggleClass('expanded').find('.open-gridelements-container').click();
        });

    };

    $(OnReady.initialize);
    return OnReady;
});
