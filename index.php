<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tannparts — PC Parts</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <div class="app" id="app">

        <nav>
            <div class="nav-inner">
                <a class="logo" href="#" onclick="navigate('home')">Tannparts</a>
                <div class="search-bar">
                    <input class="search-input" type="text" placeholder="Search parts, brands..." id="searchInput"
                        oninput="handleSearch(this.value)">
                </div>
                <div class="nav-links">
                    <div class="nav-item">
                        <button class="nav-link" onclick="navigate('products','CPU')">CPUs <svg viewBox="0 0 12 12"
                                fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M2 4l4 4 4-4" />
                            </svg></button>
                        <div class="dropdown">
                            <div class="drop-label">By Manufacturer</div>
                            <a class="drop-item" onclick="navigate('products','CPU')">Intel Core Series</a>
                            <a class="drop-item" onclick="navigate('products','CPU')">AMD Ryzen Series</a>
                            <div class="drop-divider"></div>
                            <div class="drop-label">By Segment</div>
                            <a class="drop-item" onclick="navigate('products','CPU')">High-End / HEDT</a>
                            <a class="drop-item" onclick="navigate('products','CPU')">Mainstream</a>
                            <a class="drop-item" onclick="navigate('products','CPU')">Budget</a>
                        </div>
                    </div>
                    <div class="nav-item">
                        <button class="nav-link" onclick="navigate('products','GPU')">GPUs <svg viewBox="0 0 12 12"
                                fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M2 4l4 4 4-4" />
                            </svg></button>
                        <div class="dropdown">
                            <div class="drop-label">By Brand</div>
                            <a class="drop-item" onclick="navigate('products','GPU')">NVIDIA GeForce</a>
                            <a class="drop-item" onclick="navigate('products','GPU')">AMD Radeon</a>
                            <div class="drop-divider"></div>
                            <a class="drop-item" onclick="navigate('products','GPU')">Gaming GPUs</a>
                            <a class="drop-item" onclick="navigate('products','GPU')">Workstation / AI</a>
                        </div>
                    </div>
                    <div class="nav-item">
                        <button class="nav-link" onclick="navigate('products','Memory')">Memory <svg viewBox="0 0 12 12"
                                fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M2 4l4 4 4-4" />
                            </svg></button>
                        <div class="dropdown">
                            <a class="drop-item" onclick="navigate('products','Memory')">DDR5 RAM</a>
                            <a class="drop-item" onclick="navigate('products','Memory')">DDR4 RAM</a>
                            <div class="drop-divider"></div>
                            <a class="drop-item" onclick="navigate('products','Memory')">RGB Memory Kits</a>
                            <a class="drop-item" onclick="navigate('products','Memory')">High-Speed Kits</a>
                        </div>
                    </div>
                    <div class="nav-item">
                        <button class="nav-link">More <svg viewBox="0 0 12 12" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path d="M2 4l4 4 4-4" />
                            </svg></button>
                        <div class="dropdown">
                            <div class="drop-label">Storage</div>
                            <a class="drop-item" onclick="navigate('products','Storage')">NVMe SSDs</a>
                            <div class="drop-divider"></div>
                            <div class="drop-label">Cooling</div>
                            <a class="drop-item" onclick="navigate('products','Cooling')">Liquid Cooling (AIO)</a>
                            <div class="drop-divider"></div>
                            <div class="drop-label">Other</div>
                            <a class="drop-item" onclick="navigate('products','Motherboard')">Motherboards</a>
                        </div>
                    </div>
                </div>
                <div class="nav-actions">
                    <button class="cart-btn" onclick="toggleCart()">
                        🛒 <span class="cart-badge" id="cartBadge" style="display:none">0</span>
                    </button>
                    <div id="authArea"></div>
                </div>
            </div>
        </nav>

        <main id="mainContent"></main>

        <footer>
            <div class="footer-inner">
                <div class="footer-brand">
                    <div style="font-family:'Syne',sans-serif;font-weight:800;font-size:1.2rem;">Tannparts</div>
                    <p>Your trusted source for premium PC components.</p>
                </div>
                <div class="footer-col">
                    <h4>Shop</h4>
                    <a onclick="navigate('products','CPU')">CPUs</a>
                    <a onclick="navigate('products','GPU')">GPUs</a>
                    <a onclick="navigate('products','Memory')">Memory</a>
                    <a onclick="navigate('products','Storage')">Storage</a>
                    <a onclick="navigate('products','Motherboard')">Motherboards</a>
                </div>
                <div class="footer-col">
                    <h4>Account</h4>
                    <a onclick="currentUser ? showOrders() : openModal('login')">My Orders</a>
                    <a onclick="openModal('login')">Sign In</a>
                    <a onclick="openModal('register')">Register</a>
                </div>
                <div class="footer-col">
                    <h4>Support</h4>
                    <a>Help Center</a>
                    <a>Community Forums</a>
                    <a>Contact Us</a>
                </div>
            </div>
            <div class="footer-bottom">
                <p>© 2026 Tannparts Inc. All rights reserved.</p>
                <p>Privacy Policy · Terms of Service</p>
            </div>
        </footer>

        <div class="cart-panel" id="cartPanel">
            <div class="cart-header">
                <h3>Your Cart</h3>
                <button class="cart-close" onclick="toggleCart()">X</button>
            </div>
            <div class="cart-body" id="cartBody"></div>
            <div class="cart-footer" id="cartFooter"></div>
        </div>

        <div class="overlay" id="authOverlay" style="display:none">
            <div class="modal" id="authModal">
                <button class="modal-close" onclick="closeModal()">X</button>
                <h2 id="modalTitle">Welcome back</h2>
                <p class="modal-sub" id="modalSub">Sign in to your Tannparts account</p>
                <div class="auth-tabs" id="authTabs">
                    <button class="auth-tab active" id="loginTab" onclick="switchTab('login')">Sign In</button>
                    <button class="auth-tab" id="registerTab" onclick="switchTab('register')">Register</button>
                </div>
                <div id="authForm"></div>
            </div>
        </div>

        <div class="toast-container" id="toastContainer"></div>
    </div>

    <script src="app.js"></script>
</body>

</html>