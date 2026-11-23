/**
 * 应用入口：Vue Router 路由表、登录守卫、根组件挂载
 */
(function () {
  const { createApp } = Vue;
  const { createRouter, createWebHistory } = VueRouter;

  const routes = [
    { path: '/', name: 'home', component: ShopViews.HomeView, meta: { title: '首页' } },
    { path: '/products', name: 'products', component: ShopViews.ProductsView, meta: { title: '全部商品' } },
    { path: '/products/:id', name: 'product-detail', component: ShopViews.ProductDetailView, meta: { title: '商品详情' } },
    { path: '/cart', name: 'cart', component: ShopViews.CartView, meta: { title: '购物车' } },
    {
      path: '/checkout',
      name: 'checkout',
      component: ShopViews.CheckoutView,
      meta: { title: '结算', requiresAuth: true }
    },
    {
      path: '/orders',
      name: 'orders',
      component: ShopViews.OrdersView,
      meta: { title: '我的订单', requiresAuth: true }
    },
    {
      path: '/orders/:id',
      name: 'order-detail',
      component: ShopViews.OrderDetailView,
      meta: { title: '订单详情', requiresAuth: true }
    },
    { path: '/login', name: 'login', component: ShopViews.LoginView, meta: { title: '登录', guestOnly: true } },
    { path: '/register', name: 'register', component: ShopViews.RegisterView, meta: { title: '注册', guestOnly: true } },
    { path: '/:pathMatch(.*)*', name: 'not-found', component: ShopViews.NotFoundView, meta: { title: '页面不存在' } }
  ];

  const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior(to, from, savedPosition) {
      return savedPosition || { top: 0 };
    }
  });

  router.beforeEach(async (to) => {
    if (!ShopStore.state.ready) await ShopStore.bootstrap();

    if (to.meta.requiresAuth && !ShopStore.state.user) {
      ShopStore.pushToast('请先登录', 'warning');
      return { name: 'login', query: { redirect: to.fullPath } };
    }

    if (to.meta.guestOnly && ShopStore.state.user) {
      return { name: 'home' };
    }

    return true;
  });

  router.afterEach((to) => {
    document.title = (to.meta.title ? to.meta.title + ' · ' : '') + '优选商城';
  });

  const App = {
    components: {
      AppNav: ShopComponents.AppNav,
      AppFooter: ShopComponents.AppFooter,
      ToastHost: ShopComponents.ToastHost
    },
    template: `
      <div class="app-shell">
        <AppNav />
        <main class="container app-main">
          <router-view v-slot="{ Component }">
            <Transition name="page" mode="out-in">
              <component :is="Component" :key="$route.path" />
            </Transition>
          </router-view>
        </main>
        <AppFooter />
        <ToastHost />
      </div>
    `
  };

  const app = createApp(App);
  app.use(router);

  ShopStore.bootstrap().finally(() => {
    app.mount('#app');
  });
})();
