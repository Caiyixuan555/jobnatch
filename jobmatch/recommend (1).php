<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'config.php';

// 1. 接收來自 index.php 表單的資料
$user_skills = isset($_POST['skills']) ? $_POST['skills'] : [];
$user_category = isset($_POST['category']) ? $_POST['category'] : '';
$user_location = isset($_POST['location']) ? $_POST['location'] : '';
$user_jobtypes = isset($_POST['jobtype']) ? $_POST['jobtype'] : []; 
$intro = isset($_POST['intro']) ? trim($_POST['intro']) : '';

// 2. 防呆機制
if (empty($user_skills) && empty($intro)) {
    echo "<script>alert('請至少選擇一項專業技能或填寫經歷描述，系統才能為您計算媒合分數！'); window.history.back();</script>";
    exit;
}

// 統一轉小寫以利進行精準字串比對
$user_skills_lower = array_map('strtolower', array_map('trim', $user_skills));
$intro_lower = strtolower($intro); 

// 🟢 從使用者的「自我介紹 / 專案經歷」中，主動萃取隱藏的技術關鍵字
$tech_dictionary = ['vue', 'react', 'angular', 'node', 'php', 'laravel', 'javascript', 'python', 'java', 'git', 'mysql', 'docker', 'css', 'html', 'typescript', 'golang', 'django', 'fastapi', 'spring boot', 'aws', 'gcp', 'kubernetes', 'figma', 'selenium', 'bootstrap', 'tailwind'];

$extracted_intro_skills = [];
if (!empty($intro_lower)) {
    foreach ($tech_dictionary as $tech) {
        if (preg_match('/\b' . preg_quote($tech, '/') . '\b/i', $intro_lower) || strpos($intro_lower, $tech) !== false) {
            if (!in_array($tech, $user_skills_lower)) {
                $extracted_intro_skills[] = $tech;
            }
        }
    }
}

$final_user_skills_lower = array_merge($user_skills_lower, $extracted_intro_skills);

// 3. 撈取資料庫中所有職缺
$stmt = $db->query("SELECT * FROM jobs");
$all_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$matched_jobs = [];

foreach ($all_jobs as $job) {
    $score = 0;
    
    $raw_title = $job['title'];
    $job_desc_lower = strtolower($job['description'] ?? '');
    $job_title_lower = strtolower($raw_title);
    
    // --- 工作型態篩選邏輯 ---
    $job_type_label = "全職"; 
    if (strpos($raw_title, '兼職') !== false || strpos($raw_title, 'Part-time') !== false || strpos($raw_title, 'PT') !== false) {
        $job_type_label = "兼職";
    } elseif (strpos($raw_title, '實習') !== false || strpos($raw_title, 'Intern') !== false) {
        $job_type_label = "實習";
    } elseif (strpos($raw_title, '接案') !== false || strpos($raw_title, '外包') !== false) {
        $job_type_label = "接案/外包";
    }

    $type_matched = false;
    if (!empty($user_jobtypes)) {
        foreach ($user_jobtypes as $ut) {
            if ($ut === $job_type_label) { $type_matched = true; break; }
        }
    } else {
        $type_matched = true; 
    }

    $clean_title = preg_replace('/[【\[\(](.*?)[】\]\)]/', '', $raw_title);
    $clean_title = trim($clean_title);
    $job['display_title'] = htmlspecialchars($clean_title, ENT_QUOTES, 'UTF-8');

    // 技能匹配計算
    $job_skills_array = array_map('trim', explode(',', $job['skills']));
    $job_skills_lower = array_map('strtolower', $job_skills_array);
    
    $matched_skills_array = array_intersect($final_user_skills_lower, $job_skills_lower);
    $match_count = count($matched_skills_array);
    $job_total_skills = count($job_skills_lower);
    
    $skill_score = 0;
    if ($job_total_skills > 0) {
        $skill_score = ($match_count / $job_total_skills) * 50;
    }
    $score += $skill_score;
    
    $category_score = 0;
    if (!empty($user_category) && $job['category'] === $user_category) {
        $category_score = 20;
    }
    $score += $category_score;
    
    $location_score = 0;
    if (!empty($user_location) && strpos($job['location'], $user_location) !== false) {
        $location_score = 15;
    }
    $score += $location_score;
    
    if ($type_matched && !empty($user_jobtypes)) {
        $score += 5;
    }

    // 智能關鍵字全文檢索加分
    $text_search_bonus = 0;
    if (!empty($intro_lower)) {
        $intro_words = array_filter(explode(' ', str_replace([',', '.', '，', '。', '、', '！'], ' ', $intro_lower)));
        foreach ($intro_words as $word) {
            if (strlen($word) >= 2) { 
                if (strpos($job_title_lower, $word) !== false || strpos($job_desc_lower, $word) !== false) {
                    $text_search_bonus += 3; 
                }
            }
        }
        if ($text_search_bonus > 10) $text_search_bonus = 10;
        $score += $text_search_bonus;
    }

    if ($score > 100) { $score = 100; } 
    
    $reason = "技能匹配了 " . $match_count . " 項";
    if ($category_score > 0) { $reason .= "、符合目標職類"; }
    if ($location_score > 0) { $reason .= "、地點適配"; }
    if (!empty($extracted_intro_skills) && count(array_intersect($extracted_intro_skills, $job_skills_lower)) > 0) {
        $reason .= "、自傳關鍵技術加成";
    }
    if ($text_search_bonus > 0) { $reason .= "、經歷語意相關"; }
    
    if ($score > 5) {
        $job['match_score'] = round($score, 0); 
        $job['match_reason'] = $reason;
        $job['type_label'] = $job_type_label;
        $matched_jobs[] = $job;
    }
}

usort($matched_jobs, function($a, $b) {
    return $b['match_score'] <=> $a['match_score'];
});

// 5. 撈取已應徵/收藏清單
$applied_jobs = []; $favorited_jobs = [];
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $applied_jobs = $db->query("SELECT job_id FROM applications WHERE user_id = $uid")->fetchAll(PDO::FETCH_COLUMN);
    $favorited_jobs = $db->query("SELECT job_id FROM favorites WHERE user_id = $uid")->fetchAll(PDO::FETCH_COLUMN);
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>AI 精準媒合結果 - JobMatch</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
    body { background-color: #f4f6f9; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
    .hero-section { background: linear-gradient(135deg, #1a365d 0%, #2a4365 100%); color: white; padding: 35px 0; border-radius: 0 0 20px 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    .list-item-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; transition: all 0.25s ease; }
    .list-item-card:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(0,0,0,0.05); border-color: #cbd5e1; }
    .skill-badge { font-size: 0.75rem; padding: 4px 8px; border-radius: 6px; margin: 0 4px 4px 0; display: inline-block; }
    .sb-match { background-color: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
    .sb-miss { background-color: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
    .sb-bonus { background-color: #fffbeb; color: #d97706; border: 1px solid #fef3c7; }
    .score-container { min-width: 90px; text-align: center; }
    .score-circle { width: 76px; height: 76px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; border: 5px solid #e2e8f0; background: #fff; }
    .score-high { border-color: #10b981; color: #10b981; }
    .score-med { border-color: #3b82f6; color: #3b82f6; }
    .score-low { border-color: #f59e0b; color: #f59e0b; }
    .score-number { font-size: 1.6rem; font-weight: 800; letter-spacing: -1px; line-height: 1; }
    .score-percent { font-size: 0.75rem; font-weight: 600; margin-left: 1px; align-self: flex-end; margin-bottom: 4px; }
    .btn-fav-outline { color: #dc3545 !important; background-color: #fff5f5 !important; border: 1.5px solid #f5c2c7 !important; transition: all 0.2s ease; }
    .btn-fav-filled { color: #ffffff !important; background-color: #dc3545 !important; border: 1.5px solid #dc3545 !important; transition: all 0.2s ease; }
    @media (min-width: 768px) { .action-column { border-left: 1px solid #f1f5f9; padding-left: 25px; min-width: 180px; } }
</style>
</head>
<body>

<?php include 'nav.php'; ?>

<div class="hero-section text-center mb-4">
    <div class="container">
        <h3 class="fw-bold m-0"><i class="fa-solid fa-wand-magic-sparkles me-2 text-warning"></i>AI 職缺推薦媒合結果</h3>
        <p class="mb-0 opacity-75 mt-1" style="font-size: 0.95rem;">已為您結合專業技能陣列與個人經歷描述進行精準排序</p>
    </div>
</div>

<div class="container mb-5" style="max-width: 1050px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="text-secondary fw-bold mb-0"><i class="fa-solid fa-list-ul me-2"></i>依契合度為您精選前 <span class="text-primary"><?= min(count($matched_jobs), 20) ?></span> 筆職缺</h6>
        <a href="index.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3"><i class="fa-solid fa-rotate-left me-1"></i>重新篩選</a>
    </div>

    <div class="d-flex flex-column gap-3">
        <?php if (empty($matched_jobs)): ?>
            <div class="text-center py-5 bg-white rounded-4 border shadow-sm">
                <i class="fa-solid fa-inbox text-muted mb-3" style="font-size: 3.5rem;"></i>
                <h5 class="fw-bold text-dark mt-2">沒有找到足夠匹配的職缺</h5>
                <p class="text-muted mb-4">建議可以調整篩選條件，或是在經歷描述中補充更多技術關鍵字！</p>
                <a href="index.php" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm"><i class="fa-solid fa-arrow-left me-1"></i>返回首頁重新選擇</a>
            </div>
        <?php else: ?>
            <?php foreach (array_slice($matched_jobs, 0, 20) as $index => $job): ?>
                
                <?php 
                $isApplied = in_array($job['id'], $applied_jobs);
                $isFav = in_array($job['id'], $favorited_jobs);

                $score = $job['match_score'];
                $score_class = ($score >= 80) ? 'score-high' : (($score >= 50) ? 'score-med' : 'score-low');
                
                $job_type_label = $job['type_label'];
                $job_type_color = "primary";
                if ($job_type_label === "兼職") $job_type_color = "warning text-dark";
                if ($job_type_label === "實習") $job_type_color = "success";
                if ($job_type_label === "接案/外包") $job_type_color = "info text-dark";

                $job_skills_arr = array_map('trim', explode(',', $job['skills']));
                $matched_skills = []; $missing_skills = []; $bonus_skills = [];

                foreach ($job_skills_arr as $js) {
                    $js_lower = strtolower($js);
                    if (in_array($js_lower, $user_skills_lower)) {
                        $matched_skills[] = $js;
                    } 
                    elseif (in_array($js_lower, $extracted_intro_skills)) {
                        $bonus_skills[] = $js; 
                    } 
                    else {
                        $missing_skills[] = $js;
                    }
                }
                ?>

                <div class="list-item-card p-3 p-md-4 shadow-sm">
                    <div class="d-flex flex-column flex-md-row align-items-md-center gap-3">
                        
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center mb-2 gap-2">
                                <span class="badge bg-dark rounded-sm">Top <?= $index + 1 ?></span>
                                <span class="text-muted fw-bold" style="font-size: 0.9rem;"><i class="fa-regular fa-building me-1"></i><?= htmlspecialchars($job['company'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            
                            <h4 class="fw-bold text-dark mb-2" style="font-size: 1.35rem;"><?= $job['display_title'] ?></h4>
                            
                            <div class="text-muted small mb-2 row g-0">
                                <div class="col-auto me-3 text-danger fw-bold">
                                    <i class="fa-solid fa-coins me-1"></i>薪資面議
                                </div>
                                <div class="col text-truncate opacity-75 d-none d-md-block" title="<?= htmlspecialchars($job['description'], ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="fa-solid fa-file-lines me-1"></i><?= isset($job['description']) ? mb_strimwidth(htmlspecialchars($job['description'], ENT_QUOTES, 'UTF-8'), 0, 95, "...") : '暫無職缺描述說明。' ?>
                                </div>
                            </div>
                            
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <span class="badge bg-<?= $job_type_color ?> rounded-1 px-2 py-1"><i class="fa-solid fa-tag me-1"></i><?= $job_type_label ?></span>
                                <span class="badge bg-light text-dark border rounded-1 px-2 py-1"><i class="fa-solid fa-location-dot text-danger opacity-75 me-1"></i><?= htmlspecialchars($job['location'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="badge bg-light text-dark border rounded-1 px-2 py-1"><i class="fa-solid fa-layer-group text-secondary me-1"></i><?= htmlspecialchars($job['category'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>

                            <div class="bg-light p-2 rounded-3 border border-light-subtle">
                                <div class="d-flex align-items-start">
                                    <span class="small fw-bold text-secondary mt-1 me-2" style="white-space: nowrap;"><i class="fa-solid fa-microchip text-primary me-1"></i>AI 比對:</span>
                                    <div class="d-flex flex-wrap">
                                        <?php foreach($matched_skills as $ms): ?>
                                            <span class="skill-badge sb-match"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($ms) ?></span>
                                        <?php endforeach; ?>
                                        <?php foreach($bonus_skills as $bs): ?>
                                            <span class="skill-badge sb-bonus" title="從您的專案經歷中自動識別萃取"><i class="fa-solid fa-wand-magic-sparkles"></i> <?= htmlspecialchars($bs) ?> (自傳)</span>
                                        <?php endforeach; ?>
                                        <?php foreach($missing_skills as $mis): ?>
                                            <span class="skill-badge sb-miss"><i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars($mis) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="text-muted small mt-1 ms-1" style="font-size: 0.8rem;">
                                    <i class="fa-solid fa-lightbulb text-warning me-1"></i>推薦依據：<?= htmlspecialchars($job['match_reason'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </div>
                        </div>

                        <div class="action-column d-flex flex-row flex-md-column align-items-center justify-content-between justify-content-md-center mt-2 mt-md-0 gap-3">
                            <div class="score-container">
                                <div class="text-muted small fw-bold mb-1 d-none d-md-block">契合度</div>
                                <div class="score-circle <?= $score_class ?> shadow-sm">
                                    <span class="score-number"><?= $score ?></span>
                                    <span class="score-percent">%</span>
                                </div>
                            </div>
                            
                            <div class="d-flex flex-column gap-2 w-100" style="min-width: 130px;">
                                <?php if($isApplied): ?>
                                    <button type="button" class="btn btn-secondary btn-sm fw-bold w-100 rounded-pill py-2" disabled><i class="fa-solid fa-check me-1"></i>已投遞</button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-primary btn-sm fw-bold w-100 rounded-pill py-2 shadow-sm" onclick="applyJob(<?= $job['id'] ?>, '<?= addslashes($job['company']) ?>', this)">
                                        <i class="fa-solid fa-paper-plane me-1"></i>投遞履歷
                                    </button>
                                <?php endif; ?>
                                
                                <button type="button" class="btn <?= $isFav ? 'btn-fav-filled' : 'btn-fav-outline' ?> btn-sm fw-bold w-100 rounded-pill py-2" onclick="toggleFavorite(<?= $job['id'] ?>, this)">
                                    <i class="fa-<?= $isFav ? 'solid' : 'regular' ?> fa-heart me-1"></i> <?= $isFav ? '已收藏' : '收藏職缺' ?>
                                </button>
                            </div>
                        </div>
                        
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'chatbot.php'; ?>

<script>
function toggleFavorite(jobId, btn) {
    // 先確認目前按鈕的狀態 (是否已經有紅色實心的 class)
    const isCurrentlyFav = btn.classList.contains('btn-fav-filled');

    // 🟢 【超級樂觀更新】：不管三七二十一，點了滑鼠的 0.001 秒立刻變色！
    if (isCurrentlyFav) {
        // 本來是實心，立刻變成空心
        btn.className = 'btn btn-fav-outline btn-sm fw-bold w-100 rounded-pill py-2';
        btn.innerHTML = '<i class="fa-regular fa-heart me-1"></i> 收藏職缺';
        // 雙重保證，直接寫入 style 覆寫所有東西
        btn.style.setProperty('background-color', '#fff5f5', 'important');
        btn.style.setProperty('color', '#dc3545', 'important');
    } else {
        // 本來是空心，立刻變成實心
        btn.className = 'btn btn-fav-filled btn-sm fw-bold w-100 rounded-pill py-2';
        btn.innerHTML = '<i class="fa-solid fa-heart me-1"></i> 已收藏';
        // 雙重保證
        btn.style.setProperty('background-color', '#dc3545', 'important');
        btn.style.setProperty('color', '#ffffff', 'important');
    }

    // 🟢 然後才在背景偷偷傳資料給資料庫
    fetch('api_favorite.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ job_id: jobId })
    })
    .then(r => r.json())
    .then(data => {
        // 如果後端報錯或是沒登入，才跳出警告
        if (data.status === 'error' || (data.message && data.message.includes('登入'))) {
            Swal.fire('提示', data.message || '請先登入', 'warning');
            setTimeout(() => window.location.href = 'login_register.php', 1500);
        } else {
            // 沒報錯的話，跳個安靜的小提示，按鈕早就在前面變色好了！
            Swal.fire({
                title: isCurrentlyFav ? '已取消' : '收藏成功',
                text: isCurrentlyFav ? '職缺已從您的收藏清單移除。' : '已成功將此職缺加入收藏清單。',
                icon: isCurrentlyFav ? 'info' : 'success',
                timer: 1500,
                showConfirmButton: false
            });
        }
    })
    .catch(err => console.error('Error:', err));
}

function applyJob(jobId, companyName, btn) {
    // 🟢 樂觀更新：一秒讓投遞按鈕變成灰色打勾狀態！
    btn.disabled = true;
    btn.className = 'btn btn-secondary btn-sm fw-bold w-100 rounded-pill py-2';
    btn.innerHTML = '<i class="fa-solid fa-check me-1"></i>已投遞';

    fetch('api_apply.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ job_id: jobId })
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'error' || (data.message && data.message.includes('登入'))) {
            Swal.fire('提示', data.message || '請先登入', 'warning');
            setTimeout(() => window.location.href = 'login_register.php', 1500);
            return;
        }
        Swal.fire('投遞成功！', `履歷已發送給 <b>${companyName}</b>`, 'success');
    })
    .catch(err => console.error('Error:', err));
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>