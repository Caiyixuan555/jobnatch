<!-- 引入 FontAwesome (如果其他頁面沒引用的話) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
/* 聊天機器人懸浮按鈕 */
#chatbot-toggler {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #0d6efd, #0dcaf0);
    color: white;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 28px;
    cursor: pointer;
    box-shadow: 0 5px 20px rgba(13, 110, 253, 0.4);
    z-index: 9999;
    transition: all 0.3s ease;
}
#chatbot-toggler:hover { transform: scale(1.1); }

/* 聊天視窗本體 */
#chatbot-window {
    position: fixed;
    bottom: 100px;
    right: 30px;
    width: 350px;
    height: 500px;
    background: #fff;
    border-radius: 15px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    z-index: 9999;
    opacity: 0;
    pointer-events: none;
    transform: translateY(20px);
    transition: all 0.3s ease;
}
#chatbot-window.show {
    opacity: 1;
    pointer-events: auto;
    transform: translateY(0);
}

/* 聊天視窗標題列 */
.chat-header {
    background: linear-gradient(135deg, #0d6efd, #0dcaf0);
    color: white;
    padding: 15px 20px;
    font-weight: bold;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.chat-header .close-btn { cursor: pointer; font-size: 20px; }

/* 聊天對話區 */
.chat-body {
    flex: 1;
    padding: 20px;
    overflow-y: auto;
    background: #f8f9fa;
    display: flex;
    flex-direction: column;
    gap: 15px;
}

/* 訊息氣泡 */
.chat-msg { max-width: 80%; padding: 10px 15px; border-radius: 15px; font-size: 0.95rem; line-height: 1.4; word-wrap: break-word; }
.msg-ai { background: #e9ecef; color: #212529; align-self: flex-start; border-bottom-left-radius: 0; }
.msg-user { background: #0d6efd; color: white; align-self: flex-end; border-bottom-right-radius: 0; }

/* 輸入區 */
.chat-footer {
    padding: 15px;
    background: #fff;
    border-top: 1px solid #dee2e6;
    display: flex;
    gap: 10px;
}
.chat-footer input {
    flex: 1;
    border: 1px solid #ced4da;
    border-radius: 20px;
    padding: 8px 15px;
    outline: none;
    transition: border-color 0.2s;
}
.chat-footer input:focus { border-color: #0d6efd; }
.chat-footer button {
    background: #0d6efd;
    color: white;
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    cursor: pointer;
    transition: background 0.2s;
}
.chat-footer button:hover { background: #0b5ed7; }

/* 打字動畫 */
.typing-indicator { display: none; align-self: flex-start; background: transparent; padding: 0; color: #adb5bd; font-size: 0.9rem;}
</style>

<!-- 觸發按鈕 -->
<div id="chatbot-toggler" onclick="toggleChat()">
    <i class="fa-solid fa-robot"></i>
</div>

<!-- 聊天視窗 -->
<div id="chatbot-window">
    <div class="chat-header">
        <span><i class="fa-solid fa-wand-magic-sparkles me-2"></i>AI 職涯顧問</span>
        <i class="fa-solid fa-xmark close-btn" onclick="toggleChat()"></i>
    </div>
    <div class="chat-body" id="chat-body">
        <div class="chat-msg msg-ai">你好！我是 JobMatch 的 AI 職涯顧問。請問有什麼我可以幫你的嗎？例如：履歷建議、面試技巧、或是職缺推薦？</div>
        <div class="typing-indicator" id="typing-indicator"><i class="fa-solid fa-ellipsis fa-fade"></i> AI 思考中...</div>
    </div>
    <div class="chat-footer">
        <input type="text" id="chat-input" placeholder="請輸入訊息..." onkeypress="handleEnter(event)">
        <button onclick="sendMessage()"><i class="fa-solid fa-paper-plane"></i></button>
    </div>
</div>

<script>
function toggleChat() {
    const chatWindow = document.getElementById('chatbot-window');
    chatWindow.classList.toggle('show');
}

function handleEnter(event) {
    if (event.key === 'Enter') sendMessage();
}

function scrollToBottom() {
    const chatBody = document.getElementById('chat-body');
    chatBody.scrollTop = chatBody.scrollHeight;
}

function sendMessage() {
    const inputField = document.getElementById('chat-input');
    const message = inputField.value.trim();
    if (!message) return;

    const chatBody = document.getElementById('chat-body');
    const typingIndicator = document.getElementById('typing-indicator');

    // 1. 顯示使用者訊息
    const userMsgDiv = document.createElement('div');
    userMsgDiv.className = 'chat-msg msg-user';
    userMsgDiv.textContent = message;
    chatBody.insertBefore(userMsgDiv, typingIndicator);
    
    // 清空輸入框並滾動到底部
    inputField.value = '';
    scrollToBottom();

    // 2. 顯示 AI 正在打字
    typingIndicator.style.display = 'block';
    scrollToBottom();

    // 3. 發送 API 請求給後端
    fetch('api_chat.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: message })
    })
    .then(response => response.json())
    .then(data => {
        typingIndicator.style.display = 'none'; // 隱藏打字動畫
        
        // 4. 顯示 AI 回覆
        const aiMsgDiv = document.createElement('div');
        aiMsgDiv.className = 'chat-msg msg-ai';
        // 將換行符號轉為 <br>
        aiMsgDiv.innerHTML = data.reply.replace(/\n/g, '<br>'); 
        chatBody.insertBefore(aiMsgDiv, typingIndicator);
        scrollToBottom();
    })
    .catch(error => {
        console.error('Error:', error);
        typingIndicator.style.display = 'none';
        const errorDiv = document.createElement('div');
        errorDiv.className = 'chat-msg msg-ai text-danger';
        errorDiv.textContent = '系統連線發生錯誤，請稍後再試！';
        chatBody.insertBefore(errorDiv, typingIndicator);
        scrollToBottom();
    });
}
</script>