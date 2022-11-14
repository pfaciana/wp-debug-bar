(function ($, window, document, undefined) {
	$(function () {
		/*
			jQuery deparam is an extraction of the deparam method from Ben Alman's jQuery BBQ
			http://benalman.com/projects/jquery-bbq-plugin/
		*/
		$.deparam = function (params, coerce) {
			var obj = {}, coerce_types = {'true': !0, 'false': !1, 'null': null};

			if (typeof params !== 'string') {
				return {};
			}

			$.each(params.replace(/\+/g, ' ').split('&'), function (j, v) {
				var param = v.split('='), key = decodeURIComponent(param[0]),
					val, cur = obj, i = 0, keys = key.split(']['), keys_last = keys.length - 1;

				if (/\[/.test(keys[0]) && /\]$/.test(keys[keys_last])) {
					keys[keys_last] = keys[keys_last].replace(/\]$/, '');
					keys = keys.shift().split('[').concat(keys);
					keys_last = keys.length - 1;
				} else {
					keys_last = 0;
				}

				if (param.length === 2) {
					val = decodeURIComponent(param[1]);
					if (coerce) {
						val = val && !isNaN(val) ? +val // number
							: val === 'undefined' ? undefined // undefined
								: coerce_types[val] !== undefined ? coerce_types[val] // true, false, null
									: val; // string
					}
					if (keys_last) {
						for (; i <= keys_last; i++) {
							key = keys[i] === '' ? cur.length : keys[i];
							cur = cur[key] = i < keys_last ? cur[key] || (keys[i + 1] && isNaN(keys[i + 1]) ? {} : []) : val;
						}
					} else {
						if ($.isArray(obj[key])) {
							obj[key].push(val);
						} else if (obj[key] !== undefined) {
							obj[key] = [obj[key], val];
						} else {
							obj[key] = val;
						}
					}
				} else if (key) {
					obj[key] = coerce ? undefined : '';
				}
			});

			return obj;
		};
	});
	$(function () {

		var autoScrollToBottom = true;

		var actionShow = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M288 144a110.94 110.94 0 0 0-31.24 5 55.4 55.4 0 0 1 7.24 27 56 56 0 0 1-56 56 55.4 55.4 0 0 1-27-7.24A111.71 111.71 0 1 0 288 144zm284.52 97.4C518.29 135.59 410.93 64 288 64S57.68 135.64 3.48 241.41a32.35 32.35 0 0 0 0 29.19C57.71 376.41 165.07 448 288 448s230.32-71.64 284.52-177.41a32.35 32.35 0 0 0 0-29.19zM288 400c-98.65 0-189.09-55-237.93-144C98.91 167 189.34 112 288 112s189.09 55 237.93 144C477.1 345 386.66 400 288 400z"></path></svg>',
			actionHide = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path fill="currentColor" d="M634 471L36 3.51A16 16 0 0 0 13.51 6l-10 12.49A16 16 0 0 0 6 41l598 467.49a16 16 0 0 0 22.49-2.49l10-12.49A16 16 0 0 0 634 471zM296.79 146.47l134.79 105.38C429.36 191.91 380.48 144 320 144a112.26 112.26 0 0 0-23.21 2.47zm46.42 219.07L208.42 260.16C210.65 320.09 259.53 368 320 368a113 113 0 0 0 23.21-2.46zM320 112c98.65 0 189.09 55 237.93 144a285.53 285.53 0 0 1-44 60.2l37.74 29.5a333.7 333.7 0 0 0 52.9-75.11 32.35 32.35 0 0 0 0-29.19C550.29 135.59 442.93 64 320 64c-36.7 0-71.71 7-104.63 18.81l46.41 36.29c18.94-4.3 38.34-7.1 58.22-7.1zm0 288c-98.65 0-189.08-55-237.93-144a285.47 285.47 0 0 1 44.05-60.19l-37.74-29.5a333.6 333.6 0 0 0-52.89 75.1 32.35 32.35 0 0 0 0 29.19C89.72 376.41 197.08 448 320 448c36.7 0 71.71-7.05 104.63-18.81l-46.41-36.28C359.28 397.2 339.89 400 320 400z"></path></svg>',
			actionExpand = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M400 32H48C21.5 32 0 53.5 0 80v352c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48V80c0-26.5-21.5-48-48-48zm-32 252c0 6.6-5.4 12-12 12h-92v92c0 6.6-5.4 12-12 12h-56c-6.6 0-12-5.4-12-12v-92H92c-6.6 0-12-5.4-12-12v-56c0-6.6 5.4-12 12-12h92v-92c0-6.6 5.4-12 12-12h56c6.6 0 12 5.4 12 12v92h92c6.6 0 12 5.4 12 12v56z"></path></svg>',
			actionCollapse = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M400 32H48C21.5 32 0 53.5 0 80v352c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48V80c0-26.5-21.5-48-48-48zM92 296c-6.6 0-12-5.4-12-12v-56c0-6.6 5.4-12 12-12h264c6.6 0 12 5.4 12 12v56c0 6.6-5.4 12-12 12H92z"></path></svg>',
			actionClear = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 8C119.034 8 8 119.033 8 256s111.034 248 248 248 248-111.034 248-248S392.967 8 256 8zm130.108 117.892c65.448 65.448 70 165.481 20.677 235.637L150.47 105.216c70.204-49.356 170.226-44.735 235.638 20.676zM125.892 386.108c-65.448-65.448-70-165.481-20.677-235.637L361.53 406.784c-70.203 49.356-170.226 44.736-235.638-20.676z"></path></svg>',
			actionShowAlt = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path fill="currentColor" d="M320 400c-75.85 0-137.25-58.71-142.9-133.11L72.2 185.82c-13.79 17.3-26.48 35.59-36.72 55.59a32.35 32.35 0 0 0 0 29.19C89.71 376.41 197.07 448 320 448c26.91 0 52.87-4 77.89-10.46L346 397.39a144.13 144.13 0 0 1-26 2.61zm313.82 58.1l-110.55-85.44a331.25 331.25 0 0 0 81.25-102.07 32.35 32.35 0 0 0 0-29.19C550.29 135.59 442.93 64 320 64a308.15 308.15 0 0 0-147.32 37.7L45.46 3.37A16 16 0 0 0 23 6.18L3.37 31.45A16 16 0 0 0 6.18 53.9l588.36 454.73a16 16 0 0 0 22.46-2.81l19.64-25.27a16 16 0 0 0-2.82-22.45zm-183.72-142l-39.3-30.38A94.75 94.75 0 0 0 416 256a94.76 94.76 0 0 0-121.31-92.21A47.65 47.65 0 0 1 304 192a46.64 46.64 0 0 1-1.54 10l-73.61-56.89A142.31 142.31 0 0 1 320 112a143.92 143.92 0 0 1 144 144c0 21.63-5.29 41.79-13.9 60.11z"></path></svg>',
			actionHideAlt = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M572.52 241.4C518.29 135.59 410.93 64 288 64S57.68 135.64 3.48 241.41a32.35 32.35 0 0 0 0 29.19C57.71 376.41 165.07 448 288 448s230.32-71.64 284.52-177.41a32.35 32.35 0 0 0 0-29.19zM288 400a144 144 0 1 1 144-144 143.93 143.93 0 0 1-144 144zm0-240a95.31 95.31 0 0 0-25.31 3.79 47.85 47.85 0 0 1-66.9 66.9A95.78 95.78 0 1 0 288 160z"></path></svg>',
			actionUp = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" ><path fill="currentColor" d="M272 480h-96c-13.3 0-24-10.7-24-24V256H48.2c-21.4 0-32.1-25.8-17-41L207 39c9.4-9.4 24.6-9.4 34 0l175.8 176c15.1 15.1 4.4 41-17 41H296v200c0 13.3-10.7 24-24 24z"></path></svg>';

		function initLoad() {
			$('.kint-rich.kint-folder').insertBefore('.kint-rich-script');

			if (typeof localStorage.rwdKintDebuggerHidePublic === "undefined") {
				localStorage.rwdKintDebuggerHidePublic = '0';
			}
			if (typeof localStorage.rwdKintDebuggerHideProtected === "undefined") {
				localStorage.rwdKintDebuggerHideProtected = '0';
			}
			if (typeof localStorage.rwdKintDebuggerHidePrivate === "undefined") {
				localStorage.rwdKintDebuggerHidePrivate = '0';
			}

			$('.kint-rich.kint-folder > dl > dt.kint-parent').append(
				'<a class="action-scroll" data-action="end"><i class="fa fa-arrow-circle-down" aria-hidden="true"></i></a>' +
				'<a class="action-auto-scroll" data-action="hide"><i class="fa fa fa-arrow-down" aria-hidden="true"></i></a>' +
				'<a class="action-auto-scroll" data-action="show" style="display: none;"><i class="fa fa fa-pause" aria-hidden="true"></i></a>' +
				'<a class="action-scope" data-scope="private" data-action="hide">' + actionShow + '</a>' +
				'<a class="action-scope" data-scope="private" data-action="show" style="display: none;">' + actionHide + '</a>' +
				'<a class="action-scope" data-scope="protected" data-action="hide">' + actionShow + '</a>' +
				'<a class="action-scope" data-scope="protected" data-action="show" style="display: none;">' + actionHide + '</a>' +
				'<a class="action-scope" data-scope="public" data-action="hide">' + actionShow + '</a>' +
				'<a class="action-scope" data-scope="public" data-action="show" style="display: none;">' + actionHide + '</a>' +
				'<a class="action-visibility" data-action="hide">' + actionShowAlt + '</a>' +
				'<a class="action-visibility" data-action="show">' + actionHideAlt + '</a>' +
				'<a class="action-visibility" data-action="delete">' + actionClear + '</a>' +
				'<a class="action-accordion" data-action="hide">' + actionCollapse + '</a>' +
				'<a class="action-accordion" data-action="show">' + actionExpand + '</a>'
			).addClass('kint-nav-bar').next().addClass('kint-file-parent');


			$(document).on('click', '.action-scope', function (e) {
				var $this = $(this), scope = $this.data('scope'), action = $this.data('action'), show = action === 'show';
				$('a[data-scope="' + scope + '"][data-action="show"]').toggle(!show);
				$('a[data-scope="' + scope + '"][data-action="hide"]').toggle(show);
				$('var.kint-scope.kint-' + scope).parent().parent().toggle(show);
				localStorage['rwdKintDebuggerHide' + scope.charAt(0).toUpperCase() + scope.slice(1)] = !show ? '1' : '0';
				return false;
			});

			$(document).on('click', '.action-accordion', function () {
				var $this = $(this), action = $this.data('action'), $parent = $this.parent().parent().find('.kint-parent');
				action === 'show' ? $parent.addClass('kint-show') : $parent.removeClass('kint-show');
				return false;
			});

			$(document).on('click', '.action-auto-scroll', function () {
				var $this = $(this), action = $this.data('action'), show = action === 'show';
				$('.action-auto-scroll[data-action="show"]').toggle(!show);
				$('.action-auto-scroll[data-action="hide"]').toggle(show);
				autoScrollToBottom = show;
				return false;
			});

			$(document).on('click', '.action-scroll[data-action="end"]', function () {
				var $this = $(this);
				scrollToBottom();
				return false;
			});

			$(document).on('click', '.kint-rich.kint-folder > dl > dt.kint-parent .action-visibility', function () {
				var $this = $(this), action = $this.data('action'),
					$folder = $this.parent().next(), $files = $folder.children();

				if (action === 'show') {
					$('.kint-hide').removeClass('kint-hide');
				} else if (action === 'hide') {
					$files.addClass('kint-hide');
				} else if (action === 'delete') {
					if (confirm('Delete all?')) {
						$folder.empty();
					}
				}

				return false;
			});

			$(document).on('click', '.kint-file .action-visibility', function () {
				var $this = $(this), action = $this.data('action'), $file = $this.closest('.kint-file'), $section = $this.closest('dl'), $parentSection = $section.parent();

				if (action === 'show') {
					$section.removeClass('kint-hide').toggleClass('kint-persistent-show');
				} else if (action === 'hide') {
					$section.addClass('kint-hide').removeClass('kint-persistent-show');
					if ($parentSection.children(':not(.kint-hide)').length < 2) {
						$parentSection.addClass('kint-hide');
					}
				} else if (action === 'delete') {
					if (confirm('Delete this item?')) {
						$section.remove();
						if ($parentSection.children().length < 2) {
							$parentSection.remove();
						}
					}
				} else if (action === 'previous') {
					$file.prevAll().addClass('kint-hide');
				}

				$file.removeClass('kint-persistent-show').toggleClass('kint-persistent-show', $file.find('.kint-persistent-show').length > 0);

				return false;
			});

			$('.rwd-debug-bar-content .kint-rich.kint-folder > dl > dt.kint-parent').trigger('click');
		}

		function postRender(reRenderTag) {
			if (reRenderTag !== false) {
				$('.kint-rich.kint-folder').addClass('kint-rerendered');
			}

			$('.kint-file:not(".kint-dark") var').each(function () {
				var $this = $(this), text = $this.text();
				text = text.split(" ")[0];
				$this.attr("data-var", text).addClass("kint-" + text);

				if (text === 'public' || text === 'protected' || text === 'private') {
					$this.addClass("kint-scope");
				} else {
					$this.addClass("kint-type");
				}
			});

			$('.kint-file:not(".kint-dark") dt').contents().filter(function () {
				return this.nodeType == Node.TEXT_NODE;
			}).wrap("<span class='kint-inner-text'></span>");

			$('.kint-file:not(".kint-dark") .kint-inner-text').each(function () {
				var $this = $(this), text = $this.text();

				if (text === ' ' || text === '') {
					$this.addClass('kint-empty-text');
				} else if (text === ' -> ' || text === ' => ') {
					$this.addClass('kint-arrow');
				} else if (text === ': ') {
					$this.addClass('kint-colon');
				} else {
					$this.addClass('kint-value');
				}
			});

			$('.kint-file:not(".kint-dark") .kint-popup-trigger').attr('data-action', 'popup');
			$('.kint-file:not(".kint-dark") .kint-access-path-trigger').attr('data-action', 'access-path');
			$('.kint-file:not(".kint-dark") .kint-search-trigger').attr('data-action', 'search');

			$('.kint-file.kint-message:not(".kint-dark")').prepend('<dl><dt></dt></dl>');

			$('.kint-file.kint-header:not(".kint-dark") dt').append(
				'<a class="action-visibility" data-action="previous">' + actionUp + '</a>'
			);

			$('.kint-file:not(".kint-dark") dt').append(
				'<a class="action-visibility" data-action="hide">' + actionShowAlt + '</a>' +
				'<a class="action-visibility" data-action="show">' + actionHideAlt + '</a>' +
				'<a class="action-visibility" data-action="delete">' + actionClear + '</a>'
			);

			$('.kint-file:not(".kint-dark") .kint-parent').append(
				'<a class="action-accordion" data-action="hide">' + actionCollapse + '</a>' +
				'<a class="action-accordion" data-action="show">' + actionExpand + '</a>'
			);

			$('.kint-file:not(".kint-dark")').addClass('kint-dark');

			if (localStorage.rwdKintDebuggerHidePublic == '1') {
				$('.action-scope[data-scope="public"][data-action="hide"]').trigger('click');
			}
			if (localStorage.rwdKintDebuggerHideProtected == '1') {
				$('.action-scope[data-scope="protected"][data-action="hide"]').trigger('click');
			}
			if (localStorage.rwdKintDebuggerHidePrivate == '1') {
				$('.action-scope[data-scope="private"][data-action="hide"]').trigger('click');
			}
		}

		function forceShow() {
			$('.kint-nav-bar').addClass('kint-show');
		}

		function scrollToBottom() {
			var $kintParent = $('.kint-file-parent');
			setTimeout(() => $kintParent.closest('[data-panel]').animate({scrollTop: $kintParent.height()}, 1000), 1);
		}

		function json_beautify(json) {
			if (typeof json === "undefined" || json == '') {
				return null;
			}
			if (typeof json !== "object") {
				json = isJson(json) || isSerialized(json) || json;
			}
			if (typeof json === "object") {
				json = JSON.stringify(json);
			}

			json = json.toString();

			return typeof js_beautify !== "undefined" ? js_beautify(json) : json;
		}

		function isJson(str) {
			try {
				return JSON.parse(str);
			} catch (e) {
				return false;
			}
		}

		function isSerialized(str) {
			var origStr = decodeURIComponent(str),
				json = $.deparam(origStr, true),
				newStr = decodeURIComponent($.param(json));

			return origStr === newStr ? json : false;
		}

		// NOTE: If nothing was sent to Kint, then Kint will not create the kintShared object and this would throw an error
		if (typeof window.kintShared !== "undefined" && typeof window.kintShared.runOnce === "function") {
			window.kintShared.runOnce(function () {
				initLoad();
				postRender(false);
			});

			$(document).ajaxComplete(function (event, xhr, request) {
				if (request.url.includes('kint_ignore=1')) {
					return;
				}

				var counter = 0, kint, prefix = 'kint-file kint-message ',
					contentLength = (xhr.responseText || '').toString().length,
					$kintParent = $('.kint-file-parent'),
					defaultEmpty = '(empty)',
					requestData = (json_beautify(request.data) || defaultEmpty),
					responseData = (json_beautify(xhr.responseText) || defaultEmpty),
					details = '<pre><u>Request</u>:\n\n' + requestData + '\n\n<u>Response</u>:\n\n' + responseData + '</pre>',
					data = $.deparam(request.data, true),
					action = data.action ? ' (' + data.action + ')' : '',
					time = (new Date()).toLocaleTimeString(),
					header = '<div class="' + prefix + 'kint-header"><time>' + time + '</time>' + request.type + ' ' + request.url + ' [' + xhr.status + ' ' + xhr.statusText + ']' + action + '</div>';

				$kintParent.append(header);

				if (xhr.status < 200 || xhr.status >= 400 || xhr.statusText === 'error') {
					$kintParent.append('<div class="' + prefix + 'kint-error">' + details + 'Error: There was aa AJAX error. Check to make sure the headers are not too big.</div>');
				} else {
					if (contentLength > window.output_buffering) {
						$kintParent.append('<div class="' + prefix + 'kint-warning">' + details + 'Warning: The response was greater than the server "output_buffering". It is possible some information is missing.</div>');
					} else if (!xhr.getResponseHeader('RWD-Debug-Bar-Kint-' + counter)) {
						$kintParent.append('<div class="' + prefix + 'kint-message">' + details + 'This Ajax Response did not return any debug information.</div>');
					} else {
						$kintParent.append('<div class="' + prefix + 'kint-message">' + details + '</div>');
					}
					while ((kint = xhr.getResponseHeader('RWD-Debug-Bar-Kint-' + counter++))) {
						if (!kint || kint == '') {
							break;
						}
						$kintParent.append(decodeURIComponent(kint));
					}
				}

				postRender();
				autoScrollToBottom && (('debouncer' in window) ? debouncer('scrollToBottom', scrollToBottom) : scrollToBottom());
			});
		}

		if (typeof add_action === 'function') {
			add_action('rdb/activate-panel/RWD_Debug_Bar_Kint_Panel', function ($panel, firstTime) {
				if (!firstTime && $('.kint-rich.kint-folder').hasClass('kint-rerendered')) {
					$panel.animate({scrollTop: $panel.prop("scrollHeight")}, 1000);
				}
				$('.kint-rich.kint-folder').removeClass('kint-rerendered');
			});
		}
	});
}(jQuery, window, document));