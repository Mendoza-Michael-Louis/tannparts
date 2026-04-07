// ============================================================
// CoreVault — app.js
// All data is fetched from PHP/MySQL via the /api/ endpoints.
// ============================================================

// ============================================================
// STATE
// ============================================================
let currentUser  = null;   // { id, name, email, initials }
let PRODUCTS     = [];     // loaded from DB
let CATEGORIES   = [];     // loaded from DB
let cart         = [];     // [{ product_id, name, icon, price, quantity, ... }]
let currentPage  = 'home';
let currentCat   = null;

// ============================================================
// API HELPERS
// ============================================================
async function api(url, method = 'GET', body = null) {
  const opts = {
    method,
    headers: { 'Content-Type': 'application/json' },
    credentials: 'same-origin',
  };
  if (body) opts.body = JSON.stringify(body);
  const res  = await fetch(url, opts);
  const data = await res.json();
  return data;
}

function apiGet(url)             { return api(url, 'GET'); }
function apiPost(url, body = {}) { return api(url, 'POST', body); }

// ============================================================
// INIT — runs on page load
// ============================================================
async function init() {
  showPageLoader();
  try {
    const [sessionRes, productsRes, catsRes] = await Promise.all([
      apiGet('api/auth.php?action=me'),
      apiGet('api/products.php?action=list'),
      apiGet('api/products.php?action=categories'),
    ]);

    if (sessionRes.user) currentUser = sessionRes.user;
    PRODUCTS   = productsRes.products   || [];
    CATEGORIES = catsRes.categories     || [];

    if (currentUser) await loadCart();
  } catch (e) {
    console.error('Init error:', e);
    showToast('⚠️ Could not connect to server');
  }

  render();
}

// ============================================================
// NAVIGATION
// ============================================================
function navigate(page, cat) {
  currentPage = page;
  currentCat  = cat || null;
  render();
  window.scrollTo({ top: 0, behavior: 'smooth' });
  const si = document.getElementById('searchInput');
  if (si) si.value = '';
}

let searchTimer = null;
function handleSearch(val) {
  clearTimeout(searchTimer);
  if (val.length > 0) {
    currentPage = 'products';
    currentCat  = null;
    searchTimer = setTimeout(() => runSearch(val), 300);
  } else {
    currentPage = 'home';
    renderMain();
  }
}

async function runSearch(q) {
  try {
    const res = await apiGet(`api/products.php?action=search&q=${encodeURIComponent(q)}`);
    document.getElementById('mainContent').innerHTML =
      renderProductsHTML(res.products || [], null, q);
  } catch (e) {
    showToast('⚠️ Search failed');
  }
}

// ============================================================
// AUTH
// ============================================================
function openModal(tab) {
  document.getElementById('authOverlay').style.display = 'flex';
  switchTab(tab || 'login');
}

function closeModal() {
  document.getElementById('authOverlay').style.display = 'none';
}

function switchTab(tab) {
  document.getElementById('loginTab').className    = 'auth-tab' + (tab === 'login'    ? ' active' : '');
  document.getElementById('registerTab').className = 'auth-tab' + (tab === 'register' ? ' active' : '');
  document.getElementById('modalTitle').textContent = tab === 'login' ? 'Welcome back' : 'Create account';
  document.getElementById('modalSub').textContent   = tab === 'login'
    ? 'Sign in to your CoreVault account'
    : 'Join CoreVault — build smarter';
  renderAuthForm(tab);
}

function renderAuthForm(tab) {
  const f = document.getElementById('authForm');
  if (tab === 'login') {
    f.innerHTML = `
      <div class="form-group">
        <label class="form-label">Email</label>
        <input class="form-input" type="email" id="loginEmail" placeholder="you@example.com">
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <input class="form-input" type="password" id="loginPass" placeholder="••••••••">
      </div>
      <button class="btn btn-primary" id="loginBtn"
        style="width:100%;padding:.75rem;font-size:.9rem;border-radius:10px;margin-top:.5rem"
        onclick="doLogin()">Sign In</button>
      <div class="modal-footer">No account? <a onclick="switchTab('register')">Register here</a></div>`;
  } else {
    f.innerHTML = `
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">First Name</label>
          <input class="form-input" id="regFirst" placeholder="John">
        </div>
        <div class="form-group">
          <label class="form-label">Last Name</label>
          <input class="form-input" id="regLast" placeholder="Doe">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Email</label>
        <input class="form-input" type="email" id="regEmail" placeholder="you@example.com">
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <input class="form-input" type="password" id="regPass" placeholder="Min 8 characters">
      </div>
      <div class="form-group">
        <label class="form-label">Confirm Password</label>
        <input class="form-input" type="password" id="regPass2" placeholder="Repeat password">
      </div>
      <button class="btn btn-primary" id="regBtn"
        style="width:100%;padding:.75rem;font-size:.9rem;border-radius:10px;margin-top:.5rem"
        onclick="doRegister()">Create Account</button>
      <div class="modal-footer">Already have an account? <a onclick="switchTab('login')">Sign in</a></div>`;
  }
}

async function doLogin() {
  const email = document.getElementById('loginEmail').value.trim();
  const pass  = document.getElementById('loginPass').value;
  if (!email || !pass) { showToast('⚠️ Please fill in all fields'); return; }

  setButtonLoading('loginBtn', true, 'Signing in…');
  try {
    const res = await apiPost('api/auth.php?action=login', { email, password: pass });
    if (!res.success) { showToast('⚠️ ' + res.error); return; }
    currentUser = res.user;
    await loadCart();
    closeModal();
    render();
    showToast('✅ Welcome back, ' + currentUser.name.split(' ')[0] + '!', 'success');
  } catch (e) {
    showToast('⚠️ Login failed. Try again.');
  } finally {
    setButtonLoading('loginBtn', false, 'Sign In');
  }
}

async function doRegister() {
  const first = document.getElementById('regFirst').value.trim();
  const last  = document.getElementById('regLast').value.trim();
  const email = document.getElementById('regEmail').value.trim();
  const pass  = document.getElementById('regPass').value;
  const pass2 = document.getElementById('regPass2').value;

  if (!first || !last || !email || !pass || !pass2) { showToast('⚠️ Please fill in all fields'); return; }
  if (pass.length < 8) { showToast('⚠️ Password must be at least 8 characters'); return; }
  if (pass !== pass2)  { showToast('⚠️ Passwords do not match'); return; }

  setButtonLoading('regBtn', true, 'Creating account…');
  try {
    const res = await apiPost('api/auth.php?action=register', {
      first_name: first, last_name: last, email, password: pass,
    });
    if (!res.success) { showToast('⚠️ ' + res.error); return; }
    currentUser = res.user;
    closeModal();
    render();
    showToast('🎉 Account created! Welcome, ' + first + '!', 'success');
  } catch (e) {
    showToast('⚠️ Registration failed. Try again.');
  } finally {
    setButtonLoading('regBtn', false, 'Create Account');
  }
}

async function logout() {
  await apiPost('api/auth.php?action=logout');
  currentUser = null;
  cart = [];
  updateCartBadge();
  render();
  showToast('👋 Signed out');
}

function renderAuthArea() {
  const el = document.getElementById('authArea');
  if (currentUser) {
    el.innerHTML = `
      <div class="user-menu">
        <div class="user-avatar">${currentUser.initials}</div>
        <div class="user-dropdown">
          <div class="user-info-header">
            <div class="user-name">${currentUser.name}</div>
            <div class="user-email">${currentUser.email}</div>
          </div>
          <div class="user-drop-item" onclick="showOrders()">📦 My Orders</div>
          <div class="user-drop-item danger" onclick="logout()">🚪 Sign Out</div>
        </div>
      </div>`;
  } else {
    el.innerHTML = `
      <button class="btn btn-ghost" onclick="openModal('login')">Sign In</button>
      <button class="btn btn-primary" onclick="openModal('register')" style="margin-left:.5rem">Register</button>`;
  }
}

// ============================================================
// CART
// ============================================================
async function loadCart() {
  try {
    const res = await apiGet('api/cart.php?action=get');
    if (res.success) { cart = res.cart; updateCartBadge(); }
  } catch (e) { console.error('Cart load error', e); }
}

function toggleCart() {
  document.getElementById('cartPanel').classList.toggle('open');
  renderCart();
}

async function addToCart(productId, el) {
  if (!currentUser) {
    openModal('login');
    showToast('⚠️ Please sign in to add items to cart');
    return;
  }
  if (el) { el.textContent = '…'; el.disabled = true; }
  try {
    const res = await apiPost('api/cart.php?action=add', { product_id: productId, quantity: 1 });
    if (!res.success) { showToast('⚠️ ' + res.error); return; }
    cart = res.cart;
    updateCartBadge();
    const prod = cart.find(i => i.product_id === productId);
    showToast('🛒 ' + (prod ? prod.name.substring(0, 28) + '…' : 'Item') + ' added', 'success');
    renderCart();
    if (el) {
      el.textContent = '✓ Added';
      el.className   = 'add-btn added';
      setTimeout(() => { el.textContent = 'Add to Cart'; el.className = 'add-btn'; el.disabled = false; }, 1500);
    }
  } catch (e) {
    showToast('⚠️ Could not add item');
    if (el) { el.textContent = 'Add to Cart'; el.disabled = false; }
  }
}

async function removeFromCart(productId) {
  try {
    const res = await apiPost('api/cart.php?action=remove', { product_id: productId });
    if (res.success) { cart = res.cart; updateCartBadge(); renderCart(); }
  } catch (e) { showToast('⚠️ Could not remove item'); }
}

async function changeQty(productId, delta) {
  const item = cart.find(i => i.product_id === productId);
  if (!item) return;
  try {
    const res = await apiPost('api/cart.php?action=update', {
      product_id: productId,
      quantity: item.quantity + delta,
    });
    if (res.success) { cart = res.cart; updateCartBadge(); renderCart(); }
  } catch (e) { showToast('⚠️ Could not update quantity'); }
}

function updateCartBadge() {
  const count = cart.reduce((sum, i) => sum + i.quantity, 0);
  const badge = document.getElementById('cartBadge');
  badge.style.display = count > 0 ? 'flex' : 'none';
  badge.textContent   = count;
}

function renderCart() {
  const body   = document.getElementById('cartBody');
  const footer = document.getElementById('cartFooter');

  if (cart.length === 0) {
    body.innerHTML = `
      <div class="cart-empty">
        <div class="cart-empty-icon">🛒</div>
        <p>Your cart is empty</p>
        <p style="font-size:.8rem;margin-top:.5rem;color:var(--text3)">Add some parts to get started!</p>
      </div>`;
    footer.innerHTML = '';
    return;
  }

  body.innerHTML = cart.map(item => `
    <div class="cart-item">
      <div class="cart-item-img">${item.icon}</div>
      <div class="cart-item-info">
        <div class="cart-item-name">${item.name}</div>
        <div class="cart-item-price">$${item.price.toFixed(2)}</div>
        <div class="cart-qty">
          <button class="qty-btn" onclick="changeQty(${item.product_id}, -1)">−</button>
          <span class="qty-val">${item.quantity}</span>
          <button class="qty-btn" onclick="changeQty(${item.product_id}, +1)">+</button>
        </div>
      </div>
      <button class="cart-remove" onclick="removeFromCart(${item.product_id})">🗑</button>
    </div>`).join('');

  const subtotal = cart.reduce((sum, i) => sum + (i.price * i.quantity), 0);
  const shipping = subtotal >= 99 ? 0 : 12.99;
  const total    = subtotal + shipping;

  footer.innerHTML = `
    <div class="cart-total-row"><span>Subtotal</span><span>$${subtotal.toFixed(2)}</span></div>
    <div class="cart-total-row"><span>Shipping</span>
      <span style="color:var(--accent3)">${shipping === 0 ? 'FREE' : '$' + shipping.toFixed(2)}</span></div>
    <div class="cart-total-row big" style="margin:1rem 0"><span>Total</span><span>$${total.toFixed(2)}</span></div>
    <button class="btn btn-primary" id="checkoutBtn"
      style="width:100%;padding:.875rem;font-size:.95rem;border-radius:10px"
      onclick="checkout()">Checkout →</button>
    ${subtotal < 99
      ? `<p style="font-size:.75rem;color:var(--text3);text-align:center;margin-top:.75rem">
           Add $${(99 - subtotal).toFixed(2)} more for free shipping</p>`
      : ''}`;
}

async function checkout() {
  if (!currentUser) {
    document.getElementById('cartPanel').classList.remove('open');
    openModal('login');
    showToast('⚠️ Please sign in to checkout');
    return;
  }
  setButtonLoading('checkoutBtn', true, 'Placing order…');
  try {
    const res = await apiPost('api/orders.php?action=place');
    if (!res.success) { showToast('⚠️ ' + res.error); return; }
    cart = [];
    updateCartBadge();
    renderCart();
    document.getElementById('cartPanel').classList.remove('open');
    showToast('🎉 Order #' + res.order_id + ' placed! Total: $' + res.total.toFixed(2), 'success');
  } catch (e) {
    showToast('⚠️ Checkout failed. Try again.');
  } finally {
    setButtonLoading('checkoutBtn', false, 'Checkout →');
  }
}

// ============================================================
// ORDERS PAGE
// ============================================================
async function showOrders() {
  navigate('orders');
  const el = document.getElementById('mainContent');
  el.innerHTML = `<div class="section"><p style="color:var(--text2)">Loading orders…</p></div>`;
  try {
    const res = await apiGet('api/orders.php?action=history');
    if (!res.success) { showToast('⚠️ ' + res.error); return; }
    el.innerHTML = renderOrdersHTML(res.orders);
  } catch (e) {
    el.innerHTML = `<div class="section"><p style="color:var(--danger)">Could not load orders.</p></div>`;
  }
}

function renderOrdersHTML(orders) {
  if (orders.length === 0) {
    return `<div class="section">
      <div class="section-header"><h2 class="section-title">📦 My Orders</h2></div>
      <div style="text-align:center;padding:4rem;color:var(--text3)">
        <div style="font-size:3rem;margin-bottom:1rem">📭</div>
        <p>No orders yet. <a style="color:var(--accent);cursor:pointer" onclick="navigate('home')">Start shopping →</a></p>
      </div>
    </div>`;
  }

  const rows = orders.map(o => `
    <div style="background:var(--card-bg);border:1px solid var(--border);border-radius:12px;
                padding:1.25rem;margin-bottom:1rem;display:flex;align-items:center;
                justify-content:space-between;flex-wrap:wrap;gap:1rem">
      <div>
        <div style="font-family:'Syne',sans-serif;font-weight:700;margin-bottom:4px">Order #${o.id}</div>
        <div style="font-size:.8rem;color:var(--text3)">
          ${new Date(o.placed_at).toLocaleDateString('en-US',{year:'numeric',month:'short',day:'numeric'})}
        </div>
        <div style="font-size:.8rem;color:var(--text2);margin-top:4px">
          ${o.item_count} item${o.item_count !== 1 ? 's' : ''}
        </div>
      </div>
      <div style="text-align:right">
        <div style="font-family:'Syne',sans-serif;font-size:1.1rem;font-weight:700">
          $${o.total.toFixed(2)}
        </div>
        <div style="display:inline-block;margin-top:4px;padding:2px 10px;border-radius:99px;
                    font-size:.75rem;font-weight:600;background:${statusColor(o.status)};color:#fff">
          ${o.status.toUpperCase()}
        </div>
      </div>
    </div>`).join('');

  return `<div class="section">
    <div class="section-header">
      <h2 class="section-title">📦 My Orders</h2>
      <button class="view-all" onclick="navigate('home')">← Back to shop</button>
    </div>
    ${rows}
  </div>`;
}

function statusColor(s) {
  return ({pending:'#f59e0b',processing:'#4f8ef7',shipped:'#7c3aed',delivered:'#06d6a0',cancelled:'#f43f5e'})[s] || '#9090aa';
}

// ============================================================
// TOAST
// ============================================================
function showToast(msg, type) {
  const cont  = document.getElementById('toastContainer');
  const toast = document.createElement('div');
  toast.className = 'toast' + (type ? ' ' + type : '');
  toast.innerHTML = msg;
  cont.appendChild(toast);
  setTimeout(() => toast.remove(), 3500);
}

// ============================================================
// RENDERING
// ============================================================
function render() {
  renderMain();
  renderAuthArea();
}

function renderMain() {
  const el        = document.getElementById('mainContent');
  const searchVal = document.getElementById('searchInput')?.value || '';
  if (searchVal)               return; // handled by runSearch()
  if (currentPage === 'orders') return; // handled by showOrders()

  if (currentPage === 'products') {
    const prods = currentCat
      ? PRODUCTS.filter(p => p.category_slug === currentCat)
      : PRODUCTS;
    el.innerHTML = renderProductsHTML(prods, currentCat);
    return;
  }

  el.innerHTML = renderHomeHTML();
}

function renderHomeHTML() {
  const featured   = [...PRODUCTS].sort(() => Math.random() - 0.5).slice(0, 6);
  const topSellers = PRODUCTS.filter(p => p.review_count > 1000);

  return `
  <div class="hero">
    <div class="hero-label">⚡ New arrivals weekly</div>
    <h1>Build Your<br><span>Dream PC</span></h1>
    <p>Top-tier components at competitive prices. From budget builds to extreme performance rigs.</p>
    <div class="hero-actions">
      <button class="btn btn-primary btn-lg" onclick="navigate('products')">Shop All Parts</button>
      <button class="btn btn-ghost btn-lg" onclick="navigate('products','GPU')">View GPUs →</button>
    </div>
  </div>

  <div style="max-width:1400px;margin:0 auto;padding:0 2rem">
    <div class="promo-banner">
      <div class="promo-text">
        <h2>🔥 Summer Sale — Up to 35% Off</h2>
        <p>Limited time deals on top CPUs, GPUs, and memory kits.</p>
      </div>
      <button class="btn btn-primary btn-lg" onclick="navigate('products')">Shop Sale →</button>
    </div>
  </div>

  <div class="section">
    <div class="section-header"><h2 class="section-title">Shop by Category</h2></div>
    <div class="cat-grid">
      ${CATEGORIES.map(c => `
        <div class="cat-card" onclick="navigate('products','${c.slug}')">
          <div class="cat-icon">${c.icon}</div>
          <div class="cat-name">${c.name}</div>
        </div>`).join('')}
    </div>
  </div>

  <div class="section">
    <div class="section-header">
      <h2 class="section-title">Featured Products</h2>
      <button class="view-all" onclick="navigate('products')">View all →</button>
    </div>
    <div class="prod-grid">${featured.map(renderProductCard).join('')}</div>
  </div>

  <div class="section">
    <div class="section-header">
      <h2 class="section-title">🏆 Top Sellers</h2>
      <button class="view-all" onclick="navigate('products')">View all →</button>
    </div>
    <div class="prod-grid">${topSellers.map(renderProductCard).join('')}</div>
  </div>`;
}

function renderProductsHTML(prods, cat, searchQuery) {
  const title = searchQuery ? `Search: "${searchQuery}"` : cat ? cat : 'All Products';
  return `
  <div class="section" style="padding-top:2rem">
    <div class="section-header">
      <h2 class="section-title">
        ${title} —
        <span style="color:var(--text2);font-weight:400">${prods.length} product${prods.length !== 1 ? 's' : ''}</span>
      </h2>
      <div style="display:flex;gap:.5rem;flex-wrap:wrap">
        ${CATEGORIES.map(c => `
          <button onclick="navigate('products','${c.slug}')"
            style="background:${currentCat === c.slug ? 'var(--accent)' : 'var(--bg3)'};
                   border:1px solid ${currentCat === c.slug ? 'var(--accent)' : 'var(--border)'};
                   color:${currentCat === c.slug ? '#fff' : 'var(--text2)'};
                   padding:.35rem .8rem;border-radius:99px;font-size:.78rem;
                   cursor:pointer;font-family:'DM Sans',sans-serif">
            ${c.icon} ${c.name}
          </button>`).join('')}
        <button onclick="navigate('products',null)"
          style="background:${!currentCat && currentPage==='products' ? 'var(--accent)' : 'var(--bg3)'};
                 border:1px solid ${!currentCat && currentPage==='products' ? 'var(--accent)' : 'var(--border)'};
                 color:${!currentCat && currentPage==='products' ? '#fff' : 'var(--text2)'};
                 padding:.35rem .8rem;border-radius:99px;font-size:.78rem;
                 cursor:pointer;font-family:'DM Sans',sans-serif">All</button>
      </div>
    </div>
    ${prods.length === 0
      ? `<div style="text-align:center;padding:4rem;color:var(--text3)">
           <div style="font-size:3rem;margin-bottom:1rem">🔍</div>
           <p>No products found</p>
         </div>`
      : `<div class="prod-grid">${prods.map(renderProductCard).join('')}</div>`}
  </div>`;
}

function renderProductCard(p) {
  const oldPrice = p.old_price;
  const discount = oldPrice ? Math.round((1 - p.price / oldPrice) * 100) : null;
  const stars    = '★'.repeat(Math.round(p.rating)) + '☆'.repeat(5 - Math.round(p.rating));
  const reviews  = (p.review_count ?? 0).toLocaleString();

  return `
  <div class="prod-card">
    <div class="prod-img">
      ${p.badge
        ? `<div class="prod-badge${p.badge === 'NEW' ? ' new' : ''}">
             ${p.badge === 'SALE' && discount ? '-' + discount + '%' : p.badge}
           </div>`
        : ''}
      <span style="font-size:3.5rem">${p.icon}</span>
    </div>
    <div class="prod-info">
      <div class="prod-brand">${p.brand}</div>
      <div class="prod-name">${p.name}</div>
      <div class="prod-rating">
        <span class="stars">${stars}</span>
        <span class="rating-count">(${reviews})</span>
      </div>
      <div class="prod-price-row">
        <div>
          <div class="prod-price">$${p.price.toFixed(2)}</div>
          ${oldPrice ? `<div class="prod-old-price">$${oldPrice.toFixed(2)}</div>` : ''}
        </div>
        <button class="add-btn" onclick="addToCart(${p.id}, this)">Add to Cart</button>
      </div>
    </div>
  </div>`;
}

// ============================================================
// UI UTILITIES
// ============================================================
function setButtonLoading(id, loading, label) {
  const btn = document.getElementById(id);
  if (!btn) return;
  btn.disabled      = loading;
  btn.textContent   = label;
  btn.style.opacity = loading ? '0.7' : '1';
}

function showPageLoader() {
  document.getElementById('mainContent').innerHTML =
    `<div style="display:flex;align-items:center;justify-content:center;min-height:40vh">
       <div style="color:var(--text3)">Loading…</div>
     </div>`;
}

// ============================================================
// BOOT
// ============================================================
document.addEventListener('DOMContentLoaded', init);
