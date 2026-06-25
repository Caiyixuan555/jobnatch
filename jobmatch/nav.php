<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php"><i class="fa-solid fa-rocket me-2"></i>JobMatch</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li class="nav-item me-3">
                        <span class="text-light fw-bold">👋 嗨，<?= htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') ?>！</span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-warning fw-bold" href="favorites.php"><i class="fa-solid fa-heart me-1"></i>我的收藏</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-info fw-bold" href="applications.php"><i class="fa-solid fa-paper-plane me-1"></i>應徵紀錄</a>
                    </li>
                    <li class="nav-item ms-2">
                        <a class="btn btn-outline-light btn-sm rounded-pill px-3" href="logout.php">登出</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="btn btn-primary btn-sm rounded-pill px-4 fw-bold" href="login_register.php">登入 / 註冊</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>