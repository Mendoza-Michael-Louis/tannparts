<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

$action = $_GET['action'] ?? '';

switch ($action) {

    case 'register': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['success'=>false,'error'=>'POST required'],405);
        $data  = get_input();
        $first = trim($data['first_name'] ?? '');
        $last  = trim($data['last_name']  ?? '');
        $email = trim($data['email']      ?? '');
        $pass  =      $data['password']   ?? '';

        if (!$first||!$last||!$email||!$pass) json_response(['success'=>false,'error'=>'All fields are required'],422);
        if (!filter_var($email,FILTER_VALIDATE_EMAIL)) json_response(['success'=>false,'error'=>'Invalid email'],422);
        if (strlen($pass)<8) json_response(['success'=>false,'error'=>'Password must be at least 8 characters'],422);

        $db   = getDB();
        $stmt = $db->prepare('SELECT user_id FROM users WHERE user_email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) json_response(['success'=>false,'error'=>'Email already registered'],409);

        $hash = password_hash($pass, PASSWORD_BCRYPT);
        $stmt = $db->prepare('INSERT INTO users (first_name, last_name, user_email, user_password) VALUES (?,?,?,?)');
        $stmt->execute([$first, $last, $email, $hash]);
        $userId = (int) $db->lastInsertId();

        $_SESSION['user_id']    = $userId;
        $_SESSION['user_name']  = "$first $last";
        $_SESSION['user_email'] = $email;

        json_response(['success'=>true,'user'=>['id'=>$userId,'name'=>"$first $last",'email'=>$email,'initials'=>strtoupper($first[0])]],201);
        break;
    }

    case 'login': {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['success'=>false,'error'=>'POST required'],405);
        $data  = get_input();
        $email = trim($data['email']    ?? '');
        $pass  =      $data['password'] ?? '';

        if (!$email||!$pass) json_response(['success'=>false,'error'=>'Email and password required'],422);

        $db   = getDB();
        $stmt = $db->prepare('SELECT user_id, first_name, last_name, user_password FROM users WHERE user_email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($pass, $user['user_password'])) {
            json_response(['success'=>false,'error'=>'Invalid email or password'],401);
        }

        $name = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_id']    = $user['user_id'];
        $_SESSION['user_name']  = $name;
        $_SESSION['user_email'] = $email;

        json_response(['success'=>true,'user'=>['id'=>(int)$user['user_id'],'name'=>$name,'email'=>$email,'initials'=>strtoupper($user['first_name'][0])]]);
        break;
    }

    case 'logout': {
        session_destroy();
        json_response(['success'=>true]);
        break;
    }

    case 'me': {
        if (isset($_SESSION['user_id'])) {
            $parts = explode(' ', $_SESSION['user_name'], 2);
            json_response(['success'=>true,'user'=>['id'=>(int)$_SESSION['user_id'],'name'=>$_SESSION['user_name'],'email'=>$_SESSION['user_email'],'initials'=>strtoupper($parts[0][0]??'U')]]);
        } else {
            json_response(['success'=>true,'user'=>null]);
        }
        break;
    }

    default:
        json_response(['success'=>false,'error'=>'Unknown action'],400);
}
