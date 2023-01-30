(function () {
	var timers = {};

	window.rdb = window.rdb || {};

	rdb.debouncer = function (id, callback, ms) {
		ms = typeof ms !== 'undefined' ? ms : 500;
		if (timers[id]) {
			clearTimeout(timers[id]);
		}
		timers[id] = setTimeout(callback, ms);
	}

	rdb.getCookie = function (name, defaultValue = null) {
		var parts = ('; ' + document.cookie).split('; ' + name + '=');
		return parts.length == 2 ? parts.pop().split(';').shift() : defaultValue;
	}

	rdb.deleteCookie = function (name, path = '/') {
		rdb.setCookie(name, '', 0, path);
	}

	rdb.setCookie = function (name, value, maxAge = null, path = '/') {
		document.cookie = `${name}=${value};max-age=${maxAge};path=${path}`;
	}

	rdb.formatLocalTime = function (time) {
		return time.replace(/^(\d{2})(\d{2})(\d{4})(\d{2})(\d{2})(\d{2})(\d{3})/g, "$1-$2-$3 $4:$5:$6.$7")
	};

}());