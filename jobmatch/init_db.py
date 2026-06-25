import sqlite3

# 連線到資料庫檔案
conn = sqlite3.connect("jobs.db")
cursor = conn.cursor()

print("🛠️  正在為您修正並建立精準的資料庫結構...")
print("--------------------------------------------------")

# 先刪除舊的 applications 表格以進行欄位更新
cursor.execute("DROP TABLE IF EXISTS applications;")

# 1. 建立 jobs (職缺資料) 資料表
cursor.execute("""
CREATE TABLE IF NOT EXISTS jobs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT,
    company TEXT,
    location TEXT,
    category TEXT,
    description TEXT,
    skills TEXT
);
""")
print("✅ `jobs` 表格檢查/建立完成")

# 2. 建立 applications (投遞紀錄) 資料表 
# 🟢 修正：將欄位改為 PHP 程式指定的 created_at
cursor.execute("""
CREATE TABLE IF NOT EXISTS applications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    job_id INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
""")
print("✅ `applications` 表格修正/建立完成")

# 3. 建立 favorites (收藏紀錄) 資料表
cursor.execute("""
CREATE TABLE IF NOT EXISTS favorites (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    job_id INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
""")
print("✅ `favorites` 表格檢查/建立完成")

# 4. 建立 users (使用者會員資料) 資料表
cursor.execute("""
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL,
    password TEXT NOT NULL,
    email TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
""")
print("✅ `users` 表格檢查/建立完成")

print("--------------------------------------------------")
conn.commit()
conn.close()

print("✨ 所有核心資料表欄位已完美對齊！")
print("🚀 請立刻回到網頁重新整理，應徵紀錄就可以正常開啟囉！")