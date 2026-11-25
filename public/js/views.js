/**
 * 页面视图：首页、商品列表、商品详情、购物车、结算、订单、登录、注册、404
 */
window.ShopViews = (function () {
  const { ref, reactive, computed, onMounted, onBeforeUnmount, watch } = Vue;
  const { useRoute, useRouter } = VueRouter;
  const { money, dateTime } = ShopUtils;

  // ---------------- 首页 ----------------
  const HomeView = {
    components: {
      ProductGrid: ShopComponents.ProductGrid,
      PageLoading: ShopComponents.PageLoading,
      EmptyState: ShopComponents.EmptyState
    },
    template: `
      <div>
        <section class="hero">
          <div class="hero-content">
            <span class="hero-tag">🌸 春日新品 · 2025 精选好物</span>
            <p class="hero-jp">今天，也遇见好东西。</p>
            <h1>优选好物<br><em>一站购齐</em></h1>
            <p>精选数码、家电、服饰与美妆，把心动好物轻轻带回家。</p>
            <div class="hero-actions">
              <router-link to="/products" class="btn btn-light btn-lg">立即选购 →</router-link>
              <router-link to="/register" class="btn btn-ghost btn-lg">注册会员</router-link>
            </div>
            <div class="hero-float-card">
              <span class="hero-avatar"><img src="/img/mascot-clerk.png" alt="" /></span>
              <div>
                <strong>今日推荐</strong>
                <small>12 件好物已上架</small>
              </div>
            </div>
          </div>
          <div class="hero-art" aria-hidden="true"></div>
        </section>

        <section class="feature-strip">
          <div class="feature-item"><span>🚚</span><div><strong>极速发货</strong><small>下单后 24 小时内发出</small></div></div>
          <div class="feature-item"><span>🛡️</span><div><strong>正品保障</strong><small>官方渠道，品质可溯</small></div></div>
          <div class="feature-item"><span>💬</span><div><strong>贴心客服</strong><small>售前售后全程陪伴</small></div></div>
        </section>

        <div class="section-head">
          <h2>最新上架</h2>
          <router-link to="/products" class="section-link">查看全部 →</router-link>
        </div>

        <PageLoading v-if="loading" />
        <EmptyState v-else-if="!products.length" icon="📦" title="暂无商品"></EmptyState>
        <ProductGrid v-else :products="products" />
      </div>
    `,
    setup() {
      const products = ref([]);
      const loading = ref(true);

      onMounted(async () => {
        try {
          const data = await ShopAPI.products({ sort: 'newest' });
          products.value = data.products.slice(0, 8);
        } catch (e) {
          ShopStore.pushToast(e.message, 'danger');
        } finally {
          loading.value = false;
        }
      });

      return { products, loading };
    }
  };

  // ---------------- 商品列表 ----------------
  const ProductsView = {
    components: {
      ProductGrid: ShopComponents.ProductGrid,
      PageLoading: ShopComponents.PageLoading,
      EmptyState: ShopComponents.EmptyState
    },
    template: `
      <div>
        <div class="page-head">
          <div>
            <h1 class="page-title">全部商品</h1>
            <p class="page-sub">共 {{ products.length }} 件商品</p>
          </div>
        </div>

        <div class="category-tabs">
          <button
            v-for="c in allCategories"
            :key="c"
            type="button"
            class="category-tab"
            :class="{ active: category === c }"
            @click="selectCategory(c)"
          >{{ c }}</button>
        </div>

        <div class="filter-bar">
          <form class="filter-form" @submit.prevent="onSearch">
            <input
              v-model="q"
              class="search-input"
              type="search"
              placeholder="搜索商品名称或描述…"
              @input="onInput"
            />
            <select v-model="sort" class="select" @change="onSearch">
              <option value="">默认排序</option>
              <option value="price_asc">价格从低到高</option>
              <option value="price_desc">价格从高到低</option>
              <option value="newest">最新上架</option>
            </select>
            <button class="btn btn-primary" type="submit">搜索</button>
          </form>
        </div>

        <PageLoading v-if="loading" />
        <EmptyState v-else-if="!products.length" icon="🔍" title="没有找到匹配的商品">
          <button class="btn btn-outline" type="button" @click="reset">清除筛选条件</button>
        </EmptyState>
        <ProductGrid v-else :products="products" />
      </div>
    `,
    setup() {
      const route = useRoute();
      const router = useRouter();

      const products = ref([]);
      const categories = ref([]);
      const loading = ref(true);
      const category = ref(route.query.category || '全部');
      const q = ref(route.query.q || '');
      const sort = ref(route.query.sort || '');
      let timer = null;

      const allCategories = computed(() => ['全部'].concat(categories.value));

      async function load() {
        loading.value = true;
        try {
          const data = await ShopAPI.products({
            category: category.value,
            q: q.value,
            sort: sort.value
          });
          products.value = data.products;
          categories.value = data.categories;
        } catch (e) {
          ShopStore.pushToast(e.message, 'danger');
        } finally {
          loading.value = false;
        }
      }

      function syncUrl() {
        const query = {};
        if (category.value !== '全部') query.category = category.value;
        if (q.value) query.q = q.value;
        if (sort.value) query.sort = sort.value;
        router.replace({ name: 'products', query });
      }

      function onSearch() {
        clearTimeout(timer);
        syncUrl();
        load();
      }

      function onInput() {
        clearTimeout(timer);
        timer = setTimeout(() => {
          syncUrl();
          load();
        }, 300);
      }

      function selectCategory(value) {
        category.value = value;
        syncUrl();
        load();
      }

      function reset() {
        category.value = '全部';
        q.value = '';
        sort.value = '';
        syncUrl();
        load();
      }

      watch(
        () => route.query,
        (query) => {
          const nextCategory = query.category || '全部';
          const nextQ = query.q || '';
          const nextSort = query.sort || '';
          if (nextCategory !== category.value || nextQ !== q.value || nextSort !== sort.value) {
            category.value = nextCategory;
            q.value = nextQ;
            sort.value = nextSort;
            load();
          }
        }
      );

      onMounted(load);
      onBeforeUnmount(() => clearTimeout(timer));

      return {
        products,
        loading,
        category,
        q,
        sort,
        allCategories,
        onSearch,
        onInput,
        selectCategory,
        reset
      };
    }
  };

  // ---------------- 商品详情 ----------------
  const ProductDetailView = {
    components: {
      QuantityStepper: ShopComponents.QuantityStepper,
      PageLoading: ShopComponents.PageLoading,
      EmptyState: ShopComponents.EmptyState
    },
    template: `
      <div>
        <div class="breadcrumb">
          <router-link to="/">首页</router-link>
          <span>/</span>
          <router-link to="/products">全部商品</router-link>
          <span>/</span>
          <span>{{ product ? product.name : '商品详情' }}</span>
        </div>

        <PageLoading v-if="loading" :count="2" />
        <EmptyState v-else-if="!product" icon="😕" title="商品不存在或已下架">
          <router-link to="/products" class="btn btn-primary">返回商品列表</router-link>
        </EmptyState>

        <div v-else class="detail-wrap">
          <div class="detail-image">
            <img :src="product.image" :alt="product.name" />
          </div>
          <div class="detail-info">
            <span class="meta">{{ product.category }}</span>
            <h1>{{ product.name }}</h1>
            <div class="detail-price">{{ money(product.price) }}</div>
            <p class="detail-desc">{{ product.description }}</p>
            <div class="stock">库存：{{ product.stock }} 件</div>

            <div class="qty-box">
              <label>数量</label>
              <QuantityStepper v-model="qty" :min="1" :max="maxQty" />
            </div>

            <button
              class="btn btn-primary btn-lg"
              type="button"
              :disabled="product.stock < 1 || adding"
              @click="addToCart"
            >{{ product.stock < 1 ? '已售罄' : (adding ? '加入中…' : '加入购物车') }}</button>

            <ul class="service-list">
              <li>✔ 正品保障</li>
              <li>✔ 极速发货</li>
              <li>✔ 7 天无理由退换</li>
            </ul>
          </div>
        </div>
      </div>
    `,
    setup() {
      const route = useRoute();
      const product = ref(null);
      const loading = ref(true);
      const qty = ref(1);
      const adding = ref(false);

      const maxQty = computed(() => (product.value ? Math.max(1, product.value.stock) : 1));

      async function load(id) {
        loading.value = true;
        qty.value = 1;
        try {
          const data = await ShopAPI.product(id);
          product.value = data.product;
        } catch (e) {
          product.value = null;
        } finally {
          loading.value = false;
        }
      }

      async function addToCart() {
        if (!product.value || product.value.stock < 1) return;
        adding.value = true;
        try {
          const data = await ShopAPI.addToCart(product.value.id, qty.value);
          ShopStore.state.cartCount = data.count;
          ShopStore.pushToast(data.message);
        } catch (e) {
          ShopStore.pushToast(e.message, 'danger');
        } finally {
          adding.value = false;
        }
      }

      watch(
        () => route.params.id,
        (id) => {
          if (id) load(id);
        },
        { immediate: true }
      );

      return { product, loading, qty, adding, maxQty, addToCart, money };
    }
  };

  // ---------------- 购物车 ----------------
  const CartView = {
    components: {
      QuantityStepper: ShopComponents.QuantityStepper,
      PageLoading: ShopComponents.PageLoading,
      EmptyState: ShopComponents.EmptyState
    },
    template: `
      <div>
        <div class="page-head">
          <div>
            <h1 class="page-title">购物车</h1>
            <p class="page-sub" v-if="items.length">共 {{ count }} 件商品</p>
          </div>
          <router-link to="/products" class="btn btn-outline btn-sm">继续购物</router-link>
        </div>

        <PageLoading v-if="loading" :count="4" />

        <EmptyState v-else-if="!items.length" icon="🛒" title="购物车还是空的">
          <router-link to="/products" class="btn btn-primary">去逛逛</router-link>
        </EmptyState>

        <div v-else class="cart-layout">
          <div class="table-wrap cart-table">
            <TransitionGroup name="cart" tag="div">
              <div v-for="item in items" :key="item.id" class="cart-item">
                <img class="thumb-sm" :src="item.image" :alt="item.name" />
                <div class="cart-info">
                  <router-link :to="'/products/' + item.id" class="cart-name">{{ item.name }}</router-link>
                  <div class="price">{{ money(item.price) }}</div>
                </div>
                <QuantityStepper
                  :model-value="item.quantity"
                  :min="1"
                  :max="99"
                  @update:model-value="v => updateQty(item, v)"
                />
                <div class="cart-subtotal">¥{{ money(item.price * item.quantity) }}</div>
                <button class="btn btn-danger btn-sm" type="button" @click="remove(item)">删除</button>
              </div>
            </TransitionGroup>

            <div class="cart-toolbar">
              <button class="btn btn-outline btn-sm" type="button" @click="clear">清空购物车</button>
            </div>
          </div>

          <div class="summary-card">
            <h3>订单摘要</h3>
            <div class="summary-row"><span>商品件数</span><span>{{ count }} 件</span></div>
            <div class="summary-row"><span>商品金额</span><span>¥{{ money(total) }}</span></div>
            <div class="summary-row"><span>运费</span><span>免运费</span></div>
            <div class="summary-total"><span>合计</span><span class="price">{{ money(total) }}</span></div>
            <router-link to="/checkout" class="btn btn-primary btn-block">去结算</router-link>
          </div>
        </div>
      </div>
    `,
    setup() {
      const router = useRouter();
      const items = ref([]);
      const total = ref(0);
      const count = ref(0);
      const loading = ref(true);

      function apply(data) {
        items.value = data.cart;
        total.value = data.total;
        count.value = data.count;
        ShopStore.state.cartCount = data.count;
      }

      async function load() {
        loading.value = true;
        try {
          apply(await ShopAPI.cart());
        } catch (e) {
          ShopStore.pushToast(e.message, 'danger');
        } finally {
          loading.value = false;
        }
      }

      async function updateQty(item, value) {
        if (value === item.quantity) return;
        try {
          apply(await ShopAPI.updateCart(item.id, value));
        } catch (e) {
          ShopStore.pushToast(e.message, 'danger');
          load();
        }
      }

      async function remove(item) {
        try {
          const data = await ShopAPI.removeCart(item.id);
          apply(data);
          ShopStore.pushToast(data.message);
        } catch (e) {
          ShopStore.pushToast(e.message, 'danger');
        }
      }

      async function clear() {
        try {
          const data = await ShopAPI.clearCart();
          apply(data);
          ShopStore.pushToast(data.message);
          router.push('/cart');
        } catch (e) {
          ShopStore.pushToast(e.message, 'danger');
        }
      }

      onMounted(load);

      return { items, total, count, loading, updateQty, remove, clear, money };
    }
  };

  // ---------------- 结算 ----------------
  const CheckoutView = {
    components: {
      PageLoading: ShopComponents.PageLoading
    },
    template: `
      <div>
        <h1 class="page-title">结算</h1>

        <PageLoading v-if="loading" :count="4" />

        <div v-else class="cart-layout">
          <div class="form-card checkout-card">
            <h2>收货信息</h2>
            <form @submit.prevent="submit" novalidate>
              <div class="form-group">
                <label for="receiver">收货人</label>
                <input
                  id="receiver"
                  v-model.trim="form.receiver"
                  type="text"
                  placeholder="请输入收货人姓名"
                  :class="{ invalid: errors.receiver }"
                  @blur="validateField('receiver')"
                />
                <div v-if="errors.receiver" class="form-error">{{ errors.receiver }}</div>
              </div>

              <div class="form-group">
                <label for="phone">手机号</label>
                <input
                  id="phone"
                  v-model.trim="form.phone"
                  type="tel"
                  maxlength="11"
                  placeholder="11 位手机号"
                  :class="{ invalid: errors.phone }"
                  @blur="validateField('phone')"
                />
                <div v-if="errors.phone" class="form-error">{{ errors.phone }}</div>
              </div>

              <div class="form-group">
                <label for="address">收货地址</label>
                <textarea
                  id="address"
                  v-model.trim="form.address"
                  rows="3"
                  placeholder="请填写详细收货地址"
                  :class="{ invalid: errors.address }"
                  @blur="validateField('address')"
                ></textarea>
                <div v-if="errors.address" class="form-error">{{ errors.address }}</div>
              </div>

              <button class="btn btn-primary btn-block" type="submit" :disabled="submitting">
                {{ submitting ? '提交中…' : '提交订单' }}
              </button>
            </form>
          </div>

          <div class="summary-card">
            <h3>商品清单</h3>
            <div v-for="item in cart" :key="item.id" class="summary-row">
              <span>{{ item.name }} × {{ item.quantity }}</span>
              <span>¥{{ money(item.price * item.quantity) }}</span>
            </div>
            <div class="summary-total"><span>合计</span><span class="price">{{ money(total) }}</span></div>
          </div>
        </div>
      </div>
    `,
    setup() {
      const router = useRouter();
      const form = reactive({ receiver: '', phone: '', address: '' });
      const errors = reactive({});
      const cart = ref([]);
      const total = ref(0);
      const loading = ref(true);
      const submitting = ref(false);

      function validateField(field) {
        const next = ShopValidators.validateCheckout(form);
        if (next[field]) errors[field] = next[field];
        else delete errors[field];
      }

      async function load() {
        try {
          const data = await ShopAPI.cart();
          if (!data.cart.length) {
            ShopStore.pushToast('购物车是空的', 'warning');
            router.replace('/cart');
            return;
          }
          cart.value = data.cart;
          total.value = data.total;
        } catch (e) {
          ShopStore.pushToast(e.message, 'danger');
        } finally {
          loading.value = false;
        }
      }

      async function submit() {
        const next = ShopValidators.validateCheckout(form);
        Object.keys(errors).forEach((key) => delete errors[key]);
        Object.assign(errors, next);
        if (Object.keys(next).length) return;

        submitting.value = true;
        try {
          const data = await ShopAPI.checkout({ ...form });
          ShopStore.state.cartCount = 0;
          ShopStore.pushToast(data.message);
          router.push('/orders/' + data.order.id);
        } catch (e) {
          if (e.data && e.data.errors) Object.assign(errors, e.data.errors);
          ShopStore.pushToast(e.message, 'danger');
        } finally {
          submitting.value = false;
        }
      }

      onMounted(load);

      return { form, errors, cart, total, loading, submitting, validateField, submit, money };
    }
  };

  // ---------------- 我的订单 ----------------
  const OrdersView = {
    components: {
      PageLoading: ShopComponents.PageLoading,
      EmptyState: ShopComponents.EmptyState,
      StatusBadge: ShopComponents.StatusBadge
    },
    template: `
      <div>
        <h1 class="page-title">我的订单</h1>

        <PageLoading v-if="loading" :count="4" />
        <EmptyState v-else-if="!orders.length" icon="📋" title="暂无订单">
          <router-link to="/products" class="btn btn-primary">去购物</router-link>
        </EmptyState>

        <div v-else class="table-wrap">
          <table>
            <thead>
              <tr><th>订单号</th><th>下单时间</th><th>金额</th><th>状态</th><th></th></tr>
            </thead>
            <tbody>
              <tr v-for="o in orders" :key="o.id">
                <td><code>{{ o.id }}</code></td>
                <td>{{ dateTime(o.createdAt) }}</td>
                <td><span class="price">{{ money(o.total) }}</span></td>
                <td><StatusBadge :status="o.status" /></td>
                <td><router-link :to="'/orders/' + o.id" class="btn btn-outline btn-sm">查看</router-link></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    `,
    setup() {
      const orders = ref([]);
      const loading = ref(true);

      onMounted(async () => {
        try {
          const data = await ShopAPI.orders();
          orders.value = data.orders;
        } catch (e) {
          ShopStore.pushToast(e.message, 'danger');
        } finally {
          loading.value = false;
        }
      });

      return { orders, loading, money, dateTime };
    }
  };

  // ---------------- 订单详情 ----------------
  const OrderDetailView = {
    components: {
      PageLoading: ShopComponents.PageLoading,
      EmptyState: ShopComponents.EmptyState,
      StatusBadge: ShopComponents.StatusBadge
    },
    template: `
      <div>
        <div class="breadcrumb">
          <router-link to="/">首页</router-link>
          <span>/</span>
          <router-link to="/orders">我的订单</router-link>
          <span>/</span>
          <span>订单详情</span>
        </div>

        <PageLoading v-if="loading" :count="4" />
        <EmptyState v-else-if="!order" icon="😕" :title="error || '订单不存在'">
          <router-link to="/orders" class="btn btn-primary">返回订单列表</router-link>
        </EmptyState>

        <div v-else class="order-detail">
          <div class="table-wrap">
            <div class="order-head">
              <div>
                <span class="meta">订单号</span>
                <code>{{ order.id }}</code>
              </div>
              <StatusBadge :status="order.status" />
            </div>

            <div class="order-meta">
              <p><strong>下单时间：</strong>{{ dateTime(order.createdAt) }}</p>
              <p><strong>收货信息：</strong>{{ order.receiver }} · {{ order.phone }} · {{ order.address }}</p>
            </div>

            <table>
              <thead>
                <tr><th>商品</th><th>单价</th><th>数量</th><th>小计</th></tr>
              </thead>
              <tbody>
                <tr v-for="item in order.items" :key="item.id">
                  <td>{{ item.name }}</td>
                  <td>¥{{ money(item.price) }}</td>
                  <td>× {{ item.quantity }}</td>
                  <td>¥{{ money(item.price * item.quantity) }}</td>
                </tr>
              </tbody>
            </table>

            <div class="order-total">合计：<span class="price">{{ money(order.total) }}</span></div>
          </div>

          <router-link to="/orders" class="btn btn-outline order-back">返回订单列表</router-link>
        </div>
      </div>
    `,
    setup() {
      const route = useRoute();
      const order = ref(null);
      const loading = ref(true);
      const error = ref('');

      onMounted(async () => {
        try {
          const data = await ShopAPI.order(route.params.id);
          order.value = data.order;
        } catch (e) {
          error.value = e.message;
        } finally {
          loading.value = false;
        }
      });

      return { order, loading, error, money, dateTime };
    }
  };

  // ---------------- 登录 ----------------
  const LoginView = {
    template: `
      <section class="auth-page">
        <div class="auth-card">
          <aside class="auth-brand">
            <div class="auth-brand-inner">
              <div class="auth-logo">优选商城</div>
              <p class="auth-jp">再次见面，真好</p>
              <h2>欢迎回来</h2>
              <p>登录后即可同步购物车、查看订单，把心动好物继续留在清单里。</p>
              <ul class="auth-points">
                <li><span>✓</span>正品保障，极速发货</li>
                <li><span>✓</span>购物车与订单实时同步</li>
                <li><span>✓</span>会员专属优惠活动</li>
              </ul>
            </div>
            <div class="auth-blob blob-a"></div>
            <div class="auth-blob blob-b"></div>
          </aside>

          <div class="auth-form">
            <h1>账号登录</h1>
            <p class="auth-sub">还没有账号？<router-link to="/register">立即注册</router-link></p>

            <form @submit.prevent="submit" novalidate>
              <div class="form-group">
                <label for="login-username">用户名</label>
                <input
                  id="login-username"
                  v-model.trim="form.username"
                  type="text"
                  placeholder="请输入用户名"
                  autocomplete="username"
                  :class="{ invalid: errors.username }"
                  @blur="validateField('username')"
                />
                <div v-if="errors.username" class="form-error">{{ errors.username }}</div>
              </div>

              <div class="form-group">
                <label for="login-password">密码</label>
                <div class="password-field">
                  <input
                    id="login-password"
                    v-model="form.password"
                    :type="showPassword ? 'text' : 'password'"
                    placeholder="请输入密码"
                    autocomplete="current-password"
                    :class="{ invalid: errors.password }"
                    @blur="validateField('password')"
                  />
                  <button type="button" class="password-toggle" @click="showPassword = !showPassword">
                    {{ showPassword ? '隐藏' : '显示' }}
                  </button>
                </div>
                <div v-if="errors.password" class="form-error">{{ errors.password }}</div>
              </div>

              <div v-if="serverError" class="form-error form-error-banner">{{ serverError }}</div>

              <button class="btn btn-primary btn-block" type="submit" :disabled="submitting">
                {{ submitting ? '登录中…' : '登录' }}
              </button>
            </form>

            <div class="auth-demo">演示管理员账号：admin / admin123</div>
          </div>
        </div>
      </section>
    `,
    setup() {
      const route = useRoute();
      const router = useRouter();
      const form = reactive({ username: '', password: '' });
      const errors = reactive({});
      const showPassword = ref(false);
      const submitting = ref(false);
      const serverError = ref('');

      function validateField(field) {
        const next = ShopValidators.validateLogin(form);
        if (next[field]) errors[field] = next[field];
        else delete errors[field];
      }

      async function submit() {
        const next = ShopValidators.validateLogin(form);
        Object.keys(errors).forEach((key) => delete errors[key]);
        Object.assign(errors, next);
        serverError.value = '';
        if (Object.keys(next).length) return;

        submitting.value = true;
        try {
          const data = await ShopAPI.login({ ...form });
          ShopStore.setUser(data.user, data.cartCount);
          ShopStore.pushToast(data.message);
          const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : '/';
          router.replace(redirect.startsWith('/') ? redirect : '/');
        } catch (e) {
          serverError.value = e.message;
        } finally {
          submitting.value = false;
        }
      }

      return { form, errors, showPassword, submitting, serverError, validateField, submit };
    }
  };

  // ---------------- 注册 ----------------
  const RegisterView = {
    template: `
      <section class="auth-page">
        <div class="auth-card">
          <aside class="auth-brand">
            <div class="auth-brand-inner">
              <div class="auth-logo">优选商城</div>
              <p class="auth-jp">初次见面，请多关照</p>
              <h2>加入优选</h2>
              <p>注册会员，开启你的春日好物清单，让每一次逛街都像翻开新的一页。</p>
              <ul class="auth-points">
                <li><span>✓</span>精选好物，一站购齐</li>
                <li><span>✓</span>订单状态实时可查</li>
                <li><span>✓</span>专属客服贴心服务</li>
              </ul>
            </div>
            <div class="auth-blob blob-a"></div>
            <div class="auth-blob blob-b"></div>
          </aside>

          <div class="auth-form">
            <h1>创建账号</h1>
            <p class="auth-sub">已有账号？<router-link to="/login">去登录</router-link></p>

            <form @submit.prevent="submit" novalidate>
              <div class="form-group">
                <label for="reg-username">用户名</label>
                <input
                  id="reg-username"
                  v-model.trim="form.username"
                  type="text"
                  placeholder="至少 2 个字符"
                  autocomplete="username"
                  :class="{ invalid: errors.username }"
                  @blur="validateField('username')"
                />
                <div v-if="errors.username" class="form-error">{{ errors.username }}</div>
              </div>

              <div class="form-group">
                <label for="reg-email">邮箱</label>
                <input
                  id="reg-email"
                  v-model.trim="form.email"
                  type="email"
                  placeholder="name@example.com"
                  autocomplete="email"
                  :class="{ invalid: errors.email }"
                  @blur="validateField('email')"
                />
                <div v-if="errors.email" class="form-error">{{ errors.email }}</div>
              </div>

              <div class="form-group">
                <label for="reg-password">密码</label>
                <div class="password-field">
                  <input
                    id="reg-password"
                    v-model="form.password"
                    :type="showPassword ? 'text' : 'password'"
                    placeholder="至少 6 位"
                    autocomplete="new-password"
                    :class="{ invalid: errors.password }"
                    @blur="validateField('password')"
                  />
                  <button type="button" class="password-toggle" @click="showPassword = !showPassword">
                    {{ showPassword ? '隐藏' : '显示' }}
                  </button>
                </div>
                <div v-if="errors.password" class="form-error">{{ errors.password }}</div>
              </div>

              <div class="form-group">
                <label for="reg-confirm">确认密码</label>
                <input
                  id="reg-confirm"
                  v-model="form.confirm"
                  :type="showPassword ? 'text' : 'password'"
                  placeholder="请再次输入密码"
                  autocomplete="new-password"
                  :class="{ invalid: errors.confirm }"
                  @blur="validateField('confirm')"
                />
                <div v-if="errors.confirm" class="form-error">{{ errors.confirm }}</div>
              </div>

              <div v-if="serverError" class="form-error form-error-banner">{{ serverError }}</div>

              <button class="btn btn-primary btn-block" type="submit" :disabled="submitting">
                {{ submitting ? '注册中…' : '注册并登录' }}
              </button>
            </form>
          </div>
        </div>
      </section>
    `,
    setup() {
      const router = useRouter();
      const form = reactive({ username: '', email: '', password: '', confirm: '' });
      const errors = reactive({});
      const showPassword = ref(false);
      const submitting = ref(false);
      const serverError = ref('');

      function validateField(field) {
        const next = ShopValidators.validateRegister(form);
        if (next[field]) errors[field] = next[field];
        else delete errors[field];
      }

      async function submit() {
        const next = ShopValidators.validateRegister(form);
        Object.keys(errors).forEach((key) => delete errors[key]);
        Object.assign(errors, next);
        serverError.value = '';
        if (Object.keys(next).length) return;

        submitting.value = true;
        try {
          const data = await ShopAPI.register({ ...form });
          ShopStore.setUser(data.user, data.cartCount);
          ShopStore.pushToast(data.message);
          router.replace('/');
        } catch (e) {
          if (e.data && e.data.errors) Object.assign(errors, e.data.errors);
          serverError.value = e.message;
        } finally {
          submitting.value = false;
        }
      }

      return { form, errors, showPassword, submitting, serverError, validateField, submit };
    }
  };

  // ---------------- 404 ----------------
  const NotFoundView = {
    template: `
      <div class="empty not-found">
        <div class="icon">😕</div>
        <h2>404</h2>
        <p>你访问的页面不存在或已被移除。</p>
        <router-link to="/" class="btn btn-primary">返回首页</router-link>
      </div>
    `
  };

  return {
    HomeView,
    ProductsView,
    ProductDetailView,
    CartView,
    CheckoutView,
    OrdersView,
    OrderDetailView,
    LoginView,
    RegisterView,
    NotFoundView
  };
})();
