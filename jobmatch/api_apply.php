<?php
session_start();
require 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => '請先登入才能應徵職缺！']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$job_id = $data['job_id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$job_id) {
    echo json_encode(['status' => 'error', 'message' => '缺少職缺資訊']);
    exit;
}

try {
    // 檢查是否已經投遞過
    $stmt = $db->prepare("SELECT id FROM applications WHERE user_id = ? AND job_id = ?");
    $stmt->execute([$user_id, $job_id]);
    
    if ($stmt->fetch()) {
        echo json_encode(['status' => 'exists', 'message' => '您已經應徵過這個職缺囉！']);
    } else {
        // 新增應徵紀錄
        $stmt = $db->prepare("INSERT INTO applications (user_id, job_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $job_id]);
        echo json_encode(['status' => 'success', 'message' => '應徵成功']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => '資料庫錯誤: ' . $e->getMessage()]);
}
?>