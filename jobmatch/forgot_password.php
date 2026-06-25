<?php
session_start();
require 'config.php';
$msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');

    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $expires_at = date("Y-m-d H:i:s", strtotime('+15 minutes')); // 🟢 設定 15 分鐘後過期
        
        $updateStmt = $db->prepare("UPDATE users SET reset_token = ?, reset_expires_at = ? WHERE email = ?");
        $updateStmt->execute([$token, $expires_at, $email]);

        $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . $token;
        
        $msg = "<div class='alert alert-success shadow-sm'>
                    <h5 class='alert-heading fw-bold'><i class='fa-solid fa-envelope-circle-check me-2'></i>驗證信已發送！</h5>
                    <p class='mb-2'>為了您的帳號安全，以下重設密碼連結將於 <strong>15 分鐘後失效</strong>：</p>
                    <div class='bg-light p-2 rounded border'><a href='$reset_link' class='alert-link text-break' style='word-wrap: break-word;'>$reset_link</a></div>
                </div>";
    } else {
        $msg = "<div class='alert alert-danger'>❌ 找不到此 Email，請確認是否輸入正確。</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>忘記密碼 - JobMatch</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    .auth-card { max-width: 500px; margin: 80px auto; border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); padding: 40px; background: white; }
</style>
</head>
<body>
<?php include 'nav.php'; ?>
<div class="container">
    <div class="auth-card">
        <h3 class="text-center fw-bold mb-3 text-dark"><i class="fa-solid fa-key text-warning me-2"></i>忘記密碼</h3>
        <p class="text-muted text-center mb-4">請輸入您註冊時使用的 Email，我們將產生具備時效性的安全重設連結。</p>
        <?= $msg ?>
        <form method="POST">
            <div class="mb-4">
                <label class="form-label text-secondary fw-bold">Email 信箱</label>
                <input type="email" name="email" class="form-control rounded-pill px-3 py-2" required placeholder="請輸入註冊信箱...">
            </div>
            <button type="submit" class="btn btn-warning w-100 rounded-pill fw-bold py-2 shadow-sm text-dark"><i class="fa-solid fa-paper-plane me-2"></i>發送重設密碼連結</button>
        </form>
    </div>
</div>
</body>
</html>