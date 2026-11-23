// 移动端导航展开 / 收起
(function () {
  var toggle = document.getElementById('navToggle');
  var collapse = document.getElementById('navCollapse');
  if (!toggle || !collapse) return;

  toggle.addEventListener('click', function () {
    collapse.classList.toggle('open');
  });
})();
