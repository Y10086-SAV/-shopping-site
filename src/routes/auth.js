const express = require('express');
const router = express.Router();
const bcrypt = require('bcryptjs');
const store = require('../store');
const { flash } = require('../middleware');

/** 注册页由前端 SPA 渲染，提交走 /api/auth/register */
router.get('/register', (req, res) => {
  res.render('app');
});

/** 处理注册 */
router.post('/register', (req, res) => {
  const { username, email, password, confirm } = req.body;
  const errors = {};

  if (!username || username.trim().length < 2) errors.username = '用户名至少 2 个字符';
  if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) errors.email = '请输入有效邮箱';
  if (!password || password.length < 6) errors.password = '密码至少 6 位';
  if (password !== confirm) errors.confirm = '两次密码不一致';
  if (store.findUserByUsername(username)) errors.username = '用户名已被占用';
  if (store.findUserByEmail(email)) errors.email = '邮箱已被注册';

  if (Object.keys(errors).length > 0) {
    return res.status(400).render('auth/register', {
      title: '注册',
      form: { username, email },
      errors
    });
  }

  const user = store.createUser({ username: username.trim(), email, password });
  req.session.user = { id: user.id, username: user.username, role: user.role };
  flash(req, 'success', `欢迎，${user.username}！`);
  res.redirect('/');
});

/** 登录页由前端 SPA 渲染，提交走 /api/auth/login */
router.get('/login', (req, res) => {
  res.render('app');
});

/** 处理登录 */
router.post('/login', (req, res) => {
  const { username, password } = req.body;
  const user = store.findUserByUsername(username);
  if (!user || !bcrypt.compareSync(password || '', user.passwordHash)) {
    return res.status(401).render('auth/login', {
      title: '登录',
      form: { username },
      error: '用户名或密码错误'
    });
  }
  req.session.user = { id: user.id, username: user.username, role: user.role };
  flash(req, 'success', `欢迎回来，${user.username}！`);
  res.redirect('/');
});

/** 退出登录 */
router.post('/logout', (req, res) => {
  req.session.user = null;
  flash(req, 'success', '已退出登录');
  res.redirect('/');
});

module.exports = router;
