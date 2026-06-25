import sqlite3
import urllib.parse
import time
import random
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By

# 精簡化多軌關鍵字：保留精華，避免被鎖，速度最快
SEARCH_TERMS = [
    # ---- 前端開發 ----
    ("前端開發", "前端"), ("前端開發", "Frontend"), ("前端開發", "React"),
    # ---- 後端開發 ----
    ("後端開發", "後端"), ("後端開發", "Backend"), ("後端開發", "Python"), ("後端開發", "Java"),
    # ---- App開發 ----
    ("App開發", "App"), ("App開發", "iOS"), ("App開發", "Android"),
    # ---- 數據分析 ----
    ("數據分析", "資料分析"), ("數據分析", "Data Analyst"), ("數據分析", "資料科學"),
    # ---- 系統維運 ----
    ("系統維運", "DevOps"), ("系統維運", "系統工程師"),
    # ---- UI設計 ----
    ("UI設計", "UI UX"), ("UI設計", "設計師"),
    # ---- 軟體測試 ----
    ("軟體測試", "測試"),
    # ---- 專案管理 ----
    ("專案管理", "PM"),
    # ---- 數位行銷 ----
    ("數位行銷", "行銷")
]

def fetch_and_save_jobs():
    print("🚀 [高速多軌數據採集模式] 正在啟動隱形 Chrome...")
    
    chrome_options = Options()
    chrome_options.add_argument("--headless") 
    chrome_options.add_argument("--disable-gpu")
    chrome_options.add_argument("--window-size=1920,1080")
    chrome_options.add_argument("user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36")

    try:
        driver = webdriver.Chrome(options=chrome_options)
        
        conn = sqlite3.connect("jobs.db")
        cursor = conn.cursor()
        
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS jobs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT,
                company TEXT,
                location TEXT,
                category TEXT,
                description TEXT,
                skills TEXT
            )
        """)
        
        cursor.execute("SELECT title, company, category FROM jobs")
        existing_jobs = {}
        for row in cursor.fetchall():
            existing_jobs[(row[0], row[1])] = row[2] if row[2] else ""
        
        total_inserted = 0
        total_updated = 0
        
        tech_keywords = [
            'vue', 'react', 'angular', 'node', 'php', 'laravel', 'javascript', 'python', 'java', 'git', 
            'mysql', 'docker', 'css', 'html', 'typescript', 'golang', 'swift', 'kotlin', 'flutter', 
            'aws', 'gcp', 'kubernetes', 'figma', 'sketch', 'selenium', 'jira', 'agile', 'seo', 
            'google analytics', 'pandas', 'machine learning', 'tableau', 'power bi', 'sql', 'c++', 'c#'
        ]
        
        for index, (category_name, keyword) in enumerate(SEARCH_TERMS, 1):
            print(f"\n📡 進度 [{index}/{len(SEARCH_TERMS)}] | 分類:【{category_name}】 -> 關鍵字:【{keyword}】")
            
            encoded_keyword = urllib.parse.quote(keyword)
            url = f"https://www.yourator.co/jobs?term={encoded_keyword}"
            
            try:
                driver.get(url)
                time.sleep(1.2) 
                
                # 智慧型向下滾動
                last_height = driver.execute_script("return document.body.scrollHeight")
                scroll_attempts = 0
                max_scrolls = 12  
                
                while scroll_attempts < max_scrolls:
                    driver.execute_script("window.scrollTo(0, document.body.scrollHeight);")
                    time.sleep(random.uniform(0.5, 0.8))
                    new_height = driver.execute_script("return document.body.scrollHeight")
                    if new_height == last_height:
                        break
                    last_height = new_height
                    scroll_attempts += 1
                
                # 🟢 關鍵升級：用 JavaScript 瞬間抽出所有節點的資料，不跟 Chrome 慢速通訊了！
                raw_job_data = driver.execute_script("""
                    return Array.from(document.querySelectorAll("a[href*='/jobs/']")).map(el => {
                        return {
                            href: el.getAttribute('href') || '',
                            text: el.innerText || el.textContent || ''
                        };
                    });
                """)
                
                if not raw_job_data:
                    print("⚠️ 網頁未響應或該關鍵字無職缺，跳過...")
                    continue
                    
                print(f"   ↳ 成功瞬間捕獲 {len(raw_job_data)} 個職缺數據，開始超高速記憶體清洗...")
                
                insert_data = []
                
                # 這裡全部都在 Python 記憶體內運算，快如閃電
                for item in raw_job_data:
                    try:
                        href = item['href']
                        if not href or href.endswith('/jobs') or href.endswith('/jobs/'):
                            continue
                        
                        card_text = item['text'].strip()
                        if not card_text:
                            continue
                            
                        lines = [l.strip() for l in card_text.split("\n") if l.strip()]
                        if len(lines) < 2:
                            continue
                        
                        title = lines[0]
                        company = lines[1]
                        
                        if "了解更多" in title or len(title) > 60 or len(title) < 2:
                            continue
                        
                        # 多重分類追加機制
                        if (title, company) in existing_jobs:
                            current_cats = [c.strip() for c in existing_jobs[(title, company)].split(",") if c.strip()]
                            if category_name not in current_cats:
                                current_cats.append(category_name)
                                updated_cats_str = ",".join(current_cats)
                                existing_jobs[(title, company)] = updated_cats_str
                                
                                cursor.execute("UPDATE jobs SET category = ? WHERE title = ? AND company = ?", (updated_cats_str, title, company))
                                conn.commit()
                                total_updated += 1
                            continue 
                            
                        location = "台灣"
                        description = f"誠徵 {title}！我們正在尋找優秀的夥伴加入團隊，主要負責 {category_name} 相關業務，需具備獨立解決問題與團隊協作能力。"
                        
                        detected_skills = []
                        full_text = (title + " " + card_text).lower()
                        for tech in tech_keywords:
                            if tech in full_text:
                                detected_skills.append(tech)
                                
                        if not detected_skills:
                            skills_str = f"{category_name.replace('開發','').replace('設計','')},{keyword}".lower()
                        else:
                            skills_str = ",".join(detected_skills)
                        
                        insert_data.append((title, company, location, category_name, description, skills_str))
                        existing_jobs[(title, company)] = category_name 
                    except Exception:
                        continue
                
                if insert_data:
                    cursor.executemany("""
                        INSERT INTO jobs (title, company, location, category, description, skills)
                        VALUES (?, ?, ?, ?, ?, ?)
                    """, insert_data)
                    conn.commit()
                    total_inserted += len(insert_data)
                    print(f"   💾 本波成功秒速寫入 {len(insert_data)} 筆全新職缺。")
                else:
                    print("   ℹ️ 本波無新職缺（皆為重複資料）。")
                
                time.sleep(0.8)
                
            except Exception as e:
                print(f"❌ 掃描關鍵字【{keyword}】時發生錯誤: {e}，跳過此詞...")
                continue

        driver.quit()
        conn.close()
        print(f"\n✨ ✨ 【大數據天網建置完成】！")
        print(f"📈 本次全新灌入： {total_inserted} 筆職缺")
        print(f"🔄 跨領域升級更新： {total_updated} 筆職缺")
        
    except Exception as e:
        print(f"❌ 瀏覽器啟動失敗，請檢查 Chrome 環境：{e}")

if __name__ == "__main__":
    fetch_and_save_jobs()