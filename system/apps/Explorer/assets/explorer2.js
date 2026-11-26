/* ==========================================================================
   Forest OS — Explorer 2.0 (Finder-style) client
   ========================================================================== */
(function () {
  'use strict';

  var KINDS_TEXT = ['text', 'code'];

  function esc(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  /* ---------- localization ------------------------------------------------
     The dictionary is emitted by main.php as window.FO_EXPLORER_I18N for the
     current session locale (see assets/lang/<locale>.lang). No visible text
     lives in this file: every label comes from T()/TP(). */
  var I18N_ROOT = window.FO_EXPLORER_I18N || {};
  var LOCALE = I18N_ROOT.locale || 'ru';
  var DICT = I18N_ROOT.strings || window.FO_EX || {};

  function T(key, vars) {
    var text = DICT[key];
    if (text === undefined || text === null || text === '') text = key;
    text = String(text);
    if (vars) {
      Object.keys(vars).forEach(function (name) {
        text = text.split('{' + name + '}').join(String(vars[name]));
      });
    }
    return text;
  }

  function pluralForm(n) {
    n = Math.abs(parseInt(n, 10) || 0);
    if (LOCALE === 'en') return n === 1 ? 'one' : 'many';
    if (n % 10 === 1 && n % 100 !== 11) return 'one';
    if (n % 10 >= 2 && n % 10 <= 4 && (n % 100 < 12 || n % 100 > 14)) return 'few';
    return 'many';
  }

  /* TP(count, 'items') → "объект" / "объекта" / "объектов" (or item/items). */
  function TP(n, base, vars) {
    var key = base + '_' + pluralForm(n);
    if (DICT[key] === undefined) key = base + '_many';
    var data = vars || {};
    data.n = n;
    return T(key, data);
  }

  function init(root) {
    if (!root || root.getAttribute('data-ready') === '1') return;
    root.setAttribute('data-ready', '1');

    var endpoint = root.getAttribute('data-endpoint');
    var token = root.getAttribute('data-token');
    var appname = root.getAttribute('data-appname') || 'Explorer';
    var appid = root.getAttribute('data-appid') || '0';
    var mobile = root.getAttribute('data-mobile') || 'false';
    var home = root.getAttribute('data-home') || '';
    var apps = [];
    try { apps = JSON.parse(root.getAttribute('data-apps') || '[]'); } catch (e) { apps = []; }

    var mode = root.getAttribute('data-mode') || '';
    var callback = root.getAttribute('data-callback') || '';
    var showonly = root.getAttribute('data-showonly') || '';

    var main = root.querySelector('.fo-main');
    var modal = root.querySelector('.fo-modal');
    var menu = root.querySelector('.fo-menu');
    var toastEl = root.querySelector('.fo-toast');
    var fileInput = root.querySelector('.fo-file');
    var sidebar = root.querySelector('.fo-sidebar');

    var state = {
      view: 'icons',
      history: [],
      index: -1,
      selection: [],
      lastIndex: -1,
      clipboard: null,
      query: '',
      multi: false,
      favs: []
    };
    try { state.favs = JSON.parse(root.getAttribute('data-favs') || '[]'); } catch (e) { state.favs = []; }

    /* ---------- helpers -------------------------------------------------- */
    function content() { return root.querySelector('.fo-content'); }
    function currentDir() {
      var c = content();
      return c ? c.getAttribute('data-dir') : '';
    }
    function parentDir() {
      var c = content();
      return c ? c.getAttribute('data-parent') : '';
    }
    function items() {
      return Array.prototype.slice.call(root.querySelectorAll('.fo-item'));
    }
    function selectedItems() {
      return items().filter(function (el) {
        return state.selection.indexOf(el.getAttribute('data-path')) !== -1;
      });
    }
    function url(params) {
      var parts = [
        'appname=' + encodeURIComponent(appname),
        'appid=' + encodeURIComponent(appid),
        'mobile=' + encodeURIComponent(mobile)
      ];
      if (mode) parts.push('explorermode=' + encodeURIComponent(mode));
      if (callback) parts.push('callback=' + encodeURIComponent(callback));
      if (showonly) parts.push('showonly=' + encodeURIComponent(showonly));
      for (var k in params) {
        if (Object.prototype.hasOwnProperty.call(params, k)) {
          parts.push(k + '=' + encodeURIComponent(params[k]));
        }
      }
      return endpoint + '?' + parts.join('&');
    }
    function toast(message, isError) {
      if (!toastEl) return;
      toastEl.textContent = message;
      toastEl.classList.toggle('is-error', !!isError);
      toastEl.hidden = false;
      clearTimeout(toastEl._timer);
      toastEl._timer = setTimeout(function () { toastEl.hidden = true; }, 2600);
    }

    /* ---------- selection ------------------------------------------------ */
    function setSelection(paths) {
      state.selection = paths.slice();
      items().forEach(function (el) {
        el.classList.toggle('is-selected', state.selection.indexOf(el.getAttribute('data-path')) !== -1);
      });
    }

    function selectItem(el, ev) {
      var all = items();
      var idx = all.indexOf(el);
      if (!el) { setSelection([]); return; }
      var path = el.getAttribute('data-path');

      if (ev && (ev.metaKey || ev.ctrlKey)) {
        var pos = state.selection.indexOf(path);
        if (pos === -1) state.selection.push(path);
        else state.selection.splice(pos, 1);
        state.lastIndex = idx;
        setSelection(state.selection);
        return;
      }
      if (ev && ev.shiftKey && state.lastIndex >= 0) {
        var a = Math.min(state.lastIndex, idx);
        var b = Math.max(state.lastIndex, idx);
        var range = [];
        for (var i = a; i <= b; i++) range.push(all[i].getAttribute('data-path'));
        setSelection(range);
        return;
      }
      state.lastIndex = idx;
      setSelection([path]);
    }

    /* ---------- navigation ---------------------------------------------- */
    function syncSidebar() {
      if (!sidebar) return;
      var dir = currentDir();
      Array.prototype.forEach.call(root.querySelectorAll('.fo-side-item[data-nav], .fo-mnav-item[data-nav]'), function (b) {
        b.classList.toggle('is-active', b.getAttribute('data-nav') === dir);
      });
    }

    function applyView() {
      var c = content();
      if (!c) return;
      c.classList.toggle('is-icons', state.view !== 'list');
      c.classList.toggle('is-list', state.view === 'list');
      Array.prototype.forEach.call(root.querySelectorAll('[data-view]'), function (b) {
        b.classList.toggle('is-on', b.getAttribute('data-view') === state.view);
      });
    }

    function applyQuery() {
      var q = state.query.trim().toLowerCase();
      items().forEach(function (el) {
        var name = (el.getAttribute('data-name') || '').toLowerCase();
        el.style.display = (!q || name.indexOf(q) !== -1) ? '' : 'none';
      });
    }

    function load(dir, push) {
      if (!dir) dir = currentDir();
      if (!dir) return;
      fetch(url({ partial: '1', dir: dir, view: state.view }), { credentials: 'same-origin' })
        .then(function (r) { return r.text(); })
        .then(function (html) {
          main.innerHTML = html;
          if (push !== false) {
            state.history = state.history.slice(0, state.index + 1);
            if (state.history[state.history.length - 1] !== dir) state.history.push(dir);
            state.index = state.history.length - 1;
          }
          state.selection = [];
          state.lastIndex = -1;
          state.query = '';
          applyView();
          syncSidebar();
          maybeAutoOpen();
        })
        .catch(function () { toast(T('err_load'), true); });
    }

    /* a shortcut to a file opens the Explorer with ?open=<path> */
    var pendingOpen = root.getAttribute('data-open') || '';
    function maybeAutoOpen() {
      if (!pendingOpen) return;
      var target = pendingOpen;
      pendingOpen = '';
      var base = target.split('/').pop();
      var el = main.querySelector('[data-path="' + String(target).replace(/"/g, '\\"') + '"]') ||
               main.querySelector('[data-path$="/' + String(base).replace(/"/g, '\\"') + '"]') ||
               main.querySelector('[data-name="' + String(base).replace(/"/g, '\\"') + '"]');
      if (el) { setTimeout(function () { openFile(el); }, 80); }
    }

    /* re-render the desktop icons after a shortcut was created */
    function refreshDesktop() {
      if (!window.jQuery) return;
      var box = document.getElementById('desktop-1');
      if (!box) return;
      window.jQuery.get('system/core/functions/desktop-fragment.php', function (html) {
        window.jQuery(box).html(html);
        if (typeof window.FO_DESKTOP_REFRESH === 'function') { window.FO_DESKTOP_REFRESH(); }
      });
    }

    function back() {
      if (state.index > 0) {
        state.index--;
        load(state.history[state.index], false);
      }
    }
    function forward() {
      if (state.index < state.history.length - 1) {
        state.index++;
        load(state.history[state.index], false);
      }
    }

    /* ---------- server calls -------------------------------------------- */
    function callAction(name, data) {
      var fd = new FormData();
      fd.append('action', name);
      fd.append('token', token || '');
      fd.append('dir', currentDir());
      if (data) {
        for (var k in data) {
          if (!Object.prototype.hasOwnProperty.call(data, k)) continue;
          var v = data[k];
          if (v === undefined || v === null) continue;
          if (Array.isArray(v)) {
            v.forEach(function (one) { fd.append(k + '[]', one); });
          } else {
            fd.append(k, v);
          }
        }
      }
      return fetch(url({}), { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function (r) {
          return r.json().catch(function () { return { ok: false, error: T('bad_response') }; });
        });
    }

    function runAction(name, data, successText) {
      return callAction(name, data).then(function (res) {
        if (res && res.ok) {
          toast(successText || T('done'));
          load(currentDir(), false);
          if (name === 'link') refreshDesktop();
        } else {
          toast((res && res.error) || T('err_action'), true);
        }
        return res;
      });
    }

    /* ---------- modal ---------------------------------------------------- */
    function closeModal() {
      if (modal) { modal.hidden = true; modal.innerHTML = ''; }
    }

    function openModal(opts) {
      if (!modal) return;
      var html = '<div class="fo-modal-card" role="dialog" aria-modal="true">';
      html += '<h2 class="fo-modal-title">' + esc(opts.title || '') + '</h2>';
      if (opts.text) html += '<p class="fo-modal-text">' + esc(opts.text) + '</p>';
      if (opts.contentHtml) html += opts.contentHtml;
      if (opts.input) {
        html += '<input class="fo-modal-input" type="text" value="' + esc(opts.value || '') +
                '" placeholder="' + esc(opts.placeholder || '') + '">';
      }
      html += '<div class="fo-modal-buttons">';
      if (opts.cancel !== false) {
        html += '<button type="button" class="fo-btn" data-modal-cancel>' + esc(opts.cancelText || T('cancel')) + '</button>';
      }
      if (opts.ok !== false) {
        html += '<button type="button" class="fo-btn ' + (opts.danger ? 'fo-btn-danger' : 'fo-btn-primary') +
                '" data-modal-ok>' + esc(opts.okText || T('ok')) + '</button>';
      }
      html += '</div></div>';
      modal.innerHTML = html;
      modal.hidden = false;

      var input = modal.querySelector('.fo-modal-input');
      if (input) {
        input.focus();
        var dot = input.value.lastIndexOf('.');
        if (dot > 0 && !opts.inputFull) input.setSelectionRange(0, dot);
        else input.select();
      }
      var okBtn = modal.querySelector('[data-modal-ok]');
      if (okBtn) {
        okBtn.addEventListener('click', function () {
          var value = input ? input.value : null;
          closeModal();
          if (opts.onOk) opts.onOk(value);
        });
      }
      var cancelBtn = modal.querySelector('[data-modal-cancel]');
      if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
      if (input) {
        input.addEventListener('keydown', function (e) {
          if (e.key === 'Enter') { e.preventDefault(); if (okBtn) okBtn.click(); }
        });
      }
    }

    /* ---------- context menu -------------------------------------------- */
    function closeMenu() {
      if (menu) { menu.hidden = true; menu.innerHTML = ''; }
    }

    function menuItem(cmd, label, shortcut, danger) {
      return '<div class="fo-menu-item' + (danger ? ' is-danger' : '') + '" data-menu="' + esc(cmd) + '">' +
             '<span>' + esc(label) + '</span>' +
             (shortcut ? '<span class="fo-menu-shortcut">' + esc(shortcut) + '</span>' : '') +
             '</div>';
    }

    function showMenu(x, y, html) {
      if (!menu) return;
      menu.innerHTML = html;
      menu.hidden = false;
      var rr = root.getBoundingClientRect();
      var mw = menu.offsetWidth;
      var mh = menu.offsetHeight;
      var left = x - rr.left;
      var top = y - rr.top;
      if (left + mw > rr.width - 6) left = rr.width - mw - 6;
      if (top + mh > rr.height - 6) top = rr.height - mh - 6;
      if (left < 6) left = 6;
      if (top < 6) top = 6;
      menu.style.left = left + 'px';
      menu.style.top = top + 'px';
    }

    function fileMenuFor(el) {
      var isDir = el.getAttribute('data-isdir') === '1';
      var ext = (el.getAttribute('data-ext') || '').toLowerCase();
      var html = '';
      html += menuItem('open', T('open'), '⌘O');
      if (!isDir) html += menuItem('openwith', T('open_with'), '›');
      if (!isDir && ext === 'php') html += menuItem('viewcode', T('view_code'), '');
      html += '<div class="fo-menu-sep"></div>';
      html += menuItem('copy', T('copy'), '⌘C');
      html += menuItem('cut', T('cut'), '⌘X');
      if (state.clipboard) html += menuItem('paste', T('paste'), '⌘V');
      html += '<div class="fo-menu-sep"></div>';
      html += menuItem('rename', T('rename'), '↩');
      html += menuItem('info', T('info'), '⌘I');
      html += menuItem('zip', T('compress'), '');
      if (ext === 'zip') html += menuItem('unzip', T('extract'), '');
      html += menuItem('link', T('shortcut'), '');
      if (isDir) {
        html += menuItem('favadd', T('fav_add'), '');
        html += menuItem('pinFolder', T('pin_dock'), '');
      }
      if (!isDir) html += menuItem('download', T('download'), '');
      html += '<div class="fo-menu-sep"></div>';
      html += menuItem('delete', T('move_trash'), '⌘⌫');
      html += menuItem('deleteperm', T('delete_forever'), '⇧⌘⌫', true);
      return html;
    }

    function emptyMenu() {
      var html = '';
      html += menuItem('mkdir', T('new_folder'), '⇧⌘N');
      html += menuItem('newfile', T('new_file'), '');
      html += menuItem('upload', T('upload_menu'), '');
      if (state.clipboard) html += menuItem('paste', T('paste'), '⌘V');
      html += '<div class="fo-menu-sep"></div>';
      html += menuItem('selectall', T('select_all'), '⌘A');
      if (currentDir() && currentDir().replace(/\/$/, '') === home + '/trash') {
        html += '<div class="fo-menu-sep"></div>';
        html += menuItem('emptytrash', T('empty_trash'), '', true);
      }
      return html;
    }

    /* apps receive paths relative to the web root ("/system/users/...") */
    function relPath(p) {
      var docroot = root.getAttribute('data-docroot') || '';
      p = String(p || '');
      if (docroot && p.indexOf(docroot) === 0) { return p.slice(docroot.length); }
      return p.charAt(0) === '/' ? p : '/' + p;
    }

    /* ---------- preview / open ------------------------------------------ */
    function openFile(el) {
      var kind = el.getAttribute('data-kind');
      var ext = (el.getAttribute('data-ext') || '').toLowerCase();
      var name = el.getAttribute('data-name');
      var path = el.getAttribute('data-path');

      /* node mode: the opened file becomes an output of the Explorer */
      if (window.FONode && window.FONode.isOn()) {
        try { window.FONode.emit('Explorer', 'path', relPath(path)); } catch (e) {}
      }

      /* php-файл внутри system/apps/<App>/ — это приложение: запускаем его */
      var appMatch = path.match(/\/system\/apps\/([^/]+)\/([^/]+\.php)$/i);
      if (appMatch && typeof window.makeprocess === 'function') {
        window.makeprocess('system/apps/' + appMatch[1] + '/' + appMatch[2], '', '', appMatch[1]);
        return;
      }

      /* зарегистрированный обработчик расширения (Rich Text Editor и т.п.) */
      var assoc = {};
      try { assoc = JSON.parse(root.getAttribute('data-assoc') || '{}') || {}; } catch (e) { assoc = {}; }
      var handler = assoc[ext];
      if (handler && handler.app && typeof window.makeprocess === 'function') {
        var parts = handler.app.split('/').filter(Boolean);
        var appLabel = parts.length >= 2 ? parts[parts.length - 2] : 'App';
        window.makeprocess(handler.app, relPath(path), handler.key || 'defaultloader', appLabel);
        return;
      }

      if (kind === 'image') {
        openModal({
          title: name,
          contentHtml: '<img class="fo-preview" alt="" src="' + esc(url({ action: 'raw', path: path })) + '">',
          ok: false, cancelText: T('close')
        });
        return;
      }
      if (KINDS_TEXT.indexOf(kind) !== -1) {
        fetch(url({ action: 'text', path: path }), { credentials: 'same-origin' })
          .then(function (r) { if (!r.ok) throw new Error(); return r.text(); })
          .then(function (text) {
            openModal({
              title: name,
              contentHtml: '<pre class="fo-pre">' + esc(text) + '</pre>',
              ok: false, cancelText: T('close')
            });
          })
          .catch(function () { toast(T('err_read'), true); });
        return;
      }
      if (ext === 'zip') {
        openModal({
          title: name,
          text: T('unzip_confirm'),
          okText: T('extract'),
          onOk: function () { runAction('unzip', { paths: [path] }, T('unzip_done')); }
        });
        return;
      }
      window.location.href = url({ action: 'raw', path: path, download: '1' });
    }

    function viewCode(el) {
      var path = el.getAttribute('data-path');
      fetch(url({ action: 'text', path: path }), { credentials: 'same-origin' })
        .then(function (r) { if (!r.ok) throw new Error(); return r.text(); })
        .then(function (text) {
          openModal({
            title: el.getAttribute('data-name'),
            contentHtml: '<pre class="fo-pre">' + esc(text) + '</pre>',
            ok: false, cancelText: T('close')
          });
        })
        .catch(function () { toast(T('err_read'), true); });
    }

    function openWith(el) {
      if (!apps.length) { toast(T('no_apps'), true); return; }
      var path = el.getAttribute('data-path');
      var html = '<div class="fo-applist">';
      apps.forEach(function (a, i) {
        html += '<button type="button" class="fo-approw" data-app="' + i + '">' + esc(a.name) + '</button>';
      });
      html += '</div>';
      openModal({ title: T('open_with'), contentHtml: html, ok: false, cancelText: T('cancel') });
      Array.prototype.forEach.call(modal.querySelectorAll('[data-app]'), function (b) {
        b.addEventListener('click', function () {
          var a = apps[parseInt(b.getAttribute('data-app'), 10)];
          closeModal();
          if (a && typeof window.makeprocess === 'function') {
            window.makeprocess(a.path, relPath(path), 'defaultloader', a.name);
          } else {
            toast(T('shell_unavailable'), true);
          }
        });
      });
    }

    function showInfo(el) {
      var path = el.getAttribute('data-path');
      fetch(url({ action: 'info', path: path }), { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          if (!d || !d.ok) { toast(T('err_info'), true); return; }
          var rows = [
            [T('prop_name'), d.name],
            [T('prop_type'), d.kind],
            [T('prop_size'), d.size_text + (d.dir ? ' (' + d.count + ' ' + TP(d.count, 'files') + ')' : '')],
            [T('prop_modified'), d.mtime],
            [T('prop_created'), d.ctime],
            [T('prop_perms'), d.perms],
            [T('prop_path'), d.path]
          ];
          var html = '<div class="fo-info">';
          rows.forEach(function (r) {
            html += '<div class="fo-info-row"><span>' + esc(r[0]) + '</span><b>' + esc(r[1]) + '</b></div>';
          });
          html += '</div>';
          html += '<div class="fo-qrblock"><div class="fo-qr"></div><div class="fo-qrpath">' + esc(d.url || d.path) + '</div></div>';
          openModal({ title: T('prop_title'), contentHtml: html, ok: false, cancelText: T('close') });
          try {
            if (window.qrcode) {
              var qr = window.qrcode(0, 'M');
              qr.addData(d.url || d.path);
              qr.make();
              var qrBox = modal.querySelector('.fo-qr');
              if (qrBox) qrBox.innerHTML = qr.createSvgTag({ cellSize: 3, margin: 2 });
            }
          } catch (e) { /* QR is optional */ }
        })
        .catch(function () { toast(T('error'), true); });
    }

    /* ---------- upload --------------------------------------------------- */
    /* Файл уходит кусками по 4 МБ: видно прогресс, работает отмена и не
       упираемся в post_max_size сервера (64 МБ на запрос). */
    var UPLOAD_CHUNK = 4 * 1024 * 1024;
    var uploadJob = null;

    function humanSize(n) {
      n = Number(n) || 0;
      if (n < 1024) return n + ' ' + T('unit_b');
      if (n < 1024 * 1024) return (n / 1024).toFixed(0) + ' ' + T('unit_kb');
      if (n < 1024 * 1024 * 1024) return (n / (1024 * 1024)).toFixed(1) + ' ' + T('unit_mb');
      return (n / (1024 * 1024 * 1024)).toFixed(2) + ' ' + T('unit_gb');
    }

    function uploadPanel() {
      var old = document.getElementById('fo-upload-panel');
      if (old) old.remove();
      var el = document.createElement('div');
      el.id = 'fo-upload-panel';
      el.className = 'fo-up';
      el.innerHTML = '<div class="fo-up-head"><span class="fo-up-title">' + esc(T('upload_title')) + '</span>' +
        '<button type="button" class="fo-up-cancel">' + esc(T('cancel')) + '</button></div>' +
        '<div class="fo-up-name"></div>' +
        '<div class="fo-up-bar"><i></i></div>' +
        '<div class="fo-up-meta"></div>';
      document.body.appendChild(el);
      return el;
    }

    function uploadFiles(files) {
      if (!files || !files.length) return;
      if (uploadJob) { toast(T('upload_busy'), true); return; }

      var list = Array.prototype.slice.call(files);
      var panel = uploadPanel();
      var nameEl = panel.querySelector('.fo-up-name');
      var barEl = panel.querySelector('.fo-up-bar i');
      var metaEl = panel.querySelector('.fo-up-meta');
      var cancelBtn = panel.querySelector('.fo-up-cancel');

      var job = uploadJob = { cancelled: false, xhr: null, uid: '' };
      var totalBytes = 0;
      list.forEach(function (f) { totalBytes += f.size; });
      var doneBytes = 0;
      var index = 0;
      var started = Date.now();

      function refresh(currentLoaded) {
        var loaded = doneBytes + currentLoaded;
        var pct = totalBytes > 0 ? Math.min(100, Math.round(loaded / totalBytes * 100)) : 0;
        barEl.style.width = pct + '%';
        var secs = Math.max(0.3, (Date.now() - started) / 1000);
        var speed = loaded / secs;
        var left = speed > 0 ? Math.max(0, Math.round((totalBytes - loaded) / speed)) : 0;
        metaEl.textContent = pct + '% · ' + humanSize(loaded) + ' ' + T('upload_of') + ' ' + humanSize(totalBytes) +
          (loaded > 0 ? ' · ' + humanSize(speed) + T('upload_per_sec') : '') +
          (pct < 100 && left > 0 ? ' · ~' + (left < 60 ? left + ' ' + T('upload_sec') : Math.round(left / 60) + ' ' + T('upload_min')) : '');
      }

      function finish(err) {
        if (job.xhr) { try { job.xhr.abort(); } catch (e) {} }
        uploadJob = null;
        panel.remove();
        if (err === 'cancelled') { toast(T('upload_cancelled')); return; }
        if (err) { toast(T('upload_error', { msg: err }), true); return; }
        toast(TP(list.length, 'upload_done'));
        load(currentDir(), false);
      }

      cancelBtn.addEventListener('click', function () {
        job.cancelled = true;
        var uid = job.uid;
        if (job.xhr) { try { job.xhr.abort(); } catch (e) {} }
        if (uid) {
          var cancelUrl = url({ action: 'upload-cancel', dir: currentDir(), upload_id: uid, token: token });
          fetch(cancelUrl, { credentials: 'same-origin' }).catch(function () {});
          /* оборванный кусок может дописаться уже после abort — просим ещё раз */
          setTimeout(function () { fetch(cancelUrl, { credentials: 'same-origin' }).catch(function () {}); }, 1200);
        }
        finish('cancelled');
      });

      function nextFile() {
        if (job.cancelled) return;
        if (index >= list.length) { finish(null); return; }
        var file = list[index];
        nameEl.textContent = file.name + ' (' + (index + 1) + ' ' + T('upload_of') + ' ' + list.length + ')';
        refresh(0);
        sendFile(file, function (loaded) {
          refresh(loaded);
        }, function (err) {
          if (err) { finish(err); return; }
          doneBytes += file.size;
          index++;
          nextFile();
        });
      }

      function sendFile(file, onProgress, done) {
        var uid = job.uid = randomHex(32);
        var total = Math.max(1, Math.ceil(file.size / UPLOAD_CHUNK));
        var chunk = 0;

        function sendChunk() {
          if (job.cancelled) return;
          var start = chunk * UPLOAD_CHUNK;
          var blob = file.slice(start, Math.min(start + UPLOAD_CHUNK, file.size));
          var xhr = new XMLHttpRequest();
          job.xhr = xhr;
          xhr.open('POST', url({
            action: 'upload-chunk', dir: currentDir(), name: file.name,
            upload_id: uid, index: chunk, total: total, token: token
          }), true);
          xhr.setRequestHeader('Content-Type', 'application/octet-stream');
          xhr.upload.onprogress = function (e) {
            if (e.lengthComputable) onProgress(start + e.loaded);
          };
          xhr.onload = function () {
            var res = null;
            try { res = JSON.parse(xhr.responseText); } catch (e) {}
            if (!res || !res.ok) {
              done((res && res.error) || T('server_replied', { code: xhr.status }));
              return;
            }
            chunk++;
            if (chunk >= total) { onProgress(file.size); done(null); return; }
            sendChunk();
          };
          xhr.onerror = function () { done(T('network_error')); };
          xhr.onabort = function () { /* отмену обрабатывает кнопка */ };
          xhr.send(blob);
        }

        sendChunk();
      }

      nextFile();
    }

    function randomHex(len) {
      var out = '';
      var chars = 'abcdef0123456789';
      for (var i = 0; i < len; i++) out += chars.charAt(Math.floor(Math.random() * 16));
      return out;
    }

    /* ---------- commands ------------------------------------------------- */
    function command(cmd) {
      var sel = selectedItems();
      var first = sel[0] || null;
      var paths = sel.map(function (el) { return el.getAttribute('data-path'); });

      if (cmd === 'back') { back(); return; }
      if (cmd === 'forward') { forward(); return; }
      if (cmd === 'up') { load(parentDir()); return; }
      if (cmd === 'refresh') { load(currentDir(), false); return; }

      if (cmd === 'selectfile') {
        var pick = selectedItems()[0];
        if (!pick) { toast(T('select_first'), true); return; }
        var pickPath = pick.getAttribute('data-path');
        if (callback) {
          try {
            Array.prototype.forEach.call(document.querySelectorAll('[' + callback + ']'), function (el) {
              el.setAttribute(callback, pickPath);
              if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') el.value = pickPath;
              else el.textContent = pickPath;
            });
          } catch (e) { /* invalid attribute name */ }
        }
        var proc = document.getElementById('process' + appid);
        if (proc) proc.remove();
        if (typeof window.checkwindows === 'function') window.checkwindows();
        return;
      }
      if (cmd === 'selectmode') {
        state.multi = !state.multi;
        Array.prototype.forEach.call(root.querySelectorAll('[data-cmd="selectmode"]'), function (b) {
          b.classList.toggle('is-on', state.multi);
        });
        toast(state.multi ? T('select_mode_on') : T('select_mode_off'));
        return;
      }

      if (cmd === 'open') {
        if (!first) return;
        if (first.getAttribute('data-isdir') === '1') load(first.getAttribute('data-path'));
        else openFile(first);
        return;
      }
      if (cmd === 'openwith') { if (first) openWith(first); return; }
      if (cmd === 'viewcode') { if (first) viewCode(first); return; }

      if (cmd === 'mkdir' || cmd === 'newfile') {
        var isDir = cmd === 'mkdir';
        openModal({
          title: isDir ? T('new_folder') : T('new_file'),
          input: true,
          placeholder: isDir ? T('folder_name') : T('file_name'),
          okText: T('create'),
          inputFull: true,
          onOk: function (name) {
            if (name && name.trim()) {
              runAction(cmd, { name: name.trim() }, isDir ? T('folder_created') : T('file_created'));
            }
          }
        });
        return;
      }
      if (cmd === 'upload') { if (fileInput) fileInput.click(); return; }

      if (cmd === 'rename') {
        if (!first) return;
        var oldName = first.getAttribute('data-name');
        openModal({
          title: T('rename'),
          input: true,
          value: oldName,
          okText: T('save'),
          inputFull: true,
          onOk: function (name) {
            if (name && name.trim() && name.trim() !== oldName) {
              runAction('rename', { paths: paths, name: name.trim() }, T('renamed'));
            }
          }
        });
        return;
      }

      if (cmd === 'copy' || cmd === 'cut') {
        if (!paths.length) return;
        state.clipboard = { mode: cmd, paths: paths.slice() };
        toast(cmd === 'copy' ? T('copied', { n: paths.length }) : T('cut_toast', { n: paths.length }));
        return;
      }
      if (cmd === 'paste') {
        if (!state.clipboard) { toast(T('clipboard_empty'), true); return; }
        runAction('paste', { paths: state.clipboard.paths, mode: state.clipboard.mode },
          state.clipboard.mode === 'cut' ? T('moved_done') : T('copied_done')).then(function (res) {
          if (res && res.ok && state.clipboard && state.clipboard.mode === 'cut') state.clipboard = null;
        });
        return;
      }
      if (cmd === 'delete') {
        if (!paths.length) return;
        openModal({
          title: T('confirm_trash_title'),
          text: TP(paths.length, 'trash_text'),
          okText: T('trash_ok'), danger: true,
          onOk: function () { runAction('delete', { paths: paths }, T('trash_done')); }
        });
        return;
      }
      if (cmd === 'deleteperm') {
        if (!paths.length) return;
        openModal({
          title: T('confirm_delete_title'),
          text: T('delete_text'),
          okText: T('delete'), danger: true,
          onOk: function () { runAction('deleteperm', { paths: paths }, T('delete_done')); }
        });
        return;
      }
      if (cmd === 'emptytrash') {
        openModal({
          title: T('confirm_empty_title'),
          text: T('empty_text'),
          okText: T('empty_ok'), danger: true,
          onOk: function () { runAction('emptytrash', {}, T('empty_done')); }
        });
        return;
      }
      if (cmd === 'zip') {
        if (!paths.length) return;
        runAction('zip', { paths: paths }, T('zip_done'));
        return;
      }
      if (cmd === 'unzip') {
        if (!first) return;
        runAction('unzip', { paths: [first.getAttribute('data-path')] }, T('unzip_done'));
        return;
      }
      if (cmd === 'link') {
        if (!first) return;
        runAction('link', { paths: [first.getAttribute('data-path')] }, T('link_done'));
        return;
      }
      if (cmd === 'favadd') {
        if (!first) return;
        var favPath = first.getAttribute('data-path');
        callAction('favadd', { path: favPath }).then(function (res) {
          if (res && res.ok) {
            state.favs = res.favs || [];
            renderFavs();
            toast(T('fav_added'));
          } else {
            toast((res && res.error) || T('fav_failed'), true);
          }
        });
        return;
      }
      if (cmd === 'pinFolder') {
        if (!first) return;
        var pinPath = first.getAttribute('data-path');
        fetch('system/core/functions/dock-edit.php', {
          method: 'POST',
          credentials: 'same-origin',
          body: new URLSearchParams({ action: 'pin', folder: pinPath })
        }).then(function (r) { return r.json(); })
          .then(function (res) { toast((res && res.ok) ? T('pinned') : T('pin_failed'), !(res && res.ok)); })
          .catch(function () { toast(T('pin_failed'), true); });
        return;
      }
      if (cmd === 'download') {
        if (!first) return;
        window.location.href = url({ action: 'raw', path: first.getAttribute('data-path'), download: '1' });
        return;
      }
      if (cmd === 'info') { if (first) showInfo(first); return; }
      if (cmd === 'selectall') { setSelection(items().map(function (el) { return el.getAttribute('data-path'); })); return; }
    }

    /* ---------- favorites ------------------------------------------------ */
    function renderFavs() {
      var box = root.querySelector('.fo-favlist');
      if (!box) return;
      if (!state.favs.length) {
        box.innerHTML = '<div class="fo-fav-empty">' + esc(T('fav_empty')) + '</div>';
        return;
      }
      box.innerHTML = state.favs.map(function (f) {
        return '<button type="button" class="fo-side-item fo-fav" data-nav="' + esc(f.path) + '">' +
          '<span class="fo-fav-name">' + esc(f.name || f.path) + '</span>' +
          '<span class="fo-fav-x" data-favdel="' + esc(f.path) + '" title="' + esc(T('fav_remove')) + '">×</span></button>';
      }).join('');
    }

    /* ---------- events --------------------------------------------------- */
    root.addEventListener('click', function (e) {
      var favDel = e.target.closest ? e.target.closest('[data-favdel]') : null;
      if (favDel) {
        e.preventDefault();
        e.stopPropagation();
        var delPath = favDel.getAttribute('data-favdel');
        callAction('favdel', { path: delPath }).then(function (res) {
          if (res && res.ok) {
            state.favs = res.favs || [];
            renderFavs();
          }
        });
        return;
      }

      var nav = e.target.closest ? e.target.closest('[data-nav]') : null;
      if (nav) { closeMenu(); load(nav.getAttribute('data-nav')); return; }

      var viewBtn = e.target.closest ? e.target.closest('[data-view]') : null;
      if (viewBtn) {
        state.view = viewBtn.getAttribute('data-view');
        applyView();
        return;
      }

      var cmd = e.target.closest ? e.target.closest('[data-cmd]') : null;
      if (cmd) { closeMenu(); command(cmd.getAttribute('data-cmd')); return; }

      var menuIt = e.target.closest ? e.target.closest('[data-menu]') : null;
      if (menuIt) {
        var c = menuIt.getAttribute('data-menu');
        closeMenu();
        command(c);
        return;
      }

      if (menu && !menu.hidden) closeMenu();

      var item = e.target.closest ? e.target.closest('.fo-item') : null;
      if (item) {
        if (state.multi) {
          var p = item.getAttribute('data-path');
          var pos = state.selection.indexOf(p);
          if (pos === -1) state.selection.push(p);
          else state.selection.splice(pos, 1);
          setSelection(state.selection);
          return;
        }
        selectItem(item, e);
        return;
      }

      if (e.target.closest && e.target.closest('.fo-content')) { setSelection([]); }
    });

    root.addEventListener('dblclick', function (e) {
      var item = e.target.closest ? e.target.closest('.fo-item') : null;
      if (!item) return;
      e.preventDefault();
      if (item.getAttribute('data-isdir') === '1') load(item.getAttribute('data-path'));
      else openFile(item);
    });

    root.addEventListener('contextmenu', function (e) {
      var item = e.target.closest ? e.target.closest('.fo-item') : null;
      e.preventDefault();
      if (item) {
        if (state.selection.indexOf(item.getAttribute('data-path')) === -1) selectItem(item, null);
        showMenu(e.clientX, e.clientY, fileMenuFor(item));
      } else if (e.target.closest('.fo-content')) {
        showMenu(e.clientX, e.clientY, emptyMenu());
      }
    });

    root.addEventListener('input', function (e) {
      if (e.target.classList && e.target.classList.contains('fo-search-input')) {
        state.query = e.target.value;
        applyQuery();
      }
    });

    root.addEventListener('dragover', function (e) {
      var c = e.target.closest ? e.target.closest('.fo-content') : null;
      if (!c) return;
      e.preventDefault();
      c.classList.add('is-dropping');
    });
    root.addEventListener('dragleave', function (e) {
      var c = e.target.closest ? e.target.closest('.fo-content') : null;
      if (c) c.classList.remove('is-dropping');
    });
    root.addEventListener('drop', function (e) {
      var c = e.target.closest ? e.target.closest('.fo-content') : null;
      if (!c) return;
      e.preventDefault();
      c.classList.remove('is-dropping');
      if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) uploadFiles(e.dataTransfer.files);
    });

    if (fileInput) {
      fileInput.addEventListener('change', function () {
        if (fileInput.files && fileInput.files.length) uploadFiles(fileInput.files);
        fileInput.value = '';
      });
    }

    var lpTimer = null;
    root.addEventListener('touchstart', function (e) {
      var it = e.target.closest ? e.target.closest('.fo-item') : null;
      if (!it) return;
      var t = e.touches && e.touches[0];
      if (!t) return;
      var x = t.clientX;
      var y = t.clientY;
      lpTimer = setTimeout(function () {
        if (state.selection.indexOf(it.getAttribute('data-path')) === -1) selectItem(it, null);
        showMenu(x, y, fileMenuFor(it));
      }, 520);
    }, { passive: true });
    root.addEventListener('touchend', function () { clearTimeout(lpTimer); });
    root.addEventListener('touchmove', function () { clearTimeout(lpTimer); });

    document.addEventListener('mousedown', function (e) {
      if (menu && !menu.hidden && !menu.contains(e.target)) closeMenu();
    });

    document.addEventListener('keydown', function (e) {
      var inside = root.contains(document.activeElement) || (modal && !modal.hidden);
      if (!inside) return;
      var meta = e.metaKey || e.ctrlKey;

      if (e.key === 'Escape') {
        if (modal && !modal.hidden) closeModal();
        closeMenu();
        return;
      }
      if (modal && !modal.hidden) return;

      if (meta && (e.key === 'a' || e.key === 'A')) {
        e.preventDefault();
        command('selectall');
      } else if (meta && (e.key === 'c' || e.key === 'C')) {
        e.preventDefault(); command('copy');
      } else if (meta && (e.key === 'x' || e.key === 'X')) {
        e.preventDefault(); command('cut');
      } else if (meta && (e.key === 'v' || e.key === 'V')) {
        e.preventDefault(); command('paste');
      } else if (meta && (e.key === 'i' || e.key === 'I')) {
        e.preventDefault(); command('info');
      } else if (meta && (e.key === 'Backspace' || e.key === 'Delete')) {
        e.preventDefault();
        command(e.shiftKey ? 'deleteperm' : 'delete');
      } else if (meta && e.key === '[') {
        e.preventDefault(); back();
      } else if (meta && e.key === ']') {
        e.preventDefault(); forward();
      } else if (meta && e.key === 'ArrowUp') {
        e.preventDefault(); load(parentDir());
      } else if (meta && e.shiftKey && (e.key === 'n' || e.key === 'N')) {
        e.preventDefault(); command('mkdir');
      } else if (e.key === 'Enter' || e.key === 'F2') {
        e.preventDefault();
        command(e.key === 'F2' ? 'rename' : 'open');
      } else if (e.key === 'Delete') {
        e.preventDefault(); command('delete');
      } else if (e.key === ' ') {
        var el = selectedItems()[0];
        if (el && el.getAttribute('data-isdir') !== '1') { e.preventDefault(); openFile(el); }
      }
    });

    /* ---------- boot ----------------------------------------------------- */
    var c = content();
    if (c) {
      state.view = c.classList.contains('is-list') ? 'list' : 'icons';
      state.history = [c.getAttribute('data-dir')];
      state.index = 0;
    }
    applyView();
    syncSidebar();
    renderFavs();
    maybeAutoOpen();
  }

  window.ForestExplorer = { init: init };
})();
