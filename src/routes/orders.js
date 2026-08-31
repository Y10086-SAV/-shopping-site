const express = require('express');
const router = express.Router();
const store = require('../store');
const { flash, requireLogin } = require('../middleware');

/** 结算页：展示购物车并填写收货信息 */
router.get('/checkout', requireLogin, (req, res) => {
  const cart = req.session.cart || [];
  if (cart.length === 0) {
    flash(req, 'warning', '购物车是空的');
    return res.redirect('/cart');
  }
  res.render('orders/checkout', { title: '结算', cart });
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

/** 我的订单列表 */
router.get('/orders', requireLogin, (req, res) => {
  const orders = store.listOrdersByUser(req.session.user.id);
  res.render('orders/list', { title: '我的订单', orders });
});

/** 订单详情 */
router.get('/orders/:id', requireLogin, (req, res) => {
  const orders = store.listAllOrders();
  const order = orders.find((o) => o.id === req.params.id);
  if (!order) return res.status(404).render('404', { title: '订单不存在' });
  // 仅本人或管理员可查看
  if (order.userId !== req.session.user.id && req.session.user.role !== 'admin') {
    return res.status(403).render('404', { title: '无权访问' });
  }
  res.render('orders/detail', { title: '订单详情', order });
});

module.exports = router;
