<?php
/**
 * Tannparts — Debug Script
 * Visit: http://localhost:8000/debug.php
 * Delete this file once everything is working!
 */
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>

<head>
    <title>Tannparts Debug</title>
    <style>
        body {
            font-family: monospace;
            background: #0a0a0f;
            color: #f0f0f8;
            padding: 2rem;
            max-width: 800px;
            margin: 0 auto
        }

        h1 {
            color: #4f8ef7;
            margin-bottom: 2rem
        }

        h2 {
            color: #9090aa;
            font-size: 1rem;
            margin: 1.5rem 0 .5rem;
            text-transform: uppercase;
            letter-spacing: .1em
        }

        .ok {
            color: #06d6a0
        }

        .fail {
            color: #f43f5e
        }

        .warn {
            color: #f59e0b
        }

        .box {
            background: #13131c;
            border: 1px solid #2a2a3a;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem
        }

        pre {
            white-space: pre-wrap;
            word-break: break-all;
            margin: 0;
            font-size: .85rem
        }
    </style>
</head>

<body>
    <h1>🔧 Tannparts Diagnostics</h1>

    <?php

    // ── 1. PHP Version ────────────────────────────────────────────
    echo '<h2>PHP</h2><div class="box">';
    $phpOk = version_compare(PHP_VERSION, '8.0.0', '>=');
    echo '<pre class="' . ($phpOk ? 'ok' : 'fail') . '">PHP ' . PHP_VERSION . ($phpOk ? ' ✓' : ' ✗ — need 8.0+') . '</pre>';
    echo '</div>';

    // ── 2. PDO Extension ──────────────────────────────────────────
    echo '<h2>PDO MySQL Extension</h2><div class="box">';
    $pdoOk = extension_loaded('pdo_mysql');
    echo '<pre class="' . ($pdoOk ? 'ok' : 'fail') . '">' . ($pdoOk ? 'pdo_mysql loaded ✓' : 'pdo_mysql NOT loaded ✗ — install php-mysql') . '</pre>';
    echo '</div>';

    // ── 3. DB Connection attempts ─────────────────────────────────
    echo '<h2>Database Connection</h2><div class="box">';

    $host = 'localhost';
    $dbname = 'tannparts';
    $charset = 'utf8mb4';

    $candidates = [
        ['root', ''],
        ['root', 'root'],
        ['root', 'password'],
        ['root', 'pass'],
    ];

    $connected = false;
    $workingUser = null;
    $workingPass = null;

    foreach ($candidates as [$user, $pass]) {
        try {
            $dsn = "mysql:host=$host;charset=$charset";
            $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            echo '<pre class="ok">Connected as ' . htmlspecialchars($user) . ' / "' . htmlspecialchars($pass) . '" ✓</pre>';
            $connected = true;
            $workingUser = $user;
            $workingPass = $pass;
            break;
        } catch (PDOException $e) {
            echo '<pre class="warn">Tried ' . htmlspecialchars($user) . ' / "' . htmlspecialchars($pass) . '" — ' . htmlspecialchars($e->getMessage()) . '</pre>';
        }
    }

    if (!$connected) {
        echo '<pre class="fail">Could not connect with any common credentials. Open db.php and set DB_USER / DB_PASS manually.</pre>';
    }
    echo '</div>';

    if ($connected) {
        // ── 4. Database exists ────────────────────────────────────
        echo '<h2>Database: tannparts</h2><div class="box">';
        try {
            $pdo->exec("USE `$dbname`");
            echo '<pre class="ok">Database "tannparts" exists and selected ✓</pre>';
            $dbSelected = true;
        } catch (PDOException $e) {
            echo '<pre class="fail">Database "tannparts" not found ✗</pre>';
            echo '<pre class="warn">Fix: Run this SQL → CREATE DATABASE tannparts CHARACTER SET utf8mb4;</pre>';
            echo '<pre class="warn">Then import schema.sql into it.</pre>';
            $dbSelected = false;
        }
        echo '</div>';

        if ($dbSelected) {
            // ── 5. Tables ─────────────────────────────────────────
            echo '<h2>Tables</h2><div class="box">';
            $expectedTables = ['users', 'categories', 'brands', 'products', 'cart_items', 'orders', 'order_items'];
            $stmt = $pdo->query("SHOW TABLES");
            $existing = array_column($stmt->fetchAll(PDO::FETCH_NUM), 0);
            foreach ($expectedTables as $t) {
                $found = in_array($t, $existing);
                echo '<pre class="' . ($found ? 'ok' : 'fail') . '">' . $t . ($found ? ' ✓' : ' ✗ — MISSING (import schema.sql)') . '</pre>';
            }
            echo '</div>';

            // ── 6. View ───────────────────────────────────────────
            echo '<h2>View: products_full</h2><div class="box">';
            try {
                $stmt = $pdo->query("SELECT COUNT(*) AS cnt FROM products_full");
                $cnt = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
                echo '<pre class="ok">products_full view exists — ' . $cnt . ' row(s) ✓</pre>';
                if ($cnt == 0) {
                    echo '<pre class="warn">View exists but has no rows. Did schema.sql seed data get imported?</pre>';
                }
            } catch (PDOException $e) {
                echo '<pre class="fail">products_full view missing or broken ✗</pre>';
                echo '<pre class="warn">Message: ' . htmlspecialchars($e->getMessage()) . '</pre>';
                echo '<pre class="warn">Fix: Re-run the CREATE OR REPLACE VIEW block at the bottom of schema.sql</pre>';
            }
            echo '</div>';

            // ── 7. Product count ──────────────────────────────────
            echo '<h2>Products Table</h2><div class="box">';
            try {
                $stmt = $pdo->query("SELECT COUNT(*) AS cnt FROM products");
                $cnt = (int) $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
                $cls = $cnt > 0 ? 'ok' : 'fail';
                echo '<pre class="' . $cls . '">' . $cnt . ' product row(s) in products table' . ($cnt > 0 ? ' ✓' : ' ✗ — import schema.sql to seed data') . '</pre>';
            } catch (PDOException $e) {
                echo '<pre class="fail">Could not query products: ' . htmlspecialchars($e->getMessage()) . '</pre>';
            }
            echo '</div>';

            // ── 8. Show working db.php config ─────────────────────
            echo '<h2>Suggested db.php settings</h2><div class="box">';
            echo '<pre class="ok">define(\'DB_HOST\', \'localhost\');' . "\n";
            echo 'define(\'DB_NAME\', \'tannparts\');' . "\n";
            echo 'define(\'DB_USER\', \'' . htmlspecialchars($workingUser) . '\');' . "\n";
            echo 'define(\'DB_PASS\', \'' . htmlspecialchars($workingPass) . '\');' . "\n";
            echo 'define(\'DB_CHARSET\', \'utf8mb4\');</pre>';
            echo '</div>';
        }
    }

    ?>

    <h2>Next Steps</h2>
    <div class="box">
        <pre>
1. Fix any ✗ items above.
2. Update db.php with the working credentials shown above.
3. If any tables are missing: run  mysql -u root -p tannparts &lt; schema.sql
4. Delete this debug.php file once everything works.
5. Open http://localhost:8000/index.php
</pre>
    </div>

</body>

</html>