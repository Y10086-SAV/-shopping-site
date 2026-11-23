/**
 * 接口封装：统一处理 fetch、JSON 解析与错误信息
 * 所有请求携带同源 Cookie，复用 Express 的 session 登录态
 */
window.ShopAPI = (function () {
  async function request(url, options) {
    const opts = Object.assign({ credentials: 'same-origin', headers: {} }, options || {});

    if (opts.body !== undefined && typeof opts.body !== 'string') {
      opts.headers['Content-Type'] = 'application/json';
      opts.body = JSON.stringify(opts.body);
    }

    const res = await fetch(url, opts);
    let data = null;
    try {
      data = await res.json();
    } catch (e) {
      data = null;
    }

    if (!res.ok) {
      const err = new Error((data && data.message) || '请求失败，请稍后再试');
      err.status = res.status;
      err.data = data || {};
      throw err;
    }
    return data;
  }

  function buildQuery(params) {
    const search = new URLSearchParams();
    Object.keys(params || {}).forEach((key) => {
      const value = params[key];
      if (value !== undefined && value !== null && value !== '' && value !== '全部') {
        search.set(key, value);
      }
    });
    const qs = search.toString();
    return qs ? '?' + qs : '';
  }

  return {
    request,
    me: () => request('/api/me'),

    products: (params) => request('/api/products' + buildQuery(params)),
    product: (id) => request('/api/products/' + encodeURIComponent(id)),

    cart: () => request('/api/cart'),
    addToCart: (productId, quantity) =>
      request('/api/cart/add', { method: 'POST', body: { productId, quantity } }),
    updateCart: (id, quantity) =>
      request('/api/cart/' + encodeURIComponent(id), { method: 'PUT', body: { quantity } }),
    removeCart: (id) =>
      request('/api/cart/' + encodeURIComponent(id), { method: 'DELETE' }),
    clearCart: () => request('/api/cart', { method: 'DELETE' }),

    register: (payload) =>
      request('/api/auth/register', { method: 'POST', body: payload }),
    login: (payload) =>
      request('/api/auth/login', { method: 'POST', body: payload }),
    logout: () => request('/api/auth/logout', { method: 'POST' }),

    checkout: (payload) =>
      request('/api/checkout', { method: 'POST', body: payload }),
    orders: () => request('/api/orders'),
    order: (id) => request('/api/orders/' + encodeURIComponent(id))
  };
})();
