const express = require('express');
const router = express.Router();
const store = require('../store');

/** 首页：展示分类 + 最新/推荐商品 */
router.get('/', (req, res) => {
  const products = store.listProducts({ sort: 'newest' }).slice(0, 8);
  res.render('index', { title: '首页', products });
});

module.exports = router;
