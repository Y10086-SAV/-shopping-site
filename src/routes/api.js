/**
 * 前端 SPA 使用的 JSON 接口层
 * 复用 store（JSON 文件数据层）与 express-session，业务规则与原有服务端路由保持一致
 */
const express = require('express');
const bcrypt = require('bcryptjs');
const store = require('../store');

const router = express.Router();

function getCart(req) {
  if (!req.session.cart) req.session.cart = [];
  return req.session.cart;
}

function cartCount(cart) {
  return cart.reduce((n, i) => n + i.quantity, 0);
}

function cartTotal(cart) {
  return cart.reduce((s, i) => s + i.price * i.quantity, 0);
}

function cartPayload(req) {
  const cart = getCart(req);
  return { cart, count: cartCount(cart), total: cartTotal(cart) };
}

function publicUser(user) {
  return user ? { id: user.id, username: user.username, role: user.role } : null;
}

function requireApiLogin(req, res, next) {
  if (!req.session.user) return res.status(401).json({ message: '请先登录' });
  next();
}

/** 当前会话：登录态 + 购物车数量 */
router.get('/me', (req, res) => {
  res.json({
    user: publicUser(req.session.user),
    cartCount: cartCount(getCart(req))
  });
});

// ---------- 商品 ----------
router.get('/products', (req, res) => {
  const { category, q, sort } = req.query;
  res.json({
    products: store.listProducts({ category, q, sort }),
    categories: store.CATEGORIES
  });
});

router.get('/products/:id', (req, res) => {
  const product = store.getProduct(req.params.id);
  if (!product) return res.status(404).json({ message: '商品不存在' });
  res.json({ product });
});

// ---------- 购物车 ----------
router.get('/cart', (req, res) => {
  res.json(cartPayload(req));
});

router.post('/cart/add', (req, res) => {
  const { productId, quantity } = req.body || {};
  const qty = Math.max(1, parseInt(quantity, 10) || 1);
  const product = store.getProduct(productId);
  if (!product) return res.status(404).json({ message: '商品不存在' });

  const cart = getCart(req);
  const item = cart.find((i) => i.id === product.id);
  if (item) {
    item.quantity += qty;
  } else {
    cart.push({
      id: product.id,
      name: product.name,
      price: product.price,
      image: product.image,
      quantity: qty
    });
  }
  req.session.cart = cart;
  res.json({ message: `已将「${product.name}」加入购物车`, ...cartPayload(req) });
});

router.put('/cart/:id', (req, res) => {
  const cart = getCart(req);
  const item = cart.find((i) => i.id === req.params.id);
  if (item) {
    const qty = parseInt(req.body && req.body.quantity, 10);
    item.quantity = Number.isFinite(qty) && qty > 0 ? qty : 1;
  }
  req.session.cart = cart;
  res.json(cartPayload(req));
});

router.delete('/cart/:id', (req, res) => {
  req.session.cart = getCart(req).filter((i) => i.id !== req.params.id);
  res.json({ message: '已移除商品', ...cartPayload(req) });
});

router.delete('/cart', (req, res) => {
  req.session.cart = [];
  res.json({ message: '购物车已清空', ...cartPayload(req) });
});

// ---------- 注册 / 登录 / 退出 ----------
router.post('/auth/register', (req, res) => {
  const { username, email, password, confirm } = req.body || {};
  const errors = {};

  if (!username || username.trim().length < 2) errors.username = '用户名至少 2 个字符';
  if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) errors.email = '请输入有效邮箱';
  if (!password || password.length < 6) errors.password = '密码至少 6 位';
  if (password !== confirm) errors.confirm = '两次密码不一致';
  if (store.findUserByUsername(username)) errors.username = '用户名已被占用';
  if (store.findUserByEmail(email)) errors.email = '邮箱已被注册';

  if (Object.keys(errors).length > 0) {
    return res.status(400).json({ message: '请检查填写内容', errors });
  }

  const user = store.createUser({ username: username.trim(), email, password });
  req.session.user = { id: user.id, username: user.username, role: user.role };
  res.status(201).json({
    message: `欢迎，${user.username}！`,
    user: publicUser(req.session.user),
    cartCount: cartCount(getCart(req))
  });
});

router.post('/auth/login', (req, res) => {
  const { username, password } = req.body || {};
  const user = store.findUserByUsername(username);
  if (!user || !bcrypt.compareSync(password || '', user.passwordHash)) {
    return res.status(401).json({ message: '用户名或密码错误' });
  }
  req.session.user = { id: user.id, username: user.username, role: user.role };
  res.json({
    message: `欢迎回来，${user.username}！`,
    user: publicUser(req.session.user),
    cartCount: cartCount(getCart(req))
  });
});

router.post('/auth/logout', (req, res) => {
  req.session.user = null;
  res.json({ message: '已退出登录', user: null });
});

// ---------- 结算与订单 ----------
router.post('/checkout', requireApiLogin, (req, res) => {
  const cart = getCart(req);
  if (cart.length === 0) {
    return res.status(400).json({ message: '购物车是空的' });
  }

  const { receiver, phone, address } = req.body || {};
  const errors = {};
  if (!receiver || !receiver.trim()) errors.receiver = '请填写收货人';
  if (!phone || !/^1\d{10}$/.test(phone)) errors.phone = '请填写有效的 11 位手机号';
  if (!address || address.trim().length < 5) errors.address = '请填写详细收货地址';

  if (Object.keys(errors).length > 0) {
    return res.status(400).json({ message: '请检查填写内容', errors });
  }

  const order = store.createOrder({
    userId: req.session.user.id,
    items: cart,
    total: cartTotal(cart),
    receiver: receiver.trim(),
    phone,
    address: address.trim()
  });

  req.session.cart = [];
  res.status(201).json({ message: '订单提交成功！', order, cartCount: 0 });
});

router.get('/orders', requireApiLogin, (req, res) => {
  res.json({ orders: store.listOrdersByUser(req.session.user.id) });
});

router.get('/orders/:id', requireApiLogin, (req, res) => {
  const order = store.listAllOrders().find((o) => o.id === req.params.id);
  if (!order) return res.status(404).json({ message: '订单不存在' });
  if (order.userId !== req.session.user.id && req.session.user.role !== 'admin') {
    return res.status(403).json({ message: '无权访问该订单' });
  }
  res.json({ order });
});

router.use((req, res) => {
  res.status(404).json({ message: '接口不存在' });
});

module.exports = router;
