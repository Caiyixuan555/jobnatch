<?php
session_start();
require 'config.php';
$msg = '';
$valid_token = false;
$token = $_GET['token'] ?? '';

if (!empty($token)) {
    $stmt = $db->prepare("SELECT * FROM users WHERE reset_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        // 🟢 資安檢核：判斷時間是否過期
        if (strtotime($user['reset_expires_at']) > time()) {
            $valid_token = true;
        } else {
            $msg = "<div class='alert alert-danger'><h5 class='fw-bold'>❌ 連結已過期</h5>您的重設連結已超過 15 分鐘的安全時效，請<a href='forgot_password.php' class='alert-link'>重新申請</a>。</div>";
        }
    } else {
        $msg = "<div class='alert alert-danger'>❌ 無效的重設連結。</div>";
    }
} else {
    $msg = "<div class='alert alert-warning'>⚠️ 缺少驗證碼。</div>";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $valid_token) {
    $new_password = $_POST['new_password'] ?? '';
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // 🟢 更新密碼並清空 Token 與時間
    $updateStmt = $db->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires_at = NULL WHERE id = ?");
    $updateStmt->execute([$hashed_password, $user['id']]);

    $msg = "<div class='alert alert-success'>✅ 密碼重設成功！請使用新密碼重新 <a href='login_register.php' class='fw-bold'>登入</a>。</div>";
    $valid_token = false;
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>設定新密碼 - JobMatch</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    .auth-card { max-width: 450px; margin: 80px auto; border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); padding: 30px; background: white; }
</style>
</head>
<body>
<?php include 'nav.php'; ?>
<div class="container">
    <div class="auth-card">
        <h3 class="text-center fw-bold mb-4 text-dark"><i class="fa-solid fa-unlock-keyhole text-success me-2"></i>設定新密碼</h3>
        <?= $msg ?>
        <?php if($valid_token): ?>
        <form method="POST">
            <div class="mb-4">
                <label class="form-label text-secondary fw-bold">請輸入新密碼</label>
                <input type="password" name="new_password" class="form-control rounded-pill px-3 py-2" required minlength="6" placeholder="最少 6 位數">
            </div>
            <button type="submit" class="btn btn-success w-100 rounded-pill fw-bold py-2 shadow-sm"><i class="fa-solid fa-floppy-disk me-2"></i>確認修改密碼</button>
        </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>