/**
 * 购物网站 —— 服务端入口
 * 技术栈：Node.js + Express + EJS + express-session + JSON 文件存储
 */
const path = require('path');
const express = require('express');
const session = require('express-session');
const methodOverride = require('method-override');

const store = require('./src/store');
const middleware = require('./src/middleware');

// 各功能路由
const indexRouter = require('./src/routes/index');
const productRouter = require('./src/routes/products');
const cartRouter = require('./src/routes/cart');
const authRouter = require('./src/routes/auth');
const orderRouter = require('./src/routes/orders');
const adminRouter = require('./src/routes/admin');

const app = express();
const PORT = process.env.PORT || 3000;

// 初始化数据（首次运行会生成 data/ 目录与种子数据、默认管理员账号）
store.init();

// ---------- 视图引擎 ----------
app.set('view engine', 'ejs');
app.set('views', path.join(__dirname, 'views'));

// ---------- 静态资源 ----------
app.use(express.static(path.join(__dirname, 'public')));

// ---------- 基础中间件 ----------
app.use(express.urlencoded({ extended: true })); // 解析表单 POST 数据
app.use(express.json());
app.use(methodOverride('_method')); // 支持 PUT / DELETE（表单里用 ?_method=PUT）

// 会话配置（密钥从环境变量读取，缺省时使用开发用默认值）
app.use(
  session({
    secret: process.env.SESSION_SECRET || 'shopping-site-dev-secret',
    resave: false,
    saveUninitialized: false,
    cookie: { maxAge: 1000 * 60 * 60 * 24 } // 1 天
  })
);

// 全局中间件：注入当前用户、购物车数量、flash 消息到所有模板
app.use(middleware.globals);

// ---------- 挂载路由 ----------
app.use('/', indexRouter);
app.use('/products', productRouter);
app.use('/cart', cartRouter);
app.use('/', authRouter);
app.use('/', orderRouter);
app.use('/admin', adminRouter);

// ---------- 404 处理 ----------
app.use((req, res) => {
  res.status(404).render('404', { title: '页面不存在' });
});

// ---------- 统一错误处理 ----------
app.use((err, req, res, next) => {
  console.error(err);
  res.status(500).render('error', {
    title: '服务器错误',
    message: '服务器开小差了，请稍后再试。'
  });
});

app.listen(PORT, () => {
  console.log(`✅ 购物网站已启动： http://localhost:${PORT}`);
  console.log(`   管理员账号：admin / admin123`);
});
