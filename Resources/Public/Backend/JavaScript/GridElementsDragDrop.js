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
		dragIdentifier: '.t3js-page-ce-draghandle',
		dropZoneAvailableIdentifier: '.t3js-page-ce-dropzone-available',
		dropPossibleClass: 't3-page-ce-dropzone-possible',
		sortableItemsIdentifier: '.t3js-page-ce-sortable',
		sortableHelperClass: '.t3-page-ce-sortable-helper',
		columnIdentifier: '.t3js-page-column',
		columnHolderIdentifier: '.t3js-page-columns',
		addContentIdentifier: '.t3js-page-new-ce',
		langClassPrefix: '.t3js-sortable-lang-',
		gridContainerIdentifier: '.t3-grid-element-container',
		copy: false
	};

	/**
	 * initializes Drag+Drop for all content elements on the page
	 */
	DragDrop.initialize = function() {
		$('td[data-language-uid]').each(function() {
			var connectWithClassName = DragDrop.langClassPrefix + $(this).data('language-uid');
			$(connectWithClassName).sortable({
				items: DragDrop.sortableItemsIdentifier,
				connectWith: connectWithClassName,
				handle: DragDrop.dragIdentifier,
				distance: 20,
				cursor: 'move',
				helper: DragDrop.sortableHelperClass,
				placeholder: DragDrop.dropPossibleClass,
				tolerance: 'pointer',
				cursorAt: {
					top: 0,
					left: 0
				},
				start: function(e, ui) {
					DragDrop.onSortStart($(this), ui);
				},
				beforeStop: function(e, ui) {
					var itemOffset = $(ui.item).offset(),
						itemWidth = $(ui.item).outerWidth(),
						itemHeight = $(ui.item).outerHeight();
					if (ui.offset.left + 15 < itemOffset.left
						|| ui.offset.left - 10 > (itemOffset.left + itemWidth)
						|| ui.offset.top + 15 < itemOffset.top
						|| ui.offset.top - 10 > (itemOffset.top + itemHeight)
					) {
						$(connectWithClassName).sortable('cancel');
						DragDrop.onSortStop($(this), ui);
					}
				},
				stop: function(e, ui) {
					DragDrop.onSortStop($(this), ui);
				},
				change: function(e, ui) {
					DragDrop.onSortChange($(this), ui);
				},
				update: function(e, ui) {
					if (this === ui.item.parent()[0]) {
						DragDrop.onSortUpdate($(this), ui, e);
					}
				}
			}).disableSelection();
		});
	};

	/**
	 * Called when an item is about to be moved
	 */
	DragDrop.onSortStart = function($container, ui) {
		var $item = $(ui.item),
			$helper = $(ui.helper),
			$placeholder = $(ui.placeholder);

		$placeholder.height($item.outerHeight(true) - $helper.find(DragDrop.addContentIdentifier).outerHeight(true));
		$placeholder.css({'margin-bottom': $(DragDrop.addContentIdentifier).first().height() - 2 - $item.outerHeight(true) + $helper.find(DragDrop.addContentIdentifier).outerHeight(true)});
		DragDrop.changeDropzoneVisibility($container, $item);

		// show all dropzones, except the own
		$helper.find(DragDrop.dropZoneAvailableIdentifier).removeClass('active');
		$container.parents(DragDrop.columnHolderIdentifier).find(DragDrop.addContentIdentifier).hide();
	};

	/**
	 * Called when the sorting stopped
	 */
	DragDrop.onSortStop = function($container, ui) {
		var $allColumns = $container.parents(DragDrop.columnHolderIdentifier);
		$allColumns.find(DragDrop.addContentIdentifier).show();
		$allColumns.find(DragDrop.dropZoneAvailableIdentifier + '.active').removeClass('active');
	};

	/**
	 * Called when the index of the element in the sortable list has changed
	 */
	DragDrop.onSortChange = function($container, ui) {
		var $placeholder = $(ui.placeholder);
		DragDrop.changeDropzoneVisibility($container, $placeholder);
	};

	DragDrop.changeDropzoneVisibility = function($container, $subject) {
		var $prev = $subject.prev(':visible'),
			droppableClassName = DragDrop.langClassPrefix + $container.data('language-uid');

		if ($prev.length === 0) {
			$prev = $subject.prevUntil(':visible').last().prev();
		}
		$container.parents(DragDrop.columnHolderIdentifier).find(droppableClassName).find(DragDrop.contentIdentifier + ':not(.ui-sortable-helper)').not($prev).find(DragDrop.dropZoneAvailableIdentifier).addClass('active');
		$prev.find(DragDrop.dropZoneAvailableIdentifier + '.active').removeClass('active');
	};

	/**
	 * Called when the new position of the element gets stored
	 */
	DragDrop.onSortUpdate = function($container, ui, e) {
		var newColumn = DragDrop.getColumnPositionForElement($container),
			gridColumn = DragDrop.getGridColumnPositionForElement($container);
		if(gridColumn !== false && gridColumn !== '') {
			newColumn = -1;
		}
		var $selectedItem = $(ui.item),
			contentElementUid = parseInt($selectedItem.data('uid'));

		// send an AJAX requst via the AjaxDataHandler
		if (contentElementUid > 0) {
			var parameters = {};
			// add the information about a possible column position change
			// add the information about a possible column position change
			var targetContentElementUid = $selectedItem.prev().data('uid');
			// the item was moved to the top of the colPos, so the page ID is used here
			if (typeof targetContentElementUid === 'undefined') {
				// the actual page is needed
				targetContentElementUid = $container.find(DragDrop.contentIdentifier).first().data('page');
				if(typeof targetContentElementUid === 'undefined') {
					targetContentElementUid = parseInt($container.find(DragDrop.contentIdentifier).first().data('container')) * -1;
				}
				if (targetContentElementUid < 0 && gridColumn !== false && gridColumn !== '') {
					targetContentElementUid += 'x' + gridColumn;
				} else if (targetContentElementUid > 0) {
					targetContentElementUid += 'x' + newColumn;
				}
			} else {
				// the negative value of the content element after where it should be moved
				targetContentElementUid = parseInt(targetContentElementUid) * -1;
			}
			parameters['cmd'] = {tt_content: {}};
			if(e.originalEvent.ctrlKey) {
				DragDrop.copy = true;
				parameters['cmd']['tt_content'][contentElementUid] = {copy: targetContentElementUid};
				parameters['DDcopy'] = 1;
			} else {
				DragDrop.copy = false;
				parameters['cmd']['tt_content'][contentElementUid] = {move: targetContentElementUid};
			}
			require(['TYPO3/CMS/Backend/AjaxDataHandler'], function(DataHandler) {
				DataHandler.process(parameters).done(function(result) {
					if (result.hasErrors) {
						$container.sortable('cancel');
					} else if(DragDrop.copy === true) {
						self.location.reload(true);
					}
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