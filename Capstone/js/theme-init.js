(function () {
  try {
    var t = localStorage.getItem('coopims-theme') || 'dark';
    document.documentElement.setAttribute('data-theme', t);
  } catch (e) {}
})();
