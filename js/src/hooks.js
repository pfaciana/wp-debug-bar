(function () {
	var actions = {};

	function get_action(tag) {
		if (typeof tag === 'undefined') {
			throw new Error('Invalid Signature!');
		}

		return actions[tag] || (actions[tag] = $.Callbacks());
	}

	window.do_action = function (tag) {
		var action, args = Array.prototype.slice.call(arguments);

		if (typeof tag === 'undefined') {
			throw new Error('Invalid Signature!');
		}

		action = get_action(args.shift());
		action.fire.apply(action, args);
	};
	window.add_action = function (tag, callback) {
		if (typeof tag === 'undefined' || typeof callback !== 'function') {
			throw new Error('Invalid Signature!');
		}

		get_action(tag).add(callback);
	};
	window.remove_action = function (tag, callback) {
		if (typeof tag === 'undefined' || typeof callback !== 'function') {
			throw new Error('Invalid Signature!');
		}

		get_action(tag).remove(callback);
	};
}());