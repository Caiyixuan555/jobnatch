<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login_register.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// 撈取已應徵清單，用來判斷按鈕狀態
$stmtApp = $db->prepare("SELECT job_id FROM applications WHERE user_id = ?");
$stmtApp->execute([$user_id]);
$applied_jobs = $stmtApp->fetchAll(PDO::FETCH_COLUMN);

// 撈取收藏清單與職缺詳細資訊
$stmt = $db->prepare("
    SELECT j.*, f.created_at as favorited_at 
    FROM jobs j
    JOIN favorites f ON j.id = f.job_id
    WHERE f.user_id = ?
    ORDER BY f.created_at DESC
");
$stmt->execute([$user_id]);
$favorited_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>我的收藏 - JobMatch</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
    body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    .hero-section { background: linear-gradient(135deg, #0f2027, #203a43, #2c5364); color: white; padding: 40px 0; border-radius: 0 0 25px 25px; margin-bottom: 30px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); }
    .job-card { border: none; border-radius: 16px; box-shadow: 0 5px 20px rgba(0,0,0,0.03); background: white; margin-bottom: 20px; border-left: 5px solid #dc3545; transition: all 0.3s ease; }
    .job-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.08); }
    .btn-fav { border-radius: 50%; width: 40px; height: 40px; display: inline-flex; justify-content: center; align-items: center; padding: 0; }
</style>
</head>
<body>

<?php include 'nav.php'; ?>

<div class="hero-section text-center">
    <div class="container">
        <h2 class="fw-bold"><i class="fa-solid fa-heart me-2 text-danger"></i>我的收藏清單</h2>
        <p class="mb-0 opacity-75">隨時關注您的夢幻職缺，別讓好機會溜走囉！</p>
    </div>
</div>

<div class="container mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <?php if (empty($favorited_jobs)): ?>
                <div class="card text-center p-5 border-0 shadow-sm rounded-4">
                    <div class="card-body">
                        <i class="fa-regular fa-folder-open text-muted mb-3" style="font-size: 3rem;"></i>
                        <h5 class="fw-bold text-secondary">您目前還沒有收藏任何職缺</h5>
                        <p class="text-muted small">快去首頁進行 AI 媒合，把喜歡的工作加入收藏吧！</p>
                        <a href="index.php" class="btn btn-primary rounded-pill px-4 mt-2">尋找好工作</a>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($favorited_jobs as $job): 
                    $isApplied = in_array($job['id'], $applied_jobs);
                ?>
                <div class="card job-card p-3" id="job-card-<?= $job['id'] ?>">
                    <div class="row align-items-center">
                        <div class="col-md-9">
                            <div class="d-flex align-items-center flex-wrap mb-2">
                                <h5 class="fw-bold text-dark mb-0 me-2"><?= htmlspecialchars($job['title'], ENT_QUOTES, 'UTF-8') ?></h5>
                                <span class="badge bg-secondary rounded-pill"><?= htmlspecialchars($job['company'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <div class="text-muted small mb-2">
                                <i class="fa-solid fa-location-dot me-1"></i><?= htmlspecialchars($job['location'], ENT_QUOTES, 'UTF-8') ?> &nbsp;|&nbsp; 
                                <i class="fa-solid fa-layer-group me-1"></i><?= htmlspecialchars($job['category'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <div class="mb-1 small">
                                <strong>需求技能：</strong> <span class="text-indigo fw-semibold"><?= htmlspecialchars($job['skills'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <div class="text-muted" style="font-size: 0.8rem;">
                                <i class="fa-regular fa-clock me-1"></i>收藏時間：<?= date('Y-m-d H:i', strtotime($job['favorited_at'])) ?>
                            </div>
                        </div>
                        
                        <div class="col-md-3 text-end border-start-md ps-4 mt-3 mt-md-0">
                            <div class="d-flex flex-row flex-md-column justify-content-end gap-2">
                                <button type="button" class="btn btn-danger text-white btn-fav shadow-sm" onclick="removeFavorite(<?= $job['id'] ?>)" title="取消收藏">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                                
                                <?php if($isApplied): ?>
                                    <button class="btn btn-secondary rounded-pill px-4" disabled><i class="fa-solid fa-check me-1"></i>已應徵</button>
                                <?php else: ?>
                                    <button class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" onclick="applyJob(<?= $job['id'] ?>, '<?= addslashes($job['company']) ?>', '<?= addslashes($job['title']) ?>', this)">
                                        <i class="fa-solid fa-paper-plane me-1"></i>投遞履歷
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'chatbot.php'; ?>

<script>
function removeFavorite(jobId) {
    Swal.fire({
        title: '確定要取消收藏嗎？',
        text: "取消後將從您的清單中移除哦！",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '確定取消',
        cancelButtonText: '保留'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('api_favorite.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ job_id: jobId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'removed') {
                    const card = document.getElementById('job-card-' + jobId);
                    card.style.opacity = '0';
                    setTimeout(() => {
                        card.remove();
                        if (document.querySelectorAll('.job-card').length === 0) { location.reload(); }
                    }, 300);
                }
            });
        }
    });
}

function applyJob(jobId, companyName, jobTitle, btnElement) {
    fetch('api_apply.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ job_id: jobId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            btnElement.disabled = true;
            btnElement.classList.replace('btn-primary', 'btn-secondary');
            btnElement.innerHTML = '<i class="fa-solid fa-check me-1"></i>已應徵';
            Swal.fire('投遞成功！', `您的履歷已發送給 <b>${companyName}</b>`, 'success');
        } else {
            Swal.fire('提示', data.message, 'warning');
        }
    });
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>