window.Tabulator ??= {};

window.Tabulator.empty = [null, ''];

window.Tabulator.search = function search(keyword, content) {
	if (keyword.startsWith('regex:i:')) {
		if (!(new RegExp(keyword.slice(8), 'i').test(content))) {
			return false;
		}
	} else if (keyword.startsWith('i:')) {
		if (!content.toLowerCase().includes(keyword.slice(2).toLowerCase())) {
			return false;
		}
	} else if (keyword.startsWith('regex:')) {
		if (!(new RegExp(keyword.slice(6))).test(content)) {
			return false;
		}
	} else if (keyword.startsWith('not:')) {
		if (content.includes(keyword.slice(4))) {
			return false;
		}
	} else if (keyword.startsWith('-')) {
		if (content.includes(keyword.slice(1))) {
			return false;
		}
	} else if (keyword.startsWith('+')) {
		if (!content.includes(keyword.slice(1))) {
			return false;
		}
	} else {
		if (!content.includes(keyword)) {
			return false;
		}
	}

	return true;
};

window.Tabulator.common ??= {};

window.Tabulator.common.arrayByLength = {
	headerSortStartingDir: 'desc',
	sorter: function (a, b, aRow, bRow, column, dir, sorterParams) {
		return a.length - b.length;
	},
	headerFilterFunc: function (headerValue, rowValue, rowData, filterParams) {
		rowValue = rowValue.length;

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
	},
	formatter: function (cell, formatterParams, onRendered) {
		return cell.getValue().length;
	},
	clickPopup: function (e, component, onRendered) {
		if (!component.getValue().length) {
			return '';
		}

		return Tabulator.formatters.files(component, {join: "<br>"}, onRendered);
	},
};

window.Tabulator.common.filesArray = {
	headerFilter: 'input',
	headerFilterFuncParams: {strict: false},
	headerFilterFunc: Tabulator.filters.advancedFile,
	sorter: Tabulator.sorter.files,
	formatterParams: {join: " | "},
	formatter: Tabulator.formatters.files,
};

window.Tabulator.common.valuesArray = {
	headerFilter: 'list',
	headerFilterParams: {
		clearable: true,
		valuesLookup: function (component, filterTerm) {
			var values = new Set();

			$.each(component.getColumn().getCells(), function (i, cell) {
				$.each(cell.getValue(), function (i, value) {
					values.add(value);
				});
			});

			return values.size ? Array.from(values).sort() : [];
		}
	},
	headerFilterFunc: function (search, value) {
		return value.includes(search);
	},
};

window.Tabulator.common.listArray = {
	headerFilter: 'input',
	headerFilterFuncParams: {strict: false},
	headerFilterFunc: Tabulator.filters.list,
	sorter: Tabulator.sorter.list,
	formatterParams: {join: "<br>"},
	formatter: Tabulator.formatters.list,
};