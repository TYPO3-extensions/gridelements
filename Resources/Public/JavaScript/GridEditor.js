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
 * Module: TYPO3\CMS\Gridelements\GridEditor
 */
define([
        'jquery',
        'TYPO3/CMS/Backend/Modal',
        'TYPO3/CMS/Backend/Severity',
        'bootstrap'
],
function ($, Modal, Severity) {
    'use strict';

    /**
     * The main ContextHelp object
     *
     * @type {{selectorEditor: string, selectorAddColumn: string, selectorRemoveColumn: string, selectorAddRow: string, selectorRemoveRow: string, selectorLinkEditor: string, selectorLinkExpandRight: string, selectorLinkShrinkLeft: string, selectorLinkExpandDown: string, selectorLinkShrinkUp: string, selectorDocHeaderSave: string, selectorDocHeaderSaveClose: string, selectorConfigPreview: string, selectorConfigPreviewButton: string, colCount: number, rowCount: number, field: string, data: Array, nameLabel: string, columnLabel: string, targetElement: null}}
     * @exports TYPO3\CMS\Gridelements\GridEditor
     */
    var GridEditor = {
        selectorEditor: '.t3js-grideditor',
        selectorAddColumn: '.t3js-grideditor-addcolumn',
        selectorRemoveColumn: '.t3js-grideditor-removecolumn',
        selectorAddRow: '.t3js-grideditor-addrow',
        selectorRemoveRow: '.t3js-grideditor-removerow',
        selectorLinkEditor: '.t3js-grideditor-link-editor',
        selectorLinkExpandRight: '.t3js-grideditor-link-expand-right',
        selectorLinkShrinkLeft: '.t3js-grideditor-link-shrink-left',
        selectorLinkExpandDown: '.t3js-grideditor-link-expand-down',
        selectorLinkShrinkUp: '.t3js-grideditor-link-shrink-up',
        selectorDocHeaderSave: '.t3js-grideditor-savedok',
        selectorDocHeaderSaveClose: '.t3js-grideditor-savedokclose',
        selectorConfigPreview: '.t3js-grideditor-preview-config',
        selectorConfigPreviewButton: '.t3js-grideditor-preview-button',
        colCount: 1,
        rowCount: 1,
        field: '',
        data: [],
        nameLabel: 'name',
        columnLabel: 'column label',
        targetElement: null,
        defaultCell: {
            spanned: 0,
            rowspan: 1,
            colspan: 1,
            name: '',
            colpos: '',
            allowed: {CType: '', list_type: '', tx_gridelements_backend_layout: ''},
            disallowed: {CType: '', list_type: '', tx_gridelements_backend_layout: ''},
            maxitems: 0
        }
    };

    /**
     *
     * @param {Object} config
     */
    GridEditor.initialize = function (config) {
        config = config || {};
        var $element = $(GridEditor.selectorEditor);
        GridEditor.colCount = $element.data('colcount');
        GridEditor.rowCount = $element.data('rowcount');
        GridEditor.field = $('input[name="' + $element.data('field') + '"]');
        GridEditor.data = $element.data('data');
        GridEditor.nameLabel = config.nameLabel || 'Name';
        GridEditor.columnLabel = config.columnLabel || 'Column';
        GridEditor.targetElement = $(GridEditor.selectorEditor);
        $(GridEditor.selectorConfigPreview).hide();

        $(document).on('click', GridEditor.selectorAddColumn, function (e) {
            e.preventDefault();
            GridEditor.addColumn();
            GridEditor.drawTable();
            GridEditor.writeConfig(GridEditor.export2LayoutRecord());
        });
        $(document).on('click', GridEditor.selectorRemoveColumn, function (e) {
            e.preventDefault();
            GridEditor.removeColumn();
            GridEditor.drawTable();
            GridEditor.writeConfig(GridEditor.export2LayoutRecord());
        });
        $(document).on('click', GridEditor.selectorAddRow, function (e) {
            e.preventDefault();
            GridEditor.addRow();
            GridEditor.drawTable();
            GridEditor.writeConfig(GridEditor.export2LayoutRecord());
        });
        $(document).on('click', GridEditor.selectorRemoveRow, function (e) {
            e.preventDefault();
            GridEditor.removeRow();
            GridEditor.drawTable();
            GridEditor.writeConfig(GridEditor.export2LayoutRecord());
        });
        $(document).on('click', GridEditor.selectorLinkEditor, function (e) {
            e.preventDefault();
            var $element = $(this);
            var col = $element.data('col');
            var row = $element.data('row');
            GridEditor.showOptions(col, row);
        });
        $(document).on('click', GridEditor.selectorLinkExpandRight, function (e) {
            e.preventDefault();
            var $element = $(this);
            var col = $element.data('col');
            var row = $element.data('row');
            GridEditor.addColspan(col, row);
            GridEditor.drawTable();
            GridEditor.writeConfig(GridEditor.export2LayoutRecord());
        });
        $(document).on('click', GridEditor.selectorLinkShrinkLeft, function (e) {
            e.preventDefault();
            var $element = $(this);
            var col = $element.data('col');
            var row = $element.data('row');
            GridEditor.removeColspan(col, row);
            GridEditor.drawTable();
            GridEditor.writeConfig(GridEditor.export2LayoutRecord());
        });
        $(document).on('click', GridEditor.selectorLinkExpandDown, function (e) {
            e.preventDefault();
            var $element = $(this);
            var col = $element.data('col');
            var row = $element.data('row');
            GridEditor.addRowspan(col, row);
            GridEditor.drawTable();
            GridEditor.writeConfig(GridEditor.export2LayoutRecord());
        });
        $(document).on('click', GridEditor.selectorLinkShrinkUp, function (e) {
            e.preventDefault();
            var $element = $(this);
            var col = $element.data('col');
            var row = $element.data('row');
            GridEditor.removeRowspan(col, row);
            GridEditor.drawTable();
            GridEditor.writeConfig(GridEditor.export2LayoutRecord());
        });

        $(GridEditor.selectorConfigPreviewButton).empty().append(TYPO3.lang['button.showPageTsConfig']);
        $(document).on('click', GridEditor.selectorConfigPreviewButton, function (e) {
            e.preventDefault();
            var $preview = $(GridEditor.selectorConfigPreview);
            var $button = $(GridEditor.selectorConfigPreviewButton);
            if ($preview.is(':visible')) {
                $button.empty().append(TYPO3.lang['button.showPageTsConfig']);
                $(GridEditor.selectorConfigPreview).slideUp();
            } else {
                $button.empty().append(TYPO3.lang['button.hidePageTsConfig']);
                $(GridEditor.selectorConfigPreview).slideDown();
            }

        });

        GridEditor.drawTable();
        GridEditor.writeConfig(GridEditor.export2LayoutRecord());
    };

    /**
     * Create a new cell from defaultCell
     * @returns {Object}
     */
    GridEditor.getNewCell = function () {
        return $.extend(true, {}, GridEditor.defaultCell);
    };

    /**
     * write data back to hidden field
     *
     * @param data
     */
    GridEditor.writeConfig = function (data) {
        GridEditor.field.val(data);
        var configLines = data.split('\n');
        var config = '';
        for (var i = 0; i < configLines.length; i++) {
            if (configLines[i].length) {
                config += '\t\t\t' + configLines[i] + '\n';
            }
        }
        $(GridEditor.selectorConfigPreview).find('code').empty().append(
            'mod.web_layout.BackendLayouts {\n' +
            '  exampleKey {\n' +
            '    title = Example\n' +
            '    icon = EXT:example_extension/Resources/Public/Images/BackendLayouts/default.gif\n' +
            '    config {\n' +
            config.replace(new RegExp('\t', 'g'), '  ') +
            '    }\n' +
            '  }\n' +
            '}\n'
        );
    };

    /**
     * Add a new row
     */
    GridEditor.addRow = function () {
        var newRow = [];
        for (var i = 0; i < GridEditor.colCount; i++) {
            var newCell = GridEditor.getNewCell();
            newCell.name = i + 'x' + GridEditor.data.length;
            newRow[i] = newCell;
        }
        GridEditor.data.push(newRow);
        GridEditor.rowCount++;
    };

    /**
     * Removes the last row of the grid and adjusts all cells that might be effected
     * by that change. (Removing colspans)
     */
    GridEditor.removeRow = function () {
        if (GridEditor.rowCount <= 1) {
            return false;
        }
        var newData = [];
        for (var rowIndex = 0; rowIndex < GridEditor.rowCount - 1; rowIndex++) {
            newData.push(GridEditor.data[rowIndex]);
        }

        // fix rowspan in former last row
        for (var colIndex = 0; colIndex < GridEditor.colCount; colIndex++) {
            if (GridEditor.data[GridEditor.rowCount - 1][colIndex].spanned === 1) {
                GridEditor.findUpperCellWidthRowspanAndDecreaseByOne(colIndex, GridEditor.rowCount - 1);
            }
        }

        GridEditor.data = newData;
        GridEditor.rowCount--;
    };

    /**
     * Takes a cell and looks above it if there are any cells that have colspans that
     * spans into the given cell. This is used when a row was removed from the grid
     * to make sure that no cell with wrong colspans exists in the grid.
     *
     * @param {Integer} col
     * @param {Integer} row integer
     */
    GridEditor.findUpperCellWidthRowspanAndDecreaseByOne = function (col, row) {
        var upperCell = GridEditor.getCell(col, row - 1);
        if (!upperCell) {
            return false;
        }

        if (upperCell.spanned === 1) {
            GridEditor.findUpperCellWidthRowspanAndDecreaseByOne(col, row - 1);
        } else {
            if (upperCell.rowspan > 1) {
                GridEditor.removeRowspan(col, row - 1);
            }
        }
    };

    /**
     * Removes the outermost right column from the grid.
     */
    GridEditor.removeColumn = function () {
        if (GridEditor.colCount <= 1) {
            return false;
        }
        var newData = [];

        for (var rowIndex = 0; rowIndex < GridEditor.rowCount; rowIndex++) {
            var newRow = [];
            for (var colIndex = 0; colIndex < GridEditor.colCount - 1; colIndex++) {
                newRow.push(GridEditor.data[rowIndex][colIndex]);
            }
            if (GridEditor.data[rowIndex][GridEditor.colCount - 1].spanned === 1) {
                GridEditor.findLeftCellWidthColspanAndDecreaseByOne(GridEditor.colCount - 1, rowIndex);
            }
            newData.push(newRow);
        }

        GridEditor.data = newData;
        GridEditor.colCount--;
    };

    /**
     * Checks if there are any cells on the left side of a given cell with a
     * rowspan that spans over the given cell.
     *
     * @param {Integer} col
     * @param {Integer} row
     */
    GridEditor.findLeftCellWidthColspanAndDecreaseByOne = function (col, row) {
        var leftCell = GridEditor.getCell(col - 1, row);
        if (!leftCell) {
            return false;
        }

        if (leftCell.spanned === 1) {
            GridEditor.findLeftCellWidthColspanAndDecreaseByOne(col - 1, row);
        } else {
            if (leftCell.colspan > 1) {
                GridEditor.removeColspan(col - 1, row);
            }
        }
    };

    /**
     * Adds a column at the right side of the grid.
     */
    GridEditor.addColumn = function () {
        for (var rowIndex = 0; rowIndex < GridEditor.rowCount; rowIndex++) {
            var newCell = GridEditor.getNewCell();
            newCell.name = GridEditor.colCount + 'x' + rowIndex;
            GridEditor.data[rowIndex].push(newCell);
        }
        GridEditor.colCount++;
    };

    /**
     * Draws the grid as table into a given container.
     * It also adds all needed links and bindings to the cells to make it editable.
     */
    GridEditor.drawTable = function () {
        var col;
        var $colgroup = $('<colgroup>');
        for (col = 0; col < GridEditor.colCount; col++) {
            $colgroup.append($('<col>').css({
                width: parseInt(100 / GridEditor.colCount, 10) + '%'
            }));
        }
        var $table = $('<table id="base" class="table editor">');
        $table.append($colgroup);

        for (var row = 0; row < GridEditor.rowCount; row++) {
            var rowData = GridEditor.data[row];
            if (rowData.length === 0) {
                continue;
            }

            var $row = $('<tr>');

            for (col = 0; col < GridEditor.colCount; col++) {
                var cell = GridEditor.data[row][col];
                if (cell.spanned === 1) {
                    continue;
                }
                var $cell = $('<td>').css({
                    height: parseInt(100 / GridEditor.rowCount, 10) * cell.rowspan + '%',
                    width: parseInt(100 / GridEditor.colCount, 10) * cell.colspan + '%'
                }).addClass('text-center');
                var $container = $('<div class="cell_container">');
                $cell.append($container);
                var $anchor = $('<a href="#" data-col="' + col + '" data-row="' + row + '">');

                $container.append(
                    $anchor
                        .clone()
                        .attr('class', 't3js-grideditor-link-editor link link_editor')
                        .attr('title', TYPO3.lang['grid_editCell'])
                );
                if (GridEditor.cellCanSpanRight(col, row)) {
                    $container.append(
                        $anchor
                            .clone()
                            .attr('class', 't3js-grideditor-link-expand-right link link_expand_right')
                            .attr('title', TYPO3.lang['grid_mergeCell'])
                    );
                }
                if (GridEditor.cellCanShrinkLeft(col, row)) {
                    $container.append(
                        $anchor
                            .clone()
                            .attr('class', 't3js-grideditor-link-shrink-left link link_shrink_left')
                            .attr('title', TYPO3.lang['grid_splitCell'])
                    );
                }
                if (GridEditor.cellCanSpanDown(col, row)) {
                    $container.append(
                        $anchor
                            .clone()
                            .attr('class', 't3js-grideditor-link-expand-down link link_expand_down')
                            .attr('title', TYPO3.lang['grid_mergeCell'])
                    );
                }
                if (GridEditor.cellCanShrinkUp(col, row)) {
                    $container.append(
                        $anchor
                            .clone()
                            .attr('class', 't3js-grideditor-link-shrink-up link link_shrink_up')
                            .attr('title', TYPO3.lang['grid_splitCell'])
                    );
                }
                $cell.append(
                    $('<table class="cell_data">')
                        .html(
                            '<tr><th>' + TYPO3.lang['grid_name'] + ': </th><td>' + (cell.name ? GridEditor.stripMarkup(cell.name) : TYPO3.lang['grid_notSet']) + '</td></tr>'
                            + '<tr><th>' + TYPO3.lang['grid_column'] + ': </th><td>' + (cell.column === undefined || typeof(cell.column) !== 'number' ? TYPO3.lang['grid_notSet'] : parseInt(cell.column, 10)) + '</td></tr>'
                            + (cell.allowed && cell.allowed.CType ? ('<tr><th>' + TYPO3.lang['grid_allowed'] + ': </th><td>' + cell.allowed.CType + '</td></tr>') : '')
                            + (cell.allowed && cell.allowed.list_type ? ('<tr><th>' + TYPO3.lang['grid_allowedListTypes'] + ': </th><td>' + cell.allowed.list_type + '</td></tr>') : '')
                            + (cell.allowed && cell.allowed.tx_gridelements_backend_layout ? ('<tr><th>' + TYPO3.lang['grid_allowedGridTypes'] + ': </th><td>' + cell.allowed.tx_gridelements_backend_layout + '</td></tr>') : '')
                            + (cell.disallowed && cell.disallowed.CType ? ('<tr><th>' + TYPO3.lang['grid_disallowed'] + ': </th><td>' + cell.disallowed.CType + '</td></tr>') : '')
                            + (cell.disallowed && cell.disallowed.list_type ? ('<tr><th>' + TYPO3.lang['grid_disallowedListTypes'] + ': </th><td>' + cell.disallowed.list_type + '</td></tr>') : '')
                            + (cell.disallowed && cell.disallowed.tx_gridelements_backend_layout ? ('<tr><th>' + TYPO3.lang['grid_disallowedGridTypes'] + ': </th><td>' + cell.disallowed.tx_gridelements_backend_layout + '</td></tr>') : '')
                            + (cell.maxitems ? ('<tr><th>' + TYPO3.lang['grid_maxitems'] + ': </th><td>' + parseInt(cell.maxitems, 10) + '</td></tr>') : '')
                            + '</table>'
                        )
                );
                if (cell.colspan > 1) {
                    $cell.attr('colspan', cell.colspan);
                }
                if (cell.rowspan > 1) {
                    $cell.attr('rowspan', cell.rowspan);
                }
                $row.append($cell);
            }
            $table.append($row);
        }
        $(GridEditor.targetElement).empty().append($table);
    };

    /**
     * Sets the name of a certain grid element.
     *
     * @param {String} newName
     * @param {Integer} col
     * @param {Integer} row
     *
     * @returns {Boolean}
     */
    GridEditor.setName = function (newName, col, row) {
        var cell = GridEditor.getCell(col, row);
        if (!cell) {
            return false;
        }
        if (newName.trim() === '') {
            cell.name = undefined;
        } else {
            cell.name = GridEditor.stripMarkup(newName);
        }
        return true;
    };

    /**
     * Sets the column field for a certain grid element. This is NOT the column of the
     * element itself.
     *
     * @param {String} newColumn
     * @param {Integer} col
     * @param {Integer} row
     *
     * @returns {Boolean}
     */
    GridEditor.setColumn = function (newColumn, col, row) {
        var cell = GridEditor.getCell(col, row);
        if (!cell) {
            return false;
        }
        if (newColumn.trim() === '') {
            cell.column = undefined;
        } else {
            cell.column = parseInt(newColumn, 10);
        }
        return true;
    };

    /**
     * Sets the column field for a certain grid element. This is NOT the column of the
     * element itself.
     *
     * @param {String} newMaxItems
     * @param {Integer} col
     * @param {Integer} row
     *
     * @returns {Boolean}
     */
    GridEditor.setMaxItems = function (newMaxItems, col, row) {
        var cell = GridEditor.getCell(col, row);
        if (!cell) {
            return false;
        }
        cell.maxitems = parseInt(newMaxItems, 10);
        return true;
    };

    /**
     * Sets the allowed CTypes of a certain grid element.
     *
     * @param {String} newAllowed
     * @param {Integer} col
     * @param {Integer} row
     *
     * @returns {Boolean}
     */
    GridEditor.setAllowed = function (newAllowed, col, row) {
        var cell = GridEditor.getCell(col, row);
        if (!cell) {
            return false;
        }
        if (!cell.allowed) {
            cell.allowed = {};
        }
        cell.allowed.CType = GridEditor.stripMarkup(newAllowed);
        return true;
    };

    /**
     * Sets the allowed CTypes of a certain grid element.
     *
     * @param {String} newDisallowed
     * @param {Integer} col
     * @param {Integer} row
     *
     * @returns {Boolean}
     */
    GridEditor.setDisallowed = function (newDisallowed, col, row) {
        var cell = GridEditor.getCell(col, row);
        if (!cell) {
            return false;
        }
        if (!cell.disallowed) {
            cell.disallowed = {};
        }
        cell.disallowed.CType = GridEditor.stripMarkup(newDisallowed);
        return true;
    };

    /**
     * Sets the allowed grid types of a certain grid element.
     *
     * @param {String} newAllowedListTypes
     * @param {Integer} col
     * @param {Integer} row
     *
     * @returns {Boolean}
     */
    GridEditor.setAllowedListTypes = function (newAllowedListTypes, col, row) {
        var cell = GridEditor.getCell(col, row);
        if (!cell) {
            return false;
        }
        if (!cell.allowed) {
            cell.allowed = {};
        }
        cell.allowed.list_type = GridEditor.stripMarkup(newAllowedListTypes);
        return true;
    };

    /**
     * Sets the allowed grid types of a certain grid element.
     *
     * @param {String} newDisallowedListTypes
     * @param {Integer} col
     * @param {Integer} row
     *
     * @returns {Boolean}
     */
    GridEditor.setDisallowedListTypes = function (newDisallowedListTypes, col, row) {
        var cell = GridEditor.getCell(col, row);
        if (!cell) {
            return false;
        }
        if (!cell.disallowed) {
            cell.disallowed = {};
        }
        cell.disallowed.list_type = GridEditor.stripMarkup(newDisallowedListTypes);
        return true;
    };

    /**
     * Sets the allowed grid types of a certain grid element.
     *
     * @param {String} newAllowedGridTypes
     * @param {Integer} col
     * @param {Integer} row
     *
     * @returns {Boolean}
     */
    GridEditor.setAllowedGridTypes = function (newAllowedGridTypes, col, row) {
        var cell = GridEditor.getCell(col, row);
        if (!cell) {
            return false;
        }
        if (!cell.allowed) {
            cell.allowed = {};
        }
        cell.allowed.tx_gridelements_backend_layout = GridEditor.stripMarkup(newAllowedGridTypes);
        return true;
    };

    /**
     * Sets the allowed grid types of a certain grid element.
     *
     * @param {String} newDisallowedGridTypes
     * @param {Integer} col
     * @param {Integer} row
     *
     * @returns {Boolean}
     */
    GridEditor.setDisallowedGridTypes = function (newDisallowedGridTypes, col, row) {
        var cell = GridEditor.getCell(col, row);
        if (!cell) {
            return false;
        }
        if (!cell.disallowed) {
            cell.disallowed = {};
        }
        cell.disallowed.tx_gridelements_backend_layout = GridEditor.stripMarkup(newDisallowedGridTypes);
        return true;
    };

    /**
     * Creates an ExtJs Window with two input fields and shows it. On save, the data
     * is written into the grid element.
     *
     * @param {Integer} col
     * @param {Integer} row
     *
     * @returns {Boolean}
     */
    GridEditor.showOptions = function (col, row) {
        var cell = GridEditor.getCell(col, row);
        if (!cell) {
            return false;
        }

        var colPos;
        if (cell.column === 0) {
            colPos = 0;
        } else if (parseInt(cell.column, 10)) {
            colPos = parseInt(cell.column, 10);
        } else {
            colPos = '';
        }
        var maxitems;
        if (cell.maxitems === 0) {
            maxitems = 0;
        } else if (parseInt(cell.maxitems, 10)) {
            maxitems = parseInt(cell.maxitems, 10);
        } else {
            maxitems = '';
        }

        var $markup = $('<div>');
        var $formGroup = $('<div class="form-group">');
        var $label = $('<label>');
        var $input = $('<input>');
        var $select = $('<select>');

        $markup.append([
            $formGroup
                .clone()
                .append([
                    $label
                        .clone()
                        .text(TYPO3.lang['grid_name'])
                        .attr('title', TYPO3.lang['grid_nameHelp'])
                    ,
                    $input
                        .clone()
                        .attr('type', 'text')
                        .attr('class', 't3js-grideditor-field-name form-control')
                        .attr('name', 'name')
                        .val(GridEditor.stripMarkup(cell.name) || '')
                ]),
            $formGroup
                .clone()
                .append([
                    $label
                        .clone()
                        .text(TYPO3.lang['grid_column'])
                        .attr('title', TYPO3.lang['grid_columnHelp'])
                    ,
                    $input
                        .clone()
                        .attr('type', 'text')
                        .attr('class', 't3js-grideditor-field-colpos form-control')
                        .attr('name', 'column')
                        .val(colPos)
                ]),
            $formGroup
                .clone()
                .append([
                    $label
                        .clone()
                        .text(TYPO3.lang['grid_maxitems'])
                        .attr('title', TYPO3.lang['grid_maxitemsHelp'])
                    ,
                    $input
                        .clone()
                        .attr('type', 'text')
                        .attr('class', 't3js-grideditor-field-maxitems form-control')
                        .attr('name', 'maxitems')
                        .val(maxitems)
                ]),
            typeof TYPO3.settings.availableCTypes !== 'undefined' ?
                $formGroup
                    .clone()
                    .append([
                        $label
                            .clone()
                            .text(TYPO3.lang['grid_allowed'])
                            .attr('title', TYPO3.lang['grid_allowedHelp'])
                        ,
                        $select
                            .clone()
                            .attr('multiple', 'true')
                            .attr('class', 't3js-grideditor-field-allowed form-control')
                            .attr('name', 'allowed')
                            .append(GridEditor.getTypeOptions(cell.allowed && cell.allowed.CType ? cell.allowed.CType : '', TYPO3.settings.availableCTypes))
                    ]) : '',
            typeof TYPO3.settings.availableCTypes !== 'undefined' ?
                $formGroup
                    .clone()
                    .append([
                        $label
                            .clone()
                            .text(TYPO3.lang['grid_disallowed'])
                            .attr('title', TYPO3.lang['grid_disallowedHelp'])
                        ,
                        $select
                            .clone()
                            .attr('multiple', 'true')
                            .attr('class', 't3js-grideditor-field-disallowed form-control')
                            .attr('name', 'disallowed')
                            .append(GridEditor.getTypeOptions(cell.disallowed && cell.disallowed.CType ? cell.disallowed.CType : '', TYPO3.settings.availableCTypes))
                    ]) : '',
            typeof TYPO3.settings.availableListTypes !== 'undefined' ?
                $formGroup
                    .clone()
                    .append([
                        $label
                            .clone()
                            .text(TYPO3.lang['grid_allowedListTypes'])
                            .attr('title', TYPO3.lang['grid_allowedListTypesHelp'])
                        ,
                        $select
                            .clone()
                            .attr('multiple', 'true')
                            .attr('class', 't3js-grideditor-field-allowed-list-types form-control')
                            .attr('name', 'allowedListTypes')
                            .append(GridEditor.getTypeOptions(cell.allowed && cell.allowed.list_type ? cell.allowed.list_type : '', TYPO3.settings.availableListTypes))
                    ]) : '',
            typeof TYPO3.settings.availableListTypes !== 'undefined' ?
                $formGroup
                    .clone()
                    .append([
                        $label
                            .clone()
                            .text(TYPO3.lang['grid_disallowedListTypes'])
                            .attr('title', TYPO3.lang['grid_disallowedListTypesHelp'])
                        ,
                        $select
                            .clone()
                            .attr('multiple', 'true')
                            .attr('class', 't3js-grideditor-field-disallowed-list-types form-control')
                            .attr('name', 'disallowedListTypes')
                            .append(GridEditor.getTypeOptions(cell.disallowed && cell.disallowed.list_type ? cell.disallowed.list_type : '', TYPO3.settings.availableListTypes))
                    ]) : '',
            typeof TYPO3.settings.availableGridTypes !== 'undefined' ?
                $formGroup
                    .clone()
                    .append([
                        $label
                            .clone()
                            .text(TYPO3.lang['grid_allowedGridTypes'])
                            .attr('title', TYPO3.lang['grid_allowedGridTypesHelp'])
                        ,
                        $select
                            .clone()
                            .attr('multiple', 'true')
                            .attr('class', 't3js-grideditor-field-allowed-grid-types form-control')
                            .attr('name', 'allowedGridTypes')
                            .append(GridEditor.getTypeOptions(cell.allowed && cell.allowed.tx_gridelements_backend_layout ? cell.allowed.tx_gridelements_backend_layout : '', TYPO3.settings.availableGridTypes))
                    ]) : '',
            typeof TYPO3.settings.availableGridTypes !== 'undefined' ?
                $formGroup
                    .clone()
                    .append([
                        $label
                            .clone()
                            .text(TYPO3.lang['grid_disallowedGridTypes'])
                            .attr('title', TYPO3.lang['grid_disallowedGridTypesHelp'])
                        ,
                        $select
                            .clone()
                            .attr('multiple', 'true')
                            .attr('class', 't3js-grideditor-field-disallowed-grid-types form-control')
                            .attr('name', 'disallowedGridTypes')
                            .append(GridEditor.getTypeOptions(cell.allowed && cell.disallowed.tx_gridelements_backend_layout ? cell.disallowed.tx_gridelements_backend_layout : '', TYPO3.settings.availableGridTypes))
                    ]) : ''
        ]);

        var $modal = Modal.show(TYPO3.lang['grid_windowTitle'], $markup, Severity.notice, [
            {
                text: $(this).data('button-close-text') || TYPO3.lang['button.cancel'] || 'Cancel',
                active: true,
                btnClass: 'btn-default',
                name: 'cancel'
            },
            {
                text: $(this).data('button-ok-text') || TYPO3.lang['button.ok'] || 'OK',
                btnClass: 'btn-' + Severity.getCssClass(Severity.notice),
                name: 'ok'
            }
        ]);
        $modal.find('select').css('min-height', 'unset');
        $modal.data('col', col);
        $modal.data('row', row);
        $modal.on('button.clicked', function (e) {
            if (e.target.name === 'cancel') {
                Modal.currentModal.trigger('modal-dismiss');
            } else if (e.target.name === 'ok') {
                GridEditor.setName($modal.find('.t3js-grideditor-field-name').val(), $modal.data('col'), $modal.data('row'));
                GridEditor.setColumn($modal.find('.t3js-grideditor-field-colpos').val(), $modal.data('col'), $modal.data('row'));
                GridEditor.setMaxItems($modal.find('.t3js-grideditor-field-maxitems').val(), $modal.data('col'), $modal.data('row'));
                if (typeof TYPO3.settings.availableCTypes !== 'undefined') {
                    GridEditor.setAllowed($modal.find('.t3js-grideditor-field-allowed').val().join(), $modal.data('col'), $modal.data('row'));
                    GridEditor.setDisallowed($modal.find('.t3js-grideditor-field-disallowed').val().join(), $modal.data('col'), $modal.data('row'));
                }
                if (typeof TYPO3.settings.availableListTypes !== 'undefined') {
                    GridEditor.setAllowedListTypes($modal.find('.t3js-grideditor-field-allowed-list-types').val().join(), $modal.data('col'), $modal.data('row'));
                    GridEditor.setDisallowedListTypes($modal.find('.t3js-grideditor-field-disallowed-list-types').val().join(), $modal.data('col'), $modal.data('row'));
                }
                if (typeof TYPO3.settings.availableGridTypes !== 'undefined') {
                    GridEditor.setAllowedGridTypes($modal.find('.t3js-grideditor-field-allowed-grid-types').val().join(), $modal.data('col'), $modal.data('row'));
                    GridEditor.setDisallowedGridTypes($modal.find('.t3js-grideditor-field-disallowed-grid-types').val().join(), $modal.data('col'), $modal.data('row'));
                }
                GridEditor.drawTable();
                GridEditor.writeConfig(GridEditor.export2LayoutRecord());
                Modal.currentModal.trigger('modal-dismiss');
            }
        });
    };

    GridEditor.getTypeOptions = function (selectedTypesCSV, availableTypes) {
        var allowedTypeOptions = [];

        var selectedTypes = [];
        if (selectedTypesCSV) {
            selectedTypes = selectedTypesCSV.split(',');
        }

        for (var i = 0; i < availableTypes.length; i++) {
            var type = availableTypes[i],
                typeKey = String(type.key),
                typeIcon = type.icon,
                typeLabel = type.label,
                typeSelected = $.inArray(typeKey, selectedTypes) !== -1,
                typeStyle = 'background: #fff url(' + typeIcon + ') 0% 50% no-repeat; height: 16px; padding: 2px 10px 0 22px;';

            allowedTypeOptions.push('<option value="' + typeKey + '" style="' + typeStyle + '"' + (typeSelected ? ' selected="selected"' : '') + '>' + typeLabel + '</option>');
        }

        return allowedTypeOptions.join('');
    };

    /**
     * Returns a cell element from the grid.
     *
     * @param {Integer} col
     * @param {Integer} row
     * @returns {Object}
     */
    GridEditor.getCell = function (col, row) {
        if (col > GridEditor.colCount - 1) {
            return false;
        }
        if (row > GridEditor.rowCount - 1) {
            return false;
        }
        if (GridEditor.data.length > row - 1 && GridEditor.data[row].length > col - 1) {
            return GridEditor.data[row][col];
        }
        return false;
    };

    /**
     * Checks whether a cell can span to the right or not. A cell can span to the right
     * if it is not in the last column and if there is no cell beside it that is
     * already overspanned by some other cell.
     *
     * @param {Integer} col
     * @param {Integer} row
     * @returns {Boolean}
     */
    GridEditor.cellCanSpanRight = function (col, row) {
        if (col === GridEditor.colCount - 1) {
            return false;
        }

        var cell = GridEditor.getCell(col, row);
        var checkCell;
        if (cell.rowspan > 1) {
            for (var rowIndex = row; rowIndex < row + cell.rowspan; rowIndex++) {
                checkCell = GridEditor.getCell(col + cell.colspan, rowIndex);
                if (!checkCell || checkCell.spanned === 1 || checkCell.colspan > 1 || checkCell.rowspan > 1) {
                    return false;
                }
            }
        } else {
            checkCell = GridEditor.getCell(col + cell.colspan, row);
            if (!checkCell || cell.spanned === 1 || checkCell.spanned === 1 || checkCell.colspan > 1 || checkCell.rowspan > 1) {
                return false;
            }
        }

        return true;
    };

    /**
     * Checks whether a cell can span down or not.
     *
     * @param {Integer} col
     * @param {Integer} row
     * @returns {Boolean}
     */
    GridEditor.cellCanSpanDown = function (col, row) {
        if (row === GridEditor.rowCount - 1) {
            return false;
        }

        var cell = GridEditor.getCell(col, row);
        var checkCell;
        if (cell.colspan > 1) {
            // we have to check all cells on the right side for the complete colspan
            for (var colIndex = col; colIndex < col + cell.colspan; colIndex++) {
                checkCell = GridEditor.getCell(colIndex, row + cell.rowspan);
                if (!checkCell || checkCell.spanned === 1 || checkCell.colspan > 1 || checkCell.rowspan > 1) {
                    return false;
                }
            }
        } else {
            checkCell = GridEditor.getCell(col, row + cell.rowspan);
            if (!checkCell || cell.spanned === 1 || checkCell.spanned === 1 || checkCell.colspan > 1 || checkCell.rowspan > 1) {
                return false;
            }
        }

        return true;
    };

    /**
     * Checks if a cell can shrink to the left. It can shrink if the colspan of the
     * cell is bigger than 1.
     *
     * @param {Integer} col
     * @param {Integer} row
     * @returns {Boolean}
     */
    GridEditor.cellCanShrinkLeft = function (col, row) {
        return (GridEditor.data[row][col].colspan > 1);
    };

    /**
     * Returns if a cell can shrink up. This is the case if a cell has at least
     * a rowspan of 2.
     *
     * @param {Integer} col
     * @param {Integer} row
     * @returns {Boolean}
     */
    GridEditor.cellCanShrinkUp = function (col, row) {
        return (GridEditor.data[row][col].rowspan > 1);
    };

    /**
     * Adds a colspan to a grid element.
     *
     * @param {Integer} col
     * @param {Integer} row
     * @returns {Boolean}
     */
    GridEditor.addColspan = function (col, row) {
        var cell = GridEditor.getCell(col, row);
        if (!cell || !GridEditor.cellCanSpanRight(col, row)) {
            return false;
        }

        for (var rowIndex = row; rowIndex < row + cell.rowspan; rowIndex++) {
            GridEditor.data[rowIndex][col + cell.colspan].spanned = 1;
        }
        cell.colspan += 1;
    };

    /**
     * Adds a rowspan to grid element.
     *
     * @param {Integer} col
     * @param {Integer} row
     * @returns {Boolean}
     */
    GridEditor.addRowspan = function (col, row) {
        var cell = GridEditor.getCell(col, row);
        if (!cell || !GridEditor.cellCanSpanDown(col, row)) {
            return false;
        }

        for (var colIndex = col; colIndex < col + cell.colspan; colIndex++) {
            GridEditor.data[row + cell.rowspan][colIndex].spanned = 1;
        }
        cell.rowspan += 1;
    };

    /**
     * Removes a colspan from a grid element.
     *
     * @param {Integer} col
     * @param {Integer} row
     * @returns {Boolean}
     */
    GridEditor.removeColspan = function (col, row) {
        var cell = GridEditor.getCell(col, row);
        if (!cell || !GridEditor.cellCanShrinkLeft(col, row)) {
            return false;
        }

        cell.colspan -= 1;

        for (var rowIndex = row; rowIndex < row + cell.rowspan; rowIndex++) {
            GridEditor.data[rowIndex][col + cell.colspan].spanned = 0;
        }
    };

    /**
     * Removes a rowspan from a grid element.
     *
     * @param {Integer} col
     * @param {Integer} row
     * @returns {Boolean}
     */
    GridEditor.removeRowspan = function (col, row) {
        var cell = GridEditor.getCell(col, row);
        if (!cell || !GridEditor.cellCanShrinkUp(col, row)) {
            return false;
        }

        cell.rowspan -= 1;
        for (var colIndex = col; colIndex < col + cell.colspan; colIndex++) {
            GridEditor.data[row + cell.rowspan][colIndex].spanned = 0;
        }
    };

    /**
     * Exports the current grid to a TypoScript notation that can be read by the
     * page module and is human readable.
     *
     * @returns {String}
     */
    GridEditor.export2LayoutRecord = function () {
        var result = "backend_layout {\n\tcolCount = " + GridEditor.colCount + "\n\trowCount = " + GridEditor.rowCount + "\n\trows {\n";
        for (var row = 0; row < GridEditor.rowCount; row++) {
            result += "\t\t" + (row + 1) + " {\n";
            result += "\t\t\tcolumns {\n";
            var colIndex = 0;
            for (var col = 0; col < GridEditor.colCount; col++) {
                var cell = GridEditor.getCell(col, row);
                if (cell && !cell.spanned) {
                    colIndex++;
                    result += "\t\t\t\t" + (colIndex) + " {\n";
                    result += "\t\t\t\t\tname = " + ((!cell.name) ? col + "x" + row : cell.name) + "\n";
                    if (cell.colspan > 1) {
                        result += "\t\t\t\t\tcolspan = " + cell.colspan + "\n";
                    }
                    if (cell.rowspan > 1) {
                        result += "\t\t\t\t\trowspan = " + cell.rowspan + "\n";
                    }
                    if (typeof(cell.column) === 'number') {
                        result += "\t\t\t\t\tcolPos = " + cell.column + "\n";
                    }
                    if (cell.allowed !== undefined && (
                            (cell.allowed.CType !== undefined && cell.allowed.CType !== '' && cell.allowed.CType !== '*') ||
                            (cell.allowed.list_type !== undefined && cell.allowed.list_type !== '') ||
                            (cell.allowed.tx_gridelements_backend_layout !== undefined && cell.allowed.tx_gridelements_backend_layout !== '')
                        )) {
                        result += "\t\t\t\t\tallowed {\n";
                        if (cell.allowed.CType !== undefined && cell.allowed.CType !== '' && cell.allowed.CType !== '*') {
                            result += "\t\t\t\t\t\tCType = " + cell.allowed.CType + "\n";
                        }
                        if (cell.allowed.list_type !== undefined && cell.allowed.list_type !== '') {
                            result += "\t\t\t\t\t\tlist_type = " + cell.allowed.list_type + "\n";
                        }
                        if (cell.allowed.tx_gridelements_backend_layout !== undefined && cell.allowed.tx_gridelements_backend_layout !== '') {
                            result += "\t\t\t\t\t\ttx_gridelements_backend_layout = " + cell.allowed.tx_gridelements_backend_layout + "\n";
                        }
                        result += "\t\t\t\t\t}\n";
                    }
                    if (cell.disallowed !== undefined && (
                            (cell.disallowed.CType !== undefined && cell.disallowed.CType !== '') ||
                            (cell.disallowed.list_type !== undefined && cell.disallowed.list_type !== '') ||
                            (cell.disallowed.tx_gridelements_backend_layout !== undefined && cell.disallowed.tx_gridelements_backend_layout !== ''))
                    ) {
                        result += "\t\t\t\t\tdisallowed {\n";
                        if (cell.disallowed.CType !== undefined && cell.disallowed.CType !== '' && cell.disallowed.CType !== '*') {
                            result += "\t\t\t\t\t\tCType = " + cell.disallowed.CType + "\n";
                        }
                        if (cell.disallowed.list_type !== undefined && cell.disallowed.list_type !== '') {
                            result += "\t\t\t\t\t\tlist_type = " + cell.disallowed.list_type + "\n";
                        }
                        if (cell.disallowed.tx_gridelements_backend_layout !== undefined && cell.disallowed.tx_gridelements_backend_layout !== '') {
                            result += "\t\t\t\t\t\ttx_gridelements_backend_layout = " + cell.disallowed.tx_gridelements_backend_layout + "\n";
                        }
                        result += "\t\t\t\t\t}\n";
                    }
                    if (cell.maxitems !== undefined && cell.maxitems !== '' && cell.maxitems !== 0) {
                        result += "\t\t\t\t\tmaxitems = " + cell.maxitems + "\n";
                    }
                    result += "\t\t\t\t}\n";
                }

            }
            result += "\t\t\t}\n";
            result += "\t\t}\n";
        }

        result += "\t}\n}\n";
        return result;
    };

    /**
     * Remove all markup
     *
     * @param {String} input
     * @returns {*|jQuery}
     */
    GridEditor.stripMarkup = function (input) {
        if (input === undefined) {
            return;
        }
        input = input.replace(/<(.*)>/gi, '');
        return $('<p>' + input + '</p>').text();
    };

    GridEditor.initialize();
    return GridEditor;
});
