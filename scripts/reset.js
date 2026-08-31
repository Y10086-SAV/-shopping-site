/**
 * 重置数据脚本：删除 data/ 目录，重启后自动重新生成种子商品与默认管理员。
 * 用法：npm run reset
 */
const fs = require('fs');
const path = require('path');

const DATA_DIR = path.join(__dirname, '..', 'data');

if (fs.existsSync(DATA_DIR)) {
  fs.rmSync(DATA_DIR, { recursive: true, force: true });
  console.log('已删除 data/ 目录。下次启动将重新生成种子数据。');
} else {
  console.log('data/ 目录不存在，无需清理。');
}
