const express = require('express');
const router = express.Router();
const store = require('../store');

/** 商品列表页：支持分类筛选、关键词搜索、排序 */
router.get('/', (req, res) => {
  const { category, q, sort } = req.query;
  const products = store.listProducts({ category, q, sort });
  res.render('products/list', {
    title: '全部商品',
    products,
    categories: store.CATEGORIES,
    currentCategory: category || '全部',
    q: q || '',
    sort: sort || ''
  });
});

/** 商品详情页 */
router.get('/:id', (req, res) => {
  const product = store.getProduct(req.params.id);
  if (!product) {
    return res.status(404).render('404', { title: '商品不存在' });
  }
  res.render('products/detail', { title: product.name, product });
});

module.exports = router;
