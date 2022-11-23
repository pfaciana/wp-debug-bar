window.Tabulator.sorter ??= {};

window.Tabulator.sorter.files = function (a, b, aRow, bRow, column, dir, sorterParams) {
	if (!a) {
		a = '';
	}

	if (!b) {
		b = '';
	}

	if (Array.isArray(a)) {
		a = a.map(x => x.text).join(' ');
	}

	if (Array.isArray(b)) {
		b = b.map(x => x.text).join(' ');
	}

	return a.localeCompare(b);
};

window.Tabulator.sorter.args = function (o1, o2, aRow, bRow, column, dir, sorterParams) {
	var a = o1.text, b = o2.text;

	if (!isNaN(a) && !isNaN(b)) {
		return a - b;
	}
	(a === true) && (a = '1');
	(a === false) && (a = '0');
	(a === null) && (a = '');
	(b === true) && (b = '1');
	(b === false) && (b = '0');
	(b === null) && (b = '');

	return (a.toString()).localeCompare(b.toString());
};

window.Tabulator.sorter.list = function (array1, array2, aRow, bRow, column, dir, sorterParams) {
	var a = array1, b = array2;

	if (typeof a == 'object' && a !== null) {
		if (Array.isArray(a)) {
			a = a.join('');
		} else {
			a = JSON.stringify(a);
		}
	}
	if (typeof b == 'object' && b !== null) {
		if (Array.isArray(b)) {
			b = b.join('');
		} else {
			b = JSON.stringify(b);
		}
	}

	if (!isNaN(a) && !isNaN(b)) {
		return a - b;
	}

	(a === true) && (a = '1');
	(a === false) && (a = '0');
	(a === null) && (a = '');
	(b === true) && (b = '1');
	(b === false) && (b = '0');
	(b === null) && (b = '');

	return (a.toString()).localeCompare(b.toString());
};