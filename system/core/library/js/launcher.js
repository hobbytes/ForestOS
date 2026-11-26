/* ==========================================================================
   Forest OS — top bar behaviour: launcher (rofi-style), user popover,
   workspace dots, current-window title.
   ========================================================================== */
(function () {
  'use strict';

  var APPS = Array.isArray(window.FO_APPS) ? window.FO_APPS.slice() : [];
  var filtered = [];
  var active = 0;

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c];
    });
  }

  function $launcher() { return $('#fo-launcher'); }
  function isOpen() { return $launcher().hasClass('is-open'); }

  /* ---------------- launcher ---------------- */
  function render() {
    var q = ($('#fo-launcher-input').val() || '').trim().toLowerCase();
    filtered = APPS.filter(function (a) {
      return !q || (a.name + ' ' + (a.hint || '') + ' ' + a.id).toLowerCase().indexOf(q) >= 0;
    });
    if (active > filtered.length - 1) active = Math.max(0, filtered.length - 1);

    var $list = $('#fo-launcher-list');
    if (!filtered.length) {
      $list.html('<div class="fo-launcher-empty">' + esc(window.FO_T ? FO_T('no_results', 'Ничего не найдено') : 'Ничего не найдено') + '</div>');
      return;
    }
    $list.html(filtered.map(function (a, i) {
      return '<button type="button" class="fo-launcher-item' + (i === active ? ' is-active' : '') + '" data-i="' + i + '">' +
        '<span class="fo-launcher-icon" style="background-image:url(' + esc(a.icon) + ')"></span>' +
        '<span class="fo-launcher-text"><b>' + esc(a.name) + '</b><span>' + esc(a.hint || '') + '</span></span>' +
        '</button>';
    }).join(''));
  }

  function openLauncher() {
    closeUserMenu();
    $launcher().addClass('is-open').attr('aria-hidden', 'false');
    $('#fo-launcher-input').val('');
    active = 0;
    render();
    setTimeout(function () { $('#fo-launcher-input').trigger('focus'); }, 30);
  }

  function closeLauncher() {
    $launcher().removeClass('is-open').attr('aria-hidden', 'true');
    /* на телефоне меню забирает фокус у окна: возвращаем приложение на экран */
    if (window.FO_MB_LAUNCHER_CLOSED) { window.FO_MB_LAUNCHER_CLOSED(); }
  }

  function run(i) {
    var a = filtered[i];
    if (!a) return;
    closeLauncher();
    if (typeof window.makeprocess === 'function') {
      window.makeprocess(a.path, a.param || '', a.key || '', a.name);
    }
  }

  function scrollActive() {
    var el = document.querySelector('#fo-launcher-list .fo-launcher-item.is-active');
    if (el && el.scrollIntoView) el.scrollIntoView({ block: 'nearest' });
  }

  /* ---------------- user popover ---------------- */
  function closeUserMenu() {
    $('#fo-usermenu').addClass('fo-hidden');
  }

  /* ---------------- workspaces ---------------- */
  function syncWorkspaces() {
    var $sel = $('.selector');
    var $ws = $('#fo-bar-workspaces');
    if (!$ws.length) return;
    if ($sel.length <= 1) {
      $ws.removeClass('is-visible').empty();
      return;
    }
    var activeIdx = 0;
    $sel.each(function (i) { if (!$(this).hasClass('selector-hidden')) activeIdx = i; });
    $ws.addClass('is-visible').html($sel.map(function (i) {
      return '<button type="button" class="fo-ws-dot' + (i === activeIdx ? ' is-active' : '') +
        '" data-ws="' + i + '" title="' + esc(window.FO_T ? FO_T('desktop', 'Рабочий стол') : 'Рабочий стол') + ' ' + (i + 1) + '"></button>';
    }).get().join(''));
  }

  window.FO_SYNC_WORKSPACES = syncWorkspaces;

  window.FO_SET_BAR_TITLE = function (title) {
    var t = title ? String(title).replace(/_/g, ' ') : 'Forest OS';
    $('#fo-bar-title').text(t);
  };

  /* ---------------- events ---------------- */
  $(document)
    .on('click', '#fo-launcher-btn', function (e) {
      e.preventDefault();
      if (isOpen()) { closeLauncher(); } else { openLauncher(); }
    })
    .on('input', '#fo-launcher-input', function () {
      active = 0;
      render();
    })
    .on('click', '.fo-launcher-item', function () {
      run(parseInt($(this).attr('data-i'), 10) || 0);
    })
    .on('click', '.fo-launcher', function (e) {
      if (e.target === this) closeLauncher();
    })
    .on('click', '.fo-power', function () {
      var p = $(this).attr('data-power');
      closeLauncher();
      if (window.SaveNotification) { try { window.SaveNotification(); } catch (err) {} }
      /* exit / reload / shutdown — общая логика в desktop-layout.js */
      if (window.FO_POWER) { window.FO_POWER(p); return; }
      if (p === 'reload') { location.href = 'os.php'; }
      else { location.href = '?action=logout'; }
    })
    .on('click', '#fo-user-btn', function (e) {
      e.stopPropagation();
      $('#fo-usermenu').toggleClass('fo-hidden');
    })
    .on('click', '#fo-usermenu .fo-popover-item', function () {
      var act = $(this).attr('data-act');
      closeUserMenu();
      if (typeof window.makeprocess !== 'function') return;
      if (act === 'accounts') {
        window.makeprocess('system/apps/Settings/users.php', window.FO_LOGIN || '', 'selectuser', 'Учётные записи');
      } else if (act === 'about') {
        window.makeprocess('system/apps/Settings/about.php', '', '', 'О системе');
      } else if (act === 'logout') {
        if (window.SaveNotification) { try { window.SaveNotification(); } catch (err) {} }
        location.href = '?action=logout';
      }
    })
    .on('click', '.fo-ws-dot', function () {
      var i = parseInt($(this).attr('data-ws'), 10) || 0;
      $('.selector').eq(i).trigger('click');
      setTimeout(syncWorkspaces, 80);
    })
    .on('click', function (e) {
      if (!$(e.target).closest('#fo-usermenu, #fo-user-btn').length) closeUserMenu();
    });

  $(document).on('keydown', function (e) {
    if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) {
      e.preventDefault();
      if (isOpen()) { closeLauncher(); } else { openLauncher(); }
      return;
    }
    if (!isOpen()) return;
    if (e.key === 'Escape') { e.preventDefault(); closeLauncher(); }
    else if (e.key === 'ArrowDown') { e.preventDefault(); active = Math.min(filtered.length - 1, active + 1); render(); scrollActive(); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); active = Math.max(0, active - 1); render(); scrollActive(); }
    else if (e.key === 'Enter') { e.preventDefault(); run(active); }
  });

  $(function () {
    render();
    syncWorkspaces();
    setTimeout(syncWorkspaces, 900);
    setTimeout(syncWorkspaces, 2200);
  });
})();
