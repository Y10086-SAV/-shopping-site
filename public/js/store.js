/**
 * 全局响应式状态：登录用户、购物车数量、全局提示
 */
window.ShopStore = (function () {
  const { reactive } = Vue;

  const state = reactive({
    user: null,
    cartCount: 0,
    ready: false,
    toasts: []
  });

  let toastSeq = 0;

  function pushToast(message, type) {
    const id = ++toastSeq;
    state.toasts.push({ id, message, type: type || 'success' });
    setTimeout(() => {
      const index = state.toasts.findIndex((t) => t.id === id);
      if (index > -1) state.toasts.splice(index, 1);
    }, 2600);
  }

  async function bootstrap() {
    try {
      const data = await ShopAPI.me();
      state.user = data.user;
      state.cartCount = data.cartCount;
    } catch (e) {
      state.user = null;
    } finally {
      state.ready = true;
    }
  }

  function setUser(user, cartCount) {
    state.user = user;
    if (typeof cartCount === 'number') state.cartCount = cartCount;
  }

  async function logout() {
    await ShopAPI.logout();
    state.user = null;
    state.cartCount = 0;
    pushToast('已退出登录');
  }

  return { state, bootstrap, setUser, logout, pushToast };
})();
