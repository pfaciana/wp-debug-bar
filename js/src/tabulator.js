(function ($, window, document, undefined) {
	if (typeof $ === 'function' && 'subscribe' in $) {
		$.subscribe('tabulator-table-setup', function (options, element, namespace = 'all') {
			if (namespace !== 'DebugBar') {
				return options;
			}

			options.pagination ??= 'local';
			options.paginationSize ??= 20;
			options.paginationSizeSelector ??= [5, 10, 20, 50, 100, true];
			options.paginationButtonCount ??= 15;
			options.movableColumns ??= true;
			options.footerElement = '<button class="clear-all-table-filters tabulator-page">Clear Filters</button> <button class="clear-all-table-sorting tabulator-page">Clear Sorting</button> ' + (options.footerElement ??= '')

			return options;
		});

		$.subscribe('tabulator-column-setup', function (column, data, initial, options, element, namespace = 'all') {
			if (namespace !== 'DebugBar') {
				return column;
			}

			if (['bool', 'boolean', 'tickCross'].includes(initial.formatter)) {
				column.width ??= 75;
				column.headerWordWrap ??= true;
			}

			if (['files', 'file'].includes(initial.formatter)) {
				if (!('headerFilter' in column) && !('headerFilterFunc' in column)) {
					column.bottomCalcFormatter ??= 'html';
					column.bottomCalcParams ??= {};
					jQuery.extend(true, column.bottomCalcParams, {table: element, name: column.field, data});
					column.bottomCalc ??= function (values, data, params) {
						var set = new Set();

						const allValues = Tabulator.helpers.arrayColumn(params.data, params.name);

						allValues.forEach(function (files) {
							if (Tabulator.helpers.isObject(files) && ('text' in files || 'url' in files)) {
								files = [files];
							}
							const names = Tabulator.helpers.arrayColumn(files, 'text');
							names.forEach(function (name) {
								if (name && name.includes(' > ')) {
									const path = name.split(' > ')[0] + ' > ';
									path && set.add(path);
								}
							});
						});

						const paths = Array.from(set).sort();

						if (!paths.length) {
							return '';
						}

						return `<select name="${params.name}" data-table="${params.table}" class="files-picker">
									<option>Filter by Section</option>
									${paths.map((path) => `<option value='regex:"${path}"'>${path}</option>`)}
								</select>`;
					}
				}
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

	$(document).on('click', '.delete-table', function () {
		var $button = $(this);
		$button.closest('.tabulator').each(function () {
			var $table = $(this);
			$.each(window.Tabulator.findTable(this), function () {
				var $tabulator = $(this)[0];
				$.publish('tabulator-table-delete', $table, $tabulator, $button);
				$tabulator.destroy();
				if ($button.hasClass('delete-container')) {
					$table.parent().remove();
				} else {
					$table.remove();
				}
			});
		});
	});

	$(document).on('change', '.files-picker', function () {
		const $this = $(this);
		Tabulator.findTable($this.data('table'))[0].setHeaderFilterValue($this.attr('name'), $this.val());
	});
}(jQuery, window, document));