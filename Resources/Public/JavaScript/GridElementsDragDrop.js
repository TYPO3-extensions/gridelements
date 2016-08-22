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

define(['jquery', 'jquery-ui/sortable', 'jquery-ui/droppable'], function ($) {

	var DragDrop = {
		contentIdentifier: '.t3js-page-ce',
		dragIdentifier: '.t3-page-ce-dragitem',
		dragHeaderIdentifier: '.t3js-page-ce-draghandle',
		dropZoneIdentifier: '.t3js-page-ce-dropzone-available',
		columnIdentifier: '.t3js-page-column',
		validDropZoneClass: 'active',
		dropPossibleHoverClass: 't3-page-ce-dropzone-possible',
		addContentIdentifier: '.t3js-page-new-ce',
		gridContainerIdentifier: '.t3-grid-element-container',
		newContentElementWizardIdentifier: '#new-element-drag-in-wizard',
		newCTypeIdentifier: '.t3-ctype-identifier',
		clone: true
	};

	/**
	 * initializes Drag+Drop for all content elements on the page
	 */
	DragDrop.initialize = function () {
		$(DragDrop.contentIdentifier).draggable({
			handle: this.dragHeaderIdentifier,
			scope: 'tt_content',
			cursor: 'move',
			distance: 20,
			addClasses: 'active-drag',
			revert: 'invalid',
			zIndex: 100,
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
		$element.children(DragDrop.dragIdentifier).addClass('dragitem-shadow');
		// Hide create new element button
		$element.children(DragDrop.dropZoneIdentifier).addClass('drag-start');
		$element.closest(DragDrop.columnIdentifier).removeClass('active');
		$('#new-element-drag-in-wizard').addClass('dragged');

		$element.parents(DragDrop.columnHolderIdentifier).find(DragDrop.addContentIdentifier).hide();
		$element.find(DragDrop.dropZoneIdentifier).hide();

		// make the drop zones visible (all except the previous one in the current list)
		var $previousDropZone = $element.prev().children(DragDrop.dropZoneIdentifier);
		var currentMimeType = $element.find('.t3-ctype-identifier').data('ctype');
		var currentGridType = $element.find('.t3-ctype-identifier').data('gridtype');
		$(DragDrop.dropZoneIdentifier).not($previousDropZone).each(function () {
			var $closestColumn = $(this).closest(DragDrop.columnIdentifier);
			if (($closestColumn.hasClass('t3-allow-all') && (!currentGridType || !$closestColumn.hasClass('t3-allow-gridtype')) ||
				!currentGridType && $closestColumn.hasClass('t3-allow-' + currentMimeType) ||
				$closestColumn.hasClass('t3-allow-gridelements_pi1') && $closestColumn.hasClass('t3-allow-gridtype-' + currentGridType) ||
				currentMimeType === 'gridelements_pi1' && $closestColumn.hasClass('t3-allow-gridtype') && $closestColumn.hasClass('t3-allow-gridtype-' + currentGridType)) &&
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
		$element.children(DragDrop.dragIdentifier).removeClass('dragitem-shadow');
		// Show create new element button
		$element.children(DragDrop.dropZoneIdentifier).removeClass('drag-start');
		$element.closest(DragDrop.columnIdentifier).addClass('active');
		$element.parents(DragDrop.columnHolderIdentifier).find(DragDrop.addContentIdentifier).show();
		$element.find(DragDrop.dropZoneIdentifier).show();
		$('#new-element-drag-in-wizard').removeClass('dragged');
		$(DragDrop.dropZoneIdentifier + '.' + DragDrop.validDropZoneClass).removeClass(DragDrop.validDropZoneClass);
	};

	/**
	 * adds CSS classes when hovering over a dropzone
	 * @param $draggableElement
	 * @param $droppableElement
	 * @private
	 */
	DragDrop.onDropHoverOver = function ($draggableElement, $droppableElement) {
		if ($droppableElement.hasClass(DragDrop.validDropZoneClass)) {
			$droppableElement.addClass(DragDrop.dropPossibleHoverClass);
		}
	};

	/**
	 * removes the CSS classes after hovering out of a dropzone again
	 * @param $draggableElement
	 * @param $droppableElement
	 * @private
	 */
	DragDrop.onDropHoverOut = function ($draggableElement, $droppableElement) {
		$droppableElement.removeClass(DragDrop.dropPossibleHoverClass);
	};

	/**
	 * this method does the whole logic when a draggable is dropped on to a dropzone
	 * sending out the request and afterwards move the HTML element in the right place.
	 *
	 * @param $draggableElement
	 * @param $droppableElement
	 * @private
	 */
	DragDrop.onDrop = function ($draggableElement, $droppableElement, evt) {
		var newColumn = DragDrop.getColumnPositionForElement($droppableElement),
			gridColumn = DragDrop.getGridColumnPositionForElement($droppableElement);
		if (gridColumn !== false && gridColumn !== '') {
			newColumn = -1;
		} else {
			gridColumn = 0;
		}

		$droppableElement.removeClass(DragDrop.dropPossibleHoverClass);
		var $pasteAction = typeof $draggableElement === 'number';

		// send an AJAX requst via the AjaxDataHandler
		var contentElementUid = $pasteAction ? $draggableElement : parseInt($draggableElement.data('uid'));
		var newContentElementOnclick = '';
		var newContentElementDefaultValues = {};
		if (!$pasteAction && $draggableElement.closest(DragDrop.newContentElementWizardIdentifier).length) {
			// all information about CType, list_type and other default values has to be fetched from onclick
			newContentElementOnclick = $draggableElement.find('a:first').attr('onclick');
			if (typeof newContentElementOnclick !== undefined) {
				// this is the relevant part defining the default values for tt_content
				// while creating content with the new element wizard the usual way
				newContentElementOnclick = unescape(newContentElementOnclick.split('document.editForm.defValues.value=unescape(\'%26')[1].split('\');')[0]);
				if (newContentElementOnclick.length) {
					// if there are any default values, they have to be reformatted to an object/array
					// this can be passed on as parameters during the onDrop action after dragging in new content
					// CType is available for each element in the wizard, so this will be the identifier later on
					newContentElementDefaultValues = $.parseJSON(
						'{' + newContentElementOnclick.replace(/\&/g, '",').replace(/defVals\[tt_content\]\[/g, '"').replace(/\]\=/g, '":"') + '"}'
					);
				}
			}
		}
		if (contentElementUid > 0 || newContentElementDefaultValues.CType) {
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
			if (newContentElementDefaultValues.CType) {
				parameters['data']['tt_content']['NEW234134'] = newContentElementDefaultValues;
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
			} else if ((evt && evt !== 'reference' && evt !== 'copyFromAnotherPage' && evt.originalEvent.ctrlKey) || $droppableElement.hasClass('t3js-paste-copy') || evt === 'copyFromAnotherPage') {
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
				if (evt === 'reference') {
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
	 * @return int|null the colPos
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
	 * @return int|null the colPos
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

	$(DragDrop.initialize);
	return DragDrop;
});