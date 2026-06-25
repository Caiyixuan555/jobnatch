<?php
require_once 'config.php'; // 引入環境變數
header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents('php://input'), true);
$user_message = $data['message'] ?? '';

if (empty(trim($user_message))) {
    echo json_encode(['reply' => '請輸入你想問的問題哦！']);
    exit;
}

$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . GEMINI_API_KEY;

$payload = [
    "contents" => [
        [
            "parts" => [
                ["text" => "你現在是 JobMatch 求職平台的專屬 AI 職涯顧問。請用繁體中文、親切專業且帶點幽默的語氣，簡短回答使用者的職涯問題。使用者的問題是：" . $user_message]
            ]
        ]
    ]
];

$max_retries = 2; // 當遇到塞車時，最多在背景自動重試 2 次
$response = '';
$result = null;

for ($i = 0; $i < $max_retries; $i++) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); 
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); 

    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    curl_close($ch);

    $result = json_decode($response, true);

    // 如果成功拿到 AI 的回覆，直接跳出迴圈
    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        break;
    }

    // 如果發現是伺服器塞車 (429 或 high demand)，就稍微等 1.5 秒再試一次
    if (isset($result['error']['message']) && strpos($result['error']['message'], 'high demand') !== false) {
        usleep(1500000); // 暫停 1.5 秒
        continue;
    }
}

// 🎯 第一層防線：若正常回傳，解析並輸出
if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
    $ai_reply = $result['candidates'][0]['content']['parts'][0]['text'];
    echo json_encode(['reply' => $ai_reply]);
    exit;
}

// 🔍 第二層防線：如果是高負載塞車，包裝成「超擬真顧問傲嬌回覆」，維持系統高級感
if (isset($result['error']['message']) && strpos($result['error']['message'], 'high demand') !== false) {
    $funny_replies = [
        "☕ 呼～不好意思！剛才諮詢我的求職者實在太多了，AI 顧問的辦公室門口現在大排長龍！關於「" . htmlspecialchars($user_message) . "」，可以請你過 10 秒鐘再問我一次嗎？我泡杯咖啡馬上回來幫你解惑！",
        "🤖 逼逼——系統偵測到全台工程師正在瘋狂湧入！AI 大脑暫時出現短暫的思考卡頓。請稍等幾秒鐘重新對話，我一定會給你最精闢的職涯建議！",
        "💼 哎呀！目前的職涯諮詢需求量瞬間爆棚！顧問正在批閱上萬份黃金履歷。關於你的問題，咱們喝口水，待會兒再聊聊？"
    ];
    echo json_encode(['reply' => $funny_replies[array_rand($funny_replies)]]);
    exit;
}

// 🔍 第三層防線：其他 Google API 報錯
if (isset($result['error']['message'])) {
    echo json_encode(['reply' => '⚠️ [Google API 報錯]：' . $result['error']['message']]);
    exit;
}

// 🎯 包底防線
echo json_encode(['reply' => "系統小幫手收到囉！關於「" . htmlspecialchars($user_message) . "」，建議你可以多利用我們的 AI 智能媒合功能篩選適合的職缺！"]);
exit;