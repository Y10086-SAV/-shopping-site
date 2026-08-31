/**
 * 全局中间件与鉴权
 */
const store = require('./store');

/** 注入模板全局变量：当前用户、购物车商品数、flash 消息 */
function globals(req, res, next) {
  res.locals.user = req.session.user || null;
  res.locals.cartCount = (req.session.cart || []).reduce((n, i) => n + i.quantity, 0);
  res.locals.flash = req.session.flash || null;
  req.session.flash = null;

  // 价格格式化辅助函数
  res.locals.fmt = (n) => Number(n).toFixed(2);

  // 购物车合计辅助函数
  res.locals.cartTotal = (cart) =>
    cart.reduce((sum, i) => sum + i.price * i.quantity, 0);

  next();
}

/** 设置 flash 消息（重定向后显示一次） */
function flash(req, type, message) {
  req.session.flash = { type, message };
}

/** 要求已登录 */
function requireLogin(req, res, next) {
  if (!req.session.user) {
    flash(req, 'warning', '请先登录');
    return res.redirect('/login');
  }
  next();
}

/** 要求管理员权限 */
function requireAdmin(req, res, next) {
  if (!req.session.user) {
    flash(req, 'warning', '请先登录');
    return res.redirect('/login');
  }
  if (req.session.user.role !== 'admin') {
    flash(req, 'danger', '没有管理员权限');
    return res.redirect('/');
  }
  next();
}

module.exports = { globals, flash, requireLogin, requireAdmin };
