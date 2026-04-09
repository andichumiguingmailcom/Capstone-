(function () {
  try {
    document.documentElement.setAttribute('data-theme', 'light');
    localStorage.removeItem('coopims-theme');
  } catch (e) {}
})();
