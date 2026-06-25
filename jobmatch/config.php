<?php
try {
    $db = new PDO("sqlite:jobs.db");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 確保 users 資料表存在，且相容 name (與 login_register.php 一致)
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT,
        username TEXT, 
        email TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        preferred_area TEXT,
        interest TEXT,
        reset_token TEXT,
        reset_expires_at TIMESTAMP,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 確保 applications 資料表有符合應徵紀錄頁面的 created_at 欄位
    $db->exec("CREATE TABLE IF NOT EXISTS applications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        job_id INTEGER NOT NULL,
        status TEXT DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // 確保 favorites 收藏表結構正常
    $db->exec("CREATE TABLE IF NOT EXISTS favorites (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        job_id INTEGER NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

} catch (PDOException $e) {
    die("資料庫初始化失敗：" . $e->getMessage());
}

// 集中管理 AI API Key
define('GEMINI_API_KEY', 'AQ.Ab8RN6Lh1QXcyqIZa7EWqTZIhZZ1DGZzhOQiWrHYZbnyYxxFiA');
?>