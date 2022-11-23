window.Tabulator.filters ??= {};

window.Tabulator.filters.minMax = function (array, config, filterMin = true, filterMax = true) {
	var values = arrayColumn(array, config.field);
	var min = Math.min(...values);
	var max = Math.max(...values);
	var empty = window.Tabulator.empty;

	config.sorter ??= function (a, b, aRow, bRow, column, dir, sorterParams) {
		return empty.includes(a) ? (dir === 'asc' ? 1 : -1) : (empty.includes(b) ? (dir === 'asc' ? -1 : 1) : a - b);
	};
	config.headerFilter ??= function (cell, onRendered, success, cancel, editorParams) {
		var start = document.createElement('input'),
			end = document.createElement('input'),
			container = document.createElement('span');

		function createInput(input, title) {
			input.setAttribute('type', 'number');
			input.setAttribute('placeholder', title);
			input.setAttribute('min', Math.floor(min) || 0);
			input.setAttribute('max', Math.ceil(max) || 100);
			input.style.padding = '4px';
			input.style.width = filterMin && filterMax ? '50%' : '100%';
			input.style.boxSizing = 'border-box';
			input.value = cell.getValue();
			input.addEventListener('change', buildValues);
			input.addEventListener('blur', buildValues);
			input.addEventListener('keydown', keypress);
			container.appendChild(input);
		}

		function buildValues() {
			success({start: start.value, end: end.value,});
		}

		function keypress(e) {
			e.keyCode === 13 && buildValues();
			e.keyCode === 27 && cancel();
		}

		filterMin && createInput(start, 'Min');
		filterMax && createInput(end, 'Max');

		return container;
	};
	config.headerFilterFunc ??= function (headerValue, rowValue, rowData, filterParams) {
		if ((headerValue.start !== "" || headerValue.end !== "") && rowValue === null) {
			return false;
		}
		if (rowValue || rowValue === 0) {
			if (headerValue.start !== "") {
				if (headerValue.end !== "") {
					return rowValue >= headerValue.start && rowValue <= headerValue.end;
				} else {
					return rowValue >= headerValue.start;
				}
			} else {
				if (headerValue.end !== "") {
					return rowValue <= headerValue.end;
				}
			}
		}
		return true;
	};
	config.headerFilterLiveFilter ??= false;

	return config;
};

window.Tabulator.filters.advanced = function (headerValue, rowValue, rowData, filterParams) {
	if (!headerValue.includes(' ') && !headerValue.includes(':') && !headerValue.includes('-') && !headerValue.includes('+')) {
		return rowValue.includes(headerValue);
	}

	var keywords = headerValue.match(/(?:[^\s"]+|"[^"]*")+/g);
	for (var keyword of keywords) {
		if (!Tabulator.search(keyword, rowValue)) {
			return false;
		}
	}

	return true;
};

window.Tabulator.filters.advancedFile = function (headerValue, rowValue, rowData, filterParams) {
	if (Array.isArray(rowValue)) {
		rowValue = rowValue.map(x => x.text).join(' ');
	}

	if ('strict' in filterParams && !filterParams.strict) {
		headerValue = (headerValue || '').toLowerCase();
		rowValue = (rowValue || '').toLowerCase();
	}

	return Tabulator.filters.advanced(headerValue, rowValue, rowData, filterParams);
};

window.Tabulator.filters.args = function (headerValue, rowValueObj, rowData, filterParams) {
	var rowValue = rowValueObj.text;
	(rowValue === true) && (rowValue = '1');
	(rowValue === false) && (rowValue = '0');
	(rowValue === null) && (rowValue = '');
	return Tabulator.filters.advanced(headerValue, rowValue.toString(), rowData, filterParams);
};

window.Tabulator.filters.list = function (headerValue, rowValue, rowData, filterParams) {
	if (Array.isArray(rowValue)) {
		rowValue = rowValue.join(' ');
	}

	if (typeof rowValue == 'object' && rowValue !== null) {
		rowValue = JSON.stringify(rowValue, null, 4);
	}

	if ('strict' in filterParams && !filterParams.strict) {
		headerValue = (headerValue || '').toLowerCase();
		rowValue = (rowValue || '').toLowerCase();
	}

	if (!headerValue.includes(' ') && !headerValue.includes(':') && !headerValue.includes('-') && !headerValue.includes('+')) {
		return rowValue.includes(headerValue);
	}

	var keywords = headerValue.match(/(?:[^\s"]+|"[^"]*")+/g);
	for (var keyword of keywords) {
		if (!Tabulator.search(keyword, rowValue)) {
			return false;
		}
	}

	return true;
};

window.Tabulator.filters.boolean = function (config) {
	var base = {
		sorter: 'boolean',
		hozAlign: 'center',
		formatter: 'tickCross',
		formatterParams: {allowEmpty: true, allowTruthy: true,},
		headerFilter: 'tickCross',
		headerFilterParams: {'tristate': true},
		width: 61,
	};

	if ('src' in config) {
		config.title = `<img alt="${config.title}" title="${config.title}" src="${config.src}" style="max-width: 100%;" />`;
		delete config.src;
	}

	return {...base, ...config};
};

(function ($, window, document, undefined) {
	$(document).on('click', '.clear-all-table-filters', function () {
		$(this).closest('.tabulator').each(function () {
			$.each(window.Tabulator.findTable(this), function () {
				this.clearHeaderFilter();
			});
		});
	});
}(jQuery, window, document));