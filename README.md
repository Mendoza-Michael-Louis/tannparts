# Tannparts — Setup Guide

## Requirements
- PHP 8.0+
- MySQL 5.7+ or MariaDB 10.4+
- A local server: [XAMPP](https://www.apachefriends.org/), [Laragon](https://laragon.org/), WAMP, or any Apache/Nginx with PHP

---

## File Structure

```
filename/
├── index.php          ← Main entry point (rename from index.html)
├── styles.css
├── app.js
├── db.php             ← DB connection config
├── schema.sql         ← Run once to create DB + seed data
└── api/
    ├── auth.php       ← Register, login, logout, session check
    ├── products.php   ← Product listing, categories, search
    ├── cart.php       ← Cart CRUD (requires login)
    └── orders.php     ← Place order, order history
```

---

## 1. Create the Database

Open your MySQL client (phpMyAdmin, TablePlus, or terminal) and run:

```bash
mysql -u root -p < schema.sql
```

Or paste the contents of `schema.sql` into phpMyAdmin's SQL tab.

This will:
- Create the `tannparts` database
- Create all tables (`users`, `categories`, `products`, `cart_items`, `orders`, `order_items`)
- Insert the 6 categories and 10 products as seed data

---

## 2. Configure the Database Connection

Open `db.php` and update your credentials:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'tannparts');
define('DB_USER', 'root');       // your MySQL username
define('DB_PASS', '');           // your MySQL password
```

---

## 3. Serve the Project

**XAMPP / Laragon:**  
Copy the entire `tannparts/` folder into your web root:
- XAMPP: `C:/xampp/htdocs/tannparts/`
- Laragon: `C:/laragon/www/tannparts/`

Then open: `http://localhost/tannparts/index.php`

**PHP built-in server (for quick testing):**
```bash
cd tannparts
php -S localhost:8000
```
Then open: `http://localhost:8000/index.php`

---

## API Endpoints

All endpoints return JSON. POST bodies are JSON.

### Auth — `api/auth.php`
| Action | Method | Body | Description |
|--------|--------|------|-------------|
| `?action=register` | POST | `{first_name, last_name, email, password}` | Create account |
| `?action=login` | POST | `{email, password}` | Sign in |
| `?action=logout` | POST | — | Destroy session |
| `?action=me` | GET | — | Get current session user |

### Products — `api/products.php`
| Action | Method | Params | Description |
|--------|--------|--------|-------------|
| `?action=list` | GET | `&category=GPU` (optional) | All or filtered products |
| `?action=categories` | GET | — | All categories |
| `?action=search` | GET | `&q=ryzen` | Full-text search |

### Cart — `api/cart.php` *(login required)*
| Action | Method | Body | Description |
|--------|--------|------|-------------|
| `?action=get` | GET | — | Get cart items |
| `?action=add` | POST | `{product_id, quantity?}` | Add or increment item |
| `?action=update` | POST | `{product_id, quantity}` | Set quantity (0 = remove) |
| `?action=remove` | POST | `{product_id}` | Remove item |
| `?action=clear` | POST | — | Empty cart |

### Orders — `api/orders.php` *(login required)*
| Action | Method | Params | Description |
|--------|--------|--------|-------------|
| `?action=place` | POST | — | Checkout: converts cart → order |
| `?action=history` | GET | — | All past orders |
| `?action=detail` | GET | `&id=42` | Single order with line items |

---

## Security Notes

- Passwords are hashed with **bcrypt** via `password_hash()`
- All DB queries use **PDO prepared statements** (SQL injection safe)
- Sessions use PHP's default session handling
- For production, add HTTPS, rate limiting, and a CSRF token header
