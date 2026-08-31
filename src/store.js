/**
 * 数据层：基于 JSON 文件的轻量存储
 * 结构：data/products.json（商品）、data/users.json（用户）、data/orders.json（订单）
 * 购物车存于 session，不落盘。
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const bcrypt = require('bcryptjs');

const DATA_DIR = path.join(__dirname, '..', 'data');
const FILES = {
  products: path.join(DATA_DIR, 'products.json'),
  users: path.join(DATA_DIR, 'users.json'),
  orders: path.join(DATA_DIR, 'orders.json')
};

// ---------- 基础读写 ----------
function ensureDataDir() {
  if (!fs.existsSync(DATA_DIR)) fs.mkdirSync(DATA_DIR, { recursive: true });
}

function readJson(file) {
  try {
    if (!fs.existsSync(file)) return [];
    const raw = fs.readFileSync(file, 'utf-8');
    return JSON.parse(raw || '[]');
  } catch (e) {
    console.error(`读取 ${file} 失败：`, e.message);
    return [];
  }
}

function writeJson(file, data) {
  fs.writeFileSync(file, JSON.stringify(data, null, 2), 'utf-8');
}

function uid(prefix) {
  return prefix + '_' + crypto.randomBytes(8).toString('hex');
}

// ---------- 商品 ----------
const CATEGORIES = ['手机数码', '电脑办公', '家用电器', '服饰鞋包', '美妆个护', '食品生鲜'];

const SEED_PRODUCTS = [
  { name: '无线蓝牙耳机 Pro', category: '手机数码', price: 299, stock: 120, image: 'https://picsum.photos/seed/earbuds/600/600', description: '主动降噪，30 小时续航，支持快充，佩戴舒适。' },
  { name: '智能手表 S8', category: '手机数码', price: 1299, stock: 60, image: 'https://picsum.photos/seed/watch/600/600', description: '全天候健康监测，血氧/心率/睡眠，5ATM 防水。' },
  { name: '旗舰智能手机 X5', category: '手机数码', price: 4599, stock: 40, image: 'https://picsum.photos/seed/phone/600/600', description: '1 亿像素主摄，6.7 英寸 OLED，5000mAh 大电池。' },
  { name: '轻薄笔记本电脑 14', category: '电脑办公', price: 5499, stock: 30, image: 'https://picsum.photos/seed/laptop/600/600', description: '2.8K 高刷屏，16G 内存 + 512G 固态，1.4kg 轻至随身。' },
  { name: '机械键盘 87 键', category: '电脑办公', price: 399, stock: 200, image: 'https://picsum.photos/seed/keyboard/600/600', description: '热插拔轴体，RGB 背光，全键无冲。' },
  { name: '4K 显示器 27 英寸', category: '电脑办公', price: 1899, stock: 45, image: 'https://picsum.photos/seed/monitor/600/600', description: 'IPS 面板，99% sRGB，Type-C 一线连。' },
  { name: '扫地机器人', category: '家用电器', price: 2199, stock: 55, image: 'https://picsum.photos/seed/vacuum/600/600', description: '激光导航，自动集尘，扫拖一体。' },
  { name: '空气净化器', category: '家用电器', price: 899, stock: 70, image: 'https://picsum.photos/seed/airpurifier/600/600', description: '除醛除菌，CADR 500，静音运行。' },
  { name: '纯棉 T 恤', category: '服饰鞋包', price: 79, stock: 500, image: 'https://picsum.photos/seed/tshirt/600/600', description: '100% 纯棉，透气亲肤，多色可选。' },
  { name: '经典休闲运动鞋', category: '服饰鞋包', price: 349, stock: 150, image: 'https://picsum.photos/seed/shoes/600/600', description: '缓震鞋底，轻便透气，百搭款式。' },
  { name: '保湿面霜 50g', category: '美妆个护', price: 159, stock: 300, image: 'https://picsum.photos/seed/cream/600/600', description: '深层锁水，修护屏障，敏感肌可用。' },
  { name: '有机坚果礼盒', category: '食品生鲜', price: 129, stock: 400, image: 'https://picsum.photos/seed/nuts/600/600', description: '每日坚果 30 袋装，多种坚果科学配比。' }
];

function listProducts({ category, q, sort } = {}) {
  let items = readJson(FILES.products);
  if (category && category !== '全部') {
    items = items.filter((p) => p.category === category);
  }
  if (q) {
    const kw = String(q).toLowerCase();
    items = items.filter(
      (p) =>
        p.name.toLowerCase().includes(kw) ||
        (p.description || '').toLowerCase().includes(kw)
    );
  }
  if (sort === 'price_asc') items.sort((a, b) => a.price - b.price);
  else if (sort === 'price_desc') items.sort((a, b) => b.price - a.price);
  else if (sort === 'newest') items.sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));
  return items;
}

function getProduct(id) {
  return readJson(FILES.products).find((p) => p.id === id) || null;
}

function createProduct(data) {
  const items = readJson(FILES.products);
  const product = {
    id: uid('p'),
    name: data.name,
    category: data.category,
    price: Number(data.price),
    stock: Number(data.stock),
    image: data.image || 'https://picsum.photos/seed/default/600/600',
    description: data.description || '',
    createdAt: new Date().toISOString()
  };
  items.push(product);
  writeJson(FILES.products, items);
  return product;
}

function updateProduct(id, data) {
  const items = readJson(FILES.products);
  const idx = items.findIndex((p) => p.id === id);
  if (idx === -1) return null;
  items[idx] = {
    ...items[idx],
    name: data.name,
    category: data.category,
    price: Number(data.price),
    stock: Number(data.stock),
    image: data.image || items[idx].image,
    description: data.description || ''
  };
  writeJson(FILES.products, items);
  return items[idx];
}

function deleteProduct(id) {
  const items = readJson(FILES.products);
  const next = items.filter((p) => p.id !== id);
  if (next.length === items.length) return false;
  writeJson(FILES.products, next);
  return true;
}

// ---------- 用户 ----------
function listUsers() {
  return readJson(FILES.users);
}

function findUserByUsername(username) {
  return readJson(FILES.users).find((u) => u.username === username) || null;
}

function findUserByEmail(email) {
  return readJson(FILES.users).find((u) => u.email === email) || null;
}

function findUserById(id) {
  return readJson(FILES.users).find((u) => u.id === id) || null;
}

function createUser({ username, email, password }) {
  const users = readJson(FILES.users);
  const user = {
    id: uid('u'),
    username,
    email,
    passwordHash: bcrypt.hashSync(password, 10),
    role: 'customer',
    createdAt: new Date().toISOString()
  };
  users.push(user);
  writeJson(FILES.users, users);
  return user;
}

// ---------- 订单 ----------
function createOrder({ userId, items, total, address, receiver, phone }) {
  const orders = readJson(FILES.orders);
  const order = {
    id: uid('o'),
    userId,
    items,
    total,
    address,
    receiver,
    phone,
    status: '待发货',
    createdAt: new Date().toISOString()
  };
  orders.push(order);
  writeJson(FILES.orders, orders);
  // 扣减库存
  const products = readJson(FILES.products);
  for (const it of items) {
    const p = products.find((x) => x.id === it.id);
    if (p) p.stock = Math.max(0, p.stock - it.quantity);
  }
  writeJson(FILES.products, products);
  return order;
}

function listOrdersByUser(userId) {
  return readJson(FILES.orders)
    .filter((o) => o.userId === userId)
    .sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));
}

function listAllOrders() {
  return readJson(FILES.orders).sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));
}

function updateOrderStatus(id, status) {
  const orders = readJson(FILES.orders);
  const order = orders.find((o) => o.id === id);
  if (!order) return null;
  order.status = status;
  writeJson(FILES.orders, orders);
  return order;
}

// ---------- 初始化 ----------
function init() {
  ensureDataDir();

  if (!fs.existsSync(FILES.products)) {
    const products = SEED_PRODUCTS.map((p) => ({
      ...p,
      id: uid('p'),
      createdAt: new Date().toISOString()
    }));
    writeJson(FILES.products, products);
    console.log('已生成种子商品数据。');
  }

  if (!fs.existsSync(FILES.users)) {
    const users = [
      {
        id: 'u_admin',
        username: 'admin',
        email: 'admin@example.com',
        passwordHash: bcrypt.hashSync('admin123', 10),
        role: 'admin',
        createdAt: new Date().toISOString()
      }
    ];
    writeJson(FILES.users, users);
    console.log('已生成默认管理员：admin / admin123');
  }

  if (!fs.existsSync(FILES.orders)) {
    writeJson(FILES.orders, []);
  }
}

module.exports = {
  CATEGORIES,
  init,
  listProducts,
  getProduct,
  createProduct,
  updateProduct,
  deleteProduct,
  listUsers,
  findUserByUsername,
  findUserByEmail,
  findUserById,
  createUser,
  createOrder,
  listOrdersByUser,
  listAllOrders,
  updateOrderStatus
};
