(function ($, window, document, undefined) {
	if (typeof $ === 'function' && 'subscribe' in $) {
		$.subscribe('tabulator-table-setup', function (options, element) {
			options.pagination ??= 'local';
			options.paginationSize ??= 20;
			options.paginationSizeSelector ??= [5, 10, 20, 50, 100, true];
			options.paginationButtonCount ??= 15;
			options.movableColumns ??= true;
			options.footerElement ??= '<button class="clear-all-table-filters tabulator-page">Clear Filters</button>';

			return options;
		});

		$.subscribe('tabulator-column-setup', function (column, data, initial, options, element) {
			if (['bool', 'boolean', 'tickCross'].includes(initial.formatter)) {
				column.width ??= 75;
				column.headerWordWrap ??= true;
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
}(jQuery, window, document));