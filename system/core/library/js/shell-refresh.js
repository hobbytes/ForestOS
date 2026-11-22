/* ==========================================================================
   Forest OS — динамическое обновление рабочего стола и дока
   --------------------------------------------------------------------------
   Любое изменение (создание папки/файла, удаление ярлыка, закрепление в доке)
   подхватывается без перезагрузки: оболочка перерисовывает нужный слой из
   свежего HTML страницы и заново навешивает обработчики.
   ========================================================================== */
(function () {
  'use strict';

  var busy = false;

  function fetchShell() {
    return fetch('os.php?nocache=' + Date.now(), { credentials: 'same-origin' })
      .then(function (r) { return r.text(); })
      .then(function (html) {
        return new DOMParser().parseFromString(html, 'text/html');
      });
  }

  function runScripts(root) {
    if (!root) { return; }
    var scripts = root.querySelectorAll('script');
    for (var i = 0; i < scripts.length; i++) {
      var code = scripts[i].textContent || '';
      if (!code.trim()) { continue; }
      try { (0, eval)(code); } catch (e) { /* один битый обработчик не должен ломать всё */ }
    }
  }

  /* ---------- док -------------------------------------------------------- */
  function refreshDock(doc) {
    var fresh = doc ? doc.querySelector('.dock-container') : null;
    var current = document.querySelector('.dock-container');
    if (!fresh || !current) { return; }
    current.innerHTML = fresh.innerHTML;
    var added = document.createElement('span');
    runScripts(fresh);
    if (window.FO_DOCK_SYNC) { window.FO_DOCK_SYNC(); }
  }

  window.FO_REFRESH_DOCK = function () {
    return fetchShell().then(function (doc) { refreshDock(doc); });
  };

  /* ---------- рабочий стол ---------------------------------------------- */
  function refreshDesktop(doc) {
    var fresh = doc ? doc.querySelector('#desktops .desktop') : null;
    var current = document.querySelector('#desktops .desktop');
    if (!fresh || !current) { return; }
    current.innerHTML = fresh.innerHTML;
    runScripts(current);
    if (window.FO_DESKTOP_REFRESH) { window.FO_DESKTOP_REFRESH(); }
    else {
      var ev = document.createEvent('Event');
      ev.initEvent('resize', true, true);
      window.dispatchEvent(ev);
    }
  }

  window.FO_REFRESH_DESKTOP = function () {
    return fetchShell().then(function (doc) { refreshDesktop(doc); });
  };

  window.FO_REFRESH_SHELL = function () {
    if (busy) { return Promise.resolve(); }
    busy = true;
    return fetchShell()
      .then(function (doc) {
        refreshDock(doc);
        refreshDesktop(doc);
      })
      .catch(function () {})
      .then(function () { busy = false; });
  };

  /* ---------- создание папки / файла ------------------------------------ */
  function askName(title, placeholder, cb) {
    var $m = $(
      '<div id="fo-newdlg" class="fo-newdlg">' +
        '<div class="fo-newdlg-card">' +
          '<div class="fo-newdlg-title">' + title + '</div>' +
          '<input type="text" class="fo-newdlg-input" placeholder="' + (placeholder || '') + '" maxlength="60">' +
          '<div class="fo-newdlg-actions">' +
            '<button type="button" data-nd="cancel">Отмена</button>' +
            '<button type="button" data-nd="ok" class="primary">Создать</button>' +
          '</div>' +
        '</div>' +
      '</div>'
    ).appendTo('body');

    var $input = $m.find('.fo-newdlg-input');
    setTimeout(function () { $input.trigger('focus'); }, 30);

    function close() { $m.remove(); }
    function ok() {
      var v = String($input.val() || '').trim();
      if (!v) { $input.trigger('focus'); return; }
      close();
      cb(v);
    }
    $m.on('click', function (e) { if (e.target === this) { close(); } });
    $m.find('[data-nd="cancel"]').on('click', close);
    $m.find('[data-nd="ok"]').on('click', ok);
    $input.on('keydown', function (e) {
      if (e.key === 'Enter') { ok(); }
      if (e.key === 'Escape') { close(); }
    });
  }

  function createItem(action, title, placeholder, defaultName) {
    askName(title, placeholder, function (name) {
      var body = new URLSearchParams();
      body.append('action', action);
      body.append('name', name);
      fetch('system/core/functions/desktop-new.php', { method: 'POST', credentials: 'same-origin', body: body })
        .then(function (r) { return r.json(); })
        .then(function (j) {
          if (!j || !j.ok) {
            var msg = (j && j.error === 'exists') ? 'Такое имя уже занято' : 'Не удалось создать';
            if (window.FO_TOAST) { window.FO_TOAST(msg); }
            return;
          }
          if (window.FO_TOAST) { window.FO_TOAST((j.kind === 'folder' ? 'Папка «' : 'Файл «') + j.name + '» создан' + (j.kind === 'folder' ? 'а' : '')); }
          window.FO_REFRESH_DESKTOP();
        })
        .catch(function () { if (window.FO_TOAST) { window.FO_TOAST('Ошибка сети'); } });
    });
    if (defaultName) { /* подсказка в placeholder */ }
  }

  window.FO_DESKTOP_NEW_FOLDER = function () { createItem('create-folder', 'Новая папка', 'например, Проекты'); };
  window.FO_DESKTOP_NEW_FILE = function () { createItem('create-file', 'Новый файл', 'например, заметка.txt'); };

  /* ---------- пункты меню рабочего стола --------------------------------- */
  function addDesktopMenuItems() {
    if (!window.FO_ADD_DESKTOP_MENU_ITEMS) { return; }
  }

  /* подключаемся к меню рабочего стола после его построения */
  $(document).on('contextmenu', function () {
    /* сам пункт добавляется в desktop-layout.js напрямую */
  });

  /* ---------- авто-обновление после изменений ---------------------------- */
  window.FO_AFTER_CHANGE = function () {
    return window.FO_REFRESH_SHELL();
  };
})();
