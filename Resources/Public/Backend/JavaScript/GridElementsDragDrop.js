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

define(['TYPO3/CMS/Backend/LayoutModule/DragDrop'], function (DragDrop) {
	DragDrop.gridContainerIdentifier = '.t3-gridElementContainer';

	/**
	 * this method does the whole logic when a draggable is dropped on to a dropzone
	 * sending out the request and afterwards move the HTML element in the right place.
	 *
	 * @param $draggableElement
	 * @param $droppableElement
	 * @private
	 */
	DragDrop.onDrop = function($draggableElement, $droppableElement) {
		var newColumn = DragDrop.getColumnPositionForElement($droppableElement),
			gridColumn = DragDrop.getGridColumnPositionForElement($droppableElement);
		if(gridColumn) {
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
			if (targetContentElementUid < 0 && gridColumn) {
				targetContentElementUid += 'x' + gridColumn;
			} else if (targetContentElementUid > 0) {
				targetContentElementUid += 'x' + newColumn;
			}

			parameters['cmd'] = {tt_content: {}};
			parameters['cmd']['tt_content'][contentElementUid] = {move: targetContentElementUid};
			// fire the request, and show a message if it has failed
			require(['TYPO3/CMS/Backend/AjaxDataHandler'], function(DataHandler) {
				DataHandler.process(parameters).done(function(result) {
					if (!result.hasErrors) {
						// insert draggable on the new position
						$draggableElement.detach().css({top: 0, left: 0})
							.insertAfter($droppableElement.closest(DragDrop.contentIdentifier));
					}
					self.location.reload(true);
				});
			});
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
			return null;
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
			return null;
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