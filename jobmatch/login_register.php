<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'config.php';
$msg = '';

// 🚀 核心修正：自動在 SQLite 建立 users 資料表（若不存在）
try {
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        preferred_area TEXT,
        interest TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) {
    $msg = "<div class='alert alert-danger'>SQLite 初始化失敗：" . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</div>";
}

// 處理表單送出
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($action == 'register') {
        $name = trim($_POST['username'] ?? ''); // 配合前端名稱
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            // 🚀 修正：配合你的需求使用 name 與包含 password 的欄位
            $stmt = $db->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$name, $email, $hashed_password]);
            $msg = "<div class='alert alert-success'>✅ 註冊成功！請切換至登入分頁進行登入。</div>";
        } catch(PDOException $e) {
            $msg = "<div class='alert alert-danger'>❌ 註冊失敗，錯誤資訊：" . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</div>";
        }
    } elseif ($action == 'login') {
        try {
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && !empty($user['password']) && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['name']; // 將欄位改為 SQLite 的 name
                
                header("Location: index.php");
                exit;
            } else {
                $msg = "<div class='alert alert-danger'>❌ 帳號或密碼輸入錯誤，請再試一次。</div>";
            }
        } catch(PDOException $e) {
            $msg = "<div class='alert alert-danger'>❌ 登入失敗，錯誤資訊：" . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>會員登入 / 註冊 - JobMatch</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    .auth-card { max-width: 450px; margin: 60px auto; border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); padding: 30px; background: white; }
    .nav-pills .nav-link { border-radius: 50px; color: #6c757d; font-weight: bold; }
    .nav-pills .nav-link.active { background-color: #0d6efd; color: white; }
</style>
</head>
<body>

<?php include 'nav.php'; ?>

<div class="container">
    <div class="auth-card">
        <ul class="nav nav-pills nav-justified mb-4" id="authTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button" role="tab">會員登入</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="register-tab" data-bs-toggle="tab" data-bs-target="#register" type="button" role="tab">免費註冊</button>
            </li>
        </ul>

        <?= $msg ?>

        <div class="tab-content" id="authTabContent">
            <div class="tab-pane fade show active" id="login" role="tabpanel">
                <form method="POST">
                    <input type="hidden" name="action" value="login">
                    <div class="mb-3">
                        <label class="form-label text-secondary fw-bold">Email 信箱</label>
                        <input type="email" name="email" class="form-control rounded-pill px-3" placeholder="example@email.com" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label text-secondary fw-bold">密碼</label>
                        <input type="password" name="password" class="form-control rounded-pill px-3" placeholder="請輸入您的密碼" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold py-2 mb-3">登入系統</button>
                    <div class="text-center">
                        <a href="forgot_password.php" class="text-decoration-none text-muted small">忘記密碼？</a>
                    </div>
                </form>
            </div>
            
            <div class="tab-pane fade" id="register" role="tabpanel">
                <form method="POST">
                    <input type="hidden" name="action" value="register">
                    <div class="mb-3">
                        <label class="form-label text-secondary fw-bold">顯示名稱</label>
                        <input type="text" name="username" class="form-control rounded-pill px-3" placeholder="例如：王小明" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary fw-bold">Email 信箱</label>
                        <input type="email" name="email" class="form-control rounded-pill px-3" placeholder="example@email.com" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label text-secondary fw-bold">設定密碼</label>
                        <input type="password" name="password" class="form-control rounded-pill px-3" placeholder="請至少設定 6 位數密碼" required minlength="6">
                    </div>
                    <button type="submit" class="btn btn-success w-100 rounded-pill fw-bold py-2">註冊新帳號</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>