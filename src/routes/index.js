const express = require('express');
const router = express.Router();

/** 首页由前端 SPA 渲染，服务端只返回应用外壳 */
router.get('/', (req, res) => {
  res.render('app');
});

module.exports = router;
