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

define(['jquery', 'jquery-ui/sortable', 'jquery-ui/droppable'], function($) {

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
		copy: true
	};

	/**
	 * initializes Drag+Drop for all content elements on the page
	 */
	DragDrop.initialize = function() {
		$(this.contentIdentifier).draggable({
			handle: this.dragHeaderIdentifier,
			scope: 'tt_content',
			cursor: 'move',
			distance: 20,
			addClasses: 'active-drag',
			revert: 'invalid',
			zIndex: 100,
			start: function(evt, ui) {
				DragDrop.onDragStart($(this));
			},
			stop: function(evt, ui) {
				DragDrop.onDragStop($(this));
			}
		});

		$(this.dropZoneIdentifier).droppable({
			accept: this.contentIdentifier,
			scope: 'tt_content',
			tolerance: 'pointer',
			over: function(evt, ui) {
				DragDrop.onDropHoverOver($(ui.draggable), $(this));
			},
			out: function(evt, ui) {
				DragDrop.onDropHoverOut($(ui.draggable), $(this));
			},
			drop: function(evt, ui) {
				DragDrop.onDrop($(ui.draggable), $(this), evt);
			}
		});
	};


	/**
	 * called when a draggable is selected to be moved
	 * @param $element a jQuery object for the draggable
	 * @private
	 */
	DragDrop.onDragStart = function($element) {
		// Add css class for the drag shadow
		$element.children(DragDrop.dragIdentifier).addClass('dragitem-shadow');
		// Hide create new element button
		$element.children(DragDrop.dropZoneIdentifier).addClass('drag-start');
		$element.closest(DragDrop.columnIdentifier).removeClass('active');

		$element.parents(DragDrop.columnHolderIdentifier).find(DragDrop.addContentIdentifier).hide();
		$element.find(DragDrop.dropZoneIdentifier).hide();

		// make the dropzones visible (all except the previous one in the current list)
		var $previousDropZone = $element.prev().children(DragDrop.dropZoneIdentifier);
		$(DragDrop.dropZoneIdentifier).not($previousDropZone).addClass(DragDrop.validDropZoneClass);
	};

	/**
	 * called when a draggable is released
	 * @param $element a jQuery object for the draggable
	 * @private
	 */
	DragDrop.onDragStop = function($element) {
		// Remove css class for the drag shadow
		$element.children(DragDrop.dragIdentifier).removeClass('dragitem-shadow');
		// Show create new element button
		$element.children(DragDrop.dropZoneIdentifier).removeClass('drag-start');
		$element.closest(DragDrop.columnIdentifier).addClass('active');
		$element.parents(DragDrop.columnHolderIdentifier).find(DragDrop.addContentIdentifier).show();
		$element.find(DragDrop.dropZoneIdentifier).show();
		$(DragDrop.dropZoneIdentifier + '.' + DragDrop.validDropZoneClass).removeClass(DragDrop.validDropZoneClass);
	};

	/**
	 * adds CSS classes when hovering over a dropzone
	 * @param $draggableElement
	 * @param $droppableElement
	 * @private
	 */
	DragDrop.onDropHoverOver = function($draggableElement, $droppableElement) {
		if ($droppableElement.hasClass(DragDrop.validDropZoneClass)) {
			$droppableElement.addClass(DragDrop.dropPossibleHoverClass);
			$draggableElement.addClass(DragDrop.dropPossibleHoverClass);
		}
	};

	/**
	 * removes the CSS classes after hovering out of a dropzone again
	 * @param $draggableElement
	 * @param $droppableElement
	 * @private
	 */
	DragDrop.onDropHoverOut = function($draggableElement, $droppableElement) {
		$droppableElement.removeClass(DragDrop.dropPossibleHoverClass);
		$draggableElement.removeClass(DragDrop.dropPossibleHoverClass);
	};

	/**
	 * this method does the whole logic when a draggable is dropped on to a dropzone
	 * sending out the request and afterwards move the HTML element in the right place.
	 *
	 * @param $draggableElement
	 * @param $droppableElement
	 * @private
	 */
	DragDrop.onDrop = function($draggableElement, $droppableElement, evt) {
		var newColumn = DragDrop.getColumnPositionForElement($droppableElement),
			gridColumn = DragDrop.getGridColumnPositionForElement($droppableElement);
		if(gridColumn !== false && gridColumn !== '') {
			newColumn = -1;
		}

		$droppableElement.removeClass(DragDrop.dropPossibleHoverClass);
		$draggableElement.removeClass(DragDrop.dropPossibleHoverClass);

		// send an AJAX requst via the AjaxDataHandler
		var contentElementUid = parseInt($draggableElement.data('uid'));
		if (contentElementUid > 0) {
			var parameters = {};
			// add the information about a possible column position change
			var targetContentElementUid = $droppableElement.closest(DragDrop.contentIdentifier).data('uid');
			// the item was moved to the top of the colPos, so the page ID is used here
			if (typeof targetContentElementUid === 'undefined') {
				// the actual page is needed
				targetContentElementUid = parseInt($droppableElement.closest(DragDrop.contentIdentifier).data('page'));
			} else {
				// the negative value of the content element after where it should be moved
				targetContentElementUid = 0-parseInt(targetContentElementUid);
			}
			if (targetContentElementUid < 0 && gridColumn !== false && gridColumn !== '') {
				targetContentElementUid += 'x' + gridColumn;
			} else if (targetContentElementUid > 0) {
				targetContentElementUid += 'x' + newColumn;
			}
			parameters['cmd'] = {tt_content: {}};
			if(evt.originalEvent.ctrlKey) {
				parameters['cmd']['tt_content'][contentElementUid] = {copy: targetContentElementUid};
				parameters['DDcopy'] = 1;
				// fire the request, and show a message if it has failed
				require(['TYPO3/CMS/Backend/AjaxDataHandler'], function(DataHandler) {
					DataHandler.process(parameters).done(function(result) {
						if (!result.hasErrors) {
							// insert draggable on the new position
							if(!$droppableElement.parent().hasClass(DragDrop.contentIdentifier.substring(1))) {
								$draggableElement.detach().css({top: 0, left: 0})
									.insertAfter($droppableElement.closest(DragDrop.dropZoneIdentifier));
							} else {
								$draggableElement.detach().css({top: 0, left: 0})
									.insertAfter($droppableElement.closest(DragDrop.contentIdentifier));
							}
							self.location.reload(true);
						}
					});
				});
			} else {
				parameters['cmd']['tt_content'][contentElementUid] = {move: targetContentElementUid};
				// fire the request, and show a message if it has failed
				require(['TYPO3/CMS/Backend/AjaxDataHandler'], function(DataHandler) {
					DataHandler.process(parameters).done(function(result) {
						if (!result.hasErrors) {
							// insert draggable on the new position
							if(!$droppableElement.parent().hasClass(DragDrop.contentIdentifier.substring(1))) {
								$draggableElement.detach().css({top: 0, left: 0})
									.insertAfter($droppableElement.closest(DragDrop.dropZoneIdentifier));
							} else {
								$draggableElement.detach().css({top: 0, left: 0})
									.insertAfter($droppableElement.closest(DragDrop.contentIdentifier));
							}
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
	DragDrop.getColumnPositionForElement = function($element) {
		var $gridContainer = $element.closest(DragDrop.gridContainerIdentifier);
		if ( $gridContainer.length) {
			return -1;
		}
		var $columnContainer = $element.closest(DragDrop.columnIdentifier);
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
	DragDrop.getGridColumnPositionForElement = function($element) {
		var $gridContainer = $element.closest(DragDrop.gridContainerIdentifier);
		var $columnContainer = $element.closest(DragDrop.columnIdentifier);
		if (!$element.parent().hasClass(DragDrop.contentIdentifier.substring(1)) && $gridContainer.length && $columnContainer.length && $columnContainer.data('colpos') !== 'undefined') {
			return $columnContainer.data('colpos');
		} else {
			return false;
		}
	};

	/**
	 * initialize function
	 */
	return function() {
		DragDrop.initialize();
		return DragDrop;
	}();
});