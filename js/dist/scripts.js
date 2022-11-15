"use strict";

(function () {
  var timers = {};
  window.debouncer = function (id, callback, ms) {
    ms = typeof ms !== 'undefined' ? ms : 500;
    if (timers[id]) {
      clearTimeout(timers[id]);
    }
    timers[id] = setTimeout(callback, ms);
  };
})();
window.getFromObjPath = function (obj, path) {
  if (typeof path !== 'string' && !(path instanceof String)) {
    return obj[path];
  }
  return path.split('.').reduce((o, i) => o[i], obj);
};
window.arrayColumn = function (array) {
  let columnKey = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;
  let indexKey = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : null;
  if (indexKey !== null) {
    let obj = {};
    for (var index in array) {
      if (array.hasOwnProperty(index) || typeof array[index] !== 'function') {
        obj[getFromObjPath(array[index], indexKey)] = columnKey !== null ? typeof columnKey === 'function' ? columnKey(array[index]) : getFromObjPath(array[index], columnKey) : array[index];
      }
    }
    return obj;
  }
  array = Array.isArray(array) ? array : Object.values(array);
  return array.map(function (value, index) {
    return typeof columnKey === 'function' ? columnKey(value) : getFromObjPath(value, columnKey);
  });
};
"use strict";

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
        rwdDebugBarTabs: '{}'
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
      });
    }
    function goToTab(tabId) {
      let groupId = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;
      var $content = $(`#${tabId}`);
      var $contentGroup = $content.closest('.debug-bar-tabs-content');
      var $tabs = $contentGroup.prev();
      $contentGroup.children().hide();
      $content.show();
      $tabs.find('.active').removeClass('active');
      $tabs.find(`[data-tab-id="${tabId}"]`).parent().addClass('active');
      groupId ?? (groupId = $contentGroup.data('group-id'));
      saveOpenedTab(groupId, tabId);
    }
    function saveOpenedTab(groupId, tabId) {
      var _localStorage;
      var tabs = JSON.parse((_localStorage = localStorage).rwdDebugBarTabs ?? (_localStorage.rwdDebugBarTabs = '{}'));
      tabs[groupId] = tabId;
      localStorage.rwdDebugBarTabs = JSON.stringify(tabs);
    }
    function openSavedTabs() {
      var _localStorage2;
      var $tabs = $container.find('.debug-bar-tabs-content');
      $tabs.children().hide();
      $tabs.children(':first-child').show();
      $container.find('.debug-bar-tabs').children(':first-child').addClass('active');
      var tabs = JSON.parse((_localStorage2 = localStorage).rwdDebugBarTabs ?? (_localStorage2.rwdDebugBarTabs = '{}'));
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
          var location,
            windowSize,
            container_styles = {
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
      $(document).on('hover', '.wp-has-submenu.wp-not-current-submenu', function (e) {
        $(this).closest('li').addClass('opensub');
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
            var active = response == '1';
            $this.attr('data-activate', active ? 0 : 1);
            $this.find('i.fa').toggleClass('fa-toggle-off', active ? 0 : 1);
            $this.find('i.fa').toggleClass('fa-toggle-on', active ? 1 : 0);
          }
        });
      });

      // Resize the Console after window resize
      $window.resize(function () {
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
      return returnType === 'string' ? isHidden ? '1' : '0' : isHidden;
    }
    function resetConsolePosition() {
      var location = localStorage.rwdDebugBarPosition.toLowerCase(),
        windowSize = location === 'top' ? $window.innerHeight() : $window.innerWidth();
      if (windowSize - 32 < ($container.position()[location] || 0)) {
        $container.css({
          'height': '',
          'width': '',
          'top': '',
          'left': ''
        });
        localStorage.rwdDebugBarTop = localStorage.rwdDebugBarLeft = '';
      }
    }
    function setConsolePosition(state) {
      var options = {
          key: 'rwdDebugBar' + localStorage.rwdDebugBarPosition,
          pos: localStorage.rwdDebugBarPosition.toLowerCase(),
          side: localStorage.rwdDebugBarPosition === 'Top' ? 'bottom' : 'right'
        },
        container_styles = {
          'height': '',
          'width': '',
          'left': '',
          'top': ''
        },
        html_styles = {
          'margin-bottom': '',
          'margin-right': ''
        };
      localStorage[options.key] = $container[0].style[options.pos] !== '' ? $container[0].style[options.pos] : localStorage[options.key];
      state = typeof state == 'undefined' || state == 'toggle' ? localStorage.rwdDebugBarState : state;
      if (state !== 'close') {
        container_styles[options.pos] = state === 'restored' ? localStorage[options.key] ? localStorage[options.key] : '60%' : '';
        $container.css(container_styles);
      }
      html_styles['margin-' + options.side] = state == 'minimized' || state == 'maximized' || isConsoleHidden() ? '' : localStorage.rwdDebugBarPosition === 'Top' ? $container.height() : $container.width();
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
      $('.wp-has-current-submenu').removeClass('wp-has-current-submenu').addClass('wp-not-current-submenu');
      $('#rwd-debug-menu-link-' + id + ', #rwd-debug-menu-link-' + id + ' > a').removeClass('wp-not-current-submenu').addClass('wp-has-current-submenu');
      $container.show();
      $panel.show();
      if (id !== panelStack[panelStack.length - 1]) {
        do_action('rdb/activate-panel', id, $panel, firstTime);
        do_action('rdb/activate-panel/' + id, $panel, firstTime);
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
      $.ajax($(this).attr('href'), {
        data: {
          kint_ignore: 1
        }
      });
      return false;
    });
  });
  // Yes! Conflict
  if (typeof window.$ === "undefined" && typeof jQuery !== "undefined") {
    window.$ = jQuery;
  }
})(jQuery, window, document);
"use strict";

(function () {
  var actions = {};
  function get_action(tag) {
    if (typeof tag === 'undefined') {
      throw new Error('Invalid Signature!');
    }
    return actions[tag] || (actions[tag] = $.Callbacks());
  }
  window.do_action = function (tag) {
    var action,
      args = Array.prototype.slice.call(arguments);
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
})();
"use strict";

(function ($, window, document, undefined) {
  $(function () {
    /*
    	jQuery deparam is an extraction of the deparam method from Ben Alman's jQuery BBQ
    	http://benalman.com/projects/jquery-bbq-plugin/
    */
    $.deparam = function (params, coerce) {
      var obj = {},
        coerce_types = {
          'true': !0,
          'false': !1,
          'null': null
        };
      if (typeof params !== 'string') {
        return {};
      }
      $.each(params.replace(/\+/g, ' ').split('&'), function (j, v) {
        var param = v.split('='),
          key = decodeURIComponent(param[0]),
          val,
          cur = obj,
          i = 0,
          keys = key.split(']['),
          keys_last = keys.length - 1;
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
      $('.kint-rich.kint-folder > dl > dt.kint-parent').append('<a class="action-scroll" data-action="end"><i class="fa fa-arrow-circle-down" aria-hidden="true"></i></a>' + '<a class="action-auto-scroll" data-action="hide"><i class="fa fa fa-arrow-down" aria-hidden="true"></i></a>' + '<a class="action-auto-scroll" data-action="show" style="display: none;"><i class="fa fa fa-pause" aria-hidden="true"></i></a>' + '<a class="action-scope" data-scope="private" data-action="hide">' + actionShow + '</a>' + '<a class="action-scope" data-scope="private" data-action="show" style="display: none;">' + actionHide + '</a>' + '<a class="action-scope" data-scope="protected" data-action="hide">' + actionShow + '</a>' + '<a class="action-scope" data-scope="protected" data-action="show" style="display: none;">' + actionHide + '</a>' + '<a class="action-scope" data-scope="public" data-action="hide">' + actionShow + '</a>' + '<a class="action-scope" data-scope="public" data-action="show" style="display: none;">' + actionHide + '</a>' + '<a class="action-visibility" data-action="hide">' + actionShowAlt + '</a>' + '<a class="action-visibility" data-action="show">' + actionHideAlt + '</a>' + '<a class="action-visibility" data-action="delete">' + actionClear + '</a>' + '<a class="action-accordion" data-action="hide">' + actionCollapse + '</a>' + '<a class="action-accordion" data-action="show">' + actionExpand + '</a>').addClass('kint-nav-bar').next().addClass('kint-file-parent');
      $(document).on('click', '.action-scope', function (e) {
        var $this = $(this),
          scope = $this.data('scope'),
          action = $this.data('action'),
          show = action === 'show';
        $('a[data-scope="' + scope + '"][data-action="show"]').toggle(!show);
        $('a[data-scope="' + scope + '"][data-action="hide"]').toggle(show);
        $('var.kint-scope.kint-' + scope).parent().parent().toggle(show);
        localStorage['rwdKintDebuggerHide' + scope.charAt(0).toUpperCase() + scope.slice(1)] = !show ? '1' : '0';
        return false;
      });
      $(document).on('click', '.action-accordion', function () {
        var $this = $(this),
          action = $this.data('action'),
          $parent = $this.parent().parent().find('.kint-parent');
        action === 'show' ? $parent.addClass('kint-show') : $parent.removeClass('kint-show');
        return false;
      });
      $(document).on('click', '.action-auto-scroll', function () {
        var $this = $(this),
          action = $this.data('action'),
          show = action === 'show';
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
        var $this = $(this),
          action = $this.data('action'),
          $folder = $this.parent().next(),
          $files = $folder.children();
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
        var $this = $(this),
          action = $this.data('action'),
          $file = $this.closest('.kint-file'),
          $section = $this.closest('dl'),
          $parentSection = $section.parent();
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
        var $this = $(this),
          text = $this.text();
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
        var $this = $(this),
          text = $this.text();
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
      $('.kint-file.kint-header:not(".kint-dark") dt').append('<a class="action-visibility" data-action="previous">' + actionUp + '</a>');
      $('.kint-file:not(".kint-dark") dt').append('<a class="action-visibility" data-action="hide">' + actionShowAlt + '</a>' + '<a class="action-visibility" data-action="show">' + actionHideAlt + '</a>' + '<a class="action-visibility" data-action="delete">' + actionClear + '</a>');
      $('.kint-file:not(".kint-dark") .kint-parent').append('<a class="action-accordion" data-action="hide">' + actionCollapse + '</a>' + '<a class="action-accordion" data-action="show">' + actionExpand + '</a>');
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
      setTimeout(() => $kintParent.closest('[data-panel]').animate({
        scrollTop: $kintParent.height()
      }, 1000), 1);
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
        var counter = 0,
          kint,
          prefix = 'kint-file kint-message ',
          contentLength = (xhr.responseText || '').toString().length,
          $kintParent = $('.kint-file-parent'),
          defaultEmpty = '(empty)',
          requestData = json_beautify(request.data) || defaultEmpty,
          responseData = json_beautify(xhr.responseText) || defaultEmpty,
          details = '<pre><u>Request</u>:\n\n' + requestData + '\n\n<u>Response</u>:\n\n' + responseData + '</pre>',
          data = $.deparam(request.data, true),
          action = data.action ? ' (' + data.action + ')' : '',
          time = new Date().toLocaleTimeString(),
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
          while (kint = xhr.getResponseHeader('RWD-Debug-Bar-Kint-' + counter++)) {
            if (!kint || kint == '') {
              break;
            }
            $kintParent.append(decodeURIComponent(kint));
          }
        }
        postRender();
        autoScrollToBottom && ('debouncer' in window ? debouncer('scrollToBottom', scrollToBottom) : scrollToBottom());
      });
    }
    if (typeof add_action === 'function') {
      add_action('rdb/activate-panel/RWD_Debug_Bar_Kint_Panel', function ($panel, firstTime) {
        if (!firstTime && $('.kint-rich.kint-folder').hasClass('kint-rerendered')) {
          $panel.animate({
            scrollTop: $panel.prop("scrollHeight")
          }, 1000);
        }
        $('.kint-rich.kint-folder').removeClass('kint-rerendered');
      });
    }
  });
})(jQuery, window, document);
"use strict";

var _window$Tabulator;
(_window$Tabulator = window.Tabulator).filters ?? (_window$Tabulator.filters = {});
window.Tabulator.filters.minMax = function (array, config) {
  let filterMin = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : true;
  let filterMax = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : true;
  var values = arrayColumn(array, config.field);
  var min = Math.min(...values);
  var max = Math.max(...values);
  var empty = window.Tabulator.empty;
  config.sorter ?? (config.sorter = function (a, b, aRow, bRow, column, dir, sorterParams) {
    return empty.includes(a) ? dir === 'asc' ? 1 : -1 : empty.includes(b) ? dir === 'asc' ? -1 : 1 : a - b;
  });
  config.headerFilter ?? (config.headerFilter = function (cell, onRendered, success, cancel, editorParams) {
    var start = document.createElement('input'),
      end = document.createElement('input'),
      container = document.createElement('span');
    function createInput(input, title) {
      input.setAttribute('type', 'number');
      input.setAttribute('placeholder', title);
      input.setAttribute('min', Math.floor(min) || 0);
      input.setAttribute('max', Math.ceil(max) || 100);
      input.style.padding = '4px';
      input.style.width = filterMin && filterMax ? '50%' : '100%';
      input.style.boxSizing = 'border-box';
      input.value = cell.getValue();
      input.addEventListener('change', buildValues);
      input.addEventListener('blur', buildValues);
      input.addEventListener('keydown', keypress);
      container.appendChild(input);
    }
    function buildValues() {
      success({
        start: start.value,
        end: end.value
      });
    }
    function keypress(e) {
      e.keyCode === 13 && buildValues();
      e.keyCode === 27 && cancel();
    }
    filterMin && createInput(start, 'Min');
    filterMax && createInput(end, 'Max');
    return container;
  });
  config.headerFilterFunc ?? (config.headerFilterFunc = function (headerValue, rowValue, rowData, filterParams) {
    if ((headerValue.start !== "" || headerValue.end !== "") && rowValue === null) {
      return false;
    }
    if (rowValue || rowValue === 0) {
      if (headerValue.start !== "") {
        if (headerValue.end !== "") {
          return rowValue >= headerValue.start && rowValue <= headerValue.end;
        } else {
          return rowValue >= headerValue.start;
        }
      } else {
        if (headerValue.end !== "") {
          return rowValue <= headerValue.end;
        }
      }
    }
    return true;
  });
  config.headerFilterLiveFilter ?? (config.headerFilterLiveFilter = false);
  return config;
};
window.Tabulator.filters.advanced = function (headerValue, rowValue, rowData, filterParams) {
  if (!headerValue.includes(' ') && !headerValue.includes(':') && !headerValue.includes('-') && !headerValue.includes('+')) {
    return rowValue.includes(headerValue);
  }
  var keywords = headerValue.match(/(?:[^\s"]+|"[^"]*")+/g);
  for (var keyword of keywords) {
    if (!Tabulator.search(keyword, rowValue)) {
      return false;
    }
  }
  return true;
};
window.Tabulator.filters.advancedFile = function (headerValue, rowValue, rowData, filterParams) {
  if (Array.isArray(rowValue)) {
    rowValue = rowValue.map(x => x.text).join(' ');
  }
  if ('strict' in filterParams && !filterParams.strict) {
    headerValue = headerValue.toLowerCase();
    rowValue = rowValue.toLowerCase();
  }
  return Tabulator.filters.advanced(headerValue, rowValue, rowData, filterParams);
};
window.Tabulator.filters.args = function (headerValue, rowValueObj, rowData, filterParams) {
  var rowValue = rowValueObj.text;
  rowValue === true && (rowValue = '1');
  rowValue === false && (rowValue = '0');
  rowValue === null && (rowValue = '');
  return Tabulator.filters.advanced(headerValue, rowValue.toString(), rowData, filterParams);
};
window.Tabulator.filters.boolean = function (config) {
  var base = {
    sorter: 'boolean',
    hozAlign: 'center',
    formatter: 'tickCross',
    formatterParams: {
      allowEmpty: true,
      allowTruthy: true
    },
    headerFilter: 'tickCross',
    headerFilterParams: {
      'tristate': true
    },
    width: 61
  };
  if ('src' in config) {
    config.title = `<img alt="${config.title}" title="${config.title}" src="${config.src}" style="max-width: 100%;" />`;
    delete config.src;
  }
  return {
    ...base,
    ...config
  };
};
"use strict";

var _window$Tabulator;
(_window$Tabulator = window.Tabulator).formatters ?? (_window$Tabulator.formatters = {});
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
  return cell.getValue().toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }) + ' ms';
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
"use strict";

var _window$Tabulator;
(_window$Tabulator = window.Tabulator).sorter ?? (_window$Tabulator.sorter = {});
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
  var a = o1.text,
    b = o2.text;
  if (!isNaN(a) && !isNaN(b)) {
    return a - b;
  }
  a === true && (a = '1');
  a === false && (a = '0');
  a === null && (a = '');
  b === true && (b = '1');
  b === false && (b = '0');
  b === null && (b = '');
  return a.toString().localeCompare(b.toString());
};
"use strict";

var _window, _window$Tabulator;
(_window = window).Tabulator ?? (_window.Tabulator = {});
window.Tabulator.empty = [null, ''];
window.Tabulator.search = function search(keyword, content) {
  if (keyword.startsWith('regex:i:')) {
    if (!new RegExp(keyword.slice(8), 'i').test(content)) {
      return false;
    }
  } else if (keyword.startsWith('i:')) {
    if (!content.toLowerCase().includes(keyword.slice(2).toLowerCase())) {
      return false;
    }
  } else if (keyword.startsWith('regex:')) {
    if (!new RegExp(keyword.slice(6)).test(content)) {
      return false;
    }
  } else if (keyword.startsWith('not:')) {
    if (content.includes(keyword.slice(4))) {
      return false;
    }
  } else if (keyword.startsWith('-')) {
    if (content.includes(keyword.slice(1))) {
      return false;
    }
  } else if (keyword.startsWith('+')) {
    if (!content.includes(keyword.slice(1))) {
      return false;
    }
  } else {
    if (!content.includes(keyword)) {
      return false;
    }
  }
  return true;
};
(_window$Tabulator = window.Tabulator).common ?? (_window$Tabulator.common = {});
window.Tabulator.common.arrayByLength = {
  headerSortStartingDir: 'desc',
  sorter: function (a, b, aRow, bRow, column, dir, sorterParams) {
    return a.length - b.length;
  },
  headerFilterFunc: function (headerValue, rowValue, rowData, filterParams) {
    rowValue = rowValue.length;
    if ((headerValue.start !== "" || headerValue.end !== "") && rowValue === null) {
      return false;
    }
    if (rowValue || rowValue === 0) {
      if (headerValue.start !== "") {
        if (headerValue.end !== "") {
          return rowValue >= headerValue.start && rowValue <= headerValue.end;
        } else {
          return rowValue >= headerValue.start;
        }
      } else {
        if (headerValue.end !== "") {
          return rowValue <= headerValue.end;
        }
      }
    }
    return true;
  },
  formatter: function (cell, formatterParams, onRendered) {
    return cell.getValue().length;
  },
  clickPopup: function (e, component, onRendered) {
    if (!component.getValue().length) {
      return '';
    }
    return Tabulator.formatters.file(component, {
      join: "<br>"
    }, onRendered);
  }
};
window.Tabulator.common.filesArray = {
  headerFilter: 'input',
  headerFilterFuncParams: {
    strict: false
  },
  headerFilterFunc: Tabulator.filters.advancedFile,
  sorter: Tabulator.sorter.file,
  formatterParams: {
    join: " | "
  },
  formatter: Tabulator.formatters.file
};
window.Tabulator.common.valuesArray = {
  headerFilter: 'list',
  headerFilterParams: {
    clearable: true,
    valuesLookup: function (component, filterTerm) {
      var values = new Set();
      $.each(component.getColumn().getCells(), function (i, cell) {
        $.each(cell.getValue(), function (i, value) {
          values.add(value);
        });
      });
      return values.size ? Array.from(values).sort() : [];
    }
  },
  headerFilterFunc: function (search, value) {
    return value.includes(search);
  }
};