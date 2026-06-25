<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'config.php';

// 檢查是否登入，未登入則導向登入註冊頁
if (!isset($_SESSION['user_id'])) {
    header("Location: login_register.php");
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // 撈取應徵紀錄（統一使用 a.created_at 撈取投遞時間）
    $stmt = $db->prepare("
        SELECT j.*, a.created_at as applied_at 
        FROM jobs j
        JOIN applications a ON j.id = a.job_id
        WHERE a.user_id = ?
        ORDER BY a.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $applied_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("資料查詢失敗，請確保已執行完最新的資料庫腳本。錯誤原因：" . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>應徵紀錄 - JobMatch</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    body { background: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    .hero-section { background: linear-gradient(135deg, #0f2027, #203a43, #2c5364); color: white; padding: 40px 0; border-radius: 0 0 30px 30px; margin-bottom: 40px; box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
    .job-card { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); transition: transform 0.2s; background: white; }
    .job-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,0.08); }
</style>
</head>
<body>

<?php include 'nav.php'; ?>

<div class="hero-section text-center">
    <div class="container">
        <h1 class="fw-bold mb-2"><i class="fa-solid fa-paper-plane me-2 text-warning"></i>我的應徵紀錄</h1>
        <p class="lead mb-0 text-white-50">追蹤您已投遞的職缺狀態，祝您順利錄取！</p>
    </div>
</div>

<div class="container mb-5" style="max-width: 900px;">
    <?php if (empty($applied_jobs)): ?>
        <div class="text-center py-5 bg-white rounded-4 shadow-sm border">
            <div class="text-muted mb-4"><i class="fa-regular fa-folder-open fa-4x text-light"></i></div>
            <h4 class="text-secondary fw-bold">尚無應徵紀錄</h4>
            <p class="text-muted mb-4">您目前還沒有投遞過任何職缺履歷喔！</p>
            <a href="index.php" class="btn btn-primary rounded-pill px-4 fw-bold"><i class="fa-solid fa-magnifying-glass me-1"></i>前往探索職缺</a>
        </div>
    <?php else: ?>
        <?php foreach ($applied_jobs as $job): ?>
        <div class="card job-card mb-3">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-md-9">
                        <div class="d-flex align-items-center mb-2 flex-wrap">
                            <h4 class="card-title fw-bold text-dark mb-0 me-3"><?= htmlspecialchars($job['title'], ENT_QUOTES, 'UTF-8') ?></h4>
                            <span class="badge bg-secondary rounded-pill px-3 py-1 mt-1 mt-sm-0"><?= htmlspecialchars($job['company'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="mb-3 text-muted small">
                            <i class="fa-solid fa-location-dot me-1 text-danger"></i> <?= htmlspecialchars($job['location'], ENT_QUOTES, 'UTF-8') ?> | 
                            <i class="fa-solid fa-briefcase me-1 text-primary"></i> <?= htmlspecialchars($job['category'], ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <p class="mb-2 text-dark small"><strong>需求技能：</strong> <span class="text-secondary"><?= htmlspecialchars($job['skills'], ENT_QUOTES, 'UTF-8') ?></span></p>
                        <p class="text-secondary small mb-0 text-truncate" style="max-width: 600px;"><?= htmlspecialchars($job['description'], ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <div class="col-md-3 text-md-end border-start ps-md-4 mt-3 mt-md-0 pt-3 pt-md-0 border-top border-top-md-none">
                        <div class="d-flex flex-row flex-md-column h-100 justify-content-between justify-content-md-center align-items-center align-items-md-end">
                            <span class="text-success fw-bold mb-md-2"><i class="fa-solid fa-circle-check me-1"></i>履歷已送出</span>
                            <span class="text-muted small text-end">應徵時間：<br class="d-none d-md-block"><?= date('Y-m-d H:i', strtotime($job['applied_at'])) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>