(function () {
  var top = document.querySelector('.top');
  var toggle = document.querySelector('.nav-toggle');
  var nav = document.querySelector('.nav');
  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      var open = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }
  function onScroll() {
    if (!top) return;
    top.classList.toggle('is-stuck', window.scrollY > 4);
  }
  onScroll();
  window.addEventListener('scroll', onScroll, { passive: true });

  document.querySelectorAll('[data-copy]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var page = btn.closest('.help-page');
      var copyLabel = (page && page.getAttribute('data-copy-label')) || '复制';
      var copiedLabel = (page && page.getAttribute('data-copied-label')) || '已复制';
      var code = btn.parentElement && btn.parentElement.querySelector('code');
      if (!code || !navigator.clipboard) return;
      navigator.clipboard.writeText(code.innerText).then(function () {
        btn.textContent = copiedLabel;
        setTimeout(function () { btn.textContent = copyLabel; }, 1500);
      });
    });
  });
})();
