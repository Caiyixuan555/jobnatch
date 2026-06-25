import sqlite3
import random

# 連線到 SQLite 資料庫
conn = sqlite3.connect("jobs.db")
cursor = conn.cursor()

# 1. 🎯 [精準對接]：職稱模板全面細分，完全對應前端新版的 13 大期望職類
job_templates = {
    "前端開發": [
        "前端網頁工程師", "Senior Frontend Engineer", "React 前端開發者", 
        "Vue 網頁工程師", "前端介面工程師"
    ],
    "後端開發": [
        "後端軟體工程師", "Java 雲端後端工程師", "Go 金流後端工程師", 
        "Python 後端架構師", "Node.js 後端工程師"
    ],
    "全端開發": [
        "全端軟體工程師 (Full-Stack)", "資深全端工程師", "Web 全端技術主管"
    ],
    "App開發": [
        "iOS App 工程師", "Android 開發工程師", "Flutter 跨平台 App 工程師", 
        "React Native 行動裝置工程師"
    ],
    "數據分析": [
        "資料科學家 (Data Scientist)", "AI 演算法研發工程師", "機器學習工程師", 
        "LLM 應用開發工程師 (AI)", "NLP 研發工程師"
    ],
    "資料工程": [
        "資料工程師 (Data Engineer)", "大數據架構師", "資料庫管理專家 (DBA)", "ETL 開發工程師"
    ],
    "系統維運": [
        "雲端架構師 (Cloud Architect)", "DevOps 運維工程師", "SRE 網站可靠性工程師", "系統維護工程師"
    ],
    "軟體測試": [
        "軟體測試工程師 (QA)", "自動化測試專家", "QA 測試工程師", "TDD 測試專員"
    ],
    "遊戲開發": [
        "Unity 遊戲核心工程師", "Unreal 遊戲開發者", "AR/VR 互動技術工程師", "3D 遊戲軟體工程師"
    ],
    "UI設計": [
        "UI/UX 視覺設計師", "產品設計師 (Product Designer)", "網頁與互動設計師", "介面設計師"
    ],
    "資訊安全": [
        "資訊安全資深工程師", "資安滲透測試專員 (Pentester)", "資安架構與維運專家", "DevSecOps 安全工程師"
    ],
    "專案管理": [
        "專案經理 (PM)", "產品經理 (Product Manager)", "Scrum Master", "敏捷專案管理師"
    ],
    "數位行銷": [
        "數位行銷專員", "SEO 優化策略師", "商業數據分析師 (BA)", "網路行銷企劃"
    ]
}

# 2. 🎯 [精準對接]：技能池字串與前端新版 index.php 100% 保持一致，確保交集比對 (array_intersect) 成功
skills_by_category = {
    "前端開發": [
        'HTML5', 'CSS3', 'JavaScript', 'TypeScript', 'React.js', 'Vue.js', 'Angular', 
        'Svelte', 'Next.js', 'Nuxt.js', 'Tailwind CSS', 'Bootstrap', 'Sass/SCSS', 'Redux', 'GraphQL'
    ],
    "後端開發": [
        'Node.js', 'Python', 'Java', 'C#', '.NET Core', 'PHP', 'Go', 'Ruby', 'Rust', 
        'Spring Boot', 'Django', 'FastAPI', 'Laravel', 'Express.js', 'NestJS', 'Microservices (微服務)', 'RESTful API', 'gRPC'
    ],
    "全端開發": [
        'HTML5', 'JavaScript', 'TypeScript', 'React.js', 'Vue.js', 'Node.js', 'Go', 
        'Docker', 'RESTful API', 'MySQL', 'PostgreSQL'
    ],
    "App開發": [
        'iOS (Swift)', 'SwiftUI', 'Android (Kotlin)', 'Jetpack Compose', 'Flutter', 
        'React Native', 'Objective-C', 'Xamarin / MAUI'
    ],
    "數據分析": [
        'Python (Pandas/NumPy)', 'R', 'TensorFlow', 'PyTorch', 'Scikit-Learn', 
        'Machine Learning', 'Deep Learning', 'Computer Vision (CV)', 'NLP (自然語言處理)', 
        'LLM (大型語言模型)', 'LangChain', 'Prompt Engineering', 'MLOps'
    ],
    "資料工程": [
        'MySQL', 'PostgreSQL', 'Microsoft SQL Server', 'Oracle', 'MongoDB', 'Redis', 
        'Elasticsearch', 'Cassandra', 'Kafka', 'RabbitMQ', 'ETL Processes', 'Hadoop/Spark'
    ],
    "系統維運": [
        'AWS', 'GCP', 'Azure', 'Docker', 'Kubernetes (K8s)', 'Git / GitHub', 
        'GitLab CI/CD', 'Jenkins', 'Terraform', 'Ansible', 'Linux', 'Nginx / Apache', 
        'Serverless', 'Prometheus / Grafana'
    ],
    "軟體測試": [
        'Unit Testing', 'Jest', 'Selenium', 'Cypress', 'Appium', 'Postman', 
        'JMeter', 'Automation Testing', 'TDD / BDD'
    ],
    "遊戲開發": [
        'Unity', 'Unreal Engine', 'C++', 'C#', 'Godot', 'ARKit / ARCore', '3D Modeling', 'Three.js (WebGL)'
    ],
    "UI設計": [
        'Figma', 'Sketch', 'Adobe XD', 'Photoshop', 'Illustrator', 
        'Wireframing', 'Prototyping', 'Design Systems', 'User Research', 'A/B Testing'
    ],
    "資訊安全": [
        'Web Security', 'OWASP Top 10', 'Penetration Testing (滲透測試)', 'Cryptography', 
        'Firewall', 'IAM', 'DevSecOps', 'ISO 27001', 'CEH'
    ],
    "專案管理": [
        'Project Management (PM)', 'Agile / Scrum', 'Jira / Confluence', 'Excel / PowerBI'
    ],
    "數位行銷": [
        'Google Analytics', 'SEO', 'Digital Marketing', 'Excel / PowerBI', 'Tableau', 'CRM / Salesforce'
    ]
}

# 建立一個全局通用技能池（用來隨機跨領域抽取，增加真實感）
all_skills_pool = []
for category_skills in skills_by_category.values():
    all_skills_pool.extend(category_skills)
all_skills_pool = list(set(all_skills_pool))  # 移除重複項目

# 3. 企業與地點名單
companies = [
    "台積電", "聯發科", "華碩", "宏碁", "鴻海", "緯創", "中鋼",
    "趨勢科技", "網銀國際", "國泰金控", "富邦金控", "訊連科技",
    "Google Taiwan", "Microsoft Taiwan", "Line Taiwan", "Appier", "Dcard", "Pinkoi"
]

locations = [
    "基隆", "台北", "新北", "桃園", "新竹", "苗栗", "台中", "彰化", "南投", 
    "雲林", "嘉義", "台南", "高雄", "屏東", "宜蘭", "花蓮", "台東", "外島", "海外", "遠端"
]

job_types = ["全職", "兼職", "實習", "接案"]
type_weights = [0.65, 0.15, 0.15, 0.05]

categories = list(job_templates.keys())

# --- 開始生成資料 ---

# 先清空資料庫內的舊資料，避免新舊技能、新舊分類混雜導致錯誤
cursor.execute("DELETE FROM jobs")

for i in range(500):
    # 隨機選擇大分類與對應基礎職稱
    category = random.choice(categories)
    base_title = random.choice(job_templates[category])
    
    # 隨機決定這份工作是全職還是實習，並加在標題上！
    j_type = random.choices(job_types, weights=type_weights)[0]
    # 如果不是全職，就在標題前面加上【實習】、【兼職】或【接案】
    title = f"【{j_type}】{base_title}" if j_type != "全職" else base_title
    
    # 隨機選擇公司與地點
    company = random.choice(companies)
    location = random.choice(locations)

    # 抽取核心技能：從該職缺專屬的技能池中，隨機挑選 2~4 個核心技能
    primary_pool = skills_by_category[category]
    num_primary = min(len(primary_pool), random.randint(2, 4))
    chosen_skills = random.sample(primary_pool, num_primary)
    
    # 抽取輔助技能：隨機從「所有技能」中再補上 1~2 個，模擬真實職缺的跨領域要求
    while len(chosen_skills) < random.randint(4, 6):
        additional_skill = random.choice(all_skills_pool)
        if additional_skill not in chosen_skills:
            chosen_skills.append(additional_skill)
            
    # 打亂技能順序，並組合成逗號隔開的字串
    random.shuffle(chosen_skills)
    skills_text = ", ".join(chosen_skills)

    # 生成職缺描述
    description = f"誠徵 {title}！我們正在尋找具備 {skills_text} 相關經驗的優秀人才。工作型態為 {j_type}，工作地點位於 {location}，歡迎充滿熱忱的您投遞履歷！"

    # 將資料寫入 SQLite 資料庫
    cursor.execute("""
    INSERT INTO jobs
    (title, company, location, category, skills, description)
    VALUES (?, ?, ?, ?, ?, ?)
    """, (
        title,
        company,
        location,
        category,
        skills_text,
        description
    ))

# 儲存變更並關閉連線
conn.commit()
conn.close()

print("🎯 成功對接最新版首頁！500 筆包含新版 13 大職類、全新專業技術與全台地點的測試資料已重新寫入 jobs.db！")