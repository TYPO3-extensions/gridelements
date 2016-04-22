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

define(['jquery', 'TYPO3/CMS/Gridelements/GridElementsDragDrop', 'jquery-ui/sortable', 'jquery-ui/droppable'], function ($, DragDrop) {

	var DragInWizard = {
		wizardUrl: '',
		wizardIdentifier: 'new-element-drag-in-wizard'
	};

	/**
	 * initializes Drag+Drop for all content elements on the page
	 */
	DragInWizard.initialize = function () {
		if ($('.t3js-module-body').length && $('.t3js-page-new-ce a').first()) {
			DragInWizard.getWizardUrl();
			if (DragInWizard.wizardUrl !== '') {
				DragInWizard.createToggleIcon();
			}
		}
	};

	/**
	 * get the URL for the new element wizard the correct module token
	 */
	DragInWizard.getWizardUrl = function () {
		var originalWizardUrl;
		var $newCeLink = $('.t3js-page-new-ce a').first();
		if ($newCeLink.attr('onclick')) {
			originalWizardUrl = $newCeLink.attr('onclick').split('\\u0026', 4);
		} else if ($newCeLink.attr('href')) {
			originalWizardUrl = $newCeLink.attr('href').split('\&', 4);
		}
		if (typeof originalWizardUrl !== 'undefined') {
			DragInWizard.wizardUrl = '\/typo3\/index.php?route=%2Frecord%2Fcontent%2Fnew&' + originalWizardUrl[1] + '&' + originalWizardUrl[2];
		}
	};

	/**
	 * create a new icon to make toggling the drag in wizard possible
	 */
	DragInWizard.createToggleIcon = function () {
		var lastIcon = $('.module-docheader-bar-column-left .btn-group .icon').last().parent();
		var addNewIcon = $('.t3-page-ce-wrapper-new-ce a').first();
		var newIcon = addNewIcon.clone().attr('class', 'btn btn-default btn-sm').insertAfter(lastIcon);
		newIcon.contents().filter(function () {
			return (this.nodeType == 3);
		}).remove();
		newIcon.removeAttr('onclick').attr('title', 'Toggle Drag In Wizard');
		newIcon.click(function () {
			top.dragInWizardActive = !top.dragInWizardActive;
			DragInWizard.toggleWizard();
			$(this).blur();
			return false;
		});
		if (top.dragInWizardActive) {
			DragInWizard.toggleWizard();
		}
	};

	/**
	 * load and/or activate the new item wizard on click
	 */
	DragInWizard.toggleWizard = function () {
		var $wizard = $('#' + DragInWizard.wizardIdentifier);
		if ($wizard.length) {
			$wizard.toggle();
		} else {
			$wizard = $('<div id="' + DragInWizard.wizardIdentifier + '"></div>');
			$('.t3js-module-docheader').append($wizard);
			$wizard.load(DragInWizard.wizardUrl + ' .t3js-module-body div[role=\'tabpanel\']:first', function () {
				DragInWizard.makeItemsSortable();
				DragInWizard.rearrangeItems();
			});
			$wizard.css('visibility', 'visible');
		}
	};

	/**
	 * make wizard items sortable so they can be dragged into content columns
	 */
	DragInWizard.makeItemsSortable = function () {
		$('#' + DragInWizard.wizardIdentifier + ' .panel-body .media').attr('language-uid', 0).find('.media-left img').addClass('t3js-page-ce-draghandle').parent().addClass('t3-page-ce-dragitem').closest('.media').addClass('t3js-page-ce t3js-page-ce-sortable');
		DragDrop.initialize();
	};

	/**
	 * rearrange wizard items, so only icons will remain as the draggable part
	 */
	DragInWizard.rearrangeItems = function () {
		var panel = $('#' + DragInWizard.wizardIdentifier + ' .t3js-tabs');
		var CType;
		var listType;
		$('#' + DragInWizard.wizardIdentifier + ' .media').each(function () {
			$(this).find('.media-left').addClass('t3-ctype-identifier');
			var description = $(this).find('.media-body');
			description = description.appendTo($(this).parent()).hide();
			$(this).find('.media-left').on('mouseenter', function () {
				description.show()
			}).on('mouseleave', function () {
				description.hide()
			});
		});
		var descriptionWidth = panel.width() - 50;
		var description = $('#' + DragInWizard.wizardIdentifier + ' .media-body');
		description.width(descriptionWidth);
		$('#' + DragInWizard.wizardIdentifier + ' .media-left input').parent().remove();
	};

	$(DragInWizard.initialize);
	return DragInWizard;
});
