const express = require('express');
const router = express.Router();
const store = require('../store');
const { flash, requireAdmin } = require('../middleware');

// 所有后台路由都需要管理员权限
router.use(requireAdmin);

/** 后台仪表盘 */
router.get('/', (req, res) => {
  const products = store.listProducts();
  const orders = store.listAllOrders();
  const users = store.listUsers();
  const revenue = orders.reduce((s, o) => s + o.total, 0);
  res.render('admin/dashboard', {
    title: '后台管理',
    stats: {
      products: products.length,
      orders: orders.length,
      users: users.length,
      revenue
    }
  });
});

/** 商品管理列表 */
router.get('/products', (req, res) => {
  res.render('admin/products', { title: '商品管理', products: store.listProducts() });
});

/** 新增商品表单 */
router.get('/products/new', (req, res) => {
  res.render('admin/product-form', {
    title: '新增商品',
    product: null,
    categories: store.CATEGORIES
  });
});

/** 提交新增商品 */
router.post('/products', (req, res) => {
  const { name, category, price, stock, image, description } = req.body;
  if (!name || !category || !price) {
    flash(req, 'danger', '名称、分类、价格必填');
    return res.redirect('/admin/products/new');
  }
  store.createProduct({ name, category, price, stock, image, description });
  flash(req, 'success', '商品已新增');
  res.redirect('/admin/products');
});

/** 编辑商品表单 */
router.get('/products/:id/edit', (req, res) => {
  const product = store.getProduct(req.params.id);
  if (!product) {
    flash(req, 'danger', '商品不存在');
    return res.redirect('/admin/products');
  }
  res.render('admin/product-form', {
    title: '编辑商品',
    product,
    categories: store.CATEGORIES
  });
});

/** 提交编辑 */
router.put('/products/:id', (req, res) => {
  const { name, category, price, stock, image, description } = req.body;
  store.updateProduct(req.params.id, { name, category, price, stock, image, description });
  flash(req, 'success', '商品已更新');
  res.redirect('/admin/products');
});

/** 删除商品 */
router.delete('/products/:id', (req, res) => {
  store.deleteProduct(req.params.id);
  flash(req, 'success', '商品已删除');
  res.redirect('/admin/products');
});

/** 订单管理 */
router.get('/orders', (req, res) => {
  res.render('admin/orders', { title: '订单管理', orders: store.listAllOrders() });
});

/** 更新订单状态 */
router.post('/orders/:id/status', (req, res) => {
  const { status } = req.body;
  if (status) store.updateOrderStatus(req.params.id, status);
  flash(req, 'success', '订单状态已更新');
  res.redirect('/admin/orders');
});

/** 用户列表 */
router.get('/users', (req, res) => {
  res.render('admin/users', { title: '用户管理', users: store.listUsers() });
});

module.exports = router;
