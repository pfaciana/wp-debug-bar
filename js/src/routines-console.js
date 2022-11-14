(function ($, window, document) {
	$(function () {
		function getRoutineEl(el) {
			if (el instanceof jQuery) {
				el = el[0];
			}
			return el.hasAttribute('data-routine-id') ? $(el) : $(el).closest('[data-routine-id]');
		}

		function routineData(el, prop, value, returnEl = true) {
			let $el = getRoutineEl(el), elID = $el.data('routine-id');

			if (typeof prop === 'undefined') {
				return $el.data();
			}

			if (typeof value === 'undefined') {
				return $el.data(prop);
			}

			const prevValue = $el.data(prop);

			if (value === 'toggle') {
				value = !prevValue;
			}

			$el.data(prop, value);

			if ('do_action' in window) {
				do_action('rdb/routine/update', elID, prop, value, prevValue, $el);
				do_action('rdb/routine/update/' + elID, prop, value, prevValue, $el);
				do_action('rdb/routine/update/' + elID + '/' + prop, value, prevValue, $el);
			}

			return returnEl ? $el : value;
		}

		function scrollToBottom($routine, $output) {
			if (!$routine instanceof jQuery) {
				$routine = getRoutineEl($output);
			}
			if (!$routine.data('autoScroll')) {
				return;
			}
			if (!$output instanceof jQuery) {
				$output = $routine.find('.routine-output-buffer');
			}
			$output.scrollTop($output.prop('scrollHeight'));
		}

		$('.routine-output-buffer, .routine-pause-scroll').on('wheel mouseup', function () {
			routineData($(this), 'autoScroll', false);
		});

		$('.routine-auto-scroll').on('click', function () {
			scrollToBottom(routineData($(this), 'autoScroll', true));
		});

		$('.routine-abort-xhr').on('click', function () {
			var $routine = getRoutineEl($(this));
			$routine.data('currentRequest') && $routine.data('currentRequest').abort();
			routineData($routine, 'currentRequest', null);
		});

		$('[data-routine-id]').find('[data-action]').on('click', function () {
			var $this = $(this),
				$routine = getRoutineEl($this),
				$output = $routine.find('.routine-output-buffer'),
				data = {action: $this.data('action')};

			var urlParams = new URLSearchParams(window.location.search);
			var dataArgs = urlParams.get(data.action);
			(![undefined, null, false, ''].includes(dataArgs)) && (data.args = dataArgs);

			routineData($routine, 'currentRequest', $.ajax({
				type: 'GET',
				url: $routine.data('ajax-url'),
				dataType: 'html',
				data: data,
				xhr: function () {
					var xhr = $.ajaxSettings.xhr();
					xhr.onprogress = function (e) {
						$output.html(e.target.responseText);
						scrollToBottom($routine, $output);
					};
					return xhr;
				},
				complete: function (response) {
					$output.append('\nDone!\n\n');
					scrollToBottom($routine, $output);
					routineData($routine, 'currentRequest', null);
				}
			}));
		});

	});
}(jQuery, window, document));