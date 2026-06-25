<!DOCTYPE html>
<html lang="zh-TW">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>JobMatch - 專業職缺媒合平台</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    .hero-section { background: linear-gradient(135deg, #0f2027, #203a43, #2c5364); color: white; padding: 60px 0; border-radius: 0 0 30px 30px; margin-bottom: 40px; box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
    .custom-card { border: none; border-radius: 20px; box-shadow: 0 8px 25px rgba(0,0,0,0.05); background: white; padding: 30px; margin-bottom: 30px; }
    .category-title { font-size: 1.15rem; font-weight: 600; color: #2c3e50; margin-bottom: 15px; display: flex; align-items: center; border-bottom: 2px solid #f0f2f5; padding-bottom: 10px; margin-top: 25px; }
    .category-title i { width: 30px; color: #0d6efd; }
    .skill-chip-container { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 15px; }
    .skill-checkbox { display: none; }
    .skill-label { padding: 8px 18px; background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 30px; font-size: 0.95rem; color: #495057; cursor: pointer; transition: all 0.3s ease; user-select: none; }
    .skill-label:hover { background-color: #e2e6ea; border-color: #dae0e5; }
    .skill-checkbox:checked + .skill-label { background-color: #0d6efd; color: white; border-color: #0d6efd; box-shadow: 0 4px 10px rgba(13, 110, 253, 0.3); font-weight: 500; }
    .sticky-sidebar { position: sticky; top: 80px; }
</style>
</head>

<body>
<?php include 'nav.php'; ?>

<div class="hero-section text-center">
    <div class="container">
        <h1 class="fw-bold mb-3"><i class="fa-solid fa-rocket me-3"></i>JobMatch 職涯導航</h1>
        <p class="fs-5 text-light opacity-75">精準鎖定你的專業，AI 媒合最適合你的理想職缺</p>
    </div>
</div>

<div class="container mb-5">
    <form id="ai-match-form" action="recommend.php" method="POST">
        <div class="row">
            <div class="col-lg-8">
                <div class="custom-card">
                    <h3 class="mb-2 fw-bold text-primary"><i class="fa-solid fa-layer-group me-2"></i>專業技能</h3>
                    <p class="text-secondary mb-4">請盡可能點選您熟悉或有經驗的技術，這將幫助系統依照技能匹配演算法更精準地為您推薦職缺。</p>
                    
                    <?php
                    $skills_matrix = [
                        "前端開發" => ['HTML5', 'CSS3', 'JavaScript', 'TypeScript', 'React.js', 'Vue.js', 'Angular', 'Svelte', 'Next.js', 'Nuxt.js', 'Tailwind CSS', 'Bootstrap', 'Sass/SCSS', 'Redux', 'GraphQL'],
                        "後端開發" => ['Node.js', 'Python', 'Java', 'C#', '.NET Core', 'PHP', 'Go', 'Ruby', 'Rust', 'Spring Boot', 'Django', 'FastAPI', 'Laravel', 'Express.js', 'NestJS', 'Microservices (微服務)', 'RESTful API', 'gRPC'],
                        "全端開發" => ['HTML5', 'JavaScript', 'TypeScript', 'React.js', 'Vue.js', 'Node.js', 'Go', 'Docker', 'RESTful API', 'MySQL', 'PostgreSQL'],
                        "App開發" => ['iOS (Swift)', 'SwiftUI', 'Android (Kotlin)', 'Jetpack Compose', 'Flutter', 'React Native', 'Objective-C', 'Xamarin / MAUI'],
                        "數據分析" => ['Python (Pandas/NumPy)', 'R', 'TensorFlow', 'PyTorch', 'Scikit-Learn', 'Machine Learning', 'Deep Learning', 'Computer Vision (CV)', 'NLP (自然語言處理)', 'LLM (大型語言模型)', 'LangChain', 'Prompt Engineering', 'MLOps'],
                        "資料工程" => ['MySQL', 'PostgreSQL', 'Microsoft SQL Server', 'Oracle', 'MongoDB', 'Redis', 'Elasticsearch', 'Cassandra', 'Kafka', 'RabbitMQ', 'ETL Processes', 'Hadoop/Spark'],
                        "系統維運" => ['AWS', 'GCP', 'Azure', 'Docker', 'Kubernetes (K8s)', 'Git / GitHub', 'GitLab CI/CD', 'Jenkins', 'Terraform', 'Ansible', 'Linux', 'Nginx / Apache', 'Serverless', 'Prometheus / Grafana'],
                        "軟體測試" => ['Unit Testing', 'Jest', 'Selenium', 'Cypress', 'Appium', 'Postman', 'JMeter', 'Automation Testing', 'TDD / BDD'],
                        "遊戲開發" => ['Unity', 'Unreal Engine', 'C++', 'C#', 'Godot', 'ARKit / ARCore', '3D Modeling', 'Three.js (WebGL)'],
                        "UI設計" => ['Figma', 'Sketch', 'Adobe XD', 'Photoshop', 'Illustrator', 'Wireframing', 'Prototyping', 'Design Systems', 'User Research', 'A/B Testing'],
                        "資訊安全" => ['Web Security', 'OWASP Top 10', 'Penetration Testing (滲透測試)', 'Cryptography', 'Firewall', 'IAM', 'DevSecOps', 'ISO 27001', 'CEH'],
                        "專案管理" => ['Project Management (PM)', 'Agile / Scrum', 'Jira / Confluence', 'Excel / PowerBI'],
                        "數位行銷" => ['Google Analytics', 'SEO', 'Digital Marketing', 'Excel / PowerBI', 'Tableau', 'CRM / Salesforce']
                    ];

                    $icons = [
                        "前端開發" => "fa-brands fa-html5",
                        "後端開發" => "fa-solid fa-server",
                        "全端開發" => "fa-solid fa-code-compare",
                        "App開發" => "fa-solid fa-mobile-screen",
                        "數據分析" => "fa-solid fa-chart-pie",
                        "資料工程" => "fa-solid fa-database",
                        "系統維運" => "fa-solid fa-cloud",
                        "軟體測試" => "fa-solid fa-vial-virus",
                        "遊戲開發" => "fa-solid fa-gamepad",
                        "UI設計" => "fa-solid fa-pen-nib",
                        "資訊安全" => "fa-solid fa-shield-halved",
                        "專案管理" => "fa-solid fa-list-check",
                        "數位行銷" => "fa-solid fa-bullhorn"
                    ];

                    $global_skill_id = 0; 

                    foreach ($skills_matrix as $category => $skills) {
                        $icon = $icons[$category] ?? "fa-solid fa-check";
                        $margin_style = ($category == "前端開發") ? 'style="margin-top:0;"' : '';
                        
                        echo "<div class='category-title' $margin_style><i class='$icon'></i>$category</div>";
                        echo "<div class='skill-chip-container'>";
                        
                        foreach ($skills as $index => $s) {
                            $global_skill_id++; 
                            $safe_id = 'sk_box_' . $global_skill_id;
                            $safe_value = htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
                            
                            echo "<input type='checkbox' class='skill-checkbox' name='skills[]' value='$safe_value' id='$safe_id'>";
                            echo "<label class='skill-label' for='$safe_id'>$safe_value</label>";
                        }
                        echo "</div>";
                    }
                    ?>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="custom-card sticky-sidebar">
                    <h4 class="mb-4 fw-bold"><i class="fa-solid fa-sliders me-2"></i>期望求職條件</h4>
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold text-secondary">期望職務類別</label>
                        <select class="form-select border-0 bg-light" name="category">
                            <option value="" selected disabled>請選擇您的目標職缺...</option>
                            <option value="前端開發">前端開發</option>
                            <option value="後端開發">後端開發</option>
                            <option value="全端開發">全端開發</option>
                            <option value="App開發">App開發</option>
                            <option value="數據分析">數據分析</option>
                            <option value="資料工程">資料工程</option>
                            <option value="系統維運">系統維運</option>
                            <option value="軟體測試">軟體測試</option>
                            <option value="遊戲開發">遊戲開發</option>
                            <option value="UI設計">UI設計</option>
                            <option value="資訊安全">資訊安全</option>
                            <option value="專案管理">專案管理</option>
                            <option value="數位行銷">數位行銷</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold text-secondary">期望工作地點</label>
                        <select class="form-select border-0 bg-light" name="location">
                            <option value="不拘">不拘 (Any)</option>
                            <option disabled>--- 北部地區 ---</option>
                            <option value="基隆">基隆市</option>
                            <option value="台北">台北市</option>
                            <option value="新北">新北市</option>
                            <option value="桃園">桃園市</option>
                            <option value="新竹">新竹縣市</option>
                            <option disabled>--- 中部地區 ---</option>
                            <option value="苗栗">苗栗縣</option>
                            <option value="台中">台中市</option>
                            <option value="彰化">彰化縣</option>
                            <option value="南投">南投縣</option>
                            <option value="雲林">雲林縣</option>
                            <option disabled>--- 南部地區 ---</option>
                            <option value="嘉義">嘉義縣市</option>
                            <option value="台南">台南市</option>
                            <option value="高雄">高雄市</option>
                            <option value="屏東">屏東縣</option>
                            <option disabled>--- 東部與其他 ---</option>
                            <option value="宜蘭">宜蘭縣</option>
                            <option value="花蓮">花蓮縣</option>
                            <option value="台東">台東縣</option>
                            <option value="外島">澎金馬外島</option>
                            <option value="海外">海外 (Overseas)</option>
                            <option value="遠端">完全遠端 (Remote)</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold text-secondary">期望工作型態 </label>
                        <div class="d-flex flex-wrap gap-2">
                            <input type="checkbox" class="btn-check" name="jobtype[]" id="jt_full" value="全職">
                            <label class="btn btn-outline-primary btn-sm rounded-pill px-3" for="jt_full">全職</label>

                            <input type="checkbox" class="btn-check" name="jobtype[]" id="jt_part" value="兼職">
                            <label class="btn btn-outline-primary btn-sm rounded-pill px-3" for="jt_part">兼職</label>

                            <input type="checkbox" class="btn-check" name="jobtype[]" id="jt_intern" value="實習">
                            <label class="btn btn-outline-primary btn-sm rounded-pill px-3" for="jt_intern">實習</label>

                            <input type="checkbox" class="btn-check" name="jobtype[]" id="jt_free" value="接案">
                            <label class="btn btn-outline-primary btn-sm rounded-pill px-3" for="jt_free">接案/外包</label>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold text-secondary">自我介紹 / 專案經歷</label>
                        <textarea class="form-control border-0 bg-light" rows="4" name="intro" placeholder="請簡單介紹您的強項、GitHub 連結或過去負責過的專案..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-3 fw-bold fs-5 rounded-pill shadow-sm">
                        <i class="fa-solid fa-magnifying-glass me-2"></i>開始媒合
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.getElementById('ai-match-form').addEventListener('submit', function(e) {
    // 1. 取得勾選的技能數量與自我介紹文字
    const checkboxes = document.querySelectorAll('input[name="skills[]"]:checked');
    const introText = document.querySelector('textarea[name="intro"]').value.trim();

    // 🟢 放寬防呆條件：如果「沒勾選技能」而且「也沒寫自我介紹」，才進行攔截
    if (checkboxes.length === 0 && introText.length === 0) {
        e.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: '哎呀，等等！',
            text: '請至少選擇一項專業技能，或是在「自我介紹 / 專案經歷」填寫您的強項描述，AI 才能為您計算媒合分數喔！',
            confirmButtonColor: '#0d6efd'
        });
        return;
    }

    // 2. 攔截送出動作，播放超炫的 AI 計算動畫
    e.preventDefault(); 
    
    Swal.fire({
        title: '<span style="color:#0f2027;"><i class="fa-solid fa-microchip text-primary me-2"></i>AI 數據運算中...</span>',
        html: `
            <div class="text-start mt-3 mb-4">
                <p class="mb-2 text-muted"><i class="fa-solid fa-check text-success me-2"></i>正在進行履歷文字探勘與語意分析...</p>
                <p class="mb-2 text-muted"><i class="fa-solid fa-check text-success me-2"></i>正在交叉檢索企業大數據庫...</p>
                <p class="mb-0 text-primary fw-bold"><i class="fa-solid fa-spinner fa-spin me-2"></i>正在計算最佳智慧適配權重...</p>
            </div>
            <div class="progress" style="height: 10px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary w-100" role="progressbar"></div>
            </div>
        `,
        showConfirmButton: false,
        allowOutsideClick: false,
        timer: 1800 // 顯示動畫 1.8 秒
    }).then(() => {
        // 3. 動畫結束後，真正將資料送往 recommend.php
        this.submit(); 
    });
});
</script>

<?php include 'chatbot.php'; ?>

</body>
</html>