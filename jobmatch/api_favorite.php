<?php
session_start();
require 'config.php';
header('Content-Type: application/json');

// 檢查是否登入
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => '請先登入才能收藏職缺！']);
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
    // 檢查是否已經收藏過
    $stmt = $db->prepare("SELECT id FROM favorites WHERE user_id = ? AND job_id = ?");
    $stmt->execute([$user_id, $job_id]);
    $fav = $stmt->fetch();

    if ($fav) {
        // 如果有，就取消收藏 (刪除)
        $stmt = $db->prepare("DELETE FROM favorites WHERE id = ?");
        $stmt->execute([$fav['id']]);
        echo json_encode(['status' => 'removed', 'message' => '已取消收藏']);
    } else {
        // 如果沒有，就加入收藏 (新增)
        $stmt = $db->prepare("INSERT INTO favorites (user_id, job_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $job_id]);
        echo json_encode(['status' => 'added', 'message' => '收藏成功']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => '資料庫錯誤: ' . $e->getMessage()]);
}
?>