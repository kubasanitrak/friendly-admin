/**
 * Friendly Admin settings UI.
 */
(function () {
  'use strict';

  var root = document.querySelector('[data-fa-menu-roles]');
  if (!root) {
    return;
  }

  var tabs = root.querySelectorAll('[data-fa-role-tab]');
  var panels = root.querySelectorAll('[data-fa-role-panel]');

  function showRole(role) {
    tabs.forEach(function (btn) {
      btn.classList.toggle('is-active', btn.getAttribute('data-fa-role-tab') === role);
    });
    panels.forEach(function (panel) {
      var match = panel.getAttribute('data-fa-role-panel') === role;
      panel.classList.toggle('is-active', match);
      if (match) {
        panel.removeAttribute('hidden');
      } else {
        panel.setAttribute('hidden', 'hidden');
      }
    });
  }

  tabs.forEach(function (btn) {
    btn.addEventListener('click', function () {
      showRole(btn.getAttribute('data-fa-role-tab'));
    });
  });

  root.addEventListener('click', function (event) {
    var target = event.target;
    if (!(target instanceof Element)) {
      return;
    }

    var panel = target.closest('[data-fa-role-panel]');
    if (!panel) {
      return;
    }

    var boxes = panel.querySelectorAll('[data-fa-role-menus] input[type="checkbox"]');
    if (target.matches('[data-fa-select-all]')) {
      event.preventDefault();
      boxes.forEach(function (box) {
        box.checked = true;
      });
    }
    if (target.matches('[data-fa-select-none]')) {
      event.preventDefault();
      boxes.forEach(function (box) {
        box.checked = false;
      });
    }
  });
})();
