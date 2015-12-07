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

define(['jquery', 'TYPO3/CMS/Backend/AjaxDataHandler', 'TYPO3/CMS/Backend/Storage', 'TYPO3/CMS/Gridelements/GridElementsDragDrop', 'TYPO3/CMS/Backend/Modal'], function ($, AjaxDataHandler, Storage, DragDrop, Modal) {

	var OnReady = {};

	AjaxDataHandler.identifier.allGridelementsToggle = '.t3js-toggle-gridelements-all';
	AjaxDataHandler.identifier.gridelementToggle = '.t3js-toggle-gridelements-list';
	AjaxDataHandler.identifier.allGridelementsColumnsToggle = '.t3js-toggle-gridelements-columns-all';
	AjaxDataHandler.identifier.gridelementColumnToggle = '.t3js-toggle-gridelements-column';

	/**
	 * initializes Drag+Drop for all content elements on the page
	 */
	OnReady.initialize = function () {
		if ($('#recordlist-tt_content').length) {
			OnReady.activateAllGridExpander();
		}
		if ($('.t3js-page-columns').length) {
			OnReady.setAllowedClasses();
			OnReady.activateAllCollapseIcons();
			OnReady.activatePasteIcons();
		}
	};

	/**
	 * sets the classes for allowed element types to the cells of the original page module
	 */
	OnReady.setAllowedClasses = function () {
		$('table.t3js-page-columns > tbody > tr > td').each(function () {
			var colPos = $(this).data('colpos') ? $(this).data('colpos') : $(this).find('> .t3-page-ce-wrapper').data('colpos');
			if (typeof colPos !== 'undefined') {
				$(this).addClass(top.pageColumnsAllowedCTypes[colPos]);
				$(this).addClass(top.pageColumnsAllowedGridTypes[colPos]);
				OnReady.setAllowedParameters($(this), colPos);
			}
		});
	};

	/**
	 * sets the parameters for allowed element types to the add new content links of the original page module
	 */
	OnReady.setAllowedParameters = function (pageColumn, colPos) {
		var allowedCTypes = top.pageColumnsAllowedCTypes[colPos].replace(/ t3-allow-/g, ',').substring(1);
		var allowedGridTypes = top.pageColumnsAllowedGridTypes[colPos].replace(/ t3-allow-gridtype-/g, ',').substring(1);
		if (allowedCTypes !== '' && allowedCTypes !== 'all' || allowedGridTypes !== '') {
			pageColumn.find('.t3js-page-new-ce:not(".t3js-page-new-ce-allowed") a').each(function () {
				$(this).attr('onclick', $(this).attr('onclick').replace(
					'\\u0026uid_pid',
					'\\u0026tx_gridelements_allowed=' + allowedCTypes + '\\u0026tx_gridelements_allowed_grid_types=' + allowedGridTypes + '\\u0026uid_pid'
				));
			});
		}
	};

	/**
	 * activates the arrow icons to show/hide content previews within a certain grid column     */
	OnReady.activateAllCollapseIcons = function () {
		OnReady.activateCollapseIcons();
		var lastIcon = $('.module-docheader-bar-column-left .btn-group .icon').last().parent();
		var addNewIcon = $('.t3js-toggle-gridelements-column').first();
		var newIcon = addNewIcon.clone().attr('class', 'btn btn-default btn-sm t3js-gridcolumn-toggle t3js-gridcolumn-expand').insertAfter(lastIcon);
		newIcon.contents().filter(function () {
			return (this.nodeType == 3);
		}).remove();
		newIcon.find('.icon-actions-view-list-collapse').remove();
		newIcon.removeAttr('onclick').attr('title', 'Expand all grid columns');
		var newIcon = addNewIcon.clone().attr('class', 'btn btn-default btn-sm t3js-gridcolumn-toggle').insertAfter(lastIcon);
		newIcon.contents().filter(function () {
			return (this.nodeType == 3);
		}).remove();
		newIcon.find('.icon-actions-view-list-expand').remove();
		newIcon.removeAttr('onclick').attr('title', 'Collapse all grid columns');
		$(document).on('click', '.t3js-gridcolumn-toggle', function (evt) {
			evt.preventDefault();

			var $me = $(this),
					collapsed = $me.hasClass('t3js-gridcolumn-expand') ? 0 : 1;

			// Store collapse state in UC
			var storedModuleDataPage = {};

			if (Storage.Persistent.isset('moduleData.page.gridelementsCollapsedColumns')) {
				storedModuleDataPage = Storage.Persistent.get('moduleData.list.gridelementsExpanded');
			}

			var collapseConfig = {};
			$('[data-columnkey]').each(function () {
				collapseConfig[$(this).data('columnkey')] = collapsed;
				$(this).removeClass('collapsed','expanded');
				$(this).addClass(collapsed ? 'collapsed' : 'expanded');
			});

			$.extend(true, storedModuleDataPage, collapseConfig);
			Storage.Persistent.set('moduleData.page.gridelementsCollapsedColumns', storedModuleDataPage);

		});
	}

	/**
	 * activates the arrow icons to show/hide content previews within a certain grid column     */
	OnReady.activateCollapseIcons = function () {
		$(document).on('click', AjaxDataHandler.identifier.gridelementColumnToggle, function (evt) {
			evt.preventDefault();

			var $me = $(this),
					column = $me.closest('.t3js-page-column').data('colpos'),
					columnKey = $me.closest('.t3js-page-column').data('columnkey'),
					isExpanded = $me.data('state') === 'expanded';

			// Store collapse state in UC
			var storedModuleDataPage = {};

			if (Storage.Persistent.isset('moduleData.page.gridelementsCollapsedColumns')) {
				storedModuleDataPage = Storage.Persistent.get('moduleData.page.gridelementsCollapsedColumns');
			}

			var expandConfig = {};
			expandConfig[columnKey] = isExpanded ? 1 : 0;

			$.extend(true, storedModuleDataPage, expandConfig);
			Storage.Persistent.set('moduleData.page.gridelementsCollapsedColumns', storedModuleDataPage).done(function () {
				$me.data('state', isExpanded ? 'collapsed' : 'expanded');
			});

			$me.closest('.t3-grid-cell').toggleClass('collapsed','expanded');
			var originalTitle = $me.attr('title');
			$me.attr('title', $me.attr('data-toggle-title'));
			$me.attr('data-toggle-title', originalTitle);
			$me.blur();

		});

		$('.t3-page-column-header-icons').each(function () {
			$(this).addClass('btn-group btn-group-sm');
			$(this).find('a').addClass('btn btn-default');
		});
	}

	/**
	 * activates the paste into / paste after and fetch copy from another page icons outside of the context menus */
	OnReady.activatePasteIcons = function () {
		$('.icon-actions-document-paste-into').parent().remove();
		$('.t3-page-ce-wrapper-new-ce').each(function () {
			$(this).addClass('btn-group btn-group-sm');
			if (top.pasteAfterLinkTemplate && top.pasteIntoLinkTemplate) {
				var parent = $(this).parent();
				if (parent.data('page') || (parent.data('container') && !parent.data('uid'))) {
					$(this).append(top.pasteIntoLinkTemplate);
				} else {
					$(this).append(top.pasteAfterLinkTemplate);
				}
				$('.t3js-page-lang-column .t3-page-ce > .t3-page-ce').removeClass('t3js-page-ce');
				$(this).find('.t3js-paste').on('click', function (evt) {
					evt.preventDefault();
					OnReady.activatePasteModal($(this));
				});
			}
		});
	}

	/**
	 * activates the paste into / paste after and fetch copy from another page icons outside of the context menus */
	OnReady.activatePasteModal = function (element) {
		var $element = $(element);
		var url = $element.data('url') || null;
		var title = $element.data('title') || 'Paste "' + $element.data('pastetitle') + '"';
		var severity = (typeof top.TYPO3.Severity[$element.data('severity')] !== 'undefined') ? top.TYPO3.Severity[$element.data('severity')] : top.TYPO3.Severity.info;
		if ($element.hasClass('t3js-paste-copy')) {
			var content = $element.data('content') || 'How do you want to paste that clipboard content here?';
			var buttons = [
				{
					text: $element.data('button-close-text') || 'Cancel',
					active: true,
					btnClass: 'btn-default',
					trigger: function () {
						Modal.currentModal.trigger('modal-dismiss');
					}
				},
				{
					text: $element.data('button-paste-text') || 'Paste as copy',
					btnClass: 'btn-' + Modal.getSeverityClass(severity),
					trigger: function () {
						Modal.currentModal.trigger('modal-dismiss');
						DragDrop.onDrop($element.data('pasteitem'), $element, null);
					}
				},
				{
					text: $element.data('button-reference-text') || 'Paste as reference',
					btnClass: 'btn-' + Modal.getSeverityClass(severity),
					trigger: function () {
						Modal.currentModal.trigger('modal-dismiss');
						DragDrop.onDrop($element.data('pasteitem'), $element, 'reference');
					}
				}
			];
			if(top.pasteReferencesAllowed !== true) {
				buttons.pop();
			}
		} else {
			var content = $element.data('content') || 'Do you want to paste that clipboard content here?';
			var buttons = [
				{
					text: $element.data('button-close-text') || 'Cancel',
					active: true,
					btnClass: 'btn-default',
					trigger: function () {
						Modal.currentModal.trigger('modal-dismiss');
					}
				},
				{
					text: $element.data('button-paste-text') || 'Paste',
					btnClass: 'btn-' + Modal.getSeverityClass(severity),
					trigger: function () {
						Modal.currentModal.trigger('modal-dismiss');
						DragDrop.onDrop($element.data('pasteitem'), $element, null);
					}
				}
			];
		}
		if (url !== null) {
			var separator = (url.indexOf('?') > -1) ? '&' : '?';
			var params = $.param({data: $element.data()});
			Modal.loadUrl(title, severity, buttons, url + separator + params);
		} else {
			Modal.show(title, content, severity, buttons);
		}
	}

	/**
	 * activates the toggle icons to open listings of nested grid container structure in the list module
	 */
	OnReady.activateAllGridExpander = function () {
		OnReady.activateGridExpander();
		$(document).on('click', AjaxDataHandler.identifier.allGridelementsToggle, function (evt) {
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
			$(container.split(',')).each(function (el, id) {
				if (id > 0) {
					expandConfig[id] = isExpanded;
					if (isExpanded === 1) {
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
	OnReady.activateGridExpander = function () {
		$(document).on('click', AjaxDataHandler.identifier.gridelementToggle, function (evt) {
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
			Storage.Persistent.set('moduleData.list.gridelementsExpanded', storedModuleDataList).done(function () {
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
	return function () {
		OnReady.initialize();
		return OnReady;
	}();
});