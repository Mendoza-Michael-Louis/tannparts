<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tannparts — PC Parts</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="app" id="app">

<!-- NAVBAR -->
<nav>
  <div class="nav-inner">
    <a class="logo" href="#" onclick="navigate('home')">
      <div class="logo-icon">⚡</div>
      Tannparts
    </a>

    <div class="search-bar">
      <span class="search-icon">🔍</span>
      <input class="search-input" type="text" placeholder="Search parts, brands..." id="searchInput" oninput="handleSearch(this.value)">
    </div>

    <div class="nav-links">
      <!-- CPUs -->
      <div class="nav-item">
        <button class="nav-link" onclick="navigate('products','CPU')">CPUs <svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 4l4 4 4-4"/></svg></button>
        <div class="dropdown">
          <div class="drop-label">By Manufacturer</div>
          <a class="drop-item" onclick="navigate('products','CPU','intel')"><div class="drop-item-icon" style="background:rgba(79,142,247,0.15)">🔵</div>Intel Core Series</a>
          <a class="drop-item" onclick="navigate('products','CPU','amd')"><div class="drop-item-icon" style="background:rgba(239,68,68,0.15)">🔴</div>AMD Ryzen Series</a>
          <div class="drop-divider"></div>
          <div class="drop-label">By Segment</div>
          <a class="drop-item" onclick="navigate('products','CPU')">🏆 High-End / HEDT</a>
          <a class="drop-item" onclick="navigate('products','CPU')">⚡ Mainstream</a>
          <a class="drop-item" onclick="navigate('products','CPU')">💰 Budget</a>
        </div>
      </div>
      <!-- GPUs -->
      <div class="nav-item">
        <button class="nav-link" onclick="navigate('products','GPU')">GPUs <svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 4l4 4 4-4"/></svg></button>
        <div class="dropdown">
          <div class="drop-label">By Brand</div>
          <a class="drop-item" onclick="navigate('products','GPU','nvidia')"><div class="drop-item-icon" style="background:rgba(6,214,160,0.15)">💚</div>NVIDIA GeForce</a>
          <a class="drop-item" onclick="navigate('products','GPU','amd')"><div class="drop-item-icon" style="background:rgba(239,68,68,0.15)">❤️</div>AMD Radeon</a>
          <div class="drop-divider"></div>
          <a class="drop-item" onclick="navigate('products','GPU')">🎮 Gaming GPUs</a>
          <a class="drop-item" onclick="navigate('products','GPU')">🤖 Workstation / AI</a>
        </div>
      </div>
      <!-- RAM -->
      <div class="nav-item">
        <button class="nav-link" onclick="navigate('products','RAM')">RAM <svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 4l4 4 4-4"/></svg></button>
        <div class="dropdown">
          <a class="drop-item" onclick="navigate('products','RAM')">🟩 DDR5 RAM</a>
          <a class="drop-item" onclick="navigate('products','RAM')">🟦 DDR4 RAM</a>
          <div class="drop-divider"></div>
          <a class="drop-item" onclick="navigate('products','RAM')">💡 RGB Memory Kits</a>
          <a class="drop-item" onclick="navigate('products','RAM')">🏎️ High-Speed Kits</a>
        </div>
      </div>
      <!-- More -->
      <div class="nav-item">
        <button class="nav-link">More <svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 4l4 4 4-4"/></svg></button>
        <div class="dropdown">
          <div class="drop-label">Storage</div>
          <a class="drop-item" onclick="navigate('products','Storage')">💾 NVMe SSDs</a>
          <div class="drop-divider"></div>
          <div class="drop-label">Cooling</div>
          <a class="drop-item" onclick="navigate('products','Cooling')">🌊 Liquid Cooling (AIO)</a>
          <div class="drop-divider"></div>
          <div class="drop-label">Other</div>
          <a class="drop-item" onclick="navigate('products','Motherboard')">🔌 Motherboards</a>
        </div>
      </div>
    </div>

    <div class="nav-actions">
      <button class="cart-btn" onclick="toggleCart()">
        🛒
        <span class="cart-badge" id="cartBadge" style="display:none">0</span>
      </button>
      <div id="authArea"></div>
    </div>
  </div>
</nav>

<!-- MAIN CONTENT -->
<main id="mainContent"></main>

<!-- FOOTER -->
<footer>
  <div class="footer-inner">
    <div class="footer-brand">
      <div class="logo" style="font-family:'Syne',sans-serif;font-weight:800;font-size:1.2rem;display:flex;align-items:center;gap:8px">
        <div class="logo-icon" style="width:28px;height:28px;background:linear-gradient(135deg,#4f8ef7,#7c3aed);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:13px">⚡</div>
        Tannparts
      </div>
      <p>Your trusted source for premium PC components. Build your dream rig with top brands, unbeatable prices, and expert support.</p>
    </div>
    <div class="footer-col">
      <h4>Shop</h4>
      <a onclick="navigate('products','CPU')">CPUs</a>
      <a onclick="navigate('products','GPU')">GPUs</a>
      <a onclick="navigate('products','RAM')">RAM</a>
      <a onclick="navigate('products','Storage')">Storage</a>
      <a onclick="navigate('products','Motherboard')">Motherboards</a>
    </div>
    <div class="footer-col">
      <h4>Account</h4>
      <a>My Orders</a>
      <a>Wishlist</a>
      <a>Returns</a>
      <a>Track Package</a>
    </div>
    <div class="footer-col">
      <h4>Support</h4>
      <a>Help Center</a>
      <a>Compatibility Checker</a>
      <a>Community Forums</a>
      <a>Contact Us</a>
    </div>
  </div>
  <div class="footer-bottom">
    <p>© 2025 Tannparts Inc. All rights reserved.</p>
    <p>Privacy Policy · Terms of Service · Cookie Settings</p>
  </div>
</footer>

<!-- CART PANEL -->
<div class="cart-panel" id="cartPanel">
  <div class="cart-header">
    <h3>Your Cart</h3>
    <button class="cart-close" onclick="toggleCart()">✕</button>
  </div>
  <div class="cart-body" id="cartBody"></div>
  <div class="cart-footer" id="cartFooter"></div>
</div>

<!-- AUTH MODAL -->
<div class="overlay" id="authOverlay" style="display:none">
  <div class="modal" id="authModal">
    <button class="modal-close" onclick="closeModal()">✕</button>
    <h2 id="modalTitle">Welcome back</h2>
    <p class="modal-sub" id="modalSub">Sign in to your account</p>
    <div class="auth-tabs" id="authTabs">
      <button class="auth-tab active" id="loginTab" onclick="switchTab('login')">Sign In</button>
      <button class="auth-tab" id="registerTab" onclick="switchTab('register')">Register</button>
    </div>
    <div id="authForm"></div>
  </div>
</div>

<!-- TOAST CONTAINER -->
<div class="toast-container" id="toastContainer"></div>

</div><!-- /.app -->

<script src="app.js"></script>
</body>
</html>
