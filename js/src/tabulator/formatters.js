window.Tabulator.formatters ??= {};

window.Tabulator.formatters.files = function (cell, formatterParams, onRendered) {
	var files = cell.getValue();

	if (typeof files === 'object' && 'text' in files) {
		files = [files];
	}

	if (!Array.isArray(files)) {
		return files ? files : '';
	}

	var links = files.map(function (file) {
		return file.url ? `<a href="${file.url}" target="_blank" class="debug-bar-file-link-format debug-bar-ide-link">${file.text}</a>` : file.text;
	});

	return links.join(formatterParams.join || " | ");
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

window.Tabulator.formatters.list = function (cell, formatterParams, onRendered) {
	var values = cell.getValue();
	if (!Array.isArray(values) && typeof values == 'object' && values !== null) {
		return '<div style="white-space: pre">' + JSON.stringify(values, null, formatterParams.space || 0) + '</div>';
	}
	return toAssociativeArray(values).join(formatterParams.join || '<br>');
};