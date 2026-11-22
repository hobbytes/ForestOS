/* ==========================================================================
   Forest OS — Node mode (системный dataflow между окнами и объектами)
   --------------------------------------------------------------------------
   * Любое приложение получает вход «Файл» автоматически; приложения могут
     объявить свои порты: FONode.register('App', {inputs:[…], outputs:[…]}).
   * Любой элемент может стать якорем: data-fo-out="path" / data-fo-in="file"
     (+ data-fo-value — что отдать; по умолчанию data-path).
   * Провод соединяет выход с входом; значения идут по связи.
   * Два режима: «Авто» (значение уходит сразу) и «По запуску» (копится
     в очереди, пока не нажмут «Выполнить»).
   * Пространства (окна + связи) сохраняются и восстанавливаются.
   ========================================================================== */
(function () {
  'use strict';

  var PORTS = {
    Explorer: { outputs: [{ id: 'path', label: 'Путь файла' }] },
    Audio_Player: { inputs: [{ id: 'path', label: 'Аудиофайл' }] },
    Image_Viewer: { inputs: [{ id: 'path', label: 'Изображение' }] },
    Rich_Text_Editor: { inputs: [{ id: 'path', label: 'Текст' }] },
    'Rich Text Editor': { inputs: [{ id: 'path', label: 'Текст' }] }
  };

  var state = {
    on: false,
    mode: 'auto',           // 'auto' | 'manual'
    queue: [],              // [{app, port, value, path}]
    links: [],              // [{from:{app,port,path?}, to:{app,port,path?}}]
    drag: null,
    raf: null
  };

  var $layer = null, $svg = null, $panel = null;

  try {
    var savedMode = localStorage.getItem('fo-node-mode');
    if (savedMode === 'manual' || savedMode === 'auto') { state.mode = savedMode; }
  } catch (e) {}

  /* ---------- window helpers -------------------------------------------- */
  function windowsOf(app) { return $('.window[data-app="' + app + '"]'); }

  function winFor(app) {
    var $w = windowsOf(app);
    return $w.length ? $w.last() : null;
  }

  function idOf($w) { return String($w && $w[0] ? $w[0].id : '').replace('app', ''); }

  function appPorts(app) {
    var def = PORTS[app];
    if (def) { return def; }
    /* obvious default for every application: it can open a file */
    return { inputs: [{ id: 'path', label: 'Файл' }] };
  }

  function windowAnchors() {
    var out = [];
    $('.window[data-app]').each(function () {
      var $w = $(this);
      var app = $w.attr('data-app');
      var def = appPorts(app);
      var id = idOf($w);
      (def.inputs || []).forEach(function (p) { out.push({ app: app, id: id, port: p.id, kind: 'in', label: p.label }); });
      (def.outputs || []).forEach(function (p) { out.push({ app: app, id: id, port: p.id, kind: 'out', label: p.label }); });
    });
    return out;
  }

  function cssPath(el) {
    if (!el || el.nodeType !== 1) { return ''; }
    if (el.id) { return '#' + el.id; }
    var parts = [];
    var cur = el;
    while (cur && cur.nodeType === 1) {
      if (cur.classList && cur.classList.contains('window')) { break; }
      if (cur === document.body) { break; }
      var tag = cur.tagName.toLowerCase();
      var cls = (cur.className || '').toString().trim().split(/\s+/).filter(function (c) {
        return c && !/^fo-node-/.test(c) && !/^fo-app$/.test(c);
      }).slice(0, 2).join('.');
      var idx = 0, sib = cur;
      while (sib) { if (sib.nodeType === 1 && sib.tagName === cur.tagName) { idx++; } sib = sib.previousElementSibling; }
      parts.unshift(tag + (cls ? '.' + cls : '') + (idx > 1 ? ':nth-of-type(' + idx + ')' : ''));
      if (parts.length >= 8) { break; }
      cur = cur.parentElement;
    }
    /* путь считается от окна приложения: $win.find('> div.a > div.b') */
    return parts.length ? '> ' + parts.join(' > ') : '';
  }

  function manualPorts(app) {
    return (window.FONodeManual && window.FONodeManual[app]) || [];
  }

  function elementAnchors() {
    var out = [];
    $('.window [data-fo-out], .window [data-fo-in], .window [data-fo-action], .window [data-fo-event]').each(function () {
      var $el = $(this);
      if ($el.closest('#fo-node-layer').length) { return; }
      var $win = $el.closest('.window');
      if (!$win.length) { return; }
      var app = $win.attr('data-app');
      if (!app) { return; }

      var r = this.getBoundingClientRect();
      if (!r.width || !r.height) { return; }
      var box = $win[0].getBoundingClientRect();
      if (r.top < box.top + 30 || r.bottom > box.bottom - 4) { return; }

      var label = $el.attr('title') || ($el.text().trim().slice(0, 22)) || 'элемент';
      var sel = cssPath(this);
      var base = {
        app: app, id: idOf($win), label: label, value: $el.attr('data-fo-value') || $el.attr('data-path') || '',
        path: $el.attr('data-path') || '', sel: sel, el: this
      };

      if ($el.is('[data-fo-in], [data-fo-action]')) {
        out.push($.extend({}, base, {
          kind: 'in',
          port: $el.attr('data-fo-action') || $el.attr('data-fo-in') || 'value',
          action: $el.is('[data-fo-action]')
        }));
      }
      if ($el.is('[data-fo-out], [data-fo-event]')) {
        out.push($.extend({}, base, {
          kind: 'out',
          port: $el.attr('data-fo-event') || $el.attr('data-fo-out') || 'value',
          event: $el.is('[data-fo-event]')
        }));
      }
    });
    return out;
  }

  function posOf(a) {
    var $w = $('#app' + a.id);
    if (!$w.length) { return null; }
    var box = $w[0].getBoundingClientRect();
    if (a.el) {
      var r = a.el.getBoundingClientRect();
      return { x: a.kind === 'in' ? r.left : r.right, y: r.top + r.height / 2 };
    }
    var bar = $w.find('.dragwindow')[0];
    var barH = bar ? bar.getBoundingClientRect().height : 32;
    return { x: a.kind === 'in' ? box.left : box.right, y: box.top + barH / 2 };
  }

  function anchorForLink(app, kind, port, path, sel) {
    if (sel) {
      var $e = elementIn(app, sel);
      if ($e && $e.length) {
        var er = $e[0].getBoundingClientRect();
        return { x: kind === 'in' ? er.left : er.right + 14, y: er.top + er.height / 2 };
      }
    }
    if (path) {
      var $w = winFor(app);
      if ($w) {
        var el = $w.find('[data-path="' + String(path).replace(/"/g, '\\"') + '"]')[0];
        if (el) {
          var r = el.getBoundingClientRect();
          return { x: kind === 'in' ? r.left : r.right, y: r.top + r.height / 2 };
        }
      }
    }
    var $win = winFor(app);
    if (!$win) { return null; }
    var list = windowAnchors().filter(function (a) { return a.app === app && a.kind === kind; });
    for (var i = 0; i < list.length; i++) {
      if (list[i].port === port) { return posOf(list[i]); }
    }
    return null;
  }

  /* ---------- rendering -------------------------------------------------- */
  function ensureLayer() {
    if ($layer && $layer.length) { return; }
    $layer = $('<div id="fo-node-layer"></div>').appendTo('body');
    $svg = $('<svg class="fo-node-wires"></svg>').appendTo($layer);
    $panel = $(
      '<div id="fo-node-panel">' +
      '<div class="fo-np-head"><span class="fo-np-title">Node-режим</span>' +
      '<span class="fo-np-modes">' +
      '<button type="button" data-np-mode="auto">Авто</button>' +
      '<button type="button" data-np-mode="manual">По запуску</button>' +
      '</span>' +
      '<button type="button" data-np="off" class="fo-np-off">Выйти</button></div>' +
      '<div class="fo-np-row"><input class="fo-np-name" placeholder="название пространства" maxlength="40">' +
      '<button type="button" data-np="save">Сохранить</button>' +
      '<button type="button" data-np="load">Загрузить</button>' +
      '<button type="button" data-np="clear">Очистить связи</button></div>' +
      '<div class="fo-np-row fo-np-run-row"><button type="button" data-np="run" class="fo-np-run">Выполнить</button>' +
      '<span class="fo-np-queue">очередь: 0</span></div>' +
      '<div class="fo-np-list"></div>' +
      '<div class="fo-np-hint">Правый клик по элементу в окне — сделать его входом или выходом.</div>' +
      '</div>'
    ).appendTo($layer);
  }

  function applyMode() {
    if (!$panel) { return; }
    $panel.find('[data-np-mode]').each(function () {
      $(this).toggleClass('is-on', $(this).attr('data-np-mode') === state.mode);
    });
    $panel.find('.fo-np-run-row').toggle(state.mode === 'manual');
    $panel.find('.fo-np-queue').text('очередь: ' + state.queue.length);
  }

  function renderAnchors() {
    if (!state.on) { return; }
    ensureLayer();
    $layer.find('.fo-node-anchor').remove();

    var stacks = {};
    windowAnchors().forEach(function (a) {
      var key = a.app + ':' + a.kind;
      stacks[key] = (stacks[key] || 0);
      var p = posOf(a);
      if (!p) { return; }
      var y = p.y + stacks[key] * 22;
      stacks[key]++;
      $('<div class="fo-node-anchor fo-node-' + a.kind + '" title="' + (a.kind === 'in' ? 'Вход: ' : 'Выход: ') + a.label + '"></div>')
        .attr({ 'data-app': a.app, 'data-port': a.port, 'data-kind': a.kind, 'data-win': a.id })
        .css({ left: Math.round(p.x) + 'px', top: Math.round(y) + 'px' })
        .appendTo($layer);
    });

    elementAnchors().forEach(function (a) {
      var p = posOf(a);
      if (!p) { return; }
      var cls = 'is-element ';
      if (a.action) { cls += 'is-action '; }
      if (a.event) { cls += 'is-event '; }
      var ax = p.x + (a.kind === 'out' ? 14 : 0);
      $('<div class="fo-node-anchor ' + cls + 'fo-node-' + a.kind + '" title="' + (a.kind === 'in' ? 'Вход: ' : 'Выход: ') + a.label + '"></div>')
        .attr({
          'data-app': a.app, 'data-port': a.port, 'data-kind': a.kind,
          'data-win': a.id, 'data-value': a.value, 'data-elpath': a.path, 'data-sel': a.sel
        })
        .css({ left: Math.round(ax) + 'px', top: Math.round(p.y) + 'px' })
        .appendTo($layer);
    });
  }

  function wirePath(a, b) {
    var dx = Math.max(40, Math.abs(b.x - a.x) * 0.4);
    return 'M ' + a.x + ' ' + a.y + ' C ' + (a.x + dx) + ' ' + a.y + ', ' + (b.x - dx) + ' ' + b.y + ', ' + b.x + ' ' + b.y;
  }

  function renderWires() {
    if (!state.on) { return; }
    ensureLayer();
    $svg.attr({ width: $(window).width(), height: $(window).height() });

    var parts = [];
    state.links.forEach(function (l, i) {
      var a = anchorForLink(l.from.app, 'out', l.from.port, l.from.path, l.from.sel);
      var b = anchorForLink(l.to.app, 'in', l.to.port, l.to.path, l.to.sel);
      if (!a || !b) { return; }
      parts.push('<path class="fo-node-wire' + (l.from.path ? ' is-file' : '') + '" data-i="' + i + '" d="' + wirePath(a, b) + '"></path>');
      parts.push('<circle class="fo-node-wire-dot" data-i="' + i + '" cx="' + ((a.x + b.x) / 2) + '" cy="' + ((a.y + b.y) / 2) + '" r="5"></circle>');
    });

    if (state.drag) {
      parts.push('<path class="fo-node-wire is-drag" d="' + wirePath(state.drag.from, state.drag.at) + '"></path>');
    }
    $svg.html(parts.join(''));
  }

  function tick() {
    if (!state.on) { return; }
    renderAnchors();
    renderWires();
    state.raf = requestAnimationFrame(tick);
  }

  /* ---------- wiring ----------------------------------------------------- */
  function nearestAnchor(clientX, clientY, kind) {
    var best = null, bestD = 22;
    $layer.find('.fo-node-anchor.fo-node-' + kind).each(function () {
      var r = this.getBoundingClientRect();
      var d = Math.hypot(r.left + r.width / 2 - clientX, r.top + r.height / 2 - clientY);
      if (d < bestD) { bestD = d; best = this; }
    });
    return best;
  }

  function startDrag(e, $a) {
    var r = $a[0].getBoundingClientRect();
    state.drag = {
      from: {
        app: $a.attr('data-app'), port: $a.attr('data-port'),
        path: $a.attr('data-elpath') || '', sel: $a.attr('data-sel') || '',
        value: $a.attr('data-value') || '',
        x: r.left + r.width / 2, y: r.top + r.height / 2
      },
      at: { x: e.pageX, y: e.pageY }
    };
    e.preventDefault();
  }

  function finishDrag(e) {
    if (!state.drag) { return; }
    var from = state.drag.from;
    state.drag = null;

    var target = nearestAnchor(e.clientX, e.clientY, 'in');
    if (!target) { return; }
    var $t = $(target);
    var toApp = $t.attr('data-app');
    var toPort = $t.attr('data-port');
    var toPath = $t.attr('data-elpath') || '';
    var toSel = $t.attr('data-sel') || '';
    if (toApp === from.app && !toSel) { return; }

    var dup = state.links.some(function (l) {
      return l.from.app === from.app && l.from.port === from.port && (l.from.sel || '') === (from.sel || '') &&
             l.to.app === toApp && l.to.port === toPort && (l.to.sel || '') === toSel;
    });
    if (dup) { return; }

    state.links.push({
      from: { app: from.app, port: from.port, path: from.path || '', sel: from.sel || '' },
      to: { app: toApp, port: toPort, path: toPath || '', sel: toSel || '' }
    });

    var label = from.path ? from.path.split('/').pop() : (from.sel ? from.app + ': элемент' : from.app);
    toast('Связь: ' + label + ' → ' + toApp + (toSel ? ': элемент' : ''));
    if (from.value) { feed(toApp, toPort, from.value, toSel); }
  }

  /* ---------- data flow -------------------------------------------------- */
  /* Почти все приложения принимают файл одинаково — параметром defaultloader.
     Поэтому доставка в приложение = перезапуск с этим ключом.
     Если цель — конкретный элемент (кнопка/поле), работаем с ним напрямую. */

  function reloadWith(id, value) {
    var $c = $('#' + id);
    if (!$c.length) { return false; }
    var loc = $c.attr('location') || '';
    if (!loc) { return false; }
    var parts = loc.split('?');
    var params = new URLSearchParams(parts[1] || '');
    params.set('defaultloader', value);
    params.delete('rteloader');
    params.delete('photoviewload');
    var url = parts[0] + '?' + params.toString();
    $c.html('<div style="padding:26px;font:13px -apple-system,BlinkMacSystemFont,Segoe UI,sans-serif;color:#7a818a">Загрузка…</div>');
    $c.load(url, null, function () {
      if (window.FO_DOCK_SYNC) { window.FO_DOCK_SYNC(); }
      if (typeof window.foFocusWindow === 'function') { window.foFocusWindow(id); }
      $(window).trigger('fo-node-input', [{ value: value }]);
    });
    return true;
  }

  function elementIn(app, sel) {
    var $w = winFor(app);
    if (!$w || !sel) { return null; }
    try { return $w.find(sel).last(); } catch (e) { return null; }
  }

  function feed(app, port, value, sel) {
    if (state.mode === 'manual') {
      state.queue.push({ app: app, port: port, value: value, sel: sel || '' });
      applyMode();
      return;
    }
    deliver(app, port, value, sel);
  }

  function emit(app, port, value) {
    if (!state.on) { return; }
    state.links.forEach(function (l) {
      if (l.from.app === app && l.from.port === port) { feed(l.to.app, l.to.port, value, l.to.sel || ''); }
    });
  }

  function deliver(app, port, value, sel) {
    var name = String(value).split('/').pop();

    /* 1) конкретный элемент: кнопка получает «нажатие», поле — значение */
    if (sel) {
      var $el = elementIn(app, sel);
      if ($el && $el.length) {
        if ($el.is('[data-fo-action]') || $el.is('button') || $el.is('[role="button"]')) {
          $el.addClass('fo-node-flash');
          setTimeout(function () { $el.removeClass('fo-node-flash'); }, 600);
          try { $el[0].click(); } catch (e) {}
          toast(app + ': нажато «' + ($el.text().trim().slice(0, 18) || port) + '»');
          return;
        }
        if ($el.is('input, textarea')) {
          $el.val(value).trigger('input').trigger('change');
          toast(app + ': поле ← ' + name);
          return;
        }
        $el.text(value);
        toast(app + ': значение ← ' + name);
        return;
      }
    }

    /* 2) JS-обработчик приложения */
    if (window.FONodeHandlers && typeof window.FONodeHandlers[app] === 'function') {
      try { window.FONodeHandlers[app](port, value); return; } catch (e) {}
    }

    /* 3) открытое окно — перезапуск с ключом */
    var $w = winFor(app);
    if ($w) {
      var id = idOf($w);
      if (reloadWith(id, value)) {
        toast(app + ' ← ' + name);
        return;
      }
      if (typeof window.foFocusWindow === 'function') { window.foFocusWindow(id); }
      toast(app + ' ← ' + name);
      return;
    }

    /* 4) окна нет — открыть приложение с файлом */
    if (typeof window.makeprocess === 'function') {
      window.makeprocess('system/apps/' + app + '/main.php', value, 'defaultloader', app);
      toast('Открываю ' + app + ': ' + name);
    }
  }

  function runQueue() {
    if (!state.queue.length) { toast('Очередь пуста'); return; }
    var items = state.queue.slice();
    state.queue = [];
    applyMode();
    items.forEach(function (it, i) {
      setTimeout(function () { deliver(it.app, it.port, it.value, it.sel || ''); }, 120 * i);
    });
    toast('Запуск: ' + items.length + ' передач');
  }

  /* ---------- manual anchors --------------------------------------------- */
  function loadAnchors() {
    api('anchors-list', {}).then(function (r) {
      window.FONodeManual = (r && r.ok && r.anchors) ? r.anchors : {};
      applyAnchors();
    }).catch(function () { window.FONodeManual = {}; });
  }

  function applyAnchors() {
    var map = window.FONodeManual || {};
    Object.keys(map).forEach(function (app) {
      var $w = winFor(app);
      if (!$w) { return; }
      (map[app] || []).forEach(function (a) {
        try {
          var $el = $w.find(a.sel).last();
          if (!$el.length) { return; }
          if (a.kind === 'in') { $el.attr(a.port === 'click' ? 'data-fo-action' : 'data-fo-in', a.port || 'value'); }
          else { $el.attr(a.port === 'click' ? 'data-fo-event' : 'data-fo-out', a.port || 'value'); }
        } catch (e) {}
      });
    });
  }

  function saveAnchors() {
    api('anchors-save', { anchors: JSON.stringify(window.FONodeManual || {}) })
      .then(function (r) { if (r && r.ok) { toast('Якоря сохранены'); } });
  }

  function addManualAnchor($el, kind) {
    var $w = $el.closest('.window');
    var app = $w.attr('data-app');
    if (!app) { return; }
    var isButton = $el.is('button, a, [role="button"]') || $el.find('button').length > 0;
    var port = isButton ? 'click' : 'value';
    if (kind === 'in') { $el.attr(port === 'click' ? 'data-fo-action' : 'data-fo-in', port); }
    else { $el.attr(port === 'click' ? 'data-fo-event' : 'data-fo-out', port); }

    window.FONodeManual = window.FONodeManual || {};
    window.FONodeManual[app] = window.FONodeManual[app] || [];
    var sel = cssPath($el[0]);
    window.FONodeManual[app].push({ sel: sel, kind: kind, port: port, label: ($el.text().trim().slice(0, 24) || port) });
    saveAnchors();
    toast((kind === 'in' ? 'Вход' : 'Выход') + ' создан: ' + app);
  }

  function removeManualAnchors($el) {
    var $w = $el.closest('.window');
    var app = $w.attr('data-app');
    var map = window.FONodeManual || {};
    if (!app || !map[app]) { toast('Ручных якорей здесь нет'); return; }
    var sel = cssPath($el[0]);
    map[app] = map[app].filter(function (a) { return a.sel !== sel; });
    $el.removeAttr('data-fo-action').removeAttr('data-fo-event').removeAttr('data-fo-in').removeAttr('data-fo-out');
    saveAnchors();
    toast('Якорь убран');
  }

  function showAnchorMenu(x, y, $el) {
    $('#fo-node-menu').remove();
    var $m = $('<div id="fo-node-menu"></div>').css({
      position: 'absolute', left: x, top: y, zIndex: 100060, minWidth: 200, padding: '5px',
      background: 'rgba(24,24,26,0.97)', border: '1px solid rgba(255,255,255,0.14)', borderRadius: '9px',
      color: '#f2f2f4', boxShadow: '0 14px 38px rgba(0,0,0,.5)',
      font: '500 12.5px/1 -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif'
    }).appendTo('body');

    function item(label, fn) {
      $('<div></div>').text(label).css({ padding: '8px 12px', borderRadius: '7px', cursor: 'default' })
        .hover(function () { $(this).css('background', 'rgba(255,255,255,.12)'); },
               function () { $(this).css('background', 'transparent'); })
        .on('click', function () { $m.remove(); fn(); })
        .appendTo($m);
    }
    item('Создать вход (принимать)', function () { addManualAnchor($el, 'in'); });
    item('Создать выход (отдавать)', function () { addManualAnchor($el, 'out'); });
    item('Убрать якоря здесь', function () { removeManualAnchors($el); });

    var w = $m.outerWidth() || 200, h = $m.outerHeight() || 100;
    $m.css({ left: Math.max(8, Math.min(x, $(window).width() - w - 8)), top: Math.max(8, Math.min(y, $(window).height() - h - 8)) });
    setTimeout(function () {
      $(document).one('mousedown', function (e) { if (!$(e.target).closest('#fo-node-menu').length) { $m.remove(); } });
    }, 0);
  }

  /* ---------- workspaces ------------------------------------------------- */
  function api(action, data) {
    var body = new URLSearchParams();
    body.append('action', action);
    Object.keys(data || {}).forEach(function (k) { body.append(k, data[k]); });
    return fetch('system/core/functions/nodes.php', { method: 'POST', credentials: 'same-origin', body: body })
      .then(function (r) { return r.json(); });
  }

  function currentWindows() {
    var out = [];
    $('.window[data-app]').each(function () {
      var $w = $(this);
      var box = $w[0].getBoundingClientRect();
      var loc = $w.find('.process-container').attr('location') || '';
      var m = loc.match(/[?&](?:defaultloader|rteloader|open)=([^&]*)/);
      out.push({
        app: $w.attr('data-app'),
        x: Math.round(box.left), y: Math.round(box.top),
        w: Math.round(box.width), h: Math.round(box.height),
        param: m ? decodeURIComponent(m[1]) : ''
      });
    });
    return out;
  }

  function renderList(list) {
    var $box = $panel.find('.fo-np-list');
    if (!list || !list.length) {
      $box.html('<div class="fo-np-empty">Пространств пока нет</div>');
      return;
    }
    $box.html(list.map(function (n) {
      return '<div class="fo-np-item"><span>' + $('<div/>').text(n.name).html() + '</span>' +
        '<button type="button" data-run="' + encodeURIComponent(n.name) + '">Запустить</button>' +
        '<button type="button" data-del="' + encodeURIComponent(n.name) + '">×</button></div>';
    }).join(''));
  }

  function refreshList() {
    api('list', {}).then(function (r) { renderList(r && r.ok ? r.items : []); });
  }

  function saveWorkspace() {
    var name = String($panel.find('.fo-np-name').val() || '').trim();
    if (name === '') { name = 'workspace-' + new Date().toISOString().slice(0, 16).replace(/[:T]/g, ''); }
    api('save', { name: name, data: JSON.stringify({ windows: currentWindows(), links: state.links }) })
      .then(function (r) {
        toast(r && r.ok ? 'Пространство «' + name + '» сохранено' : 'Не удалось сохранить');
        refreshList();
      });
  }

  function loadWorkspace(name) {
    api('load', { name: name }).then(function (r) {
      if (!r || !r.ok) { toast('Не удалось загрузить'); return; }
      var data = r.data || {};
      state.links = data.links || [];
      (data.windows || []).forEach(function (win, i) {
        setTimeout(function () {
          var $w = winFor(win.app);
          if ($w) {
            $w.css({ left: win.x + 'px', top: win.y + 'px', width: win.w + 'px', height: win.h + 'px' });
            return;
          }
          if (typeof window.makeprocess === 'function') {
            window.makeprocess('system/apps/' + win.app + '/main.php', win.param || '', win.param ? 'defaultloader' : '', win.app);
          }
        }, 250 * i);
      });
      toast('Пространство «' + name + '» запущено');
    });
  }

  function toast(msg) {
    if (window.FO_TOAST) { window.FO_TOAST(msg); }
  }

  /* ---------- mode ------------------------------------------------------- */
  function on() {
    if (state.on) { return; }
    state.on = true;
    $('body').addClass('fo-node-mode');
    ensureLayer();
    $layer.addClass('is-on');
    applyMode();
    refreshList();
    loadAnchors();
    if (state.anchorTimer) { clearInterval(state.anchorTimer); }
    state.anchorTimer = setInterval(applyAnchors, 1500);
    state.raf = requestAnimationFrame(tick);
  }

  function off() {
    if (!state.on) { return; }
    state.on = false;
    state.drag = null;
    if (state.anchorTimer) { clearInterval(state.anchorTimer); state.anchorTimer = null; }
    $('body').removeClass('fo-node-mode');
    if ($layer) { $layer.removeClass('is-on'); $layer.find('.fo-node-anchor').remove(); $svg.html(''); }
    if (state.raf) { cancelAnimationFrame(state.raf); state.raf = null; }
  }

  function toggle() { if (state.on) { off(); } else { on(); } }

  /* ---------- events ----------------------------------------------------- */
  $(document).on('mousedown', '.fo-node-anchor.fo-node-out', function (e) { startDrag(e, $(this)); });
  $(document).on('mousemove', function (e) { if (state.drag) { state.drag.at = { x: e.pageX, y: e.pageY }; } });
  $(document).on('mouseup', function (e) { if (state.drag) { finishDrag(e); } });

  /* клик по элементу-событию отправляет значение по связям */
  $(document).on('click', '.window [data-fo-event]', function () {
    if (!state.on) { return; }
    var $w = $(this).closest('.window');
    var app = $w.attr('data-app');
    if (!app) { return; }
    var val = $(this).attr('data-fo-value') || $(this).attr('data-path') || $(this).text().trim().slice(0, 80) || 'click';
    emit(app, $(this).attr('data-fo-event') || 'click', val);
  });

  /* правый клик по элементу в node-режиме: сделать его портом */
  $(document).on('contextmenu', '.window', function (e) {
    if (!state.on) { return; }
    var $el = $(e.target);
    if ($el.closest('#fo-node-layer, .fo-node-anchor, .dragwindow, .appwindowbutton').length) { return; }
    e.preventDefault();
    e.stopPropagation();
    showAnchorMenu(e.pageX, e.pageY, $el);
  });

  $(document).on('click', '.fo-node-wire-dot', function (e) {
    e.stopPropagation();
    var i = parseInt($(this).attr('data-i'), 10);
    if (isNaN(i)) { return; }
    var l = state.links[i];
    state.links.splice(i, 1);
    if (l) { toast('Связь удалена'); }
  });

  $(document).on('click', '[data-np]', function () {
    var act = $(this).attr('data-np');
    if (act === 'save') { saveWorkspace(); }
    else if (act === 'load') { refreshList(); }
    else if (act === 'clear') { state.links = []; toast('Связи очищены'); }
    else if (act === 'run') { runQueue(); }
    else if (act === 'off') { off(); }
  });

  $(document).on('click', '[data-np-mode]', function () {
    state.mode = $(this).attr('data-np-mode');
    try { localStorage.setItem('fo-node-mode', state.mode); } catch (e) {}
    applyMode();
    toast(state.mode === 'auto' ? 'Авто: значения идут сразу' : 'По запуску: значения копятся в очереди');
  });

  $(document).on('click', '[data-run]', function () { loadWorkspace(decodeURIComponent($(this).attr('data-run'))); });

  $(document).on('click', '[data-del]', function () {
    var name = decodeURIComponent($(this).attr('data-del'));
    api('delete', { name: name }).then(function () { toast('Удалено'); refreshList(); });
  });

  $(document).on('keydown', function (e) {
    if (e.ctrlKey && e.shiftKey && (e.key === 'N' || e.key === 'n')) { e.preventDefault(); toggle(); }
    else if (e.key === 'Escape' && state.on) { off(); }
  });

  window.FONode = {
    toggle: toggle, on: on, off: off,
    emit: emit, deliver: deliver,
    run: runQueue,
    setMode: function (m) { state.mode = (m === 'manual') ? 'manual' : 'auto'; applyMode(); },
    mode: function () { return state.mode; },
    queue: function () { return state.queue; },
    isOn: function () { return state.on; },
    links: function () { return state.links; },
    register: function (app, def) { PORTS[app] = def || {}; }
  };
})();
