# 截图采集说明

README 中的截图位于 `docs/screenshots/`，统一为 **1366 × 900 PNG**，均为当前日漫天空樱花主题的实际运行效果。

## 截图清单

| 文件名 | 页面 | 来源 |
| --- | --- | --- |
| `home.png` | 首页 | PHP 版 `index.php` |
| `products.png` | 商品列表 | Node.js 版 `/products` |
| `detail.png` | 商品详情 | PHP 版 `product.php?id=1` |
| `cart.png` | 购物车 | PHP 版，演示账号 + 购物车数据 |
| `checkout.png` | 结算 | PHP 版，演示收货信息 |
| `orders.png` | 我的订单 | PHP 版，演示订单 |
| `order-detail.png` | 订单详情 | PHP 版，演示订单 |
| `login.png` | 登录 | PHP 版 `login.php` |
| `register.png` | 注册 | PHP 版 `register.php` |
| `admin.png` | 后台仪表盘 | PHP 版 `admin/index.php` |
| `admin-products.png` | 后台商品管理 | PHP 版 `admin/products.php` |
| `admin-orders.png` | 后台订单管理 | PHP 版 `admin/orders.php` |

## 重新采集

1. 启动 PHP 版：`php -S 127.0.0.1:8080 -t php`
2. 启动 Node.js 版：`npm start`（商品列表截图使用）
3. 浏览器视口设为 **1366 × 900**，隐藏滚动条。
4. 购物车、结算、订单与后台页面需要登录；建议使用临时演示账号和演示订单，避免暴露真实用户信息。
5. 后台订单截图中的收货人、手机号、订单号应替换为演示数据。
6. 截图完成后删除临时账号与订单，保持数据库干净。

> 可用 Playwright / Puppeteer / Edge Headless 等工具批量截图，再覆盖到本目录。
