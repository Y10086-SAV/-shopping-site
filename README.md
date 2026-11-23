# 优选商城 · Shopping Site

一个开箱即用的购物网站：**前台为 Vue 3 + Vue Router 单页应用（SPA）**，**后台管理沿用 EJS 服务端渲染**，服务端基于 **Node.js + Express** 提供 JSON 接口，使用 **JSON 文件**存储数据，零数据库依赖，克隆即跑。

[![Node](https://img.shields.io/badge/Node.js-%3E%3D18-339933?logo=node.js&logoColor=white)](https://nodejs.org)
[![Vue](https://img.shields.io/badge/Vue-3.x-42B883?logo=vue.js&logoColor=white)](https://vuejs.org)
[![Vue Router](https://img.shields.io/badge/Vue%20Router-4.x-35495E?logo=vue.js&logoColor=white)](https://router.vuejs.org)
[![Express](https://img.shields.io/badge/Express-4.x-000000?logo=express&logoColor=white)](https://expressjs.com)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

> 学习与演示项目，适合作为前端框架 / 全栈入门练手，或应届生简历中的实战作品。

## ✨ 功能特性

- 🛍️ **商品浏览与搜索**：首页推荐、分类筛选、关键词搜索、价格排序、商品详情
- 🛒 **购物车**：加购、改数量、删除、清空、金额实时合计（存于 session）
- 👤 **用户系统**：注册、登录、退出，密码使用 bcrypt 加密存储
- 📦 **下单结账**：收货信息表单校验、提交订单、自动扣减库存、订单列表与详情
- 🧑‍💼 **后台管理**：仪表盘统计（商品/订单/用户/营收）、商品增删改、订单状态流转、用户列表
- 🎬 **交互体验**：页面切换过渡、列表增删动画、骨架屏、全局提示、响应式适配

## 🧱 技术架构

- **前台**：Vue 3（组合式 API）+ Vue Router 4，单页应用，10 个路由视图（含 404）
- **后台**：EJS 服务端渲染，通过 `/admin` 访问，`requireAdmin` 中间件鉴权
- **服务端**：Express 提供 `/api` 下的 JSON 接口，`express-session` 维护登录态与购物车
- **数据层**：JSON 文件（`data/products.json`、`users.json`、`orders.json`）

> 改造原则：保留原有全部业务功能与后台实现，仅将面向用户的前台重构为 SPA；原有服务端表单路由继续保留以兼容旧链接。

## 📸 界面预览

| 首页 | 商品列表 |
| --- | --- |
| ![首页](docs/screenshots/home.png) | ![商品列表](docs/screenshots/products.png) |

| 商品详情 | 购物车 |
| --- | --- |
| ![商品详情](docs/screenshots/detail.png) | ![购物车](docs/screenshots/cart.png) |

| 登录 | 注册 |
| --- | --- |
| ![登录](docs/screenshots/login.png) | ![注册](docs/screenshots/register.png) |

| 结算 | 后台仪表盘 |
| --- | --- |
| ![结算](docs/screenshots/checkout.png) | ![后台仪表盘](docs/screenshots/admin.png) |

## 🚀 快速开始

环境要求：**Node.js ≥ 18**

```bash
# 1. 安装依赖
npm install

# 2. 启动服务
npm start
```

打开浏览器访问 <http://localhost:3000>。

首次启动会自动生成 `data/` 目录、12 件种子商品和默认管理员账号。

## 🔑 默认账号

| 角色   | 用户名 | 密码     |
| ------ | ------ | -------- |
| 管理员 | admin  | admin123 |

> ⚠️ 演示账号仅用于本地开发，部署前请务必修改。

## 📁 目录结构

```
shopping-site/
├── server.js                 # 服务端入口
├── package.json
├── src/
│   ├── store.js              # 数据层（JSON 读写 + 增删改查）
│   ├── middleware.js         # 全局中间件与鉴权
│   └── routes/
│       ├── api.js            # 前台 SPA 的 JSON 接口
│       ├── index.js          # 首页（返回 SPA 外壳）
│       ├── products.js       # 商品列表 / 详情（返回 SPA 外壳）
│       ├── cart.js           # 购物车
│       ├── auth.js           # 注册 / 登录 / 退出
│       ├── orders.js         # 结算 / 订单
│       └── admin.js          # 后台管理（EJS）
├── views/
│   ├── app.ejs               # SPA 外壳（前台唯一模板）
│   ├── admin/                # 后台 EJS 页面
│   └── partials/             # 后台公共头部 / 尾部
├── public/
│   ├── css/style.css         # 全局样式（含动画与响应式）
│   ├── js/
│   │   ├── api.js            # 接口封装
│   │   ├── validators.js     # 表单正则校验
│   │   ├── utils.js          # 格式化工具
│   │   ├── store.js          # 全局响应式状态
│   │   ├── components.js     # 公共组件（导航 / 卡片 / 步进器等）
│   │   ├── views.js          # 各页面视图组件
│   │   └── app.js            # 路由表与应用入口
│   └── vendor/               # 本地化的 Vue / Vue Router
├── scripts/reset.js          # 重置数据脚本
├── docs/
│   ├── screenshots/          # 页面效果图与功能结构图
│   └── 优选商城-前端技术课程设计报告.docx / .pdf
└── data/                     # 运行时自动生成（已 gitignore）
```

## 🛠️ 技术栈

| 依赖 | 用途 |
| --- | --- |
| [Vue 3](https://vuejs.org) | 前台框架（组合式 API） |
| [Vue Router 4](https://router.vuejs.org) | 前台路由与导航守卫 |
| [Express 4](https://expressjs.com) | Web 框架与 JSON 接口 |
| [EJS](https://ejs.co) | 后台管理模板引擎 |
| [express-session](https://github.com/expressjs/session) | 会话管理（登录态 + 购物车） |
| [bcryptjs](https://github.com/dcodeIO/bcrypt.js) | 密码加密 |
| [method-override](https://github.com/expressjs/method-override) | 表单支持 PUT / DELETE |

## 📄 设计报告

课程设计报告见 `docs/优选商城-前端技术课程设计报告.docx`（含需求分析、功能结构图、各模块实现与效果图、课程评分点对照及总结），同目录下提供 PDF 版本便于预览。

## 🧑‍💻 常用脚本

| 命令 | 说明 |
| --- | --- |
| `npm start` | 启动服务（常规模式） |
| `npm run dev` | 启动开发模式（文件变更自动重启） |
| `npm run reset` | 清空 `data/` 目录，重启后重新生成种子数据 |

## 📝 环境变量

复制 `.env.example` 为 `.env`（已 gitignore），按需修改：

| 变量 | 默认值 | 说明 |
| --- | --- | --- |
| `PORT` | `3000` | 服务端口 |
| `SESSION_SECRET` | 开发用默认值 | 会话加密密钥，生产环境务必修改 |

## ❓ FAQ

**端口被占用怎么办？**

```bash
PORT=3001 npm start
```

**如何重置数据？**

```bash
npm run reset
npm start
```

**图片加载慢或无法显示？**

种子数据使用 [picsum.photos](https://picsum.photos) 占位图，需联网。替换成本地图片可修改 `data/products.json` 中的 `image` 字段。

**购物车数据会丢吗？**

购物车存于服务端 session，浏览器关闭后按会话 cookie 有效期（默认 1 天）保留；生产部署建议改用持久化存储（如 Redis）。

## 📄 License

[MIT](LICENSE)

## ⚠️ 免责声明

本项目仅用于学习与演示，请勿直接用于生产环境。
