/** 通用格式化工具 */
window.ShopUtils = (function () {
  function money(n) {
    return Number(n || 0).toFixed(2);
  }

  function dateTime(iso) {
    if (!iso) return '';
    return new Date(iso).toLocaleString('zh-CN');
  }

  function statusClass(status) {
    if (status === '待发货') return 'badge-pending';
    if (status === '已发货') return 'badge-shipped';
    return 'badge-done';
  }

  return { money, dateTime, statusClass };
})();
