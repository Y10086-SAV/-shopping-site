# 截图采集说明

项目 README 引用了 `docs/screenshots/` 下的截图。由于图片需在浏览器中实机截取，请按以下步骤在本地补全（每个页面各一张即可）。

## 启动项目

```bash
cd shopping-site
npm install
npm start
```

浏览器访问 <http://localhost:3000>。

## 待截取的页面清单

| 文件名 | 页面 | 说明 |
| --- | --- | --- |
| `home.png` | 首页 `/` | 顶部横幅 + 最新上架商品网格 |
| `products.png` | 商品列表 `/products` | 分类标签 + 搜索排序栏 + 商品网格 |
| `detail.png` | 商品详情 `/products/<id>` | 左侧商品图 + 右侧信息与加购按钮 |
| `cart.png` | 购物车 `/cart` | 先加几件商品，展示购物车条目与订单摘要 |
| `checkout.png` | 结算 `/checkout` | 需先登录，展示收货表单与商品清单 |
| `orders.png` | 我的订单 `/orders` | 提交订单后展示订单列表 |
| `admin.png` | 后台仪表盘 `/admin` | 用 admin / admin123 登录后访问 |

## 建议

- 使用浏览器开发者工具（F12）将视口宽度设为 1280px 左右，保证布局一致。
- 截图保存为 PNG，直接覆盖到 `docs/screenshots/` 目录，保持上述文件名即可。
- Windows 可用 `Win + Shift + S`，macOS 用 `Cmd + Shift + 4`。

> 提示：也可用 Playwright/Puppeteer 等工具自动截图，但手动截取对演示项目已足够。
