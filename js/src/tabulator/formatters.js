window.Tabulator.formatters ??= {};

window.Tabulator.formatters.file = function (cell, formatterParams, onRendered) {
	if (!Array.isArray(cell.getValue())) {
		return cell.getValue();
	}

	var files = cell.getValue().map(function (value) {
		return value.url ? `<a href="${value.url}" target="_blank" class="debug-bar-file-link-format debug-bar-ide-link">${value.text}</a>` : value.text;
	});

	return files.join(formatterParams.join || " | ");
};

window.Tabulator.formatters.timeMs = function (cell, formatterParams, onRendered) {
	return cell.getValue().toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ms';
};

window.Tabulator.formatters.args = function (cell, formatterParams, onRendered) {
	if (cell.getValue() === null) {
		return '';
	}

	var values = [];

	function formatArg(arg) {
		return `<span title="(${arg.type}) ${arg.type === 'same' ? 'This value did not change' : arg.text}" data-type="${arg.type}">${arg.type === 'same' ? '(same)' : arg.text}</span>`;
	}

	$.each(Array.isArray(cell.getValue()) ? cell.getValue() : [cell.getValue()], function (i, arg) {
		values.push(formatArg(arg));
	});

	return '<div>' + values.join("\n") + '</div>';
};