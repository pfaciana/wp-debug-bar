(function () {
	var timers = {};

	window.debouncer = function (id, callback, ms) {
		ms = typeof ms !== 'undefined' ? ms : 500;
		if (timers[id]) {
			clearTimeout(timers[id]);
		}
		timers[id] = setTimeout(callback, ms);
	}
}());