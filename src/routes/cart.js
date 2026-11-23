const express = require('express');
const router = express.Router();
const store = require('../store');
const { flash } = require('../middleware');

/**
 * 购物车存储在 session 中，结构：
 * [{ id, name, price, image, quantity }]
 */

function getCart(req) {
  if (!req.session.cart) req.session.cart = [];
  return req.session.cart;
}

/** 购物车页由前端 SPA 渲染，数据通过 /api/cart 获取 */
router.get('/', (req, res) => {
  res.render('app');
});

/** 加入购物车 */
router.post('/add', (req, res) => {
  const { productId, quantity } = req.body;
  const qty = Math.max(1, parseInt(quantity, 10) || 1);
  const product = store.getProduct(productId);
  if (!product) {
    flash(req, 'danger', '商品不存在');
    return res.redirect('/products');
  }
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
  req.session.cart = cart; // 顶层重新赋值，确保 session 变更被持久化
  flash(req, 'success', `已将「${product.name}」加入购物车`);
  res.redirect(req.get('Referer') || '/cart');
});

/** 更新数量 */
router.post('/:id/update', (req, res) => {
  const cart = getCart(req);
  const item = cart.find((i) => i.id === req.params.id);
  if (item) {
    const qty = parseInt(req.body.quantity, 10);
    item.quantity = Number.isFinite(qty) && qty > 0 ? qty : 1;
  }
  req.session.cart = cart; // 顶层重新赋值，确保 session 变更被持久化
  res.redirect('/cart');
});

/** 移除商品 */
router.post('/:id/remove', (req, res) => {
  req.session.cart = getCart(req).filter((i) => i.id !== req.params.id);
  flash(req, 'success', '已移除商品');
  res.redirect('/cart');
});

/** 清空购物车 */
router.post('/clear', (req, res) => {
  req.session.cart = [];
  res.redirect('/cart');
});

module.exports = router;
