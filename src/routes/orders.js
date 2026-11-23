const express = require('express');
const router = express.Router();
const store = require('../store');
const { flash, requireLogin } = require('../middleware');

/** 结算页由前端 SPA 渲染，数据通过 /api/cart 获取 */
router.get('/checkout', requireLogin, (req, res) => {
  const cart = req.session.cart || [];
  if (cart.length === 0) {
    flash(req, 'warning', '购物车是空的');
    return res.redirect('/cart');
  }
  res.render('app');
});

/** 提交订单 */
router.post('/checkout', requireLogin, (req, res) => {
  const cart = req.session.cart || [];
  if (cart.length === 0) {
    flash(req, 'warning', '购物车是空的');
    return res.redirect('/cart');
  }
  const { receiver, phone, address } = req.body;
  const errors = {};
  if (!receiver || !receiver.trim()) errors.receiver = '请填写收货人';
  if (!phone || !/^1\d{10}$/.test(phone)) errors.phone = '请填写有效的 11 位手机号';
  if (!address || address.trim().length < 5) errors.address = '请填写详细收货地址';

  if (Object.keys(errors).length > 0) {
    return res.status(400).render('orders/checkout', {
      title: '结算',
      cart,
      form: { receiver, phone, address },
      errors
    });
  }

  const total = cart.reduce((sum, i) => sum + i.price * i.quantity, 0);
  const order = store.createOrder({
    userId: req.session.user.id,
    items: cart,
    total,
    receiver: receiver.trim(),
    phone,
    address: address.trim()
  });

  req.session.cart = [];
  flash(req, 'success', '订单提交成功！');
  res.redirect('/orders/' + order.id);
});

/** 我的订单列表由前端 SPA 渲染，数据通过 /api/orders 获取 */
router.get('/orders', (req, res) => {
  res.render('app');
});

/** 订单详情由前端 SPA 渲染，权限校验在 /api/orders/:id 中完成 */
router.get('/orders/:id', (req, res) => {
  res.render('app');
});

module.exports = router;
