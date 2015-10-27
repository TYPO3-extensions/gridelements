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
 * this JS code initializes several settings for the Layout module (Web => Page)
 * based on jQuery UI
 */

define(['jquery'], function($) {

	var OnReady = {
	};

	/**
	 * initializes Drag+Drop for all content elements on the page
	 */
	OnReady.initialize = function() {
		OnReady.setAllowedClasses();
		OnReady.activateContentToggles();
	};

	/**
	 * sets the classes for allowed element types to the cells of the original page module
	 */
	OnReady.setAllowedClasses = function() {
		$('table.t3js-page-columns > tbody > tr > td').each(function() {
			$(this).addClass(top.pageColumnsAllowedCTypes[$(this).data('colpos')]);
			OnReady.setAllowedParameters($(this));
		});
	};

	/**
	 * sets the parameters for allowed element types to the add new content links of the original page module
	 */
	OnReady.setAllowedParameters = function(pageColumn) {
		var allowedCTypes = top.pageColumnsAllowedCTypes[pageColumn.data('colpos')].replace(/ t3-allow-/g, ',').substring(1);
		if (allowedCTypes !== '' && allowedCTypes !== 'all') {
			pageColumn.find('.t3js-page-new-ce:not(".t3js-page-new-ce-allowed") a').each(function() {
				$(this).attr('onclick', $(this).attr('onclick').replace(
					'\\u0026uid_pid',
					'\\u0026tx_gridelements_allowed=' + allowedCTypes + '\\u0026uid_pid'
				));
			});
		}
	};

	/**
	 * activates the arrow icons to show/hide content previews within a certain grid column
	 */
	OnReady.activateContentToggles = function() {
		$('.toggle-content').each(function () {
			$(this).click(function () {
				$(this).closest('.t3-grid-cell').toggleClass('invisible-content');
				return false;
			});
		});
	}

	/**
	 * initialize function
	 */
	return function() {
		OnReady.initialize();
		return OnReady;
	}();
});