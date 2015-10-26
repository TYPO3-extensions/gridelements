/*
 * drag-and-drop library for content elements
 * requires ExtJS
 * 
 * - FEATURE: reload-less DnD: compare page lastchange to current page "age" on ajax (pageRenderTime inserted by onReady injector)
 */

GridElementsDD = function() {
	var
	// set when initAll() has finished
		isInitialized = false,

	// default draggable template - filled on initAll
		defaultTemplate = '',

	// basic setup for all drag elements (existing content elements that can be dragged around)

		dragBehaviorDragelements = {
			// the current class
			dragClass: null,

			// flag for "draggables" (new content elements dragged in)
			isDraggable: false,

			// cache for content of the dragger
			draggerContent: null,

			// called whenever dragging starts (mousedown for a little while)
			b4StartDrag: function() {

				Ext.dd.ScrollManager.register('typo3-docbody');

				Ext.dd.ScrollManager.frequency = 50;
				Ext.dd.ScrollManager.increment = 20;
				Ext.dd.ScrollManager.animate = false;

				// reset all top. properties set below
				top.originalfirstDroptarget = null;
				top.originalPositionDropTargetId = null;
				top.elementUID = null;
				top.targetUID = null;
				// memorize current id
				top.elId = this.id;
				top.elementCType = '';
				top.elementGridType = '';
				// set isDragging for other scripts
				top.isDragging = true;

				// cache dragger
				if (!this.el) {
					this.el = Ext.get(this.getEl());
				}
				if (!this.scrollBody) {
					this.scrollBody = Ext.get('typo3-docbody');
				}

				top.startScrollTop = this.scrollBody.dom.scrollTop;
				top.startScrollLeft = this.scrollBody.dom.scrollLeft;

				// is this a new or an existing element?
				var dragEl = Ext.get(this.el);
				if (dragEl.select('span.ce-icons-left a span').elements.length > 0) {
					top.elementUID = dragEl.select('span.ce-icons-left a span').elements[0].getAttribute('title').split(' ')[0].replace('id=', '').match(/^(\d)+/g);

					var currentSpacer = document.createElement('div');
					Ext.get(currentSpacer).addClass('t3-dd-spacer');
					Ext.get(currentSpacer).insertAfter(this.getEl());

				} else {
					top.elementUID = 'NEW';
				}

				// is this a top level element?
				top.isTopLevelOnly = (dragEl.select('div.t3-gridTLContainer').elements.length ? true : false);

				// get CType
				var draggableClassName = '';
				if (dragEl.select('div.t3-page-ce-body div[class^=t3-ctype-]').elements.length){
					// existing ce
					draggableClassName = dragEl.select('div.t3-page-ce-body div[class^=t3-ctype-]').elements[0].className;
				} else {
					// new ce
					draggableClassName = dragEl.select('div[class^=t3-ctype-]').elements[0].className;
					Ext.select('#x-dd-draggablecontainer').addClass('dragging-active');
				}
				top.elementCType = draggableClassName.split(' ')[0].substr(9);
				if (draggableClassName.split(' ')[1]) {
					top.elementGridType = draggableClassName.split(' ')[1].substr(12);
				}

				// always cache the original XY Coordinates of the element
				this.originalXY = this.el.getXY();

				// memorize current position of this.el in DOM
				this.nextEl = this.el.next();
				this.prevEl = this.el.prev();

				// reset invalidDrop
				this.invalidDrop = false;

				// add dragging class
				this.el.addClass('x-dd-dragging');

				if (this.isDraggable) {
					// cache current content within dragger
					this.draggerContent = this.el.dom.innerHTML;

					// this uses the actual dragger icon:
					this.draggerTemplate = '<div>' + this.prevEl.dom.innerHTML + '</div>';

					/*
					 // TODO: get actual template (maybe get actual content after drop finished), this is just a demo one
					 this.draggerTemplate = '\
					 <div class="t3-page-ce x-dd-makemedrag x-dd-makedroptarget x-dd-droptargetgroup-els active">\
					 <h4 class="t3-page-ce-header">\
					 <div class="t3-row-header">\
					 <a href="#" onclick="window.location.href=\'{editurl}\'; return false;" title="Edit"><span class="t3-icon t3-icon-actions t3-icon-actions-document t3-icon-document-open">&nbsp;</span></a>\
					 <a href="{hideurl}" title="Hide"><span class="t3-icon t3-icon-actions t3-icon-actions-edit t3-icon-edit-hide">&nbsp;</span></a>\
					 <a href="{deleteurl}" onclick="return confirm(String.fromCharCode(65,114,101,32,121,111,117,32,115,117,114,101,32,121,111,117,32,119,97,110,116,32,116,111,32,100,101,108,101,116,101,32,116,104,105,115,32,114,101,99,111,114,100,63));" title="Delete"><span class="t3-icon t3-icon-actions t3-icon-actions-edit t3-icon-edit-delete">&nbsp;</span></a>\
					 <span class="t3-page-ce-icons-move">\
					 <a href="{moveupurl}" title="Move record up"><span class="t3-icon t3-icon-actions t3-icon-actions-move t3-icon-move-up">&nbsp;</span></a>\
					 <a href="{movedownurl}" title="Move record down"><span class="t3-icon t3-icon-actions t3-icon-actions-move t3-icon-move-down">&nbsp;</span></a>\
					 </span>\
					 </div>\
					 </h4>\
					 <div class="t3-page-ce-body">\
					 <div class="t3-page-ce-type">\
					 <a href="#" onclick="showClickmenu(&quot;tt_content&quot;,&quot;263&quot;,&quot;1&quot;,&quot;&quot;,&quot;..%2F..%2F..%2F|727949ac25&quot;,&quot;&quot;);return false;" oncontextmenu="showClickmenu(&quot;tt_content&quot;,&quot;263&quot;,&quot;1&quot;,&quot;&quot;,&quot;..%2F..%2F..%2F|727949ac25&quot;,&quot;&quot;);return false;"><span title="" class="t3-icon t3-icon-mimetypes t3-icon-mimetypes-x t3-icon-x-content-table">&nbsp;</span></a>\
					 <span class="t3-icon t3-icon-flags t3-icon-flags-gb t3-icon-gb">&nbsp;</span>&nbsp;English (Default) &nbsp;<strong>Table</strong>\
					 </div>\
					 <div>\
					 <strong><a href="#" onclick="window.location.href=\'{editurl}\'; return false;" title="Edit">{exampletitle}</a></strong><br>\
					 <span class="exampleContent">{exampletext}</span>\
					 </div>\
					 </div>\
					 <div class="x-dd-droptargetarea debugme">\
					 <div class="x-dd-droptargetarea" title="">{dropzonetext}</div>\
					 </div>\
					 </div>\
					 ';
					 */

					// template node found? use default one
					if (this.draggerTemplate) {
						this.el.dom.innerHTML = this.draggerTemplate;
					}else{
						this.el.dom.innerHTML = defaultTemplate;
					}

					var newElRel = String(this.prevEl.getAttribute('onclick'));
					newElRel = newElRel.split('document.editForm.defValues.value=unescape(\'');
					newElRel = newElRel[1].split('\');goToalt_doc();');
					this.el.dom.rel = unescape(newElRel[0]).replace(/defVals\[tt_content\]/g, 'data[tt_content][NEW]');

				} else {
					this.nextEl = false;
				}

				// activate dropzones of elements other than the current one, depending on ctype and toplevel
				Ext.each(Ext.select('.x-dd-droptargetgroup-' + this.dragClass).elements, function(elementNow) {

					var showDropTarget = false;

					if (top.elId !== elementNow.id){

						var elNow = Ext.get(elementNow);
						var parentGridContainer = elNow.findParent('div.t3-gridContainer', 7);

						if (elNow.select('.x-dd-droptargetarea').elements[0]){
							if (elNow.findParent('td.t3-allow-all', 5)
								|| elNow.findParent('td.t3-allow-'+top.elementCType, 5) && top.elementGridType === ''
								|| elNow.findParent('td.t3-allow-gridelements_pi1', 5) && elNow.findParent('td.t3-allow-gridtype-'+top.elementGridType, 5)){
								if (top.isTopLevelOnly && parentGridContainer){
									if (elNow.findParent('td.t3-gridTL', 5)){
										showDropTarget = true;
									}
								}else{
									showDropTarget = true;
								}
							}
						}

					}

					if (showDropTarget){
						Ext.get(elementNow).select('.x-dd-droptargetarea').addClass('x-dd-showdroptarget');
					}else{
						Ext.get(elementNow).select('.x-dd-droptargetarea').removeClass('x-dd-showdroptarget');
					}

				});
			},
			// called when the drag element is dragged over the a drop target with the same ddgroup
			onDragEnter: function(evtObj, targetElId) {
				// colorize the drag target if the drag node's parent is not the same as the drop target
				if (
					targetElId !== this.getEl().id
					&&
					(
					(
					top.originalPositionDropTargetId
					&&
					top.originalPositionDropTargetId !== targetElId
					)
					||
					(
					!top.originalPositionDropTargetId
					&&
					top.originalfirstDroptarget !== targetElId
					)
					)
				) {
					Ext.get(targetElId).addClass('x-dd-overdroparea');
				}
			},
			onDrag: function(evtObj, targetElId) {
				this.el.dom.style.left = evtObj.xy[0] - this.originalXY[0] + this.scrollBody.dom.scrollLeft - top.startScrollLeft + 'px';
				this.el.dom.style.top = evtObj.xy[1] - this.originalXY[1] + this.scrollBody.dom.scrollTop - top.startScrollTop  + 'px';
			},
			// called when element is dragged out of a dropzone with the same ddgroup
			onDragOut: function(evtObj, targetElId) {
				// remove "over" class from drop target
				Ext.get(targetElId).removeClass('x-dd-overdroparea');
			},

			// called when element is dropped not anything other than a dropzone with the same ddgroup
			onInvalidDrop: function() {
				// set invalidDrop flag
				this.invalidDrop = true;
			},

			// called when dnd completes successfully
			onDragDrop: function(evtObj, targetElId) {

				Ext.get(this.el.dom.id).select('.x-dd-showdroptarget').removeClass('x-dd-showdroptarget');

				// reset invalidDrop
				this.invalidDrop = false;

				if (
					// move node only if the drag element is not the same as the drop target
				Ext.get(targetElId).hasClass('x-dd-showdroptarget')
				&&
				this.el.dom.id !== targetElId
				&&
					// cancel drops resulting in current position
				(top.originalPositionDropTargetId !== targetElId)
				&&
					// cancel drops resulting in current position
				(top.originalfirstDroptarget !== targetElId)
				) {

					// we need a flag to define, if we have to insert the element on top of any column
					var columnInsert = true;

					// the title contains the relevant information
					// when it is "column-12345-6" the position is on top of column 6 of element 12345
					// when it is "id=12345" the position is after element 12345
					// otherwise the position is on top of column x of the current page
					var targetTitle = Ext.get(targetElId).getAttribute('title');
					if (targetTitle.substr(0,7) === 'column-') {
						top.targetUID = targetTitle.substr(7);
					} else if (targetTitle.substr(0,3) === 'id=') {
						top.targetUID = targetTitle.substr(3).split(' ')[0];
						columnInsert = false;
					} else {
						top.targetUID = targetTitle.replace(/DD_DROP_PID/g, top.elementUID);
					}

					// Ajax timeout should match the server timeout
					Ext.Ajax.timeout = 600000;

					// if the user pressed the CTRL-key while dropping, the action has to be a copy
					// otherwise it's just a move
					var
						actionURL = '',
						ctrlPressed = false;

					if (evtObj.ctrlKey) {
						actionURL = top.copyURL.replace(/DD_DRAG_UID/g, top.elementUID);
						ctrlPressed = true;
					} else {
						actionURL = top.moveURL.replace(/DD_DRAG_UID/g, top.elementUID);
					}

					// the rest of the action URL works the same way for both actions
					actionURL = actionURL.replace(/DD_DROP_UID/g, '-' + top.targetUID);
					actionURL = actionURL.replace('../../../', top.TS.PATH_typo3);

					// we don't need the redirect URL, since we will do a reload after the Ajax action
					// so a redirect within the Ajax action would be too much server load here
					actionURL = actionURL.replace('&redirect=1', '');

					GridElementsDD.doCmdAction(actionURL, ctrlPressed);

					// add after Ext.get(targetElId)
					if (columnInsert) {
						this.el.insertAfter(Ext.get(targetElId).parent());
					} else {
						this.el.insertAfter(Ext.get(targetElId).parent().parent());
					}

					// remove the drag invitation
					this.onDragOut(evtObj, targetElId);

					// clear the styles and reset content to previous
					this.el.dom.style.position ='';
					this.el.dom.style.top = '';
					this.el.dom.style.left = '';
				} else {

					// invalid drop, initiate a repair
					this.onInvalidDrop();
				}
			},

			// called after dnd ends with or without success
			endDrag: function() {
				// Remove tmp spacer div from grid layout
				var ddSpacer = Ext.select('.t3-dd-spacer');
				if (ddSpacer.elements.length){
					ddSpacer.first().remove();
				}

				// invoke animation only if invalidDrop is true
				if (this.invalidDrop === true) {

					// define animation
					var animCfgObj = {
						easing: 'easeNone', //'elasticOut',
						duration: 0.2,
						scope: this,
						callback: function() {
							// remove the position attribute
							this.el.dom.style.position = '';
							this.el.dom.style.left = '';
							this.el.dom.style.top = '';

							if (this.isDraggable) {
								// replace content with original draggerContent
								this.el.dom.innerHTML = this.draggerContent;
							}

							// put item back to original DOM position
							this.el.insertAfter(this.prevEl);
						}
					};

					// apply animation
					this.el.moveTo(this.originalXY[0], this.originalXY[1], animCfgObj);

					// reset invalidDrop
					this.invalidDrop = false;
				}

				// remove dragging class
				this.el.removeClass('x-dd-dragging');

				// deactivate all dropzones after all drops
				Ext.each(Ext.select('.x-dd-droptargetgroup-' + this.dragClass).elements, function(elementNow) {
					Ext.get(elementNow).select('.x-dd-droptargetarea').removeClass('x-dd-showdroptarget');
				});

				// set isDragging for other scripts
				top.isDragging = false;
				Ext.select('.dragging-active').removeClass('dragging-active');
			}
		},

	// copy dragBehaviorDragelements onto dragBehaviorDraggables
		dragBehaviorDraggables = Ext.apply({}, dragBehaviorDragelements);

	// end var

	// overwrite dragBehaviorDraggables specials
	dragBehaviorDraggables.isDraggable = true;
	dragBehaviorDraggables.onDragDrop = function(evtObj, targetElId) {
		// remove invalidDrop flag
		this.invalidDrop = false;

		// move node only if the drag element's parent is not the same as the drop target
		if (this.el.dom.parentNode.id !== targetElId && Ext.get(targetElId).hasClass('x-dd-showdroptarget')) {

			// clone template element
			var newContentEl = Ext.get(this.el).dom.cloneNode(true);

			// reset the element's ID as this 
			newContentEl.id = '';

			// add clone to DOM after Ext.get(targetElId) to ...
			var extNewEl = Ext.get(newContentEl).insertAfter(Ext.get(targetElId).parent().parent());

			// assign drag element group
			var	dragElementNow = new Ext.dd.DD(Ext.get(extNewEl), 'droptargets-' + this.dragClass, {
				isTarget: false,
				scroll: false,
				maintainOffset: false
			});

			/*
			 // assign an ID to contained H4
			 var tempH4El = Ext.get(extNewEl.select('h4').elements[0]);

			 // restrict drag handle to h4 within
			 dragElementNow.setHandleElId(extNewEl.select('h4').elements[0].id);
			 */

			// apply the overrides object to the newly created instance of DD
			dragBehaviorDragelements.dragClass = this.dragClass;
			Ext.apply(dragElementNow, dragBehaviorDragelements);

			// make extNewEl a dropzone too - one for each contained class!
			var
			// init matchingClass
				matchingClass = '',
			// get all currentClasses
				currentClasses = extNewEl.dom.className.split(' ');

			// look for x-dd-droptargetgroup-XYZ class
			for(var i in currentClasses) {

				if (!currentClasses.hasOwnProperty(i)) {
					continue;
				}

				if (/x-dd-droptargetgroup-/.test(currentClasses[i]) === true) {
					matchingClass = currentClasses[i].split('-')[3];

					// get all possible droptargets
					// there might be more than one in cascaded elements (sub-elements)
					var allMatches = extNewEl.select('.x-dd-droptargetarea').elements;

					// we need to make the last one a drop target
					// this ensures we get the one matching the current element, not one of a sub-element
					var dropZoneNow = new Ext.dd.DDTarget(allMatches[allMatches.length - 1], 'droptargets-' + matchingClass);
				}
			}

			// remove the drag invitation from drop area
			this.onDragOut(evtObj, targetElId);

			// clear the styles and reset content to previous
			/*
			 this.el.dom.style.position = 'relative';
			 this.el.dom.style.top = '';
			 this.el.dom.style.left = '';
			 */

			// replace content with original draggerContent
			//this.el.dom.innerHTML = this.draggerContent;

			// set title
			var targetTitle = Ext.get(targetElId).getAttribute('title');

			if (targetTitle.substr(0,7) === 'column-') {
				top.targetUID = targetTitle.substr(7);
			} else if (targetTitle.substr(0,3) === 'id=') {
				top.targetUID = targetTitle.substr(3);
			} else {
				top.targetUID = targetTitle.replace(/DD_DROP_PID/g, 'NEW');
			}

			// show loading spinner
			top.TYPO3.Backend.ContentContainer.setMask();

			actionURL = top.TYPO3.configuration.PATH_typo3 + 'alt_doc.php?' + this.el.dom.rel + '&edit[tt_content][]=new';

			var languageLink = Ext.get(targetElId).parent().parent().select('.t3-page-ce-wrapper-new-ce a').elements[0];
			var languageUid = 0;
			if (typeof languageLink !== 'undefined') {
				languageLink = languageLink.getAttribute('onclick').split('&sys_language_uid=');
				if (languageLink[1] !== 'undefined') {
					languageUid = languageLink[1].split('&')[0];
				}
				Ext.Ajax.request({
					url: actionURL,
					params: {
						doSave: 1,
						'data[tt_content][NEW][pid]': '-' + top.targetUID,
						'data[tt_content][NEW][sys_language_uid]': languageUid,
						'data[tt_content][NEW][header]': TYPO3.l10n.localize('tx_gridelements_js.newcontentelementheader'),
						DDinsertNew : top.DDpid,
						formToken : top.DDtoken
					},
					method: 'GET',
					success: function( result, request ) {
						if (GridElementsDD.baseConf.doReloadsAfterDrops) {
							// reload page to verify/show updates
							locationSplit = self.location.href.split('#');
							self.location.href = locationSplit[0] + '#ce' + top.targetUID;
							self.location.reload(true);
						}else{
							// after the operation has finished, we simply hide the spinner
							top.TYPO3.Backend.ContentContainer.removeMask();
						}
					},
					failure: function( result, request ) {
						if (GridElementsDD.baseConf.doReloadsAfterDrops) {
							// reload page to verify/show updates
							locationSplit = self.location.href.split('#');
							self.location.href = locationSplit[0] + '#ce' + top.targetUID;
							self.location.reload(true);
						}else{
							// TODO: error happened - remove just dropped element and show error');
							// after the operation has finished, we simply hide the spinner
							top.TYPO3.Backend.ContentContainer.removeMask();
						}
					}
				});
			} else {
				Ext.Ajax.request({
					url: actionURL,
					params: {
						doSave: 1,
						'data[tt_content][NEW][pid]': '-' + top.targetUID,
						'data[tt_content][NEW][header]': TYPO3.l10n.localize('tx_gridelements_js.newcontentelementheader'),
						DDinsertNew : top.DDpid,
						formToken : top.DDtoken
					},
					method: 'GET',
					success: function( result, request ) {
						if (GridElementsDD.baseConf.doReloadsAfterDrops) {
							// reload page to verify/show updates
							locationSplit = self.location.href.split('#');
							self.location.href = locationSplit[0] + '#ce' + top.targetUID;
							self.location.reload(true);
						}else{
							// after the operation has finished, we simply hide the spinner
							top.TYPO3.Backend.ContentContainer.removeMask();
						}
					},
					failure: function( result, request ) {
						if (GridElementsDD.baseConf.doReloadsAfterDrops) {
							// reload page to verify/show updates
							locationSplit = self.location.href.split('#');
							self.location.href = locationSplit[0] + '#ce' + top.targetUID;
							self.location.reload(true);
						}else{
							// TODO: error happened - remove just dropped element and show error');
							// after the operation has finished, we simply hide the spinner
							top.TYPO3.Backend.ContentContainer.removeMask();
						}
					}
				});
			}


		} else {
			// This was an invalid drop, initiate a repair
			this.onInvalidDrop();
		}
	};

	return {

		// some config vars, can be set by the onReady script
		baseConf: {
			// this is set by the onReady loader script (in lib/class.tx_gridelements_addjavascript.php) which gets the current time from the server
			pageRenderTime: null,
			// base url of the extension, used for Ajax URLs
			extBaseUrl: '',
			doReloadsAfterDrops: false,
			useIconsForNew: false
		},

		// stores the UIDs of copied items
		copyItemUids: {},

		// retrieves a localized string from the TYPO3.lang global
		// llId is a key from locallang_db.xml w/o the "tx_gridelements_js." part
		getLL: function(llId) {
			return TYPO3.lang["tx_gridelements_js." + llId];
		},

		// initialize this lib
		initAll: function() {

			this.defaultTemplate = '<div class="x-dd-defaulttpl">' + TYPO3.l10n.localize('tx_gridelements_js.missingcontenttemplate') + '</div>';

			// check, if this.baseConf.pageRenderTime has been str_replaced by onReady script
			if (this.baseConf.pageRenderTime === 'insert_server_time_here') {
				this.baseConf.pageRenderTime = null;
			}

			// this is called when you click an item in the content selection wizard (popup)
			// yes, this one has to be a global!
			window.setFormValueFromBrowseWin = function(colPosUidPid, tableUid, headerText){

				top.elementUID = tableUid.replace(/tt_content_/g, '');
				top.targetUID = colPosUidPid;

				// Ajax timeout should match the server timeout
				Ext.Ajax.timeout = 600000;

				var
					actionURL = '',
					ctrlPressed = true;

				actionURL = top.copyURL.replace(/DD_DRAG_UID/g, top.elementUID);
				actionURL = actionURL.replace(/DD_DROP_UID/g, top.targetUID);
				actionURL = actionURL.replace('../../../', top.TS.PATH_typo3);

				// we don't need the redirect URL, since we will do a reload after the Ajax action
				// so a redirect within the Ajax action would be too much server load here
				actionURL = actionURL.replace('&redirect=1', '');

				GridElementsDD.doCmdAction(actionURL, ctrlPressed);

			}

			// make elements draggable
			Ext.each(Ext.select('.x-dd-makemedrag').elements, function(elementNow) {

				var
					extElNow = Ext.get(elementNow),
					currentClasses = Ext.get(extElNow).dom.className.split(' '),
					matchingClass = '';

				// reset previous settings
				dragBehaviorDragelements.dragClass = null;

				// look for x-dd-droptargetgroup class in all currentClasses and make element draggable using the found targets
				for(var i in currentClasses) {

					if (!currentClasses.hasOwnProperty(i)) {
						continue;
					}

					// find x-dd-droptargetgroup-XYZ class
					if (/x-dd-droptargetgroup-/.test(currentClasses[i]) === true) {
						matchingClass = currentClasses[i].split('-')[3];

						// add Ext.dd.DD class to element with matching IDs
						var dragElementNow = new Ext.dd.DD(elementNow, 'droptargets-' + matchingClass, {
							isTarget: false,
							scroll: false,
							maintainOffset: false
						});

						// restrict drag handle to h4 within
						var handleEl = Ext.get(extElNow.select('h4').elements[0]);
						if (handleEl) {
							dragElementNow.setHandleElId(handleEl.id);
						}

						// apply the overrides object to the newly created instance of DD
						dragBehaviorDragelements.dragClass = matchingClass;
						Ext.apply(dragElementNow, dragBehaviorDragelements);

						// only do this for 1st matching class
						break;
					}
				}

			});

			// add draggers for icons with class x-dd-makedragger: <div class="x-dd-droptargetgroup" style="left: 100px; top: 100px;"></div>
			Ext.each(Ext.select('.x-dd-makedragger').elements, this.makeDragger);

			// define drop targets depending on their x-dd-droptargetgroup-XYZ class
			Ext.each(Ext.select('.x-dd-makedroptarget').elements, function(elementNow) {

				var
					matchingClass = '',
					currentClasses = Ext.get(elementNow).dom.className.split(' ');

				// look for x-dd-droptargetgroup-XYZ class
				for(var i in currentClasses) {

					if (!currentClasses.hasOwnProperty(i)) {
						continue;
					}

					//if (currentClasses[i] === 'x-dd-droptargetgroup-all' || /x-dd-droptargetgroup-/.test(currentClasses[i]) === true) {
					if (/x-dd-droptargetgroup-/.test(currentClasses[i]) === true) {
						matchingClass = currentClasses[i].split('-')[3];

						// get all possible droptargets
						// there might be more than one in cascaded elements (sub-elements)
						var allMatches = Ext.get(elementNow).select('.x-dd-droptargetarea').elements;

						// we need to make the last one a drop target
						// this ensures we get the one matching the current element, not one of a sub-element
						var dropZoneNow = new Ext.dd.DDTarget(allMatches[allMatches.length - 1], 'droptargets-' + matchingClass);

					}
				}
			});

			// add "new reference from other page" icons
			var
				newFromPageIconConf = {
					tag: 'a',
					href: '#',
					title: TYPO3.l10n.localize('tx_gridelements_js.copyfrompage'),
					rel: '',
					cn: {
						tag: 'span',
						'class': top.geSprites.copyfrompage,
						html: '&nbsp;'
					}
				},
			// add doc header "New" icon to a new array that collects all "New" icons
			// arrNewicons = [Ext.select('.t3-icon-document-new', true, 'typo3-docheader-row1').elements[0]];

			// for now: no "get copy from ..." icon on top of page
				arrNewicons = [];

			// add all other ”New" icons to array
			Ext.each(Ext.select('.t3-icon-document-new, .fa-plus-square', true, Ext.select('.t3-page-ce-wrapper-new-ce, .t3-page-ce-new-ce').elements).elements, function(){
				arrNewicons.push(this);
			});

			// add new icon and bind click event
			Ext.each(arrNewicons, function(){
				var parent = typeof this.parent === 'function' ? this.parent() : null;
				newFromPageIconConf.rel = '';
				if (parent){
					var
						onclickAttr = parent.dom.getAttribute('onclick'),
						uidPidMatch = onclickAttr.match(/uid_pid=([-\d]+)/),
						colPosMatch = onclickAttr.match(/colPos=([-\d]+)/),
						containerMatch = onclickAttr.match(/tx_gridelements_container=([-\d]+)/),
						containerColPosMatch = onclickAttr.match(/tx_gridelements_columns=([-\d]+)/),
						uidPid = uidPidMatch !== null && typeof uidPidMatch !== 'undefined' ? uidPidMatch[1] : '';
					colPos = colPosMatch !== null && typeof colPosMatch !== 'undefined' ? colPosMatch[1] : '';
					container = containerMatch !== null && typeof containerMatch !== 'undefined' ? containerMatch[1] : '';
					containerColPos = containerColPosMatch !== null && typeof containerColPosMatch !== 'undefined' ? containerColPosMatch[1] : '';

					if (container > 0) {
						newFromPageIconConf.rel = - container + 'x' + containerColPos;
					} else if (container === '' && uidPid > 0) {
						newFromPageIconConf.rel = uidPid + 'x' + colPos;
					} else {
						newFromPageIconConf.rel = uidPid;
					}

					Ext.DomHelper.insertAfter(parent, newFromPageIconConf, true).on('click', function(targetEvent, targetEl){
							var url = top.backPath + 'browser.php?mode=db&bparams=' + targetEl.parentNode.rel + '|||tt_content|';
							var browserWin = window.open(url, "Typo3WinBrowser", "height=650,width=650,status=0,menubar=0,resizable=1,scrollbars=1");
							browserWin.focus();
						}
					);
				}
			});

			this.isInitialized = true;
		},

		doCmdAction: function(actionURL, ctrlPressed) {
			// before executing the Ajax action, we have to activate the mask with the spinning wheel
			top.TYPO3.Backend.ContentContainer.setMask();
			Ext.Ajax.request({
				url: actionURL,
				success: function( result, request ) {
					if (GridElementsDD.baseConf.doReloadsAfterDrops) {
						// reload page to verify/show updates
						locationSplit = self.location.href.split('#');
						self.location.href = locationSplit[0] + '#ce' + top.targetUID;
						self.location.reload(true);
					}else{
						// after the operation has finished, we simply hide the spinner
						top.TYPO3.Backend.ContentContainer.removeMask();
					}
				},
				failure: function( result, request ) {
					if (GridElementsDD.baseConf.doReloadsAfterDrops) {
						// reload page to verify/show updates
						locationSplit = self.location.href.split('#');
						self.location.href = locationSplit[0] + '#ce' + top.targetUID;
						self.location.reload(true);
					}else{
						// TODO: error happened - put dragged element back to original position');
						top.TYPO3.Backend.ContentContainer.removeMask();
					}
				}
			});
		},

		makeDragger: function(elementNow) {
			var
				extElNow = Ext.get(elementNow),
				elementNowTitle = extElNow.getAttribute('title'),
				elementNowWidth = extElNow.getComputedWidth(),
				elementNowHeight = extElNow.getHeight(),
				elementNowMargins = extElNow.getMargins(),
				elementNowValign = extElNow.getStyles('vertical-align')['vertical-align'],
				elementNowCType = (extElNow.getAttribute('onclick').match(/tt_content%5D%5BCType%5D%3D\w+/) ? 't3-ctype-' + extElNow.getAttribute('onclick').match(/tt_content%5D%5BCType%5D%3D\w+/)[0].substr(27) : ''),
				elementNowGridType = (extElNow.getAttribute('onclick').match(/tt_content%5D%5Btx_gridelements_backend_layout%5D%3D\w+/) ? ' t3-gridtype-' + extElNow.getAttribute('onclick').match(/tt_content%5D%5Btx_gridelements_backend_layout%5D%3D\w+/)[0].substr(52) : ''),
				elementNowTopLevelLayout = (extElNow.getAttribute('onclick').match(/isTopLevelLayout/) ? ' t3-gridTLContainer' : ''),
				elementNowClassContainer = '<div class="' + elementNowCType + elementNowGridType + elementNowTopLevelLayout + '" style="display:none"></div>',
				currentEl = Ext.DomHelper.insertHtml('afterEnd', elementNow, '<div title="' + elementNowTitle + '" class="x-dd-droptargetgroup" style="position: relative; display: inline-block; z-index: 99; width: ' + elementNowWidth + 'px; height: ' + elementNowHeight + 'px; margin-left: -' + (elementNowWidth + elementNowMargins.right) + 'px; margin-top: -' + elementNowMargins.top + 'px; vertical-align: ' + elementNowValign + '">' + elementNowClassContainer + '</div>'),
				currentClasses = Ext.get(extElNow).dom.className.split(' '),
				matchingClass = '';

			// reset previous settings
			dragBehaviorDraggables.dragClass = null;
			dragBehaviorDraggables.useTpl = null;

			// set right margin to the one of the original element
			currentEl.style.marginTop = elementNowMargins.top + 'px';
			currentEl.style.marginRight = elementNowMargins.right + 'px';

			// look for x-dd-usetpl-XYZ class
			for(var i in currentClasses) {
				if (!currentClasses.hasOwnProperty(i)) {
					continue;
				}

				// find useTpl in x-dd-usetpl-XYZ class if dragBehaviorDraggables.useTpl was not found yet
				if (/x-dd-usetpl-/.test(currentClasses[i]) === true) {
					dragBehaviorDraggables.useTpl = currentClasses[i].split('-')[3];

					// break after first one
					break;
				}
			}

			// look for x-dd-droptargetgroup-XYZ class
			for(var h in currentClasses) {
				if (!currentClasses.hasOwnProperty(h)) {
					continue;
				}

				// find x-dd-droptargetgroup-XYZ class
				if (/x-dd-droptargetgroup-/.test(currentClasses[h]) === true) {
					// get XYZ class part from x-dd-droptargetgroup-XYZ
					matchingClass = currentClasses[h].split('-')[3];

					// use matchingClass for dragBehaviorDraggables.useTpl if not found before
					if (dragBehaviorDraggables.useTpl === null) {
						dragBehaviorDraggables.useTpl = matchingClass;
					}

					// add Ext.dd.DD class to element with matching IDs
					var draggerNow = new Ext.dd.DD(currentEl, 'droptargets-' + matchingClass, {
						isTarget: false,
						scroll: false,
						maintainOffset: false
					});

					// apply the overrides object to the newly created instance of DD
					dragBehaviorDraggables.dragClass = matchingClass;
					Ext.apply(draggerNow, dragBehaviorDraggables);

				}
			}
		},

		listenForCopyItem: function(intItemUid) {
			//console.log('GridElementsDD.listenForCopyItem() reached, intItemUid = ' + intItemUid, GridElementsDD.copyItemUids);
			GridElementsDD.copyItemUids[intItemUid] = true;
		},

		handleClipboardItem: function(clipboardItemUid, params) {

			// set top vars so they're instantly available in reloaded frames
			if (params.search(/setCopyMode.+/) !== -1) {
				top.DDclipboardfilled = (top.DDclipboardfilled === "copy" && top.DDclipboardElId === clipboardItemUid) ? "" : "copy";
			} else {
				top.DDclipboardfilled = (top.DDclipboardfilled === "move" && top.DDclipboardElId === clipboardItemUid) ? "" : "move";
			}

			top.DDclipboardElId = top.DDclipboardfilled ? clipboardItemUid : 0;

			// remove and re-add insert icons
			GridElementsDD.removePasteAndRefIcons();
			if (top.DDclipboardfilled) {
				GridElementsDD.addPasteAndRefIcons(clipboardItemUid);
			}

		},

		removePasteAndRefIcons: function() {
			// console.log('removePasteAndRefIcons reached');
			// remove all existing paste-copy icons
			var pasteIcons = Ext.select('.t3-icon-gridelements-pastecopy').elements;
			Ext.each(pasteIcons, function(iconEl) {
				Ext.get(iconEl).parent().remove();
			});

			// remove all existing paste-reference icons
			var pasteRefIcons = Ext.select('.t3-icon-gridelements-pasteref').elements;
			Ext.each(pasteRefIcons, function(iconEl) {
				Ext.get(iconEl).parent().remove();
			});
		},

		addPasteAndRefIcons: function(clipboardItemUid) {
			// console.log('addPasteAndRefIcons reached');
			// add paste icons to column headers
			if (clipboardItemUid.substr(0,5) !== '_FILE') {
				colHeader = Ext.select('.t3-page-ce-wrapper-new-ce, .t3-page-ce-new-ce').elements;
				Ext.each(colHeader, function(currentColHeader) {
					var lastColHeaderLink = Ext.get(currentColHeader).select('a:last').elements[0];
					var dropZoneID = lastColHeaderLink.rel;

					// add "paste copy" icon
					var
						pasteCopyIconConf = {
							tag: 'a',
							href: '#',
							title: TYPO3.l10n.localize('tx_gridelements_js.pastecopy'),
							cn: {
								tag:'span',
								'class': top.geSprites.pastecopy,
								html:'&nbsp;'
							}
						},
						pasteCopyIcon = Ext.DomHelper.insertAfter(lastColHeaderLink, pasteCopyIconConf, true);

					if (top.DDclipboardfilled === 'move'){

						// bind click event
						pasteCopyIcon.on('click', function(){
							GridElementsDD.ajaxThenReload(
								top.moveURL.replace('DD_DRAG_UID', clipboardItemUid).replace('DD_DROP_UID', dropZoneID) + "&CB[paste]=tt_content%7C" + dropZoneID + "&CB[pad]=normal"
							);
							return false;
						});

					} else if (top.DDclipboardfilled === 'copy'){

						// bind click event
						pasteCopyIcon.on('click', function(){
							GridElementsDD.ajaxThenReload(
								top.pasteTpl.replace('DD_REFYN', '0').replace('DD_DRAG_UID', clipboardItemUid).replace('DD_DROP_UID', dropZoneID)
							);
							return false;
						});

						// also add "paste reference" icon in this case
						if(top.pasteReferenceAllowed === 'true') {
							var
								pasteRefIconConf = {
									tag: 'a',
									href: '#',
									title: TYPO3.l10n.localize('tx_gridelements_js.pasteref'),
									cn: {
										tag: 'span',
										'class': top.geSprites.pasteref,
										html: '&nbsp;'
									}
								},
								pasteRefIcon = Ext.DomHelper.insertAfter(pasteCopyIcon, pasteRefIconConf, true);

							// bind click event
							pasteRefIcon.on('click', function () {
								GridElementsDD.ajaxThenReload(
									top.pasteTpl.replace('DD_REFYN', 1).replace('DD_DRAG_UID', clipboardItemUid).replace('DD_DROP_UID', dropZoneID)
								);
								return false;
							});
						}
					}
				});
			}
		},

		ajaxThenReload: function(actionURL) {

			// show spinner icon
			top.TYPO3.Backend.ContentContainer.setMask();

			Ext.Ajax.request({
				url: actionURL,
				success: function(result, request) {
					if (GridElementsDD.baseConf.doReloadsAfterDrops) {
						// reload page to verify/show updates
						locationSplit = self.location.href.split('#');
						self.location.href = locationSplit[0] + '#ce' + top.targetUID;
						self.location.reload(true);
					}else{
						// after the operation has finished, we simply hide the spinner
						top.TYPO3.Backend.ContentContainer.removeMask();
					}
				},
				failure: function(result, request) {
					if (GridElementsDD.baseConf.doReloadsAfterDrops) {
						// reload page to verify/show updates
						locationSplit = self.location.href.split('#');
						self.location.href = locationSplit[0] + '#ce' + top.targetUID;
						self.location.reload(true);
					}else{
						// TODO: handle error
						top.TYPO3.Backend.ContentContainer.removeMask();
					}
				}
			});
		},

		getPasteLinkForItem: function(itemUID, isReference) {
			if (typeof isReference === 'undefined') {
				isReference = 0;
			}

			// use URL template and replace UID field
			return top.UrlTpl.replace('DD_TARGET_UID', itemUID) +
				(isReference ? '&reference=1' : '') +
				'&redirect=' +
				top.rawurlencode(top.content.list_frame.document.location.pathname + top.content.list_frame.document.location.search);
		}

	};
}();