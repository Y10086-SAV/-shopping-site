const express = require('express');
const router = express.Router();

/** 商品列表与详情均由前端 SPA 路由渲染，数据通过 /api/products 获取 */
router.get('/', (req, res) => {
  res.render('app');
});

router.get('/:id', (req, res) => {
  res.render('app');
});

module.exports = router;
