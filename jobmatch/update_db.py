import sqlite3

conn = sqlite3.connect("jobs.db")
cursor = conn.cursor()

# 建立「應徵紀錄」資料表 (如果不存在的話)
cursor.execute("""
CREATE TABLE IF NOT EXISTS applications(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    job_id INTEGER NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id),
    FOREIGN KEY(job_id) REFERENCES jobs(id),
    UNIQUE(user_id, job_id)  -- 確保同一個工作只能應徵一次
)
""")

conn.commit()
conn.close()

print("✅ 資料庫更新成功！已安全加入 applications (應徵紀錄) 資料表。")