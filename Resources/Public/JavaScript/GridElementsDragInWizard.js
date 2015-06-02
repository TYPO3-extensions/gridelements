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

define(['jquery', 'TYPO3/CMS/Gridelements/GridElementsDragDrop', 'jquery-ui/sortable', 'jquery-ui/droppable'], function($, DragDrop) {

	var DragInWizard = {
		wizardUrl: '',
		wizardIdentifier: 'new-element-drag-in-wizard'
	};

	/**
	 * initializes Drag+Drop for all content elements on the page
	 */
	DragInWizard.initialize = function() {
		if($('#ext-backend-Modules-Layout-index-php').length && $('.t3js-page-new-ce a').first().attr('onclick')) {
			DragInWizard.getWizardUrl();
			DragInWizard.createToggleIcon();
		}
	};

	/**
	 * get the URL for the new element wizard the correct module token
	 */
	DragInWizard.getWizardUrl = function() {
		var originalWizardUrl = $('.t3js-page-new-ce a').first().attr('onclick').split('\\u0026', 4);
		DragInWizard.wizardUrl = '\/typo3\/mod.php?M=new_content_element&' + originalWizardUrl[1] + '&' + originalWizardUrl[2];
	};

	/**
	 * create a new icon to make toggling the drag in wizard possible
	 */
	DragInWizard.createToggleIcon = function() {
		var lastIcon = $('.typo3-docheader-buttons .left .buttongroup .t3-icon').last().parent();
		var newIcon = lastIcon.clone().insertAfter(lastIcon);
		newIcon.removeAttr('onclick').attr('title', 'Toggle Drag In Wizard');
		newIcon.find('span').removeAttr('class').addClass('t3-icon fa fa-plus-square');
		newIcon.click(function() {
			DragInWizard.toggleWizard();
		});
	};

	/**
	 * load and/or activate the new item wizard on click
	 */
	DragInWizard.toggleWizard = function() {
		if($('#' + DragInWizard.wizardIdentifier).length) {
			$('#' + DragInWizard.wizardIdentifier).toggle();
		} else {
			$('#typo3-inner-docbody').prepend('<div id="' + DragInWizard.wizardIdentifier + '"></div>');
			$('#' + DragInWizard.wizardIdentifier).load(DragInWizard.wizardUrl + ' #typo3-inner-docbody div[role=\'tabpanel\']:first', function() {
				DragInWizard.makeItemsSortable();
				DragInWizard.rearrangeItems();
			});
			$('#' + DragInWizard.wizardIdentifier).css('visibility', 'visible');
		}
	};

	/**
	 * make wizard items sortable so they can be dragged into content columns
	 */
	DragInWizard.makeItemsSortable = function() {
		$('#' + DragInWizard.wizardIdentifier + ' .panel-body .media').attr('language-uid', 0).find('.media-left img').addClass('t3js-page-ce-draghandle').parent().addClass('t3-page-ce-dragitem').closest('.media').addClass('t3js-page-ce t3js-page-ce-sortable');
		DragDrop.initialize();
	};

	/**
	 * rearrange wizard items, so only icons will remain as the draggable part
	 */
	DragInWizard.rearrangeItems = function() {
		$('#' + DragInWizard.wizardIdentifier + ' .media').each(function() {
			var CType = $(this).find('input').attr('value').split('_')[1];
			$(this).find('.media-left').addClass('t3-ctype-identifier').attr('data-ctype', CType);
		});
		$('#' + DragInWizard.wizardIdentifier + ' .media-left input').parent().remove();
	};

	/**
	 * initialize function
	 */
	return function() {
		DragInWizard.initialize();
		return DragInWizard;
	}();
});