/* ==========================================================================
   Forest OS — mobile shell (touch-first)
   --------------------------------------------------------------------------
   Только узкий экран (<= 760px), всё под body.fo-mobile — десктоп не затрагивается.
   * приложение = полноэкранное окно без заголовка;
   * нижняя панель: рабочий стол / запущенные / док;
   * док выезжает по кнопке и прячется, пока приложение открыто;
   * список запущенных приложений с закрытием одного и всех;
   * свайп от края экрана переключает приложения (обычная прокрутка не мешает);
   * долгое нажатие = контекстное меню (как правый клик).
   ========================================================================== */
(function () {
  'use strict';

  var mq = window.matchMedia('(max-width: 760px)');
  var pressTimer = null, startX = 0, startY = 0, startT = 0, moved = false, pressTarget = null;
  var swipe = null, edgeSwipe = false;
  var swallowClickUntil = 0;   /* после свайпа/долгого нажатия клик не должен срабатывать */

  var EDGE_ZONE = 28;      /* px от края экрана, где начинается свайп */
  var SWIPE_MIN = 76;      /* минимальная длина свайпа */
  var SWIPE_MAX_MS = 650;  /* слишком медленный жест — это прокрутка */

  function narrow() { return mq.matches; }

  function touchesOf(e) {
    var oe = e.originalEvent || e;
    return (oe && oe.touches) ? oe.touches : [];
  }
  function changedOf(e) {
    var oe = e.originalEvent || e;
    return (oe && oe.changedTouches) ? oe.changedTouches : [];
  }

  /* ---------- windows ---------------------------------------------------- */
  function wins() { return $('.window[data-app]'); }
  function activeWin() { return $('.window.windowactive').first(); }
  function idOf($w) {
    var el = ($w && $w.nodeType) ? $w : ($w && $w[0] ? $w[0] : null);
    return el ? String(el.id || '').replace('app', '') : '';
  }

  function sync() {
    document.body.classList.toggle('fo-mobile', narrow());
    if (narrow()) { ensureBar(); }
    else { $('#fo-mobile-bar').remove(); $('#fo-mb-switcher').remove(); }
    updateCount();
  }

  function updateCount() {
    var n = wins().length;
    $('#fo-mb-count').text(n).toggle(n > 0);
    var hasActive = activeWin().length > 0;
    if (n > 0 && hasActive) { document.body.classList.add('fo-mb-app'); }
    else if (!hasActive) { document.body.classList.remove('fo-mb-app'); }
  }

  function appColor(app) {
    var h = 0;
    for (var i = 0; i < app.length; i++) { h = (h * 31 + app.charCodeAt(i)) | 0; }
    return 'hsl(' + (Math.abs(h) % 360) + ' 42% 38%)';
  }

  function focusWindow(id) {
    var $w = $('#app' + id);
    if (!$w.length) { return; }
    $('.window').removeClass('windowactive');
    $w.addClass('windowactive');
    if (typeof window.foFocusWindow === 'function') { window.foFocusWindow(id); }
    if (window.FO_SET_BAR_TITLE) { window.FO_SET_BAR_TITLE($w.find('.process-title-text').text()); }
  }

  function closeWindow(id) {
    var $w = $('#app' + id);
    var $btn = $w.find('.appwindowbutton').filter(function () {
      return /close/i.test(this.className) || /закры/i.test(this.title || '');
    }).first();
    if ($btn.length) { $btn.trigger('click'); return true; }
    $w.closest('.process').remove();
    return false;
  }

  function closeAll() {
    var ids = [];
    wins().each(function () { ids.push(idOf($(this))); });
    ids.forEach(function (id) { closeWindow(id); });
    $('.window').removeClass('windowactive');
    document.body.classList.remove('fo-mb-app', 'fo-mb-dock');
    closeSwitcher();
    updateCount();
  }

  /* ---------- bottom bar ------------------------------------------------ */
  var ICONS = {
    home: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 11.2 12 4.4l8 6.8V19a1.4 1.4 0 0 1-1.4 1.4H14v-6h-4v6H5.4A1.4 1.4 0 0 1 4 19z"/></svg>',
    apps: '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3.6" y="3.6" width="7.2" height="7.2" rx="2.1"/><rect x="13.2" y="3.6" width="7.2" height="7.2" rx="2.1"/><rect x="3.6" y="13.2" width="7.2" height="7.2" rx="2.1"/><rect x="13.2" y="13.2" width="7.2" height="7.2" rx="2.1"/></svg>',
    dock: '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2.8" y="8.6" width="18.4" height="7.4" rx="3.7"/><circle class="dot" cx="7.6" cy="12.3" r="1.25"/><circle class="dot" cx="12" cy="12.3" r="1.25"/><circle class="dot" cx="16.4" cy="12.3" r="1.25"/></svg>'
  };

  function ensureBar() {
    if ($('#fo-mobile-bar').length) { return; }
    $(
      '<div id="fo-mobile-bar">' +
        '<button type="button" data-mb="home" aria-label="Рабочий стол">' + ICONS.home + '</button>' +
        '<button type="button" data-mb="apps" id="fo-mb-apps" aria-label="Запущенные приложения">' + ICONS.apps +
          '<i id="fo-mb-count">0</i></button>' +
        '<button type="button" data-mb="dock" aria-label="Док">' + ICONS.dock + '</button>' +
      '</div>'
    ).appendTo('body');
  }

  function home() {
    $('.window').removeClass('windowactive');
    document.body.classList.remove('fo-mb-app', 'fo-mb-dock');
    closeSwitcher();
    updateCount();
  }

  function toggleDock() {
    document.body.classList.toggle('fo-mb-dock');
    if (document.body.classList.contains('fo-mb-dock')) { closeSwitcher(); }
  }

  /* ---------- app switcher --------------------------------------------- */
  function closeSwitcher() {
    $('#fo-mb-switcher').removeClass('is-on');
    $('body').removeClass('fo-mb-switcher-open');
  }

  function openSwitcher() {
    var $s = $('#fo-mb-switcher');
    if (!$s.length) {
      $s = $('<div id="fo-mb-switcher">' +
        '<div class="fo-mb-sheet-head">' +
          '<b>Запущенные</b>' +
          '<div class="fo-mb-head-actions">' +
            '<button type="button" class="fo-mb-closeall" data-mbs="closeall">Закрыть все</button>' +
            '<button type="button" class="fo-mb-close" data-mbs="close">Готово</button>' +
          '</div>' +
        '</div>' +
        '<div class="fo-mb-hint">Свайп от края экрана переключает приложения, × закрывает</div>' +
        '<div class="fo-mb-cards"></div></div>').appendTo('body');
    }
    var $cards = $s.find('.fo-mb-cards').empty();
    var list = wins();

    if (!list.length) {
      $cards.append('<div class="fo-mb-empty">Нет запущенных приложений</div>');
      $s.find('[data-mbs="closeall"]').addClass('is-off');
    } else {
      $s.find('[data-mbs="closeall"]').removeClass('is-off');
      list.each(function () {
        var $w = $(this);
        var app = $w.attr('data-app') || 'App';
        var id = idOf($w);
        var title = $w.find('.process-title-text').first().text().trim() || app;
        var $card = $(
          '<div class="fo-mb-card" data-mb-card="' + id + '">' +
            '<div class="fo-mb-ico" style="background:linear-gradient(160deg,' + appColor(app) + ',rgba(255,255,255,.05))">' +
              '<span>' + (app.charAt(0) || '?') + '</span>' +
            '</div>' +
            '<div class="fo-mb-name">' + $('<i/>').text(title).html() + '</div>' +
            '<button type="button" class="fo-mb-kill" data-mb-kill="' + id + '" aria-label="Закрыть">✕</button>' +
          '</div>'
        );
        if ($w.hasClass('windowactive')) { $card.addClass('is-active'); }
        $cards.append($card);
      });
    }

    $s.addClass('is-on');
    $('body').addClass('fo-mb-switcher-open');
    document.body.classList.remove('fo-mb-dock');
  }

  /* ---------- swipe: только от края экрана ------------------------------ */
  function nextWindow(dir) {
    var list = wins();
    if (list.length < 2) { return; }
    var $act = activeWin();
    var idx = list.index($act);
    if (idx < 0) { idx = 0; }
    var next = (idx + dir + list.length) % list.length;
    focusWindow(idOf(list[next]));
    document.body.classList.add('fo-mb-app');
  }

  function isTextSurface(el) {
    return $(el).closest('input, textarea, [contenteditable="true"], .fo-editor, [class*="editor"]').length > 0;
  }

  $(document).on('touchstart', function (e) {
    if (!narrow()) { return; }
    var t = touchesOf(e)[0];
    if (!t) { return; }
    startX = t.clientX; startY = t.clientY; startT = Date.now();
    moved = false;
    swipe = null;
    pressTarget = e.target;

    var w = window.innerWidth;
    edgeSwipe = !isTextSurface(e.target) &&
      !$(e.target).closest('#fo-mobile-bar, .dock-container, #fo-mb-switcher, .fo-node-anchor').length &&
      (t.clientX <= EDGE_ZONE || t.clientX >= w - EDGE_ZONE) &&
      wins().length > 1;

    /* long press = context menu */
    if (isTextSurface(e.target) || $(e.target).closest('#fo-node-layer').length) { return; }
    clearTimeout(pressTimer);
    pressTimer = setTimeout(function () {
      if (moved) { return; }
      var ev = new MouseEvent('contextmenu', {
        bubbles: true, cancelable: true, view: window,
        clientX: startX, clientY: startY, button: 2
      });
      swallowClickUntil = Date.now() + 500;
      (pressTarget || document.body).dispatchEvent(ev);
      if (navigator.vibrate) { try { navigator.vibrate(12); } catch (err) {} }
    }, 460);
  });

  $(document).on('touchmove', function (e) {
    if (!narrow()) { return; }
    var t = touchesOf(e)[0];
    if (!t) { return; }
    var dx = t.clientX - startX;
    var dy = t.clientY - startY;
    if (Math.abs(dx) > 10 || Math.abs(dy) > 10) {
      moved = true;
      clearTimeout(pressTimer);
      pressTimer = null;
    }
    if (!edgeSwipe) { return; }
    if (Math.abs(dx) > 18 && Math.abs(dx) > Math.abs(dy) * 1.6) {
      swipe = dx;
      $('.window.windowactive').css('transform', 'translateX(' + Math.round(dx * 0.35) + 'px)');
    }
  });

  $(document).on('touchend', function (e) {
    clearTimeout(pressTimer);
    pressTimer = null;
    if (!narrow()) { return; }
    if (swipe !== null) {
      var dx = swipe;
      var fast = (Date.now() - startT) < SWIPE_MAX_MS;
      swipe = null;
      edgeSwipe = false;
      $('.window').css('transform', '');
      swallowClickUntil = Date.now() + 450;
      if (Math.abs(dx) > SWIPE_MIN && fast) { nextWindow(dx < 0 ? 1 : -1); }
      return;
    }
    edgeSwipe = false;
    var t = changedOf(e)[0] || null;
    if (!t) { return; }
    var dy = t.clientY - startY;
    var dx2 = t.clientX - startX;
    if (dy > 90 && Math.abs(dy) > Math.abs(dx2) * 1.6 && $(e.target).closest('#topbar').length) {
      var $w = activeWin();
      if ($w.length) { closeWindow(idOf($w)); updateCount(); }
    }
  });

  /* клик сразу после свайпа или долгого нажатия не должен «нажимать» элементы */
  document.addEventListener('click', function (e) {
    if (!narrow() || Date.now() >= swallowClickUntil) { return; }
    e.stopPropagation();
    e.preventDefault();
  }, true);

  /* ---------- taps ------------------------------------------------------- */
  $(document).on('click', '[data-mb]', function (e) {
    e.preventDefault();
    var act = $(this).attr('data-mb');
    if (act === 'home') { home(); }
    else if (act === 'apps') {
      /* повторное нажатие закрывает сетку */
      if ($('#fo-mb-switcher').hasClass('is-on')) { closeSwitcher(); }
      else { openSwitcher(); }
    }
    else if (act === 'dock') { toggleDock(); }
  });

  $(document).on('click', '[data-mbs="close"]', function () { closeSwitcher(); });
  $(document).on('click', '[data-mbs="closeall"]', function (e) { e.stopPropagation(); closeAll(); });

  $(document).on('click', '.fo-mb-card', function (e) {
    if ($(e.target).closest('[data-mb-kill]').length) { return; }
    var id = $(this).attr('data-mb-card');
    closeSwitcher();
    document.body.classList.add('fo-mb-app');
    focusWindow(id);
  });

  $(document).on('click', '[data-mb-kill]', function (e) {
    e.stopPropagation();
    closeWindow($(this).attr('data-mb-kill'));
    updateCount();
    openSwitcher();
  });

  $(document).on('click', '#fo-mb-close', function (e) {
    e.preventDefault();
    var $w = activeWin();
    if (!$w.length) { return; }
    closeWindow(idOf($w));
    updateCount();
    $('.window').removeClass('windowactive');
    var $next = $('.window').last();
    if ($next.length) { focusWindow(idOf($next)); }
    else { document.body.classList.remove('fo-mb-app'); }
  });

  $(document).on('click', '.window', function () {
    if (narrow()) { document.body.classList.add('fo-mb-app'); }
  });

  $(document).on('click', '.desktop .link', function () {
    if (!narrow()) { return; }
    setTimeout(function () { document.body.classList.add('fo-mb-app'); updateCount(); }, 300);
  });

  $(document).on('click', '.dock-icon', function () {
    if (!narrow()) { return; }
    document.body.classList.add('fo-mb-app');
    document.body.classList.remove('fo-mb-dock');
  });

  $(document).on('click', '#fo-mb-switcher', function (e) {
    if (e.target === this) { closeSwitcher(); }
  });

  /* тап вне дока закрывает выехавшую панель */
  $(document).on('click', function (e) {
    if (!narrow() || !document.body.classList.contains('fo-mb-dock')) { return; }
    if ($(e.target).closest('.dock-container, #fo-mobile-bar').length) { return; }
    document.body.classList.remove('fo-mb-dock');
  });

  $(document).on('keydown', function (e) {
    if (e.key === 'Escape') { closeSwitcher(); document.body.classList.remove('fo-mb-dock'); }
  });

  setInterval(function () { if (narrow()) { updateCount(); } }, 1500);

  if (mq.addEventListener) { mq.addEventListener('change', sync); }
  else if (mq.addListener) { mq.addListener(sync); }
  $(function () { sync(); });
  document.addEventListener('DOMContentLoaded', sync);

  window.FOMobile = {
    is: narrow,
    home: home,
    switcher: openSwitcher,
    dock: toggleDock,
    closeAll: closeAll,
    count: function () { return wins().length; }
  };
})();
