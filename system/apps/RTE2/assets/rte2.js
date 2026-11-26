/* ==========================================================================
   Forest OS — Rich Text Editor 2.0 (RTE2)
   One IIFE per app instance; initialises every not-yet-started boot block.
   Every string comes from the dictionary passed by main.php.
   ========================================================================== */
(function () {
  'use strict';

  var DRAFT_APP = 'RTE2';
  var STATE_CMDS = {
    bold: 1, italic: 1, underline: 1, strikeThrough: 1,
    insertUnorderedList: 1, insertOrderedList: 1,
    justifyLeft: 1, justifyCenter: 1, justifyRight: 1, justifyFull: 1
  };
  var ERROR_KEYS = {
    auth: 'err_auth', kicked: 'err_auth', token: 'err_token',
    bad_path: 'err_path', outside: 'err_path', forbidden: 'err_forbidden',
    not_found: 'err_not_found', write: 'err_write', exists: 'err_exists',
    bad_name: 'err_name', too_large: 'err_too_large', save: 'err_save',
    parse: 'err_save', network: 'err_network', open: 'err_open'
  };

  var boots = document.querySelectorAll('script.rte2-boot');
  for (var i = 0; i < boots.length; i++) {
    if (boots[i].getAttribute('data-done')) { continue; }
    boots[i].setAttribute('data-done', '1');
    try {
      init(boots[i]);
    } catch (err) {
      if (window.console) { console.error('RTE2 init:', err); }
    }
  }

  function init(bootEl) {
    var root = document.getElementById(bootEl.getAttribute('data-root') || '');
    if (!root) { return; }
    var boot;
    try { boot = JSON.parse(bootEl.textContent || '{}'); } catch (e) { return; }

    var i18n = boot.i18n || {};
    window.RTE2I18N = i18n;
    function T(key) {
      return Object.prototype.hasOwnProperty.call(i18n, key) ? i18n[key] : key;
    }

    var ed = root.querySelector('.rte2-editor');
    if (!ed) { return; }
    var el = {
      name: root.querySelector('[data-role="file-name"]'),
      path: root.querySelector('[data-role="file-path"]'),
      status: root.querySelector('[data-role="status"]'),
      counts: root.querySelector('[data-role="counts"]'),
      fmt: root.querySelector('[data-role="format"]'),
      banner: root.querySelector('[data-role="banner"]'),
      dialog: root.querySelector('[data-role="dialog"]'),
      dlgTitle: root.querySelector('[data-role="dlg-title"]'),
      dlgLabel: root.querySelector('[data-role="dlg-label"]'),
      dlgInput: root.querySelector('[data-role="dlg-input"]'),
      dlgFormats: root.querySelector('[data-role="dlg-formats"]'),
      dlgFormat: root.querySelector('[data-role="dlg-format"]'),
      dlgHint: root.querySelector('[data-role="dlg-hint"]'),
      dlgError: root.querySelector('[data-role="dlg-error"]'),
      dlgOk: root.querySelector('[data-role="dlg-ok"]'),
      dlgCancel: root.querySelector('[data-role="dlg-cancel"]')
    };

    var endpoint = boot.endpoint || 'system/apps/RTE2/save.php';
    var target = String(boot.target || '');
    var draftSource = '';
    var format = boot.format === 'text' ? 'text' : 'html';
    var dirty = false;
    var saving = false;
    var draftTimer = null;
    var lastDraft = null;
    var selection = null;
    var dialogKind = '';
    var dialogOnOk = null;

    function all(sel) { return Array.prototype.slice.call(root.querySelectorAll(sel)); }

    function errorText(code) {
      var key = ERROR_KEYS[String(code || '')] || 'err_save';
      return T(key);
    }

    function nameOf(p) {
      var s = String(p || '');
      var k = s.lastIndexOf('/');
      return k >= 0 ? s.slice(k + 1) : s;
    }
    function dirOf(p) {
      var s = String(p || '');
      var k = s.lastIndexOf('/');
      return k >= 0 ? s.slice(0, k) : s;
    }

    /* ---------------- status / file meta ---------------- */

    function setState(state, text) {
      root.setAttribute('data-state', state);
      if (el.status) { el.status.textContent = text; }
    }

    function setFileMeta() {
      var n;
      var p;
      if (target) {
        n = nameOf(target);
        p = dirOf(target);
      } else if (draftSource) {
        n = T('untitled');
        p = T('draft_target').replace('{file}', nameOf(draftSource));
      } else {
        n = T('untitled');
        p = T('draft_hint');
      }
      if (el.name) {
        el.name.textContent = n;
        el.name.title = target || '';
      }
      if (el.path) {
        el.path.textContent = p;
        el.path.title = p;
      }
      if (el.fmt) {
        el.fmt.textContent = (format === 'text') ? T('mode_text') : T('mode_html');
      }
    }

    function showBanner(text) {
      if (!el.banner) { return; }
      el.banner.textContent = text;
      el.banner.hidden = false;
      el.banner.classList.add('is-on');
    }

    /* ---------------- content ---------------- */

    function getHtml() { return ed.innerHTML; }

    function getText() {
      var t = (typeof ed.innerText === 'string') ? ed.innerText : ed.textContent;
      return String(t || '').replace(/\u00a0/g, ' ');
    }

    function normalize() {
      if (ed.innerHTML === '<br>' || ed.innerHTML === '<div><br></div>') { ed.innerHTML = ''; }
    }

    function setHtml(html) {
      ed.innerHTML = String(html || '');
      normalize();
    }

    function setPlain(text) {
      ed.textContent = '';
      var lines = String(text || '').replace(/\r\n/g, '\n').replace(/\r/g, '\n').split('\n');
      var frag = document.createDocumentFragment();
      for (var i = 0; i < lines.length; i++) {
        var line = document.createElement('div');
        if (lines[i] === '') {
          line.appendChild(document.createElement('br'));
        } else {
          line.textContent = lines[i];
        }
        frag.appendChild(line);
      }
      ed.appendChild(frag);
    }

    function updateCounts() {
      var text = getText();
      var clean = text.replace(/[\s\u00a0]+/g, ' ').replace(/^ | $/g, '');
      var words = clean ? clean.split(' ').length : 0;
      if (el.counts) {
        el.counts.textContent = T('counts')
          .replace('{chars}', String(text.length))
          .replace('{words}', String(words));
      }
    }

    /* ---------------- draft (FO_APP_STATE) ---------------- */

    function saveDraft() {
      clearTimeout(draftTimer);
      if (!window.FO_APP_STATE) { return; }
      var html = getHtml();
      if (html === lastDraft) { return; }
      lastDraft = html;
      window.FO_APP_STATE.save(DRAFT_APP, {
        html: html,
        file: target,
        format: format,
        at: Date.now()
      });
      if (dirty) { setState('draft', T('status_draft')); }
    }

    function markDirty() {
      dirty = true;
      setState('dirty', T('status_unsaved'));
      clearTimeout(draftTimer);
      draftTimer = setTimeout(saveDraft, 700);
    }

    function restoreDraft() {
      if (target || !window.FO_APP_STATE) { return; }
      window.FO_APP_STATE.load(DRAFT_APP).then(function (st) {
        if (!st || typeof st.html !== 'string' || st.html === '') { return; }
        if (dirty || st.html === getHtml()) { return; }
        setHtml(st.html);
        if (st.file) { draftSource = String(st.file); }
        if (st.format === 'text') { format = 'text'; }
        lastDraft = st.html;
        dirty = false;
        setFileMeta();
        updateCounts();
        setState('draft', T('status_draft_restored'));
        toast(T('toast_draft_restored'));
      }).catch(function () {});
    }

    /* ---------------- toast ---------------- */

    function toast(msg) {
      if (typeof window.FO_TOAST === 'function') { window.FO_TOAST(msg); }
    }

    /* ---------------- toolbar state ---------------- */

    function updateToolbar() {
      var buttons = all('[data-cmd]');
      for (var i = 0; i < buttons.length; i++) {
        var cmd = buttons[i].getAttribute('data-cmd');
        if (!STATE_CMDS[cmd]) { continue; }
        var on = false;
        try { on = document.queryCommandState(cmd); } catch (e) { on = false; }
        buttons[i].classList.toggle('is-on', !!on);
      }
      var block = '';
      try { block = String(document.queryCommandValue('formatBlock') || '').toLowerCase(); } catch (e) { block = ''; }
      var blockButtons = all('[data-block]');
      for (var j = 0; j < blockButtons.length; j++) {
        blockButtons[j].classList.toggle('is-on',
          blockButtons[j].getAttribute('data-block') === block);
      }
    }

    /* ---------------- selection helpers ---------------- */

    function saveSelection() {
      var sel = window.getSelection ? window.getSelection() : null;
      if (!sel || !sel.rangeCount) { selection = null; return; }
      var range = sel.getRangeAt(0);
      if (ed.contains(range.commonAncestorContainer)) {
        selection = range.cloneRange();
      } else {
        selection = null;
      }
    }

    function restoreSelection() {
      ed.focus();
      if (!selection) { return; }
      var sel = window.getSelection ? window.getSelection() : null;
      if (!sel) { return; }
      try {
        sel.removeAllRanges();
        sel.addRange(selection);
      } catch (e) { /* range detached — caret stays in editor */ }
    }

    function insertNode(node) {
      var sel = window.getSelection ? window.getSelection() : null;
      if (sel && sel.rangeCount) {
        var range = sel.getRangeAt(0);
        range.deleteContents();
        range.insertNode(node);
        range.setStartAfter(node);
        range.collapse(true);
        sel.removeAllRanges();
        sel.addRange(range);
      } else {
        ed.appendChild(node);
      }
    }

    function isSafeUrl(url) {
      var u = String(url || '').replace(/^\s+|\s+$/g, '');
      if (!u) { return false; }
      if (/^\s*(javascript|vbscript|data:text\/html)/i.test(u)) { return false; }
      return /^(https?:\/\/|mailto:|\/|\.\/|\.\.\/|#)/i.test(u) ||
        /^[A-Za-z0-9._~%\-\/]+$/.test(u);
    }

    function isSafeImage(url) {
      var u = String(url || '').replace(/^\s+|\s+$/g, '');
      if (!u) { return false; }
      if (/^\s*(javascript|vbscript|data:text\/html)/i.test(u)) { return false; }
      return /^(https?:\/\/|data:image\/(png|jpe?g|gif|webp|svg\+xml);|\/|\.\/|\.\.\/)/i.test(u) ||
        /^[A-Za-z0-9._~%\-\/]+$/.test(u);
    }

    /* ---------------- dialog ---------------- */

    function openDialog(cfg) {
      if (!el.dialog) { return; }
      dialogKind = cfg.kind || 'prompt';
      dialogOnOk = cfg.onOk || null;
      el.dlgTitle.textContent = cfg.title || '';
      el.dlgOk.textContent = cfg.okText || T('ok');
      el.dlgCancel.textContent = cfg.cancelText || T('cancel');
      var isPrompt = dialogKind === 'prompt';
      el.dlgLabel.hidden = !(isPrompt && cfg.label);
      el.dlgLabel.textContent = cfg.label || '';
      el.dlgInput.hidden = !isPrompt;
      el.dlgInput.value = isPrompt ? String(cfg.value || '') : '';
      el.dlgInput.placeholder = cfg.placeholder || '';
      el.dlgFormats.hidden = !cfg.formats;
      if (cfg.formats) { el.dlgFormat.value = cfg.format || 'html'; }
      el.dlgHint.textContent = cfg.hint || '';
      el.dlgHint.hidden = !cfg.hint;
      el.dlgError.textContent = '';
      el.dlgError.hidden = true;
      el.dlgOk.disabled = false;
      el.dialog.hidden = false;
      el.dialog.classList.add('is-on');
      setTimeout(function () {
        if (isPrompt) {
          el.dlgInput.focus();
          el.dlgInput.select();
        } else {
          el.dlgOk.focus();
        }
      }, 30);
    }

    function closeDialog() {
      if (!el.dialog) { return; }
      el.dialog.hidden = true;
      el.dialog.classList.remove('is-on');
      dialogOnOk = null;
      dialogKind = '';
    }

    function showDialogError(msg) {
      if (!el.dlgError) { return; }
      el.dlgError.textContent = String(msg || '');
      el.dlgError.hidden = false;
    }

    function submitDialog() {
      var value = '';
      if (dialogKind === 'prompt') {
        value = String(el.dlgInput.value || '').trim();
        if (!value) {
          showDialogError(T('err_url'));
          el.dlgInput.focus();
          return;
        }
      }
      var result = dialogOnOk ? dialogOnOk(value, el.dlgFormat ? el.dlgFormat.value : '') : true;
      if (result === false) { return; }
      closeDialog();
    }

    if (el.dlgOk) { el.dlgOk.addEventListener('click', submitDialog); }
    if (el.dlgCancel) { el.dlgCancel.addEventListener('click', closeDialog); }
    if (el.dlgFormat) {
      el.dlgFormat.addEventListener('change', function () {
        if (el.dlgFormats.hidden || !el.dlgInput || el.dlgInput.hidden) { return; }
        var value = String(el.dlgFormat.value || 'html');
        var name = String(el.dlgInput.value || '').trim();
        var match = /^(.*)\.[A-Za-z0-9]{1,8}$/.exec(name);
        var base = match ? match[1] : name;
        if (base) { el.dlgInput.value = base + '.' + value; }
        if (el.dlgHint) {
          el.dlgHint.textContent = T('save_as_hint')
            .replace('{folder}', String(boot.documents || 'Documents'))
            .replace('{ext}', '.' + value);
        }
      });
    }
    if (el.dialog) {
      el.dialog.addEventListener('mousedown', function (e) {
        if (e.target === el.dialog) { closeDialog(); }
      });
      el.dialog.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && dialogKind === 'prompt') {
          e.preventDefault();
          submitDialog();
        } else if (e.key === 'Escape') {
          e.preventDefault();
          closeDialog();
        }
      });
    }

    /* ---------------- commands ---------------- */

    function afterChange() {
      normalize();
      updateCounts();
      updateToolbar();
      markDirty();
    }

    function applyBlock(tag) {
      ed.focus();
      var current = '';
      try { current = String(document.queryCommandValue('formatBlock') || '').toLowerCase(); } catch (e) { current = ''; }
      var next = (current === tag) ? 'p' : tag;
      try { document.execCommand('formatBlock', false, '<' + next + '>'); } catch (e) { /* ignore */ }
      afterChange();
    }

    function clearFormatting() {
      try { document.execCommand('removeFormat', false, null); } catch (e) { /* ignore */ }
      try { document.execCommand('formatBlock', false, '<p>'); } catch (e) { /* ignore */ }
      var nodes = ed.querySelectorAll('[style], [class], [align]');
      for (var i = 0; i < nodes.length; i++) {
        nodes[i].removeAttribute('style');
        nodes[i].removeAttribute('class');
        nodes[i].removeAttribute('align');
      }
      afterChange();
    }

    function runCommand(cmd) {
      if (cmd === 'link') { askLink(); return; }
      if (cmd === 'image') { askImage(); return; }
      if (cmd === 'clear') { ed.focus(); clearFormatting(); return; }
      ed.focus();
      try { document.execCommand(cmd, false, null); } catch (e) { /* ignore */ }
      afterChange();
    }

    function askLink() {
      saveSelection();
      openDialog({
        kind: 'prompt',
        title: T('link_title'),
        label: T('link_label'),
        value: 'https://',
        hint: T('link_hint'),
        okText: T('ok'),
        onOk: function (url) {
          if (!isSafeUrl(url)) {
            showDialogError(T('err_url'));
            return false;
          }
          restoreSelection();
          var sel = window.getSelection ? window.getSelection() : null;
          var collapsed = !sel || !sel.rangeCount || sel.isCollapsed;
          if (collapsed) {
            var a = document.createElement('a');
            a.setAttribute('href', url);
            a.textContent = url;
            insertNode(a);
          } else {
            try { document.execCommand('createLink', false, url); } catch (e) { /* ignore */ }
          }
          afterChange();
          return true;
        }
      });
    }

    function askImage() {
      saveSelection();
      openDialog({
        kind: 'prompt',
        title: T('image_title'),
        label: T('image_label'),
        value: 'https://',
        hint: T('image_hint'),
        okText: T('ok'),
        onOk: function (url) {
          if (!isSafeImage(url)) {
            showDialogError(T('err_url'));
            return false;
          }
          restoreSelection();
          ed.focus();
          try { document.execCommand('insertImage', false, url); } catch (e) {
            var img = document.createElement('img');
            img.setAttribute('src', url);
            img.setAttribute('alt', '');
            insertNode(img);
          }
          afterChange();
          return true;
        }
      });
    }

    /* ---------------- saving ---------------- */

    function applySave(data) {
      if (data && data.file) {
        target = String(data.file);
        if (data.format) { format = data.format === 'text' ? 'text' : 'html'; }
      }
      draftSource = '';
      dirty = false;
      lastSaved = getHtml();
      lastDraft = getHtml();
      if (window.FO_APP_STATE) { window.FO_APP_STATE.clear(DRAFT_APP); }
      setFileMeta();
      updateCounts();
      setState('saved', T('status_saved'));
      var savedName = (data && data.name) ? String(data.name) : nameOf(target);
      toast(T('toast_saved').replace('{file}', savedName));
      if (el.dialog && !el.dialog.hidden) { closeDialog(); }
    }

    function persist(mode, opts) {
      opts = opts || {};
      var body = new URLSearchParams();
      body.set('token', boot.token || '');
      body.set('mode', mode);
      body.set('html', getHtml());
      body.set('text', getText());
      if (mode === 'save') {
        body.set('file', opts.file || target);
      } else {
        body.set('name', opts.name || '');
        if (opts.overwrite) { body.set('overwrite', '1'); }
      }
      return fetch(endpoint, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
        body: body.toString()
      }).then(function (res) {
        return res.json().catch(function () { return { ok: false, error: 'parse' }; })
          .then(function (data) {
            if (data && data.ok) { return { ok: true, data: data }; }
            return { ok: false, error: (data && data.error) ? String(data.error) : 'save' };
          });
      }).catch(function () {
        return { ok: false, error: 'network' };
      });
    }

    function doSave() {
      if (!target) { openSaveAs(); return; }
      if (saving) { return; }
      saving = true;
      setState('saving', T('status_saving'));
      persist('save', { file: target }).then(function (res) {
        saving = false;
        if (res.ok) {
          applySave(res.data);
        } else {
          var msg = errorText(res.error);
          setState('error', msg);
          toast(msg);
        }
      });
    }

    function doSaveAs(name, overwrite) {
      if (saving) { return; }
      saving = true;
      if (el.dlgOk) { el.dlgOk.disabled = true; }
      persist('saveas', { name: name, overwrite: !!overwrite }).then(function (res) {
        saving = false;
        if (el.dlgOk) { el.dlgOk.disabled = false; }
        if (res.ok) {
          applySave(res.data);
          return;
        }
        if (res.error === 'exists') {
          askOverwrite(name);
          return;
        }
        showDialogError(errorText(res.error));
        setState('error', errorText(res.error));
      });
    }

    function askOverwrite(name) {
      openDialog({
        kind: 'confirm',
        title: T('overwrite_title'),
        hint: T('overwrite_text').replace('{file}', name),
        okText: T('overwrite_ok'),
        onOk: function () {
          doSaveAs(name, true);
          return false;
        }
      });
    }

    function openSaveAs() {
      var ext = format === 'text' ? 'txt' : 'html';
      var base = target ? nameOf(target).replace(/\.[A-Za-z0-9]{1,8}$/, '') : T('default_name');
      var hint = T('save_as_hint')
        .replace('{folder}', String(boot.documents || 'Documents'))
        .replace('{ext}', '.' + ext);
      openDialog({
        kind: 'prompt',
        title: T('save_as_title'),
        label: T('save_as_name'),
        value: base + '.' + ext,
        hint: hint,
        formats: true,
        format: ext,
        okText: T('ok'),
        onOk: function (value, chosenFormat) {
          var name = String(value || '').trim();
          if (!name) {
            showDialogError(T('err_name'));
            return false;
          }
          if (!/\.[A-Za-z0-9]{1,8}$/.test(name)) {
            name += '.' + (chosenFormat || 'html');
          }
          doSaveAs(name, false);
          return false;
        }
      });
    }

    function resetDoc() {
      ed.innerHTML = '';
      target = '';
      draftSource = '';
      format = 'html';
      dirty = false;
      lastDraft = '';
      lastSaved = '';
      if (window.FO_APP_STATE) { window.FO_APP_STATE.clear(DRAFT_APP); }
      setFileMeta();
      updateCounts();
      updateToolbar();
      setState('new', T('status_new'));
      ed.focus();
    }

    function askNew() {
      if (!dirty && getHtml() === '') { resetDoc(); return; }
      openDialog({
        kind: 'confirm',
        title: T('new_title'),
        hint: T('new_text'),
        okText: T('new_ok'),
        onOk: function () {
          resetDoc();
          return true;
        }
      });
    }

    /* ---------------- paste sanitizer ---------------- */

    function unwrap(node, allowed) {
      var frag = document.createDocumentFragment();
      for (var c = node.firstChild; c; c = c.nextSibling) {
        var clean = sanitizeNode(c, allowed);
        if (clean) { frag.appendChild(clean); }
      }
      return frag;
    }

    function sanitizeNode(node, allowed) {
      if (node.nodeType === 3) { return document.createTextNode(node.nodeValue); }
      if (node.nodeType !== 1) { return null; }
      var tag = node.tagName;
      if (tag === 'SCRIPT' || tag === 'STYLE' || tag === 'IFRAME' || tag === 'OBJECT' ||
          tag === 'EMBED' || tag === 'LINK' || tag === 'META' || tag === 'FORM' ||
          tag === 'INPUT' || tag === 'BUTTON' || tag === 'SVG') {
        return null;
      }
      if (!allowed[tag]) { return unwrap(node, allowed); }
      if (tag === 'A') {
        var href = node.getAttribute('href') || '';
        if (!isSafeUrl(href)) { return unwrap(node, allowed); }
        var link = document.createElement('a');
        link.setAttribute('href', href);
        link.setAttribute('target', '_blank');
        link.setAttribute('rel', 'noopener noreferrer');
        for (var lc = node.firstChild; lc; lc = lc.nextSibling) {
          var lclean = sanitizeNode(lc, allowed);
          if (lclean) { link.appendChild(lclean); }
        }
        return link;
      }
      if (tag === 'IMG') {
        var src = node.getAttribute('src') || '';
        if (!isSafeImage(src)) { return null; }
        var img = document.createElement('img');
        img.setAttribute('src', src);
        var alt = node.getAttribute('alt');
        if (alt) { img.setAttribute('alt', alt); }
        return img;
      }
      var out = document.createElement(tag);
      for (var c = node.firstChild; c; c = c.nextSibling) {
        var clean = sanitizeNode(c, allowed);
        if (clean) { out.appendChild(clean); }
      }
      return out;
    }

    function insertCleanHtml(html) {
      var doc;
      try {
        doc = new DOMParser().parseFromString(String(html), 'text/html');
      } catch (e) {
        return false;
      }
      var allowed = {
        P: 1, DIV: 1, BR: 1, B: 1, STRONG: 1, I: 1, EM: 1, U: 1, S: 1, STRIKE: 1,
        H1: 1, H2: 1, H3: 1, UL: 1, OL: 1, LI: 1, BLOCKQUOTE: 1, PRE: 1, CODE: 1,
        A: 1, IMG: 1, HR: 1, SPAN: 1, TABLE: 1, THEAD: 1, TBODY: 1, TR: 1, TD: 1, TH: 1
      };
      var frag = document.createDocumentFragment();
      var body = doc.body;
      for (var i = 0; i < body.childNodes.length; i++) {
        var clean = sanitizeNode(body.childNodes[i], allowed);
        if (clean) { frag.appendChild(clean); }
      }
      if (!frag.childNodes.length) { return false; }
      var sel = window.getSelection ? window.getSelection() : null;
      if (!sel || !sel.rangeCount || !ed.contains(sel.anchorNode)) {
        ed.appendChild(frag);
        return true;
      }
      var range = sel.getRangeAt(0);
      range.deleteContents();
      var nodes = Array.prototype.slice.call(frag.childNodes);
      for (var j = 0; j < nodes.length; j++) {
        range.insertNode(nodes[j]);
        range.setStartAfter(nodes[j]);
        range.collapse(true);
      }
      sel.removeAllRanges();
      sel.addRange(range);
      return true;
    }

    /* ---------------- events ---------------- */

    root.addEventListener('mousedown', function (e) {
      var hit = e.target.closest ? e.target.closest('[data-act], [data-cmd], [data-block]') : null;
      if (hit && !(hit.closest && hit.closest('.rte2-dialog'))) {
        e.preventDefault();
      }
    });

    root.addEventListener('click', function (e) {
      if (!e.target.closest) { return; }
      var act = e.target.closest('[data-act]');
      if (act && !act.closest('.rte2-dialog')) {
        var name = act.getAttribute('data-act');
        if (name === 'save') { doSave(); }
        else if (name === 'saveas') { openSaveAs(); }
        else if (name === 'new') { askNew(); }
        return;
      }
      var block = e.target.closest('[data-block]');
      if (block) { applyBlock(block.getAttribute('data-block')); return; }
      var cmd = e.target.closest('[data-cmd]');
      if (cmd) { runCommand(cmd.getAttribute('data-cmd')); }
    });

    ed.addEventListener('input', function () {
      normalize();
      updateCounts();
      updateToolbar();
      markDirty();
    });

    ed.addEventListener('keydown', function (e) {
      if (!(e.ctrlKey || e.metaKey)) { return; }
      var key = String(e.key || '').toLowerCase();
      if (key === 's') {
        e.preventDefault();
        if (e.shiftKey) { openSaveAs(); } else { doSave(); }
      }
    });

    ed.addEventListener('paste', function (e) {
      if (!e.clipboardData) { return; }
      e.preventDefault();
      var html = e.clipboardData.getData('text/html');
      var text = e.clipboardData.getData('text/plain');
      var pasted = false;
      if (html) { pasted = insertCleanHtml(html); }
      if (!pasted && text) {
        try { document.execCommand('insertText', false, text); } catch (err) { /* ignore */ }
      }
      afterChange();
    });

    ed.addEventListener('click', function (e) {
      var link = e.target.closest ? e.target.closest('a') : null;
      if (link) { e.preventDefault(); }
    });

    document.addEventListener('selectionchange', function () {
      var sel = window.getSelection ? window.getSelection() : null;
      if (!sel || !sel.rangeCount) { return; }
      if (!ed.contains(sel.anchorNode)) { return; }
      updateToolbar();
    });

    if (el.banner) {
      el.banner.addEventListener('click', function () {
        el.banner.hidden = true;
        el.banner.classList.remove('is-on');
      });
    }

    window.addEventListener('pagehide', saveDraft);

    document.addEventListener('visibilitychange', function () {
      if (document.visibilityState === 'hidden') { saveDraft(); }
    });

    /* ---------------- init ---------------- */

    if (format === 'text') {
      setPlain(boot.content || '');
    } else {
      setHtml(boot.content || '');
    }
    normalize();
    lastSaved = getHtml();
    lastDraft = getHtml();
    setFileMeta();
    updateCounts();
    updateToolbar();

    if (boot.error) {
      var message = errorText(boot.error);
      if (boot.requested) { message += ': ' + String(boot.requested); }
      showBanner(message);
      setState('error', message);
      toast(message);
    } else if (target) {
      setState('saved', T('status_saved'));
    } else {
      setState('new', T('status_new'));
    }

    try {
      document.execCommand('defaultParagraphSeparator', false, 'p');
    } catch (e) { /* ignore */ }

    restoreDraft();

    root.__rte2 = {
      editor: ed,
      save: doSave,
      saveAs: openSaveAs,
      getTarget: function () { return target; },
      getFormat: function () { return format; }
    };
  }
})();
