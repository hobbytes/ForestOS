/* ==========================================================================
   Forest OS — free desktop layout, dock pinning, safety clamps
   ========================================================================== */
(function () {
  'use strict';

  var HINT_DELAY = 650;
  var hintTimer = null;

  function dockRect() {
    var d = document.querySelector('.dock-container');
    return d ? d.getBoundingClientRect() : null;
  }

  function overlaps(a, b) {
    if (!a || !b) return false;
    return !(a.right < b.left || a.left > b.right || a.bottom < b.top || a.top > b.bottom);
  }

  function makeAbsolute(el) {
    var $el = $(el);
    if ($el.hasClass('fo-placed')) return;
    var off = $el.offset();
    if (!off) return;
    $el.css({
      position: 'absolute',
      left: Math.round(off.left),
      top: Math.round(off.top),
      margin: 0
    }).addClass('fo-placed');
  }

  /* Keep a shortcut on screen and always above the dock. */
  function clampToDesktop($l) {
    var o = gridOrigin();
    var p = $l.position();
    var $p = $l.offsetParent();
    var pw = $p.length ? $p.width() : window.innerWidth;
    var ph = $p.length ? $p.height() : window.innerHeight;
    var iw = $l.outerWidth() || 96;
    var ih = $l.outerHeight() || 96;
    var maxX = Math.max(o.left, pw - iw - 8);
    var d = dockRect();
    var maxY = (d && d.top > 60)
      ? Math.round(d.top - o.contTop - ih - 10)
      : Math.round(ph - ih - 8);
    maxY = Math.max(o.top, maxY);
    var x = Math.min(Math.max(o.left, Math.round(p.left)), maxX);
    var y = Math.min(Math.max(o.top, Math.round(p.top)), maxY);
    if (x !== Math.round(p.left) || y !== Math.round(p.top)) {
      $l.css({ left: x + 'px', top: y + 'px' });
      return true;
    }
    return false;
  }

  function saveLayout() {
    var layout = {};
    $('.link').each(function () {
      var $l = $(this);
      var key = $l.attr('data-key');
      if (!key) return;
      var off = $l.offset();
      if (!off) return;
      layout[key] = { x: Math.round(off.left), y: Math.round(off.top) };
    });
    $.post('system/core/functions/desktop-layout.php', {
      device: window.FO_DESKTOP_DEVICE || 'desktop',
      layout: JSON.stringify(layout)
    });
  }
  window.FO_SAVE_DESKTOP_LAYOUT = saveLayout;

  /* ---------------- free slots for new shortcuts ---------------- */
  function placeNewIcons() {
    var $new = $('.link').filter(function () { return !$(this).hasClass('fo-placed'); });
    if (!$new.length) { return; }

    var m = gridMetrics(), o = gridOrigin(), b = gridBounds();
    var map = occupiedCells(null);

    $new.each(function () {
      var cell = null;
      for (var r = 0; r <= b.maxR && !cell; r++) {
        for (var c = 0; c <= b.maxC; c++) {
          if (!map[c + ':' + r]) { cell = { c: c, r: r }; break; }
        }
      }
      if (!cell) { cell = { c: 0, r: 0 }; }
      map[cell.c + ':' + cell.r] = true;
      $(this).css({
        position: 'absolute',
        left: (o.left + cell.c * m.w) + 'px',
        top: (o.top + cell.r * m.h) + 'px'
      });
    });
  }

  /* ---------------- feedback ---------------- */
  function toast(msg) {
    var $t = $('#fo-toast');
    if (!$t.length) { $t = $('<div id="fo-toast"></div>').appendTo('body'); }
    $t.text(msg).addClass('fo-show');
    clearTimeout($t.data('timer'));
    $t.data('timer', setTimeout(function () { $t.removeClass('fo-show'); }, 2600));
  }
  window.FO_TOAST = toast;

  function showHint(el, text) {
    var $h = $('#fo-link-hint');
    if (!$h.length) { $h = $('<div id="fo-link-hint"></div>').appendTo('body'); }
    $h.text(text);
    var r = el.getBoundingClientRect();
    var top = r.top - 30;
    if (top < 34) top = r.bottom + 8;
    var left = r.left + r.width / 2;
    $h.css({ left: Math.round(left), top: Math.round(top), transform: 'translate(-50%, 0)' });
    requestAnimationFrame(function () { $h.addClass('fo-show'); });
  }

  function hideHint() {
    clearTimeout(hintTimer);
    $('#fo-link-hint').removeClass('fo-show');
  }

  /* ---------------- dock ---------------- */
  function pin(app) {
    return $.post('system/core/functions/dock-edit.php', { action: 'pin', app: app })
      .done(function () { if (window.FO_REFRESH_DOCK) { window.FO_REFRESH_DOCK(); } });
  }

  function unpin(app) {
    return $.post('system/core/functions/dock-edit.php', { action: 'unpin', app: app })
      .done(function () { if (window.FO_REFRESH_DOCK) { window.FO_REFRESH_DOCK(); } });
  }

  function dockIconEl(app) {
    var $i = $('<div class="dock-icon fo-dock-new"><span class="dock-dot"></span></div>')
      .attr('data-app', app)
      .css('background-image', 'url(system/apps/' + app + '/app.png)')
      .on('click', function () {
        if (typeof window.foDockClick === 'function') {
          window.foDockClick(app);
        } else if (typeof window.makeprocess === 'function') {
          window.makeprocess('system/apps/' + app + '/main.php', app, '', app);
        }
      });
    return $i;
  }

  function addDockIcon(app) {
    var $dock = $('.dock-container');
    if (!$dock.length) return false;
    if ($('.dock-icon[data-app="' + app + '"]').length) return false;
    $dock.append(dockIconEl(app));
    return true;
  }

  function removeDockIcon(app) {
    $('.dock-icon[data-app="' + app + '"]').remove();
  }

  
  /* close a menu only when the click is outside it (a mousedown on an item
     must not remove the menu before its click lands) */
  function closeMenuOnOutside() {
    setTimeout(function () {
      $(document).one('mousedown.foMenu', function (e) {
        if (!$(e.target).closest('#fo-desk-menu').length) { closeMenu(); }
      });
    }, 0);
  }

  function closeMenu() { $('#fo-desk-menu').remove(); }

  function showDockMenu(x, y, app) {
    closeMenu();
    var $m = $('<div id="fo-desk-menu"></div>').css({
      position: 'absolute', left: x, top: y, zIndex: 100000,
      background: 'rgba(24,24,26,0.96)', border: '1px solid rgba(255,255,255,0.14)',
      borderRadius: '9px', boxShadow: '0 14px 38px rgba(0,0,0,0.5)', padding: '5px',
      minWidth: '190px', backdropFilter: 'blur(16px)',
      font: '500 12.5px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif',
      color: '#f2f2f4'
    }).appendTo('body');

    // windows of this app: jump straight to the one you need
    var $wins = $('.window[data-app="' + app + '"]');
    if ($wins.length > 1) {
      var $head = $('<div></div>').text('Окна').css({
        padding: '6px 11px 4px', fontSize: '10.5px', opacity: '.55', textTransform: 'uppercase',
        letterSpacing: '.04em'
      }).appendTo($m);
      $wins.each(function () {
        var $w = $(this);
        var id = String($w.attr('id') || '').replace('app', '');
        var title = $.trim($w.find('.process-title-text').first().text()) || app;
        var hidden = $w.css('display') === 'none' || $w.attr('data-minimized') === '1';
        $('<div></div>').text(title + (hidden ? ' · свёрнуто' : '')).css({
          padding: '7px 11px', borderRadius: '6px', cursor: 'default',
          overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap'
        })
          .hover(function () { $(this).css({ background: 'rgba(255,255,255,0.12)' }); },
                 function () { $(this).css({ background: 'transparent' }); })
          .on('click', function () {
            closeMenu();
            if (hidden) {
              if (typeof window.foRestoreWindow === 'function') { window.foRestoreWindow(id); }
            } else if (typeof window.foFocusWindow === 'function') {
              window.foFocusWindow(id);
            }
          })
          .appendTo($m);
      });
      $('<div></div>').css({ height: '1px', margin: '5px 8px', background: 'rgba(255,255,255,0.12)' }).appendTo($m);
    }

    $('<div></div>').text('Открепить «' + app + '» от дока')
      .css({ padding: '7px 11px', borderRadius: '6px', cursor: 'default' })
      .hover(function () { $(this).css({ background: 'rgba(255,255,255,0.12)' }); },
             function () { $(this).css({ background: 'transparent' }); })
      .on('click', function () {
        closeMenu();
        unpin(app).done(function () {
          removeDockIcon(app);
          toast('«' + app + '» откреплён от дока');
        });
      })
      .appendTo($m);

    setTimeout(function () {
      closeMenuOnOutside();
    }, 0);

    menuItem($m, 'Поставить разделитель после', function () {
      addSeparatorAfter($('.dock-icon[data-app="' + app + '"]'));
    });

    // keep the menu on screen and above the dock when the icon sits in it
    var mw = $m.outerWidth() || 200;
    var mh = $m.outerHeight() || 120;
    var maxX = window.innerWidth - mw - 8;
    var maxY = window.innerHeight - mh - 8;
    var left = Math.max(8, Math.min(x, maxX));
    var top = y;
    var dock = dockRect();
    if (dock && y > dock.top - 10) {
      top = Math.round(dock.top - mh - 10);
    } else if (top + mh > window.innerHeight - 8) {
      top = y - mh - 10;
    }
    top = Math.max(40, Math.min(top, maxY));
    $m.css({ left: left, top: top });
  }

  function bindDock() {
    var $dock = $('.dock-container');
    $dock.off('contextmenu.fo').on('contextmenu.fo', '.dock-icon[data-app]', function (e) {
      e.preventDefault();
      e.stopPropagation();
      showDockMenu(e.pageX, e.pageY, $(this).attr('data-app'));
    });
    $dock.off('contextmenu.fo-folder').on('contextmenu.fo-folder', '.dock-icon[data-folder]', function (e) {
      e.preventDefault();
      e.stopPropagation();
      showFolderDockMenu(e.pageX, e.pageY, $(this).attr('data-folder'), $(this).attr('data-store') || '');
    });
    $dock.off('contextmenu.fo-sep').on('contextmenu.fo-sep', '.dock-separator', function (e) {
      e.preventDefault();
      e.stopPropagation();
      showSeparatorMenu(e.pageX, e.pageY, $(this));
    });
    $dock.off('contextmenu.fo-bg').on('contextmenu.fo-bg', function (e) {
      if ($(e.target).closest('.dock-icon, .dock-separator').length) { return; }
      e.preventDefault();
      showDockBgMenu(e.pageX, e.pageY);
    });
    bindDockSort();
  }

  /* ---------------- порядок иконок в доке ---------------- */

  var suppressDockClick = false;

  function dockItems() {
    return $('.dock-container').children('.dock-icon, .dock-separator');
  }

  function dockTokens() {
    var out = [];
    dockItems().each(function () {
      var $e = $(this);
      if ($e.hasClass('dock-separator')) { out.push('sep'); }
      else if ($e.attr('data-app')) { out.push('app:' + $e.attr('data-app')); }
      else if ($e.attr('data-store')) { out.push('folder:' + $e.attr('data-store')); }
    });
    return out;
  }

  function saveDockOrder() {
    var order = dockTokens();
    if (!order.length) { return; }
    $.post('system/core/functions/dock-edit.php', { action: 'reorder', order: order })
      .done(function (res) {
        if (res && res.ok === false) { toast('Не удалось сохранить порядок'); return; }
        /* сервер чистит лишние разделители — перерисовываем, чтобы совпадало */
        if (window.FO_REFRESH_DOCK) { window.FO_REFRESH_DOCK(); }
      })
      .fail(function () { toast('Не удалось сохранить порядок'); });
  }

  function bindDockSort() {
    var dock = document.querySelector('.dock-container');
    if (!dock || dock.getAttribute('data-fo-sort') === '1') { return; }
    dock.setAttribute('data-fo-sort', '1');
    var dragEl = null, startX = 0, startY = 0, moved = false;

    dock.addEventListener('mousedown', function (e) {
      if (e.button !== 0) { return; }
      var el = e.target.closest('.dock-icon, .dock-separator');
      if (!el) { return; }
      dragEl = el; startX = e.clientX; startY = e.clientY; moved = false;
      document.addEventListener('mousemove', onMove, true);
      document.addEventListener('mouseup', onUp, true);
    });

    function onMove(e) {
      if (!dragEl) { return; }
      if (!moved) {
        if (Math.abs(e.clientX - startX) < 6 && Math.abs(e.clientY - startY) < 6) { return; }
        moved = true;
        dragEl.classList.add('dock-dragging');
        dock.classList.add('dock-sorting');
      }
      e.preventDefault();
      var over = document.elementFromPoint(e.clientX, e.clientY);
      var target = over && over.closest ? over.closest('.dock-icon, .dock-separator') : null;
      if (!target || target === dragEl || target.parentNode !== dock) { return; }
      var r = target.getBoundingClientRect();
      var before = (e.clientX - r.left) < r.width / 2;
      dock.insertBefore(dragEl, before ? target : target.nextSibling);
    }

    function onUp() {
      document.removeEventListener('mousemove', onMove, true);
      document.removeEventListener('mouseup', onUp, true);
      if (!dragEl) { return; }
      dragEl.classList.remove('dock-dragging');
      dock.classList.remove('dock-sorting');
      if (moved) {
        suppressDockClick = true;
        setTimeout(function () { suppressDockClick = false; }, 80);
        saveDockOrder();
      }
      dragEl = null; moved = false;
    }
  }

  /* клик после перетаскивания не должен открывать приложение */
  document.addEventListener('click', function (e) {
    if (!suppressDockClick) { return; }
    if (e.target.closest && e.target.closest('.dock-container')) {
      e.stopPropagation();
      e.preventDefault();
    }
  }, true);

  function menuItem($m, text, onClick) {
    return $('<div></div>').text(text)
      .css({ padding: '7px 11px', borderRadius: '6px', cursor: 'default',
             overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' })
      .hover(function () { $(this).css({ background: 'rgba(255,255,255,0.12)' }); },
             function () { $(this).css({ background: 'transparent' }); })
      .on('click', function () { closeMenu(); onClick(); })
      .appendTo($m);
  }

  function addSeparatorAfter($el) {
    var idx = dockItems().index($el) + 1;
    $.post('system/core/functions/dock-edit.php', { action: 'sep-add', index: idx })
      .done(function () { if (window.FO_REFRESH_DOCK) { window.FO_REFRESH_DOCK(); } toast('Разделитель добавлен'); })
      .fail(function () { toast('Не удалось добавить разделитель'); });
  }

  function showSeparatorMenu(x, y, $sep) {
    closeMenu();
    var $m = $('<div id="fo-desk-menu"></div>').css({
      position: 'absolute', left: x, top: y, zIndex: 100000,
      background: 'rgba(24,24,26,0.96)', border: '1px solid rgba(255,255,255,0.14)',
      borderRadius: '9px', boxShadow: '0 14px 38px rgba(0,0,0,0.5)', padding: '5px',
      minWidth: '190px', backdropFilter: 'blur(16px)',
      font: '500 12.5px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif',
      color: '#f2f2f4'
    }).appendTo('body');

    var idx = dockItems().index($sep);
    menuItem($m, 'Убрать разделитель', function () {
      $.post('system/core/functions/dock-edit.php', { action: 'sep-del', index: idx })
        .done(function () { if (window.FO_REFRESH_DOCK) { window.FO_REFRESH_DOCK(); } toast('Разделитель убран'); })
        .fail(function () { toast('Не удалось убрать разделитель'); });
    });
    setTimeout(function () { closeMenuOnOutside(); }, 0);
    $m.css({ left: Math.max(8, Math.min(x, window.innerWidth - ($m.outerWidth() || 200) - 8)),
             top: Math.max(40, Math.min(y, window.innerHeight - ($m.outerHeight() || 90) - 8)) });
  }

  function showDockBgMenu(x, y) {
    closeMenu();
    var $m = $('<div id="fo-desk-menu"></div>').css({
      position: 'absolute', left: x, top: y, zIndex: 100000,
      background: 'rgba(24,24,26,0.96)', border: '1px solid rgba(255,255,255,0.14)',
      borderRadius: '9px', boxShadow: '0 14px 38px rgba(0,0,0,0.5)', padding: '5px',
      minWidth: '210px', backdropFilter: 'blur(16px)',
      font: '500 12.5px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif',
      color: '#f2f2f4'
    }).appendTo('body');

    menuItem($m, 'Добавить разделитель', function () {
      var idx = dockItems().length;
      $.post('system/core/functions/dock-edit.php', { action: 'sep-add', index: idx })
        .done(function () { if (window.FO_REFRESH_DOCK) { window.FO_REFRESH_DOCK(); } toast('Разделитель добавлен'); })
        .fail(function () { toast('Не удалось добавить разделитель'); });
    });
    setTimeout(function () { closeMenuOnOutside(); }, 0);
    $m.css({ left: Math.max(8, Math.min(x, window.innerWidth - ($m.outerWidth() || 220) - 8)),
             top: Math.max(40, Math.min(y, window.innerHeight - ($m.outerHeight() || 60) - 8)) });
  }

  function showFolderDockMenu(x, y, rel, store) {
    closeMenu();
    var $m = $('<div id="fo-desk-menu"></div>').css({
      position: 'absolute', left: x, top: y, zIndex: 100000,
      background: 'rgba(24,24,26,0.96)', border: '1px solid rgba(255,255,255,0.14)',
      borderRadius: '9px', boxShadow: '0 14px 38px rgba(0,0,0,0.5)', padding: '5px',
      minWidth: '190px', backdropFilter: 'blur(16px)',
      font: '500 12.5px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif',
      color: '#f2f2f4'
    }).appendTo('body');

    $('<div></div>').text('Открыть «' + (rel.split('/').pop() || rel) + '»')
      .css({ padding: '7px 11px', borderRadius: '6px', cursor: 'default' })
      .hover(function () { $(this).css({ background: 'rgba(255,255,255,0.12)' }); },
             function () { $(this).css({ background: 'transparent' }); })
      .on('click', function () {
        closeMenu();
        if (window.FO_DOCK_FOLDER) { window.FO_DOCK_FOLDER(rel, rel.split('/').pop()); }
      })
      .appendTo($m);

    $('<div></div>').text('Убрать папку из дока')
      .css({ padding: '7px 11px', borderRadius: '6px', cursor: 'default' })
      .hover(function () { $(this).css({ background: 'rgba(255,255,255,0.12)' }); },
             function () { $(this).css({ background: 'transparent' }); })
      .on('click', function () {
        closeMenu();
        $.post('system/core/functions/dock-edit.php', { action: 'unpin', folder: store || rel })
          .done(function (res) {
            if (res && res.ok === false) { toast('Не удалось убрать папку'); return; }
            if (window.FO_REFRESH_DOCK) { window.FO_REFRESH_DOCK(); } else { $('.dock-icon[data-folder]').remove(); }
            toast('Папка убрана из дока');
          })
          .fail(function () { toast('Не удалось убрать папку'); });
      })
      .appendTo($m);

    menuItem($m, 'Поставить разделитель после', function () {
      addSeparatorAfter($('.dock-icon[data-folder="' + rel.replace(/"/g, '\\"') + '"]'));
    });

    setTimeout(function () {
      closeMenuOnOutside();
    }, 0);
  }

  /* ---------------- icons ---------------- */

  /* ---------------- сетка рабочего стола ---------------- */


  /* origin of the grid in the same coordinates the shortcuts use (the offset parent) */












  








  
  /* ---------------- сетка рабочего стола ---------------- */
  function gridMetrics() {
    var $l = $('.link').first();
    var w = $l.length ? Math.round($l.outerWidth()) : 96;
    var h = $l.length ? Math.round($l.outerHeight()) : 104;
    return { w: Math.max(92, w + 12), h: Math.max(96, h + 12) };
  }

  /* первая клетка: под топ-баром, в координатах контейнера ярлыков */
  function gridOrigin() {
    var barH = $('#topbar').outerHeight() || 34;
    var $p = $('.link').first().offsetParent();
    var pTop = 0, pLeft = 0;
    if ($p && $p.length) { var r = $p[0].getBoundingClientRect(); pTop = Math.round(r.top); pLeft = Math.round(r.left); }
    return { left: 6, top: Math.max(8, Math.round(barH + 8 - pTop)), contTop: pTop, contLeft: pLeft };
  }

  function gridBounds() {
    var m = gridMetrics();
    var $p = $('.link').first().offsetParent();
    var pw = ($p && $p.length) ? $p[0].clientWidth : window.innerWidth;
    var ph = ($p && $p.length) ? $p[0].clientHeight : window.innerHeight;
    var d = dockRect();
    var usableH = (d && d.top > 60) ? (d.top - gridOrigin().contTop - 12) : (ph - 12);
    return {
      maxC: Math.max(0, Math.floor((pw - 14) / m.w) - 1),
      maxR: Math.max(0, Math.floor((usableH - 8) / m.h) - 1)
    };
  }

  function gridGuide() {
    var $g = $('#fo-desktop-grid');
    if (!$g.length) { $g = $('<div id="fo-desktop-grid"></div>').appendTo('body'); }
    var m = gridMetrics(), o = gridOrigin();
    $g.css({
      left: (o.contLeft + o.left) + 'px',
      top: (o.contTop + o.top) + 'px',
      right: '0px',
      bottom: '0px',
      backgroundSize: m.w + 'px ' + m.h + 'px'
    });
    return $g;
  }

  function occupiedCells(excludeEl) {
    var m = gridMetrics(), o = gridOrigin(), map = {};
    $('.link').each(function () {
      if (this === excludeEl) { return; }
      var p = $(this).position();
      var c = Math.round((p.left - o.left) / m.w), r = Math.round((p.top - o.top) / m.h);
      map[c + ':' + r] = true;
    });
    return map;
  }

  function findFreeCell(c0, r0, map, maxC, maxR) {
    var best = null, bestD = Infinity;
    for (var r = 0; r <= maxR; r++) {
      for (var c = 0; c <= maxC; c++) {
        if (map[c + ':' + r]) { continue; }
        var dd = Math.abs(c - c0) + Math.abs(r - r0);
        if (dd < bestD) { bestD = dd; best = { c: c, r: r }; }
      }
    }
    return best || { c: Math.min(c0, maxC), r: Math.min(r0, maxR) };
  }

  function snapToGrid($el) {
    if (!$el || !$el.length) { return; }
    var m = gridMetrics(), o = gridOrigin(), b = gridBounds();
    var p = $el.position();
    var iw = $el.outerWidth() || m.w, ih = $el.outerHeight() || m.h;
    var c0 = Math.min(b.maxC, Math.max(0, Math.floor((p.left + iw / 2 - o.left) / m.w)));
    var r0 = Math.min(b.maxR, Math.max(0, Math.floor((p.top + ih / 2 - o.top) / m.h)));
    var cell = findFreeCell(c0, r0, occupiedCells($el[0]), b.maxC, b.maxR);
    var left = o.left + cell.c * m.w;
    var top = o.top + cell.r * m.h;
    if (Math.abs(left - p.left) > 1 || Math.abs(top - p.top) > 1) {
      $el.addClass('fo-snapping');
      $el.css({ left: left + 'px', top: top + 'px' });
      setTimeout(function () { $el.removeClass('fo-snapping'); }, 240);
    }
  }

  window.FO_GRID_INFO = function () { return { metrics: gridMetrics(), origin: gridOrigin(), bounds: gridBounds() }; };
  window.FO_SNAP_GRID = function () {
    $('.link').each(function () { snapToGrid($(this)); });
    saveLayout();
  };

  function bindIcons() {
    var $links = $('.link').not('.fo-bound');
    if (!$links.length) return;
    $links.addClass('fo-bound');

    $links.draggable({
      containment: [0, 28, window.innerWidth - 2, window.innerHeight - 2],
      scroll: false,
      start: function () {
        hideHint();
        makeAbsolute(this);
        $(this).css('z-index', 1200).addClass('fo-dragging');
        gridGuide();
        $('body').addClass('fo-grid-drag');
      },
      drag: function (e, ui) {
        var d = dockRect();
        var w = $(this).outerWidth() || 96;
        var h = $(this).outerHeight() || 96;
        var r = { left: ui.position.left, top: ui.position.top, right: ui.position.left + w, bottom: ui.position.top + h };
        $('.dock-container').toggleClass('fo-dock-target', !!(d && overlaps(r, d)));
      },
      stop: function () {
        var $l = $(this);
        var el = this;
        var app = $l.attr('data-app');
        var d = dockRect();
        var onDock = d && overlaps(el.getBoundingClientRect(), d);
        var folderRel = $l.attr('data-folder') || '';

        $('.dock-container').removeClass('fo-dock-target');
        $('body').removeClass('fo-grid-drag');
        $l.css('z-index', '').removeClass('fo-dragging');

        if (onDock && folderRel) {
          $.post('system/core/functions/dock-edit.php', { action: 'pin', folder: folderRel })
            .done(function () { toast('Папка закреплена в доке'); if (window.FO_REFRESH_DOCK) { window.FO_REFRESH_DOCK(); } })
            .fail(function () { toast('Не удалось закрепить папку'); });
        } else if (onDock && app) {
          pin(app)
            .done(function (res) {
              var data = null;
              try { data = typeof res === 'string' ? JSON.parse(res) : res; } catch (e) { data = null; }
              if (data && data.ok === false) {
                toast('Не удалось закрепить «' + app + '» в доке');
                return;
              }
              toast('«' + app + '» закреплён в доке');            })
            .fail(function () { toast('Не удалось закрепить «' + app + '» в доке'); });
        } else if (onDock && !app) {
          toast('В док можно закрепить только приложение');
        }

        snapToGrid($l);
        clampToDesktop($l);
        saveLayout();
      }
    }).css('-webkit-user-select', 'none');

    // Hover hint: explains dragging instead of showing a "hand".
    $links.on('mouseenter.fo', function () {
      var el = this;
      var app = $(this).attr('data-app');
      var text = app ? 'Перетащите, чтобы переместить · двойной клик — открыть'
                     : 'Перетащите, чтобы переместить · двойной клик — открыть';
      clearTimeout(hintTimer);
      hintTimer = setTimeout(function () { showHint(el, text); }, HINT_DELAY);
    }).on('mouseleave.fo', hideHint).on('mousedown.fo', hideHint);

    $links.on('contextmenu.fo', function (e) {
      e.preventDefault();
      e.stopPropagation();
      showDesktopMenu(e.pageX, e.pageY, this);
    });
  }

  /* right-click on the desktop: shortcuts, refresh, personalisation, logout */
  function showDesktopMenu(x, y, linkEl) {
    closeMenu();
    var $m = $('<div id="fo-desk-menu"></div>').css({
      position: 'absolute', left: x, top: y, zIndex: 100000,
      background: 'rgba(24,24,26,0.96)', border: '1px solid rgba(255,255,255,0.14)',
      borderRadius: '9px', boxShadow: '0 14px 38px rgba(0,0,0,0.5)', padding: '5px',
      minWidth: '200px', backdropFilter: 'blur(16px)',
      font: '500 12.5px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif',
      color: '#f2f2f4'
    }).appendTo('body');

    function add(label, fn, danger) {
      var $i = $('<div></div>').text(label).css({
        padding: '8px 12px', borderRadius: '7px', cursor: 'default',
        color: danger ? '#ff8f86' : '#f2f2f4'
      })
        .hover(function () { $(this).css({ background: 'rgba(255,255,255,0.12)' }); },
               function () { $(this).css({ background: 'transparent' }); })
        .on('click', function () { closeMenu(); fn(); })
        .appendTo($m);
      return $i;
    }

    if (linkEl) {
      var $l = $(linkEl);
      var key = $l.attr('data-key') || '';
      var app = $l.attr('data-app') || '';
      var lid = $l.attr('i');

      add('Открыть', function () {
        var inner = document.getElementById('link' + lid);
        if (!inner) { return; }
        inner.dispatchEvent(new MouseEvent('dblclick', { bubbles: true }));
        if (typeof inner.onclick === 'function') { inner.onclick(); }
      });

      if (app) {
        add('Открепить от дока', function () {
          unpin(app).done(function () { removeDockIcon(app); toast('«' + app + '» откреплён от дока'); });
        });
      }

      $('<div></div>').css({ height: '1px', margin: '5px 8px', background: 'rgba(255,255,255,0.12)' }).appendTo($m);

      add('Удалить ярлык', function () {
        if (!key) { return; }
        $.post('system/core/functions/desktop-link.php', { action: 'delete', file: key })
          .done(function () {
            $l.remove();
            saveLayout();
            toast('Ярлык удалён');
            if (window.FO_REFRESH_DESKTOP) { setTimeout(window.FO_REFRESH_DESKTOP, 120); }
          })
          .fail(function () { toast('Не удалось удалить ярлык'); });
      }, true);
    } else {
      add('Создать папку', function () { if (window.FO_DESKTOP_NEW_FOLDER) { window.FO_DESKTOP_NEW_FOLDER(); } });
      add('Создать файл', function () { if (window.FO_DESKTOP_NEW_FILE) { window.FO_DESKTOP_NEW_FILE(); } });
      $('<div></div>').css({ height: '1px', margin: '5px 8px', background: 'rgba(255,255,255,0.12)' }).appendTo($m);
      add('Обновить', function () { if (window.FO_REFRESH_SHELL) { window.FO_REFRESH_SHELL(); } else { window.location.reload(); } });
      add('Персонализация', function () {
        if (typeof window.makeprocess === 'function') {
          window.makeprocess('system/apps/Settings/main.php', '', '', 'Settings');
        }
      });
      add('Каталог приложений', function () {
        if (typeof window.makeprocess === 'function') {
          window.makeprocess('system/apps/Apps_House/main.php', '', '', 'Apps_House');
        }
      });
      add('Node-режим', function () {
        if (window.FONode) { window.FONode.toggle(); }
      });
      $('<div></div>').css({ height: '1px', margin: '5px 8px', background: 'rgba(255,255,255,0.12)' }).appendTo($m);

      add('Выход · Exit', function () { if (window.FO_POWER) { window.FO_POWER('exit'); } else { window.location.href = '?action=logout'; } });
      add('Перезагрузка · Reload', function () { if (window.FO_POWER) { window.FO_POWER('reload'); } else { window.location.href = 'os.php'; } });

      $('<div></div>').css({ height: '1px', margin: '5px 8px', background: 'rgba(255,255,255,0.12)' }).appendTo($m);
      add('Выключение · Shut Down', function () { if (window.FO_POWER) { window.FO_POWER('shutdown'); } }, true);
    }

    var mw = $m.outerWidth() || 210;
    var mh = $m.outerHeight() || 140;
    var left = Math.max(8, Math.min(x, $(window).width() - mw - 8));
    var top = Math.max(8, Math.min(y, $(window).height() - mh - 8));
    $m.css({ left: left, top: top });

    setTimeout(function () {
      closeMenuOnOutside();
    }, 0);
  }

  /* re-bind + re-place after the desktop markup was refreshed */
  function refreshDesktopIcons() {
    bindIcons();
    placeNewIcons();

    /* ярлыки из старых раскладок могли наложиться — раскладываем по сетке */
    (function () {
      var m = gridMetrics(), seen = {}, clashes = [];
      $('.link').each(function () {
        var p = $(this).position();
        var c = Math.round(p.left / m.w), r = Math.round(p.top / m.h);
        var k = c + ':' + r;
        if (seen[k]) { clashes.push($(this)); } else { seen[k] = 1; }
      });
      if (clashes.length) {
        clashes.forEach(function ($l) { snapToGrid($l); });
        saveLayout();
      }
    })();
    if (window.FO_DOCK_SYNC) { window.FO_DOCK_SYNC(); }
  }
  window.FO_DESKTOP_REFRESH = refreshDesktopIcons;

  /* ---------------- init ---------------- */
  $(function () {
    if (!window.FO_DESKTOP_DEVICE) return;

    if ($('.selector').length <= 1) { $('body').addClass('fo-single-desktop'); }
    else { $('body').removeClass('fo-single-desktop'); }

    bindDock();

    /* right click anywhere on the free desktop (not only on #desktops) */
    $(document).on('contextmenu.fo-desktop', function (e) {
      if (e.isDefaultPrevented && e.isDefaultPrevented()) { return; }
      if ($(e.target).closest('.window, .dock-container, .link, #topbar, .fo-app, #fo-desk-menu, .fo-menu, #notifications').length) { return; }
      e.preventDefault();
      e.stopPropagation();
      showDesktopMenu(e.pageX, e.pageY, null);
    });

    // Repair layouts saved under the dock before this fix.
    var changed = false;
    $('.link.fo-placed').each(function () {
      if (clampToDesktop($(this))) changed = true;
    });
    if (changed) saveLayout();

    bindIcons();
    placeNewIcons();

    /* выравниваем все ярлыки по сетке: линии и клетки совпадают */
    (function () {
      var moved = false;
      $('.link').each(function () {
        var $l = $(this);
        var before = $l.position();
        snapToGrid($l);
        var after = $l.position();
        if (Math.abs(before.left - after.left) > 1 || Math.abs(before.top - after.top) > 1) { moved = true; }
      });
      if (moved) { saveLayout(); }
    })();

    $(window).on('resize', function () {
      var moved = false;
      $('.link.fo-placed').each(function () {
        if (clampToDesktop($(this))) moved = true;
      });
      if (moved) saveLayout();
    });

    if (window.FO_DOCK_SYNC) { window.FO_DOCK_SYNC(); }
  });
})();

/* ==========================================================================
   Рабочее пространство: открытые окна переживают выход и переезжают между
   устройствами. Состояние лежит на сервере (settings/workspace.json),
   запись атомарная, у файла есть ревизия — старая вкладка не затрёт новое.
   ========================================================================== */
(function () {
  'use strict';

  var API = 'system/core/functions/workspace.php';
  var token = '';
  var rev = 0;
  var ready = null;
  var timer = null;
  var restoring = false;
  var shuttingDown = false;
  var dirty = false;

  function wsUrl(action) {
    return API + '?action=' + action + '&token=' + encodeURIComponent(token);
  }

  function loadState() {
    return fetch(API + '?action=get', { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (!d || !d.ok) { return { windows: [] }; }
        token = d.token || '';
        var ws = d.workspace || {};
        rev = Number(ws.rev) || 0;
        return ws;
      })
      .catch(function () { return { windows: [] }; });
  }

  function collect() {
    var windows = [];
    $('.window[data-app]').each(function () {
      var $w = $(this);
      var dest = $w.attr('data-dest') || '';
      if (!dest) { return; }
      var pos = $w.position();
      windows.push({
        dest: dest,
        name: $w.attr('data-title') || $w.attr('data-app') || 'App',
        param: $w.attr('data-param') || '',
        key: $w.attr('data-key') || '',
        x: Math.round(pos.left || 0),
        y: Math.round(pos.top || 0),
        w: Math.round($w.outerWidth() || 0),
        h: Math.round($w.outerHeight() || 0),
        max: $w.hasClass('windowfullscreen'),
        shaded: $w.attr('data-minimized') === '1'
      });
    });
    return { rev: rev, active: $('.window.windowactive').first().attr('data-app') || '', windows: windows };
  }

  function saveNow() {
    if (restoring || shuttingDown) { return Promise.resolve(false); }
    return Promise.resolve(ready).then(function () {
      return fetch(wsUrl('save'), {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(collect())
      }).then(function (r) { return r.json(); }).then(function (d) {
        if (d && d.ok) {
          rev = Number(d.rev) || rev;
          dirty = false;
          return true;
        }
        return false;
      });
    }).catch(function () { return false; });
  }

  function scheduleSave() {
    if (restoring || shuttingDown) { return; }
    dirty = true;
    clearTimeout(timer);
    timer = setTimeout(saveNow, 1200);
  }

  function beaconSave() {
    if (restoring || shuttingDown || !token || !dirty) { return; }
    try {
      navigator.sendBeacon(wsUrl('save'), new Blob([JSON.stringify(collect())], { type: 'application/json' }));
    } catch (e) { /* ignore */ }
  }

  function applyGeometry($w, item) {
    var vw = $(window).width() || 1200;
    var vh = $(window).height() || 800;
    var w = Math.max(240, Math.min(item.w || 900, vw - 20));
    var h = Math.max(160, Math.min(item.h || 600, vh - 80));
    var x = Math.max(0, Math.min(item.x || 0, vw - 120));
    var y = Math.max(28, Math.min(item.y || 0, vh - 60));
    $w.css({ position: 'absolute', left: x + 'px', top: y + 'px', width: w + 'px', height: h + 'px' });
    if (item.max) { $w.addClass('windowfullscreen'); }
    if (item.shaded && typeof window.foMinimizeWindow === 'function') {
      try { window.foMinimizeWindow(String($w.attr('id') || '').replace('app', '')); } catch (e) {}
    }
  }

  function restore(ws) {
    var list = (ws && ws.windows) || [];
    if (!list.length || typeof window.makeprocess !== 'function') { return; }
    restoring = true;
    var before = $('.window[data-app]').length;
    list.forEach(function (item) {
      try { window.makeprocess(item.dest, item.param || '', item.key || '', item.name || 'App'); } catch (e) {}
    });
    var tries = 0;
    var poll = setInterval(function () {
      var $ws = $('.window[data-app]');
      if ($ws.length - before >= list.length || ++tries > 50) {
        clearInterval(poll);
        $ws.slice(before).each(function (i) {
          if (list[i]) { applyGeometry($(this), list[i]); }
        });
        restoring = false;
      }
    }, 200);
  }

  /* Exit / Reload / Shut Down */
  window.FO_POWER = function (kind) {
    var go = function () {
      window.location.href = (kind === 'reload') ? 'os.php' : '?action=logout';
    };
    if (kind === 'shutdown') {
      shuttingDown = true;
      fetch(wsUrl('clear'), { method: 'POST', credentials: 'same-origin' })
        .catch(function () { })
        .then(go);
      return;
    }
    saveNow().then(go).catch(go);
  };

  window.FO_WORKSPACE_SAVE = saveNow;

  $(function () {
    if (!document.getElementById('proceses')) { return; }
    ready = loadState();
    ready.then(restore);

    var target = document.getElementById('proceses');
    if (target && window.MutationObserver) {
      new MutationObserver(function () { scheduleSave(); }).observe(target, { childList: true });
    }
    $(document).on('mouseup', '.window', scheduleSave);
    $(window).on('pagehide beforeunload', beaconSave);
  });
})();
