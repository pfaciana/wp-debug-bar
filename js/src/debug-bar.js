(function ($, window, document, undefined) {
	$(function () {

		window.$window = $(window);
		window.$html = $('html');
		window.$body = $('body');

		var $container = $('#rwd-debug-bar-wrap'),
			ajaxUrl = $container.data('ajax-url'),
			isAdmin = $body.hasClass('wp-admin'),
			hiddenKey = isAdmin ? 'rwdDebugBarAdminHidden' : 'rwdDebugBarHidden',
			allStates = 'minimized restored maximized',
			panelStack = [];

		function defineLocalStorage() {
			var defaults = {
				rwdDebugBarTop: '',
				rwdDebugBarLeft: '',
				rwdDebugBarPosition: 'Top',
				rwdDebugBarState: 'maximized',
				rwdDebugBarHidden: '1',
				rwdDebugBarAdminHidden: '1',
				rwdDebugBarTabs: '{}',
			};
			$.each(defaults, function (key, value) {
				if (typeof localStorage[key] === "undefined") {
					localStorage[key] = value;
				}
			});
		}

		function initTheDebugBarTabs() {
			openSavedTabs();
			$container.find('.debug-bar-tabs a').on('click', function () {
				goToTab($(this).data('tab-id'));
			}).on('keypress', function (e) {
				if (e.which == 13 || e.which == 32) {
					goToTab($(this).data('tab-id'));
				}
			});
		}

		function goToTab(tabId, groupId = null) {
			var $content = $(`#${tabId}`);
			var $contentGroup = $content.closest('.debug-bar-tabs-content');
			var $tabs = $contentGroup.prev();

			$contentGroup.children().hide();
			$content.show();

			$tabs.find('.active').removeClass('active');
			$tabs.find(`[data-tab-id="${tabId}"]`).parent().addClass('active');

			groupId ??= $contentGroup.data('group-id');
			saveOpenedTab(groupId, tabId);
		}

		function saveOpenedTab(groupId, tabId) {
			var tabs = JSON.parse(localStorage.rwdDebugBarTabs ??= '{}');
			tabs[groupId] = tabId;
			localStorage.rwdDebugBarTabs = JSON.stringify(tabs);
		}

		function openSavedTabs() {
			var $tabs = $container.find('.debug-bar-tabs-content');
			$tabs.children().hide();
			$tabs.children(':first-child').show();
			$container.find('.debug-bar-tabs').children(':first-child').addClass('active');
			var tabs = JSON.parse(localStorage.rwdDebugBarTabs ??= '{}');
			$.each(tabs, function (groupId, tabId) {
				goToTab(tabId, groupId);
			});
		}

		function initTheDebugBarWindowAndPanels() {
			$container.appendTo($body);

			// Hide All Panels First
			$('.rwd-debug-bar-content > section').hide();

			// Go to the last view panel, if saved
			if (typeof localStorage.rwdDebugBarPanel !== "undefined") {
				goToPanel(localStorage.rwdDebugBarPanel);
			}

			// Show/Hide Debug Bar Window
			localStorage[hiddenKey] == '1' ? $container.hide() : $container.show();
			//$container.css('position', 'fixed');

			// Set the Initial Console state
			setConsoleState(localStorage.rwdDebugBarState);

			// Set the fallback #querylist if the original DebugBar is not present
			// to help with compatibility for 3rd party extensions
			setTimeout(function () {
				if ($('#querylist').length < 1) {
					$('.rwd-debug-bar-content').attr('id', 'querylist');
				}
			}, 1);
		}

		function attachEventHandlers() {
			// DebugBar Window Resize
			$container.resizable({
				handles: 'n, w',
				stop: function (event, ui) {
					var location, windowSize, container_styles = {
						'height': '',
						'width': '',
						'left': '',
						'top': ''
					};
					localStorage.rwdDebugBarPosition = ui.originalPosition.top != ui.position.top ? 'Top' : 'Left';

					// Convert to Percentages instead of Pixels
					location = localStorage.rwdDebugBarPosition.toLowerCase();
					windowSize = location === 'top' ? $window.innerHeight() : $window.innerWidth();
					container_styles[location] = Math.round(ui.position[location] / windowSize * 1000) / 10 + '%';
					$container.css(container_styles);

					restoreConsole();
				}
			});

			// WordPress Top Bar 'RWD Debug Bar' Toggle Show/Hide
			$('#wp-admin-bar-rwd-debug-bar > a').on('click', function () {
				$container.toggle();
				localStorage[hiddenKey] = isConsoleHidden('string');
				resetConsolePosition();
				setConsolePosition('toggle');
				return false;
			});

			// WordPress Top Bar Sub Links for 'RWD Debug Bar' parent
			$('.rwd-debug-admin-bar-link a').on('click', function () {
				return goToPanel(this.hash.substring(1));
			});


			// [                                                                    _ ɵ □ X ]
			// DebugBar Top Bar Restore (Min/Max) Button
			$('#rwd-debug-bar-restore > button').on('click', function () {
				return !$container.hasClass('restored') ? restoreConsole() : maximizeConsole();
			});
			// DebugBar Top Bar Maximize Button
			$('#rwd-debug-bar-maximize > button').on('click', maximizeConsole);
			// DebugBar Top Bar Minimize Button
			$('#rwd-debug-bar-minimize > button').on('click', minimizeConsole);
			// DebugBar Top Bar Flip Dock Position Button
			$('#rwd-debug-bar-flip > button').on('click', function () {
				localStorage.rwdDebugBarPosition = localStorage.rwdDebugBarPosition !== 'Top' ? 'Top' : 'Left';
				setConsolePosition(localStorage.rwdDebugBarState);
				return false;
			});
			// DebugBar Top Bar Close Button
			$('#rwd-debug-bar-close > button').on('click', function () {
				$container.hide();
				localStorage[hiddenKey] = '1';
				setConsolePosition('close');
				return false;
			});

			// DebugBar Left Bar column. Primary and Sub Links
			$(document).on('click', '.rwd-debug-menu-link', function (e) {
				goToPanel($(this).closest('[data-panel]').data('panel'));
			});

			// Each panel's activate/deactivate toggle controls
			$('.rwd-debug-panel-action').on('click', function (e) {
				var $this = $(this);

				$.ajax(ajaxUrl, {
					method: 'POST',
					data: {
						action: 'rwd_debug_bar_panels_status',
						activate: $this.attr('data-activate'),
						panels: $this.closest('[data-panel]').data('panel')
					},
					success: function (response) {
						var active = (response == '1');
						$this.attr('data-activate', active ? 0 : 1);
						$this.find('i.fa').toggleClass('fa-toggle-off', active ? 0 : 1);
						$this.find('i.fa').toggleClass('fa-toggle-on', active ? 1 : 0);
					},
				});
			});

			// Resize the Console after window resize
			$window.on('resize', function () {
				debouncer('rwdDebugBar', function () {
					if (!isConsoleHidden()) {
						resetConsolePosition();
						setConsolePosition(localStorage.rwdDebugBarState);
					}
				});
			});
		}

		function visibleWindow() {
			if (isConsoleHidden()) {
				return;
			}

			if (localStorage.rwdDebugBarPosition === 'Top' || localStorage.rwdDebugBarState === 'minimized') {
				$('#rwd-debug-bar-window-size').text(Math.ceil($container.width()) + ' x ' + Math.ceil($window.height() - $container.height()));
			} else {
				$('#rwd-debug-bar-window-size').text(Math.ceil($window.width() - $container.width()) + ' x ' + Math.ceil($container.height()));
			}
		}

		function isConsoleHidden(returnType) {
			var isHidden = $container.is(':hidden') == true;
			return returnType === 'string' ? (isHidden ? '1' : '0') : isHidden;
		}

		function resetConsolePosition() {
			var location = localStorage.rwdDebugBarPosition.toLowerCase(),
				windowSize = location === 'top' ? $window.innerHeight() : $window.innerWidth();

			if ((windowSize - 32) < ($container.position()[location] || 0)) {
				$container.css({'height': '', 'width': '', 'top': '', 'left': ''});
				localStorage.rwdDebugBarTop = localStorage.rwdDebugBarLeft = '';
			}
		}

		function setConsolePosition(state) {
			var options = {
				key: 'rwdDebugBar' + localStorage.rwdDebugBarPosition,
				pos: localStorage.rwdDebugBarPosition.toLowerCase(),
				side: localStorage.rwdDebugBarPosition === 'Top' ? 'bottom' : 'right',
			}, container_styles = {
				'height': '',
				'width': '',
				'left': '',
				'top': ''
			}, html_styles = {
				'margin-bottom': '',
				'margin-right': ''
			};

			localStorage[options.key] = $container[0].style[options.pos] !== '' ? $container[0].style[options.pos] : localStorage[options.key];
			state = typeof state == 'undefined' || state == 'toggle' ? localStorage.rwdDebugBarState : state;

			if (state !== 'close') {
				container_styles[options.pos] = state === 'restored' ? (localStorage[options.key] ? localStorage[options.key] : '60%') : '';
				$container.css(container_styles);
			}

			html_styles['margin-' + options.side] = (state == 'minimized' || state == 'maximized' || isConsoleHidden()) ? '' : (localStorage.rwdDebugBarPosition === 'Top' ? $container.height() : $container.width());
			$html.css(html_styles);

			visibleWindow();
		}

		function setConsoleState(state) {
			$container.removeClass(allStates).addClass(state);
			localStorage.rwdDebugBarState = state;
			setConsolePosition(state);
			return false;
		}

		function minimizeConsole() {
			setConsoleState('minimized');
			return $('#rwd-debug-bar-restore > button').focus();
		}

		function maximizeConsole() {
			setConsoleState('maximized');
			return $('#rwd-debug-bar-restore > button').focus();
		}

		function restoreConsole() {
			setConsoleState('restored');
			return $('#rwd-debug-bar-maximize > button').focus();
		}

		function goToPanel(id) {
			var $panel = $('.rwd-debug-bar-content [data-panel=' + id + ']'),
				firstTime = $.inArray(id, panelStack) == -1;

			localStorage.rwdDebugBarPanel = id;
			$('.rwd-debug-bar-content > section').hide();
			$('#rwd-debug-bar-side-menu').find('.wp-has-current-submenu').removeClass('wp-has-current-submenu').addClass('wp-not-current-submenu');
			$('#rwd-debug-menu-link-' + id + ', #rwd-debug-menu-link-' + id + ' > a').removeClass('wp-not-current-submenu').addClass('wp-has-current-submenu');
			$container.show();
			$panel.show();
			if (id !== panelStack[panelStack.length - 1] && typeof $ === 'function' && 'publish' in $) {
				$.publish('rdb/activate-panel', id, $panel, firstTime);
				$.publish('rdb/activate-panel/' + id, $panel, firstTime);
				panelStack.push(id);
			}
			return false;
		}

		if ($container.length) {
			defineLocalStorage();
			initTheDebugBarWindowAndPanels();
			initTheDebugBarTabs();
			attachEventHandlers();
		}

	});
	$(function () {
		// Formatters
		$(document).on('click', '[class*="ide-link"]', function (e) {
			e.preventDefault();
			$.ajax($(this).attr('href'), {data: {kint_ignore: 1}});
			return false;
		});
	});
	// Yes! Conflict
	if (typeof window.$ === "undefined" && typeof jQuery !== "undefined") {
		window.$ = jQuery;
	}
}(jQuery, window, document));