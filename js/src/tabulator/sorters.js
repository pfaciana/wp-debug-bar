window.Tabulator.sorter ??= {};

window.Tabulator.sorter.file = function (a, b, aRow, bRow, column, dir, sorterParams) {
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