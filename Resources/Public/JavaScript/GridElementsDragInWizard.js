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
		if($('#typo3-index-php').length && $('.t3js-page-new-ce a').first().attr('onclick')) {
			DragInWizard.getWizardUrl();
			DragInWizard.createToggleIcon();
		}
	};

	/**
	 * get the URL for the new element wizard the correct module token
	 */
	DragInWizard.getWizardUrl = function() {
		var originalWizardUrl = $('.t3js-page-new-ce a').first().attr('onclick').split('\\u0026', 4);
		DragInWizard.wizardUrl = '\/typo3\/index.php?route=%2Frecord%2Fcontent%2Fnew&' + originalWizardUrl[1] + '&' + originalWizardUrl[2];
	};

	/**
	 * create a new icon to make toggling the drag in wizard possible
	 */
	DragInWizard.createToggleIcon = function() {
		var lastIcon = $('.typo3-docheader-buttons .left .buttongroup .icon').last().parent();
		var addNewIcon = $('.t3-page-ce-wrapper-new-ce a').first();
		var newIcon = addNewIcon.clone().attr('class', '').insertAfter(lastIcon);
		newIcon.contents().filter(function(){
			return (this.nodeType == 3);
		}).remove();
		newIcon.removeAttr('onclick').attr('title', 'Toggle Drag In Wizard');
		newIcon.click(function() {
			top.dragInWizardActive = top.dragInWizardActive === true ? false : true;
			DragInWizard.toggleWizard();
		});
		if(top.dragInWizardActive) {
			DragInWizard.toggleWizard();
		}
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
		var panel = $('#' + DragInWizard.wizardIdentifier + ' .panel-body');
		var descriptionWidth = panel.width() - 20;
		var CType;
		$('#' + DragInWizard.wizardIdentifier + ' .media').each(function() {
			var CTypeCheck = $(this).find('input').attr('value').match(/^([^_]*?)_(.*)$/);
			CTypeCheck.shift();
			if(CTypeCheck[0] === 'gridelements') {
				CType = 'gridelements_pi1';
				var txGridelementsBackendLayout = CTypeCheck[1];
				$(this).find('.media-left').addClass('t3-ctype-identifier').attr('data-tx_gridelements_backend_layout', txGridelementsBackendLayout);
			} else {
				CType = CTypeCheck[1];
			}
			$(this).find('.media-left').addClass('t3-ctype-identifier').attr('data-ctype', CType);

			var description = $(this).find('.media-body');
			description = description.appendTo( panel );
			description.width(descriptionWidth);
			$(this).find('.media-left').on('mouseenter', function() { description.fadeIn() }).on('mouseleave', function() { description.hide() });
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