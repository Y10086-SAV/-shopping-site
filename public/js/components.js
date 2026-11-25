/**
 * 公共组件：导航、页脚、提示、商品卡片、数量步进器、空状态、骨架屏、状态徽章
 */
window.ShopComponents = (function () {
  const { computed, ref, watch } = Vue;
  const { useRouter } = VueRouter;

  const AppNav = {
    template: `
      <nav class="navbar">
        <div class="container navbar-inner">
          <router-link to="/" class="logo">优选商城</router-link>

          <button class="nav-toggle" type="button" aria-label="切换导航" @click="open = !open">
            <span></span><span></span><span></span>
          </button>

          <div class="nav-collapse" :class="{ open }">
            <div class="nav-links">
              <router-link to="/" exact-active-class="active" @click="open = false">首页</router-link>
              <router-link to="/products" active-class="active" @click="open = false">全部商品</router-link>
              <a v-if="user && user.role === 'admin'" href="/admin" @click="open = false">后台管理</a>
            </div>

            <div class="nav-actions">
              <router-link to="/cart" class="cart-link" title="购物车" @click="open = false">
                <span class="cart-icon">🛒</span>
                <span class="cart-badge" :class="{ pop: bump }">{{ cartCount }}</span>
              </router-link>

              <template v-if="user">
                <span class="nav-user">你好，{{ user.username }}</span>
                <router-link v-if="user.role !== 'admin'" to="/orders" class="btn btn-outline btn-sm" @click="open = false">我的订单</router-link>
                <button class="btn btn-outline btn-sm" type="button" @click="logout">退出</button>
              </template>
              <template v-else>
                <router-link to="/login" class="btn btn-outline btn-sm" @click="open = false">登录</router-link>
                <router-link to="/register" class="btn btn-primary btn-sm" @click="open = false">注册</router-link>
              </template>
            </div>
          </div>
        </div>
      </nav>
    `,
    setup() {
      const router = useRouter();
      const state = ShopStore.state;
      const open = ref(false);
      const bump = ref(false);
      let timer = null;

      watch(
        () => state.cartCount,
        (next, prev) => {
          if (next !== prev) {
            bump.value = true;
            clearTimeout(timer);
            timer = setTimeout(() => (bump.value = false), 320);
          }
        }
      );

      async function logout() {
        try {
          await ShopStore.logout();
          router.push('/');
        } catch (e) {
          ShopStore.pushToast(e.message, 'danger');
        }
      }

      return {
        open,
        bump,
        logout,
        user: computed(() => state.user),
        cartCount: computed(() => state.cartCount)
      };
    }
  };

  const AppFooter = {
    template: `
      <footer class="footer">
        <div class="container">
          <p class="footer-jp">樱花树下，好好购物。</p>
          <p>优选商城 · Vue 3 + Vue Router + Node.js/Express 课程设计项目</p>
          <p class="footer-sub">本站为教学演示项目，商品与订单数据仅用于功能展示。</p>
        </div>
      </footer>
    `
  };

  const ToastHost = {
    template: `
      <div class="toast-host" aria-live="polite">
        <TransitionGroup name="toast">
          <div v-for="t in state.toasts" :key="t.id" class="toast" :class="'toast-' + t.type">
            {{ t.message }}
          </div>
        </TransitionGroup>
      </div>
    `,
    setup() {
      return { state: ShopStore.state };
    }
  };

  const ProductCard = {
    props: {
      product: { type: Object, required: true }
    },
    template: `
      <router-link class="product-card" :to="'/products/' + product.id">
        <div class="thumb">
          <img :src="product.image" :alt="product.name" loading="lazy" />
        </div>
        <div class="info">
          <div class="name">{{ product.name }}</div>
          <div class="meta">{{ product.category }}</div>
          <div class="bottom">
            <span class="price">{{ money(product.price) }}</span>
            <span class="meta">库存 {{ product.stock }}</span>
          </div>
        </div>
      </router-link>
    `,
    setup() {
      return { money: ShopUtils.money };
    }
  };

  const ProductGrid = {
    props: {
      products: { type: Array, default: () => [] }
    },
    components: { ProductCard },
    template: `
      <TransitionGroup name="card" tag="div" class="product-grid">
        <ProductCard v-for="p in products" :key="p.id" :product="p" />
      </TransitionGroup>
    `
  };

  const QuantityStepper = {
    props: {
      modelValue: { type: Number, default: 1 },
      min: { type: Number, default: 1 },
      max: { type: Number, default: 99 }
    },
    emits: ['update:modelValue'],
    template: `
      <div class="stepper">
        <button type="button" class="stepper-btn" :disabled="modelValue <= min" @click="change(-1)">−</button>
        <input
          class="stepper-input"
          type="number"
          :value="modelValue"
          :min="min"
          :max="max"
          @change="onInput"
        />
        <button type="button" class="stepper-btn" :disabled="modelValue >= max" @click="change(1)">＋</button>
      </div>
    `,
    setup(props, { emit }) {
      function clamp(value) {
        return Math.min(props.max, Math.max(props.min, value));
      }
      function change(delta) {
        emit('update:modelValue', clamp(props.modelValue + delta));
      }
      function onInput(event) {
        const value = parseInt(event.target.value, 10);
        emit('update:modelValue', Number.isFinite(value) ? clamp(value) : props.min);
      }
      return { change, onInput };
    }
  };

  const EmptyState = {
    props: {
      icon: { type: String, default: '📦' },
      title: { type: String, default: '暂无内容' }
    },
    template: `
      <div class="empty">
        <div class="icon">{{ icon }}</div>
        <p>{{ title }}</p>
        <slot></slot>
      </div>
    `
  };

  const PageLoading = {
    props: {
      count: { type: Number, default: 8 }
    },
    template: `
      <div class="product-grid">
        <div v-for="i in count" :key="i" class="skeleton-card">
          <div class="skeleton skeleton-thumb"></div>
          <div class="skeleton skeleton-line"></div>
          <div class="skeleton skeleton-line short"></div>
        </div>
      </div>
    `
  };

  const StatusBadge = {
    props: {
      status: { type: String, default: '' }
    },
    template: `<span class="badge" :class="cls">{{ status }}</span>`,
    computed: {
      cls() {
        return ShopUtils.statusClass(this.status);
      }
    }
  };

  return {
    AppNav,
    AppFooter,
    ToastHost,
    ProductCard,
    ProductGrid,
    QuantityStepper,
    EmptyState,
    PageLoading,
    StatusBadge
  };
})();
