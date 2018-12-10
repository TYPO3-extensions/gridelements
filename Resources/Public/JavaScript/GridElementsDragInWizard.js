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

define(['jquery', 'TYPO3/CMS/Gridelements/GridElementsDragDrop', 'jquery-ui/draggable', 'jquery-ui/droppable'], function ($, DragDrop) {

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
		var $newCeLink = $('.t3js-toggle-new-content-element-wizard').first();
		var originalWizardUrl = $newCeLink.data('url').split('\&', 4);
		if (typeof originalWizardUrl !== 'undefined') {
			DragInWizard.wizardUrl = originalWizardUrl[0] + '&' + originalWizardUrl[1] + '&' + originalWizardUrl[2];
		}
	};

	/**
	 * create a new icon to make toggling the drag in wizard possible
	 */
	DragInWizard.createToggleIcon = function () {
		var lastIcon = $('.module-docheader-bar-column-left .btn-group .icon').last().parent();
		var addNewIcon = $('.t3js-toggle-new-content-element-wizard').first();
		var newIcon = addNewIcon.clone().attr('class', 'btn btn-default btn-sm t3js-toggle-new-content-element-wizard').insertAfter(lastIcon);
		newIcon.contents().filter(function () {
			return (this.nodeType === 3);
		}).remove();
		newIcon.attr('title', 'Toggle Drag In Wizard');
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
			if (!$wizard.hasClass('active')) {
				$wizard.show();
				$wizard.addClass('active');
			} else {
                $wizard.removeClass('active');
                setTimeout(function() {
                    $wizard.hide();
                }, 800);
			}
		} else {
			$wizard = $('<div id="' + DragInWizard.wizardIdentifier + '"></div>');
			$('.t3js-module-docheader').append($wizard);
			$wizard.load(DragInWizard.wizardUrl + ' #new-content-element-wizard-carousel div[role=\'tabpanel\']:first', function () {
				DragInWizard.makeItemsDraggable();
				DragInWizard.rearrangeItems();
			});
            if (!$wizard.hasClass('active')) {
                $wizard.show();
                $wizard.addClass('active');
            } else {
                $wizard.removeClass('active');
                setTimeout(function() {
                    $wizard.hide();
                }, 800);
            }
		}
	};

	/**
	 * make wizard items draggable so they can be dragged into content columns
	 */
	DragInWizard.makeItemsDraggable = function () {
		$('#' + DragInWizard.wizardIdentifier + ' .panel-body .media').attr('language-uid', 0).find('.media-left img').addClass('t3js-page-ce-draghandle').parent().addClass('t3-page-ce-dragitem t3-page-ce-header-draggable').closest('.media').addClass('t3js-page-ce t3js-page-ce-draggable');
		DragDrop.default.initialize();
	};

	/**
	 * rearrange wizard items, so only icons will remain as the draggable part
	 */
	DragInWizard.rearrangeItems = function () {
		var panel = $('#' + DragInWizard.wizardIdentifier + ' .t3js-tabs');
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
