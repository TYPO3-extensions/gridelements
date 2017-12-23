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
 * this JS code does the drag+drop logic for the Layout module (Web => Page)
 * based on jQuery UI
 */
define(['jquery', 'jquery-ui/droppable', 'TYPO3/CMS/Backend/LayoutModule/DragDrop'], function ($, Droppable, DragDrop) {
    'use strict';

    /**
     * @exports TYPO3/CMS/Gridelements/DragDrop
     */
    DragDrop.draggableIdentifier = '.t3js-page-ce:has(.t3-page-ce-header-draggable)';
    DragDrop.gridContainerIdentifier = '.t3-grid-element-container';
    DragDrop.newContentElementWizardIdentifier = '#new-element-drag-in-wizard';
    DragDrop.cTypeIdentifier = '.t3-ctype-identifier';
    DragDrop.contentWrapperIdentifier = '.t3-page-ce-wrapper';
    DragDrop.disabledNewContentIdentifier = '.t3-page-ce-disable-new-ce';
    DragDrop.newContentElementOnclick = '';
    DragDrop.newContentElementDefaultValues = {};
    DragDrop.drag = {};
    DragDrop.types = {};
    DragDrop.ownDropZone = {};
    DragDrop.column = {};

    /**
     * initializes Drag+Drop for all content elements on the page
     */
    DragDrop.initialize = function () {
        $(DragDrop.draggableIdentifier).draggable({
            handle: this.dragHeaderIdentifier,
            scope: 'tt_content',
            cursor: 'move',
            distance: 20,
            addClasses: 'active-drag',
            revert: 'invalid',
            start: function (evt, ui) {
                DragDrop.onDragStart($(this));
            },
            stop: function (evt, ui) {
                DragDrop.onDragStop($(this));
            }
        });
        $(DragDrop.dropZoneIdentifier).droppable({
            accept: this.contentIdentifier,
            scope: 'tt_content',
            tolerance: 'pointer',
            over: function (evt, ui) {
                DragDrop.onDropHoverOver($(ui.draggable), $(this));
            },
            out: function (evt, ui) {
                DragDrop.onDropHoverOut($(ui.draggable), $(this));
            },
            drop: function (evt, ui) {
                DragDrop.onDrop($(ui.draggable), $(this), evt);
            }
        });
    };

    /**
     * called when a draggable is selected to be moved
     * @param $element a jQuery object for the draggable
     * @private
     */
    DragDrop.onDragStart = function ($element) {
        // Add css class for the drag shadow
        DragDrop.originalStyles = $element.get(0).style.cssText;
        DragDrop.drag = $element.children(DragDrop.drag);
        DragDrop.drag.addClass('dragitem-shadow');
        DragDrop.types = $element.find(DragDrop.cTypeIdentifier);
        if ($element.closest(DragDrop.newContentElementWizardIdentifier).length == 0) {
            $element.append('<div class="ui-draggable-copy-message">' + TYPO3.lang['dragdrop.copy.message'] + '</div>');
        } else {
            // all information about CType, list_type and other default values has to be fetched from onclick
            DragDrop.newContentElementOnclick = $element.find('a:first').attr('onclick');
            if (typeof DragDrop.newContentElementOnclick !== 'undefined') {
                // this is the relevant part defining the default values for tt_content
                // while creating content with the new element wizard the usual way
                DragDrop.newContentElementOnclick = unescape(DragDrop.newContentElementOnclick.split('document.editForm.defValues.value=unescape(\'%26')[1].split('\');')[0]);
                if (DragDrop.newContentElementOnclick.length) {
                    // if there are any default values, they have to be reformatted to an object/array
                    // this can be passed on as parameters during the onDrop action after dragging in new content
                    // CType is available for each element in the wizard, so this will be the identifier later on
                    DragDrop.newContentElementDefaultValues = $.parseJSON(
                        '{' + DragDrop.newContentElementOnclick.replace(/\&/g, '",').replace(/defVals\[tt_content\]\[/g, '"').replace(/\]\=/g, '":"') + '"}'
                    );
                    DragDrop.types.data('ctype', DragDrop.newContentElementDefaultValues.CType);
                    DragDrop.types.data('list_type', DragDrop.newContentElementDefaultValues.list_type);
                    DragDrop.types.data('tx_gridelements_backend_layout', DragDrop.newContentElementDefaultValues.tx_gridelements_backend_layout);
                }
            }
        }
        // Hide create new element button
        DragDrop.ownDropZone = $element.children(DragDrop.dropZoneIdentifier);
        DragDrop.ownDropZone.addClass('drag-start');
        DragDrop.column = $element.closest(DragDrop.columnIdentifier);
        DragDrop.column.removeClass('active');

        $element.parents(DragDrop.columnHolderIdentifier).find(DragDrop.addContentIdentifier).hide();
        $element.find(DragDrop.dropZoneIdentifier).hide();

        // make the drop zones visible
        var siblingsDropZones = $element.parents(DragDrop.disabledNewContentIdentifier).find(DragDrop.dropZoneIdentifier);
        var disabledDropZones = $(DragDrop.disabledNewContentIdentifier + ' > ' + DragDrop.contentWrapperIdentifier + ' > ' + DragDrop.contentIdentifier + ' > ' + DragDrop.dropZoneIdentifier);
        var contentType = DragDrop.types.data('ctype');
        contentType = contentType ? contentType.toString() : '';
        var listType = DragDrop.types.data('list_type');
        listType = listType ? listType.toString() : '';
        var gridType = DragDrop.types.data('tx_gridelements_backend_layout');
        gridType = gridType ? gridType.toString() : '';
        $(DragDrop.dropZoneIdentifier).not(DragDrop.ownDropZone).each(function () {
            var closestColumn = $(this).closest(DragDrop.columnIdentifier);
            var allowedContentType = closestColumn.data('allowed-ctype') ? closestColumn.data('allowed-ctype').toString().split(',') : null;
            var disallowedContentType = closestColumn.data('disallowed-ctype') ? closestColumn.data('disallowed-ctype').toString().split(',') : null;
            var allowedListType = closestColumn.data('allowed-list_type') ? closestColumn.data('allowed-list_type').toString().split(',') : null;
            var disallowedListType = closestColumn.data('disallowed-list_type') ? closestColumn.data('disallowed-list_type').toString().split(',') : null;
            var allowedGridType = closestColumn.data('allowed-tx_gridelements_backend_layout') ? closestColumn.data('allowed-tx_gridelements_backend_layout').toString().split(',') : null;
            var disallowedGridType = closestColumn.data('disallowed-tx_gridelements_backend_layout') ? closestColumn.data('disallowed-tx_gridelements_backend_layout').toString().split(',') : null;
            if ((
                    (!allowedContentType || $.inArray('*', allowedContentType) > -1 || $.inArray(contentType, allowedContentType) > -1) &&
                    (!disallowedContentType || ($.inArray('*', disallowedContentType) === -1 && $.inArray(contentType, disallowedContentType) === -1))
                ) && (listType === '' || (
                    (!allowedListType || $.inArray('*', allowedListType) > -1 || $.inArray(listType, allowedListType) > -1) &&
                    (!disallowedListType || ($.inArray('*', disallowedListType) === -1 && $.inArray(listType, disallowedListType) === -1))
                )) && (gridType === '' || (
                    (!allowedGridType || $.inArray('*', allowedGridType) > -1 || $.inArray(gridType, allowedGridType) > -1) &&
                    (!disallowedGridType || ($.inArray('*', disallowedGridType) === -1 && $.inArray(listType, disallowedGridType) === -1))
                )) &&
                (
                    $(this).not(disabledDropZones).length ||
                    siblingsDropZones.length
                ) &&
                $(this).parent().find('.icon-actions-document-new').length
            ) {
                $(this).addClass(DragDrop.validDropZoneClass);
            } else {
                $(this).closest(DragDrop.contentIdentifier).find('> ' + DragDrop.addContentIdentifier + ', > > ' + DragDrop.addContentIdentifier).show();
            }
        });
    };


    /**
     * called when a draggable is released
     * @param $element a jQuery object for the draggable
     * @private
     */
    DragDrop.onDragStop = function ($element) {
        // Remove css class for the drag shadow
        DragDrop.drag.removeClass('dragitem-shadow');
        // Show create new element button
        DragDrop.ownDropZone.removeClass('drag-start');
        DragDrop.column.addClass('active');
        $element.parents(DragDrop.columnHolderIdentifier).find(DragDrop.addContentIdentifier).show();
        $element.find(DragDrop.dropZoneIdentifier).show();
        $element.find('.ui-draggable-copy-message').remove();

        // Reset inline style
        $element.get(0).style.cssText = DragDrop.originalStyles.replace('z-index: 100;', '');

        $(DragDrop.dropZoneIdentifier + '.' + DragDrop.validDropZoneClass).removeClass(DragDrop.validDropZoneClass);
    };

    /**
     * this method does the whole logic when a draggable is dropped on to a dropzone
     * sending out the request and afterwards move the HTML element in the right place.
     *
     * @param $draggableElement
     * @param $droppableElement
     * @param {Event} evt the event
     * @param reference if content should be pasted as copy or reference
     * @private
     */
    DragDrop.onDrop = function ($draggableElement, $droppableElement, evt, reference) {
        var newColumn = DragDrop.getColumnPositionForElement($droppableElement),
            gridColumn = DragDrop.getGridColumnPositionForElement($droppableElement);
        if (gridColumn !== false && gridColumn !== '') {
            newColumn = -1;
        } else {
            gridColumn = 0;
        }

        $droppableElement.removeClass(DragDrop.dropPossibleHoverClass);
        var $pasteAction = typeof $draggableElement === 'number';

        // send an AJAX request via the AjaxDataHandler
        var contentElementUid = $pasteAction ? $draggableElement : parseInt($draggableElement.data('uid'));
        if (contentElementUid > 0 || (DragDrop.newContentElementDefaultValues.CType && !$pasteAction)) {
            var parameters = {};
            // add the information about a possible column position change
            var targetFound = $droppableElement.closest(DragDrop.contentIdentifier).data('uid');
            // the item was moved to the top of the colPos, so the page ID is used here
            var targetPid = 0;
            if (typeof targetFound === 'undefined') {
                // the actual page is needed
                targetPid = $('[data-page]').first().data('page');
            } else {
                // the negative value of the content element after where it should be moved
                targetPid = 0 - parseInt(targetFound);
            }
            var container = parseInt($droppableElement.closest(DragDrop.gridContainerIdentifier).closest(DragDrop.contentIdentifier).data('uid'));
            var language = parseInt($droppableElement.closest('[data-language-uid]').data('language-uid'));
            var colPos = 0;
            if (container > 0 && gridColumn !== false && gridColumn !== '') {
                colPos = -1;
            } else if (targetPid !== 0) {
                colPos = newColumn;
            }
            parameters['cmd'] = {tt_content: {}};
            parameters['data'] = {tt_content: {}};
            var copyAction = (evt && evt.originalEvent && evt.originalEvent.ctrlKey || $droppableElement.hasClass('t3js-paste-copy') || evt === 'copyFromAnotherPage');
            if (DragDrop.newContentElementDefaultValues.CType) {
                parameters['data']['tt_content']['NEW234134'] = DragDrop.newContentElementDefaultValues;
                parameters['data']['tt_content']['NEW234134']['pid'] = targetPid;
                parameters['data']['tt_content']['NEW234134']['colPos'] = colPos;
                parameters['data']['tt_content']['NEW234134']['tx_gridelements_container'] = container;
                parameters['data']['tt_content']['NEW234134']['tx_gridelements_columns'] = gridColumn;
                parameters['data']['tt_content']['NEW234134']['sys_language_uid'] = language;

                if (!parameters['data']['tt_content']['NEW234134']['header']) {
                    parameters['data']['tt_content']['NEW234134']['header'] = TYPO3.l10n.localize('tx_gridelements_js.newcontentelementheader');
                }

                if (language > -1) {
                    parameters['data']['tt_content']['NEW234134']['sys_language_uid'] = language;
                }
                parameters['DDinsertNew'] = 1;

                // fire the request, and show a message if it has failed
                require(['TYPO3/CMS/Backend/AjaxDataHandler'], function (DataHandler) {
                    DataHandler.process(parameters).done(function (result) {
                        if (!result.hasErrors) {
                            // insert draggable on the new position
                            if (!$pasteAction) {
                                if (!$droppableElement.parent().hasClass(DragDrop.contentIdentifier.substring(1))) {
                                    $draggableElement.detach().css({top: 0, left: 0})
                                        .insertAfter($droppableElement.closest(DragDrop.dropZoneIdentifier));
                                } else {
                                    $draggableElement.detach().css({top: 0, left: 0})
                                        .insertAfter($droppableElement.closest(DragDrop.contentIdentifier));
                                }
                            }
                            self.location.reload(true);
                        }
                    });
                });
            } else if (copyAction) {
                parameters['cmd']['tt_content'][contentElementUid] = {
                    copy: {
                        action: 'paste',
                        target: targetPid,
                        update: {
                            colPos: colPos,
                            tx_gridelements_container: container,
                            tx_gridelements_columns: gridColumn
                        }
                    }
                };
                if (reference === 'reference') {
                    parameters['reference'] = 1;
                }
                if (language > -1) {
                    parameters['cmd']['tt_content'][contentElementUid]['copy']['update']['sys_language_uid'] = language;
                }
                if (evt === 'copyFromAnotherPage') {
                    parameters['CB'] = {setCopyMode: 1};
                }
                // fire the request, and show a message if it has failed
                require(['TYPO3/CMS/Backend/AjaxDataHandler'], function (DataHandler) {
                    DataHandler.process(parameters).done(function (result) {
                        if (!result.hasErrors) {
                            // insert draggable on the new position
                            if (!$pasteAction) {
                                if (!$droppableElement.parent().hasClass(DragDrop.contentIdentifier.substring(1))) {
                                    $draggableElement.detach().css({top: 0, left: 0})
                                        .insertAfter($droppableElement.closest(DragDrop.dropZoneIdentifier));
                                } else {
                                    $draggableElement.detach().css({top: 0, left: 0})
                                        .insertAfter($droppableElement.closest(DragDrop.contentIdentifier));
                                }
                            }
                            self.location.reload(true);
                        }
                    });
                });
            } else {
                parameters['data']['tt_content'][contentElementUid] = {
                    colPos: colPos,
                    tx_gridelements_container: container,
                    tx_gridelements_columns: gridColumn
                };
                if (language > -1) {
                    parameters['data']['tt_content'][contentElementUid]['sys_language_uid'] = language;
                }
                parameters['cmd']['tt_content'][contentElementUid] = {move: targetPid};
                // fire the request, and show a message if it has failed
                require(['TYPO3/CMS/Backend/AjaxDataHandler'], function (DataHandler) {
                    DataHandler.process(parameters).done(function (result) {
                        if (!result.hasErrors) {
                            // insert draggable on the new position
                            if (!$pasteAction) {
                                if (!$droppableElement.parent().hasClass(DragDrop.contentIdentifier.substring(1))) {
                                    $draggableElement.detach().css({top: 0, left: 0})
                                        .insertAfter($droppableElement.closest(DragDrop.dropZoneIdentifier));
                                } else {
                                    $draggableElement.detach().css({top: 0, left: 0})
                                        .insertAfter($droppableElement.closest(DragDrop.contentIdentifier));
                                }
                            }
                            self.location.reload(true);
                        }
                    });
                });
            }
        }
    };

    /**
     * returns the next "upper" container colPos parameter inside the code
     * @param $element
     * @return int|boolean the colPos
     */
    DragDrop.getColumnPositionForElement = function ($element) {
        var $gridContainer = $element.closest(DragDrop.gridContainerIdentifier);
        if ($gridContainer.length) {
            return -1;
        }
        var $columnContainer = $element.closest('[data-colpos]');
        if ($columnContainer.length && $columnContainer.data('colpos') !== 'undefined') {
            return $columnContainer.data('colpos');
        } else {
            return false;
        }
    };

    /**
     * returns the next "upper" container colPos parameter inside the code
     * @param $element
     * @return int|boolean the colPos
     */
    DragDrop.getGridColumnPositionForElement = function ($element) {
        var $gridContainer = $element.closest(DragDrop.gridContainerIdentifier);
        var $columnContainer = $element.closest(DragDrop.columnIdentifier);
        if ($gridContainer.length && $columnContainer.length && $columnContainer.data('colpos') !== 'undefined') {
            return $columnContainer.data('colpos');
        } else {
            return false;
        }
    };

    return DragDrop;
});
