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

define(['jquery', 'TYPO3/CMS/Backend/AjaxDataHandler', 'TYPO3/CMS/Backend/Storage'], function($, AjaxDataHandler, Storage) {

	var OnReady = {
	};

	AjaxDataHandler.identifier.allGridelementsToggle = '.t3js-toggle-gridelements-all';
	AjaxDataHandler.identifier.gridelementToggle = '.t3js-toggle-gridelements-list';

	/**
	 * initializes Drag+Drop for all content elements on the page
	 */
	OnReady.initialize = function() {
		if($('#recordlist-tt_content').length) {
			OnReady.activateAllGridExpander();
		}
		if($('.t3js-page-columns').length) {
			OnReady.setAllowedClasses();
			OnReady.activateContentToggles();
		}
	};

	/**
	 * sets the classes for allowed element types to the cells of the original page module
	 */
	OnReady.setAllowedClasses = function() {
		$('table.t3js-page-columns > tbody > tr > td').each(function() {
			$(this).addClass(top.pageColumnsAllowedCTypes[$(this).data('colpos')]);
			$(this).addClass(top.pageColumnsAllowedGridTypes[$(this).data('colpos')]);
			OnReady.setAllowedParameters($(this));
		});
	};

	/**
	 * sets the parameters for allowed element types to the add new content links of the original page module
	 */
	OnReady.setAllowedParameters = function(pageColumn) {
		var allowedCTypes = top.pageColumnsAllowedCTypes[pageColumn.data('colpos')].replace(/ t3-allow-/g, ',').substring(1);
		var allowedGridTypes = top.pageColumnsAllowedGridTypes[pageColumn.data('colpos')].replace(/ t3-allow-gridtype-/g, ',').substring(1);
		if (allowedCTypes !== '' && allowedCTypes !== 'all' || allowedGridTypes !== '') {
			pageColumn.find('.t3js-page-new-ce:not(".t3js-page-new-ce-allowed") a').each(function() {
				$(this).attr('onclick', $(this).attr('onclick').replace(
					'\\u0026uid_pid',
					'\\u0026tx_gridelements_allowed=' + allowedCTypes + '\\u0026tx_gridelements_allowed_grid_types=' + allowedGridTypes + '\\u0026uid_pid'
				));
			});
		}
	};

	/**
	 * activates the arrow icons to show/hide content previews within a certain grid column	 */
	OnReady.activateContentToggles = function() {
		$('.toggle-content').each(function () {
			$(this).click(function () {
				$(this).closest('.t3-grid-cell').toggleClass('invisible-content');
				return false;
			});
		});
	}

	/**
	 * activates the toggle icons to open listings of nested grid container structure in the list module
	 */
	OnReady.activateAllGridExpander = function() {
		OnReady.activateGridExpander();
		$(document).on('click', AjaxDataHandler.identifier.allGridelementsToggle, function(evt) {
			evt.preventDefault();

			var $me = $(this),
				container = '0,' + $me.data('container-ids'),
				isExpanded = this.id === 't3-gridelements-expand-all' ? 1 : 0;

			// Store collapse state in UC
			var storedModuleDataList = {};

			if (Storage.Persistent.isset('moduleData.list.gridelementsExpanded')) {
				storedModuleDataList = Storage.Persistent.get('moduleData.list.gridelementsExpanded');
			}

			var expandConfig = {};
			$(container.split(',')).each(function(el, id) {
				if(id > 0) {
					expandConfig[id] = isExpanded;
					if(isExpanded === 1) {
						$('[data-uid=' + id + ']').find('.t3js-toggle-gridelements-list').addClass('open-gridelements-container');
						$('[data-trigger-container=' + id + ']').show();
					} else {
						$('[data-uid=' + id + ']').find('.t3js-toggle-gridelements-list').removeClass('open-gridelements-container');
						$('[data-trigger-container=' + id + ']').hide();
					}
				}
			});

			$.extend(true, storedModuleDataList, expandConfig);
			Storage.Persistent.set('moduleData.list.gridelementsExpanded', storedModuleDataList);

		});

	};

	/**
	 * activates the toggle icons to open listings of nested grid container structure in the list module
	 */
	OnReady.activateGridExpander = function() {
		$(document).on('click', AjaxDataHandler.identifier.gridelementToggle, function(evt) {
			evt.preventDefault();

			var $me = $(this),
				container = $me.closest('tr').data('uid'),
				isExpanded = $me.data('state') === 'expanded';

			// Store collapse state in UC
			var storedModuleDataList = {};

			if (Storage.Persistent.isset('moduleData.list.gridelementsExpanded')) {
				storedModuleDataList = Storage.Persistent.get('moduleData.list.gridelementsExpanded');
			}

			var expandConfig = {};
			expandConfig[container] = isExpanded ? 0 : 1;

			$.extend(true, storedModuleDataList, expandConfig);
			Storage.Persistent.set('moduleData.list.gridelementsExpanded', storedModuleDataList).done(function() {
				$me.data('state', isExpanded ? 'collapsed' : 'expanded');
			});

			$(this).toggleClass('open-gridelements-container');
			var originalTitle = $(this).attr('data-original-title');
			$(this).attr('data-original-title', $(this).attr('data-toggle-title'));
			$(this).attr('data-toggle-title', originalTitle);
			$(this).blur();

			$('[data-trigger-container=' + $(this).closest('tr').data('uid') + ']').toggle().find('.open-gridelements-container').click();
		});

	};

	/**
	 * initialize function
	 */
	return function() {
		OnReady.initialize();
		return OnReady;
	}();
});