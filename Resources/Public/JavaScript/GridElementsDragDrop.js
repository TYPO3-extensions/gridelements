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
	DragDrop.gridContainerIdentifier = '.t3-grid-element-container';
	DragDrop.newContentElementWizardIdentifier = '#new-element-drag-in-wizard';

	/**
	 * this method does the whole logic when a draggable is dropped on to a dropzone
	 * sending out the request and afterwards move the HTML element in the right place.
	 *
	 * @param $draggableElement
	 * @param $droppableElement
	 * @param {Event} evt the event
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

		// send an AJAX request via the AjaxDataHandler
		var contentElementUid = $pasteAction ? $draggableElement : parseInt($draggableElement.data('uid'));
		if (contentElementUid > 0) {
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
			var copyAction = (evt && evt.originalEvent.ctrlKey || $droppableElement.hasClass('t3js-paste-copy'));
			if (copyAction) {
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

});
