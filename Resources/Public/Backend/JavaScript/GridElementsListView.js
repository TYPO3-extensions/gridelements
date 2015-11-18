/*
 * drag-and-drop library for content elements
 * requires ExtJS
 * 
 * - FEATURE: reload-less DnD: compare page lastchange to current page "age" on ajax (pageRenderTime inserted by onReady injector)
 */
GridElementsListView = function() {

	return {
		elExpandCollapse: function(id, sortField, level) {
			var el = Ext.get(Ext.query("a[rel='" + id + "']>span").first());

			if (el.hasClass('t3-icon-pagetree-collapse')) {
				el.removeClass('t3-icon-pagetree-collapse'); //.addClass('t3-icon-pagetree-expand');
				GridElementsListView.addSpinner(el);

				var idParam = id.split(':');
				var sorting = sortField.split(':');
				Ext.Ajax.timeout = 600000;
				Ext.Ajax.request({
					url: 'ajax.php',
					params: {
						ajaxID: 'tx_gridelements::controller',
						cmd: 'getListRows',
						table: idParam[0],
						uid: idParam[1],
						level: level,
						sortField: sorting[0],
						sortRev: sorting[1]
					},
					timeout: 10000,
					success: function(req){
						GridElementsListView.ajaxSuccess(el, req);
					},
					failure: function() {
						GridElementsListView.ajaxFailure(el);
					}
				});

			} else {
				el.removeClass('t3-icon-pagetree-expand').addClass('t3-icon-actions').addClass('t3-icon-pagetree-collapse');
				GridElementsListView.removeSpinner(el);

				var tr =  Ext.get(el.findParent('tr'));
				tr = tr.next();
				var forBrealk = false;
				for (var i=0; i <= 100; i++) {
					var trNext = tr.next();
					if (tr.hasClass('tr-' + el.id)) {
						forBrealk = true;
					}
					tr.remove();

					if (forBrealk) {
						break
					} else {
						tr = trNext;
					}
				}
			}
		},

		ajaxSuccess: function(el, req) {
			GridElementsListView.removeSpinner(el)
			el.removeClass('t3-icon-pagetree-collapse').addClass('t3-icon-pagetree-expand');
			if (req.responseText) {
				htmlRows = Ext.util.JSON.decode(req.responseText)
			}

			var tr =  Ext.get(el.findParent('tr'));
			tr.insertHtml('afterEnd','<tr class="hidden tr-' + el.id + '"><td></td></tr>');
			htmlRows.list.reverse().each(function(el) {
				var newTr = tr.insertHtml('afterEnd',el);
			})
		},

		ajaxFailure: function(id) {
			alert('ajaxFailure');
		},

		addSpinner: function(el) {
			el.removeClass('t3-icon-actions').addClass('spinner');
		},

		removeSpinner: function(el) {
			el.addClass('t3-icon-actions').removeClass('spinner');
		}

}
}();