import sqlite3

conn = sqlite3.connect("jobs.db")
cursor = conn.cursor()

try:
    cursor.execute("ALTER TABLE users ADD COLUMN reset_expires_at TIMESTAMP")
    print("✅ 太棒了！成功為 users 資料表加入 'reset_expires_at' (密碼過期時間) 欄位！")
except sqlite3.OperationalError:
    print("⚠️ 欄位可能已經存在，不用重複新增。")

conn.commit()
conn.close()