document.addEventListener('click', function (e) {
  var t = e.target.closest('[data-print]'); if (t) { window.print(); }
  var b = e.target.closest('.burger'); if (b) { document.querySelector('.sidebar').classList.toggle('open'); }
});
document.addEventListener('submit', function (e) {
  var m = e.target.getAttribute('data-confirm');
  if (m && !confirm(m)) e.preventDefault();
});
