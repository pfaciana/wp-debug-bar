(function ($, window, document, undefined) {
	if (typeof $ === 'function' && 'subscribe' in $) {
		$.subscribe('tabulator-table-setup', function (options, element) {
			options.pagination ??= 'local';
			options.paginationSize ??= 20;
			options.paginationSizeSelector ??= [5, 10, 20, 50, 100, true];
			options.paginationButtonCount ??= 15;
			options.movableColumns ??= true;
			options.footerElement ??= '<button class="clear-all-table-filters tabulator-page">Clear Filters</button> <button class="clear-all-table-sorting tabulator-page">Clear Sorting</button>';

			return options;
		});

		$.subscribe('tabulator-column-setup', function (column, data, initial, options, element) {
			if (['bool', 'boolean', 'tickCross'].includes(initial.formatter)) {
				column.width ??= 75;
				column.headerWordWrap ??= true;
			}

			if (['subscribers'].includes(initial.formatter)) {
				column.headerFilter ??= 'input';
				column.headerFilterFuncParams ??= {strict: false};
				column.headerFilterFunc ??= function (headerValue, rowValues, rowData, filterParams) {
					if (headerValue == null || headerValue === '') {
						return true;
					}
					if (!rowValues || !Array.isArray(rowValues) || !rowValues.length) {
						return false;
					}
					var row = [...new Set(window.Tabulator.helpers.arrayColumn(rowValues, 'text'))].join('');
					if ('strict' in filterParams && !filterParams.strict) {
						row = row.toLowerCase();
						headerValue = headerValue.toLowerCase();
					}
					return row.includes(headerValue);
				};
				column.formatter = function (cell, formatterParams, onRendered) {
					var value = cell.getValue();
					if (!Array.isArray(value) || !value.length) {
						return 0;
					}
					return value.reduce(function (sum, subscriber) {
						return sum + ('count' in subscriber ? subscriber.count : 1);
					}, 0);
				};
				column.sorter ??= function (a, b, aRow, bRow, column, dir, sorterParams) {
					var aSize = Array.isArray(a) ? a.reduce(function (sum, subscriber) {
						return sum + ('count' in subscriber ? subscriber.count : 1);
					}, 0) : (+!!a);
					var bSize = Array.isArray(b) ? b.reduce(function (sum, subscriber) {
						return sum + ('count' in subscriber ? subscriber.count : 1);
					}, 0) : (+!!b);
					const sizeDiff = aSize - bSize;

					if (sizeDiff) {
						return sizeDiff;
					}

					return window.Tabulator.helpers.compare(a, b);
				};
				column.clickPopup ??= function (e, component, onRendered) {
					if (!component.getValue().length) {
						return '';
					}

					return '<div style="max-width: 50vw; max-height: 50vh">' + window.Tabulator.formatters.files(component, {join: "<br>"}, onRendered) + '</div>';
				};
			}

			return column;
		});
	}

	$(document).on('click', '.clear-all-table-filters', function () {
		$(this).closest('.tabulator').each(function () {
			$.each(window.Tabulator.findTable(this), function () {
				this.clearHeaderFilter();
			});
		});
	});

	$(document).on('click', '.clear-all-table-sorting', function () {
		$(this).closest('.tabulator').each(function () {
			$.each(window.Tabulator.findTable(this), function () {
				this.clearSort();
			});
		});
	});
}(jQuery, window, document));