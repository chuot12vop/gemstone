(function () {
  var btn = document.querySelector('[data-nav-toggle]');
  var panel = document.querySelector('[data-nav-panel]');
  if (!btn || !panel) return;
  btn.addEventListener('click', function () {
    var open = panel.classList.toggle('is-open');
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
  });
})();
