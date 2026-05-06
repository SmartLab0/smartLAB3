<?php
    session_start();
    
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.html");
        exit();
    }
    
    require_once 'config.php';
    
    $user_id = $_SESSION['user_id'];
    $full_name = $_SESSION['fullname'];
    
    // جلب محفوظات المحادثات من قاعدة البيانات - كل رسالة كمحادثة مستقلة
    $conversations = [];
    if ($conn && $conn->connect_errno === 0) {
        $history_sql = "SELECT id, user_message, created_at 
                       FROM chatbot_logs 
                       WHERE user_id = ? 
                       ORDER BY created_at DESC 
                       LIMIT 50";
        $stmt = $conn->prepare($history_sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $history_result = $stmt->get_result();
        
        // كل رسالة كمحادثة منفصلة
        while ($row = $history_result->fetch_assoc()) {
            $conversations[] = [
                'id' => $row['id'],
                'title' => $row['user_message'],
                'date' => date('Y-m-d', strtotime($row['created_at'])),
                'time' => date('H:i', strtotime($row['created_at']))
            ];
        }
        $stmt->close();
    }
    ?><!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المساعد الذكي - منصة المعامل الذكية</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .chatbot-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .chatbot-header {
            background: linear-gradient(135deg, #017d75 0%, #005f59 100%);
            color: white;
            border-radius: 20px;
            padding: 30px;
            display: flex;
            align-items: center;
            gap: 25px;
            margin-bottom: 30px;
            box-shadow: 0 8px 25px rgba(1, 125, 117, 0.2);
        }
        .bot-avatar {
            width: 90px; height: 90px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .bot-avatar i { font-size: 45px; color: #017d75; }
        .bot-info { flex: 1; }
        .bot-name { font-size: 32px; margin-bottom: 10px; font-weight: 700; }
        .bot-description { font-size: 18px; opacity: 0.9; line-height: 1.5; }
        .chat-wrapper {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 30px;
            margin-bottom: 30px;
        }
        @media (max-width: 992px) { .chat-wrapper { grid-template-columns: 1fr; } }
        .chat-window {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            height: 600px;
            display: flex;
            flex-direction: column;
        }
        .chat-messages {
            flex: 1;
            padding: 25px;
            overflow-y: auto;
            background: #f8f9fa;
        }
        .message {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            animation: fadeInUp 0.3s ease;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .message.user { flex-direction: row-reverse; }
        .message-avatar {
            width: 45px; height: 45px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        .message.user .message-avatar { background: #017d75; }
        .message-avatar i { color: #017d75; font-size: 20px; }
        .message.user .message-avatar i { color: white; }
        .message-content { max-width: 75%; }
        .message.user .message-content { text-align: left; }
        .message-text {
            background: transparent !important;
            padding: 8px 0;
            line-height: 1.6;
            white-space: pre-line;
            word-wrap: break-word;
            color: #333;
            box-shadow: none !important;
        }
        .message.user .message-text {
            background: transparent !important;
            color: #017d75;
            font-weight: 500;
        }
        .message.bot .message-text {
            background: transparent !important;
            color: #333;
        }
        .message-time {
            font-size: 12px;
            color: #6c757d;
            margin-top: 8px;
            text-align: left;
        }
        .message.user .message-time { text-align: right; }
        .typing-indicator .message-text {
            display: flex;
            gap: 5px;
            padding: 15px 20px;
        }
        .dot {
            width: 8px; height: 8px;
            background: #017d75;
            border-radius: 50%;
            animation: bounce 1.4s infinite;
        }
        .dot:nth-child(2) { animation-delay: 0.2s; }
        .dot:nth-child(3) { animation-delay: 0.4s; }
        @keyframes bounce {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-10px); }
        }
        .chat-input-area {
            border-top: 1px solid #e9ecef;
            background: white;
            padding: 20px;
        }
        .text-input-section { padding: 20px; }
        .input-tools {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        .tool-btn, .send-btn {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            width: 45px; height: 45px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #495057;
            transition: all 0.3s ease;
            font-size: 18px;
        }
        .tool-btn:hover, .send-btn:hover {
            background: #017d75;
            color: white;
            border-color: #017d75;
            transform: scale(1.1);
        }
        .tool-btn:disabled, .send-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }
        .send-btn { background: #017d75; color: white; border: none; width: 55px; height: 55px; }
        .text-input-wrapper {
            display: flex;
            gap: 15px;
            align-items: flex-end;
        }
        #messageInput {
            flex: 1;
            padding: 15px;
            border: 2px solid #e9ecef;
            border-radius: 15px;
            font-family: 'Cairo', sans-serif;
            font-size: 16px;
            resize: none;
            min-height: 60px;
            max-height: 120px;
            line-height: 1.5;
            transition: all 0.3s;
        }
        #messageInput:focus {
            outline: none;
            border-color: #017d75;
            box-shadow: 0 0 0 3px rgba(1, 125, 117, 0.1);
        }
        #messageInput:disabled {
            background: #e9ecef;
            cursor: not-allowed;
        }
        .voice-input-section {
            display: none;
            align-items: center;
            gap: 20px;
            padding: 15px;
            background: linear-gradient(135deg, #017d75 0%, #005f59 100%);
            border-radius: 15px;
            margin-top: 15px;
        }
        .voice-visualizer {
            display: flex;
            align-items: center;
            gap: 6px;
            height: 40px;
            flex: 1;
        }
        .voice-bar {
            width: 8px; height: 5px;
            background: white;
            border-radius: 4px;
            transition: height 0.2s ease;
        }
        .stop-voice-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            font-family: 'Cairo', sans-serif;
        }
        .chat-history-section {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            height: 600px;
            display: flex;
            flex-direction: column;
        }
        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
        }
        .history-header h3 {
            color: #005f59;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 20px;
        }
        .history-list {
            flex: 1;
            overflow-y: auto;
        }
        .no-history {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        .no-history i {
            font-size: 60px;
            margin-bottom: 15px;
            opacity: 0.3;
        }
        .history-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
            cursor: pointer;
            transition: background 0.3s ease;
            border-radius: 10px;
            margin-bottom: 5px;
        }
        .history-item:hover { background: #f8f9fa; }
        .history-icon {
            width: 40px; height: 40px;
            background: #e9ecef;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .history-icon i { color: #017d75; font-size: 18px; }
        .history-content { flex: 1; min-width: 0; }
        .history-message {
            color: #495057;
            font-size: 14px;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .history-time {
            font-size: 12px;
            color: #6c757d;
        }
        .retry-btn {
            background: #017d75;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 10px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .retry-btn:hover { background: #005f59; }
        @media (max-width: 768px) {
            .chatbot-container { padding: 10px; }
            .chatbot-header { flex-direction: column; text-align: center; gap: 15px; padding: 20px; }
            .bot-avatar { width: 70px; height: 70px; }
            .bot-avatar i { font-size: 35px; }
            .bot-name { font-size: 24px; }
            .bot-description { font-size: 16px; }
            .chat-window, .chat-history-section { height: 500px; }
            .message-content { max-width: 85%; }
            .text-input-wrapper { flex-direction: column; gap: 10px; }
            .send-btn { width: 100%; height: 50px; border-radius: 10px; }
        }
    </style>
</head>
<body>
    <header class="main-header">
        <div class="header-container">
            <div class="logo-section">
                <img src="logo.jpg" alt="شعار الجامعة" class="header-logo">
            </div>
            <div class="controls-section">
                <button class="icon-btn" onclick="window.location.href='home.php'">
                    <i class="fas fa-home"></i>
                </button>
            </div>
        </div>
    </header>

    <main class="chatbot-container">
        <div class="chatbot-header">
            <div class="bot-avatar">
                <i class="fas fa-robot"></i>
            </div>
            <div class="bot-info">
                <h1 class="bot-name">المساعد الذكي للمعامل</h1>
                <p class="bot-description">اسأل وأنا أشرح لك بالتفصيل</p>
            </div>
        </div>

        <div class="chat-wrapper">
            <div class="chat-window">
                <div class="chat-messages" id="chatMessages">
                </div>

                <div class="chat-input-area">
                    <div class="text-input-section">
                        <div class="input-tools">
                            <button class="tool-btn" onclick="toggleVoiceInput()" title="إدخال صوتي" id="voiceBtn">
                                <i class="fas fa-microphone"></i>
                            </button>
                        </div>
                        
                        <div class="text-input-wrapper">
                            <textarea 
                                id="messageInput" 
                                placeholder="اكتب سؤالك هنا..."
                                rows="1"
                                onkeydown="handleEnter(event)"
                                oninput="autoResizeTextarea(this)"
                            ></textarea>
                            <button class="send-btn" onclick="sendMessage()" title="إرسال" id="sendBtn">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                        
                        <div class="voice-input-section" id="voiceInputSection">
                            <div class="voice-visualizer" id="voiceVisualizer">
                                <div class="voice-bar"></div>
                                <div class="voice-bar"></div>
                                <div class="voice-bar"></div>
                                <div class="voice-bar"></div>
                                <div class="voice-bar"></div>
                            </div>
                            <button class="stop-voice-btn" onclick="stopVoiceInput()">
                                <i class="fas fa-stop"></i>
                                إيقاف التسجيل
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="chat-history-section">
                <div class="history-header">
                    <h3>
                        <i class="fas fa-history"></i>
                        المحادثات السابقة
                    </h3>
                    <span class="badge" id="historyCount"><?php echo count($conversations); ?></span>
                </div>
                <div class="history-list" id="historyList">
                    <?php if (empty($conversations)): ?>
                    <div class="no-history">
                        <i class="far fa-comments"></i>
                        <p>لا توجد محادثات سابقة</p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($conversations as $conv): ?>
                        <div class="history-item" onclick="loadConversation(<?php echo $conv['id']; ?>)">
                            <div class="history-icon"><i class="fas fa-comment"></i></div>
                            <div class="history-content">
                                <div class="history-message"><?php echo htmlspecialchars(mb_substr($conv['title'], 0, 30)); ?>...</div>
                                <div class="history-time"><?php echo $conv['date']; ?> <?php echo $conv['time']; ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script>
        // متغيرات عامة
        let isRecording = false;
        let mediaRecorder = null;
        let audioChunks = [];
        let voiceVisualizerInterval = null;
        let isWaitingForResponse = false;
        let lastMessage = '';
        let currentConversationId = null;
        
        // بيانات المحادثات من PHP
        const conversations = <?php echo json_encode($conversations); ?>;
        const full_name = '<?php echo $full_name; ?>';
        
        function toggleVoiceInput() {
            const voiceSection = document.getElementById('voiceInputSection');
            const textWrapper = document.querySelector('.text-input-wrapper');
            
            if (voiceSection.style.display === 'none' || voiceSection.style.display === '') {
                startVoiceInput();
                voiceSection.style.display = 'flex';
                textWrapper.style.display = 'none';
            } else {
                stopVoiceInput();
                voiceSection.style.display = 'none';
                textWrapper.style.display = 'flex';
            }
        }
        
        async function startVoiceInput() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                alert('المتصفح لا يدعم التسجيل الصوتي');
                return;
            }
            
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                mediaRecorder = new MediaRecorder(stream);
                audioChunks = [];
                
                mediaRecorder.ondataavailable = event => {
                    if (event.data.size > 0) audioChunks.push(event.data);
                };
                
                mediaRecorder.onstop = () => {
                    const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
                    processAudioInput(audioBlob);
                    stream.getTracks().forEach(track => track.stop());
                };
                
                mediaRecorder.start();
                isRecording = true;
                startVoiceVisualizer();
                
            } catch (error) {
                alert('تعذر الوصول للميكروفون');
                toggleVoiceInput();
            }
        }
        
        function startVoiceVisualizer() {
            const bars = document.querySelectorAll('.voice-bar');
            voiceVisualizerInterval = setInterval(() => {
                if (isRecording) {
                    bars.forEach(bar => {
                        bar.style.height = (Math.random() * 30 + 5) + 'px';
                    });
                }
            }, 150);
        }
        
        function stopVoiceInput() {
            if (mediaRecorder && isRecording) {
                mediaRecorder.stop();
                isRecording = false;
                clearInterval(voiceVisualizerInterval);
                document.querySelectorAll('.voice-bar').forEach(bar => bar.style.height = '5px');
            }
        }
        
        async function processAudioInput(audioBlob) {
            if (isWaitingForResponse) {
                alert("الرجاء الانتظار حتى يكمل البوت الرد");
                return;
            }
            
            addMessage('user', '🎤 جاري تحويل الصوت...', false);
            
            const formData = new FormData();
            formData.append('audio', audioBlob, 'recording.webm');
            
            try {
                const response = await fetch('transcribe.php', { method: 'POST', body: formData });
                const data = await response.json();
                
                if (data.text) {
                    // نحذف رسالة "جاري التحويل"
                    document.querySelector('.message:last-child')?.remove();
                    
                    addMessage('user', `🎤 "${data.text}"`, false);
                    lastMessage = data.text;
                    setTimeout(() => generateBotResponse(data.text), 500);
                } else {
                    addMessage('bot', 'عذراً، لم أستطع فهم الصوت');
                }
            } catch (error) {
                addMessage('bot', 'حدث خطأ في تحويل الصوت');
            }
        }
        
        function loadConversation(id) {
            window.location.href = `chatbot.php?load=${id}`;
        }
        
        function addMessageToChat(sender, content, time) {
            const container = document.getElementById('chatMessages');
            
            const msgDiv = document.createElement('div');
            msgDiv.className = `message ${sender}`;
            
            msgDiv.innerHTML = `
                <div class="message-avatar"><i class="fas fa-${sender === 'user' ? 'user' : 'robot'}"></i></div>
                <div class="message-content">
                    <div class="message-text">${formatMessage(content)}</div>
                    <div class="message-time">${time || new Date().toLocaleTimeString('ar-SA', { hour: '2-digit', minute: '2-digit' })}</div>
                </div>
            `;
            
            container.appendChild(msgDiv);
        }
        
        function disableInputs(disabled) {
            document.getElementById('messageInput').disabled = disabled;
            document.getElementById('sendBtn').disabled = disabled;
            document.getElementById('voiceBtn').disabled = disabled;
        }
        
        function sendMessage() {
            if (isWaitingForResponse) {
                alert("الرجاء الانتظار حتى يكمل البوت الرد");
                return;
            }
            
            const input = document.getElementById('messageInput');
            const message = input.value.trim();
            if (!message) return;
            
            addMessage('user', message);
            input.value = '';
            autoResizeTextarea(input);
            
            lastMessage = message;
            isWaitingForResponse = true;
            disableInputs(true);
            generateBotResponse(message);
        }
        
        function handleEnter(event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                sendMessage();
            }
        }
        
        function autoResizeTextarea(textarea) {
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
        }
        
        function addMessage(sender, text, isVoice = false) {
            const time = new Date().toLocaleTimeString('ar-SA', { hour: '2-digit', minute: '2-digit' });
            let displayText = (isVoice && sender === 'user') ? `🎤 ${text}` : text;
            addMessageToChat(sender, displayText, time);
        }
        
        function formatMessage(text) {
            return text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                      .replace(/\*(.*?)\*/g, '<em>$1</em>')
                      .replace(/\n/g, '<br>');
        }
        
        function showTypingIndicator() {
            const container = document.getElementById('chatMessages');
            const indicator = document.createElement('div');
            indicator.className = 'message bot typing-indicator';
            indicator.id = 'typingIndicator';
            indicator.innerHTML = `
                <div class="message-avatar"><i class="fas fa-robot"></i></div>
                <div class="message-content">
                    <div class="message-text">
                        <span class="dot"></span>
                        <span class="dot"></span>
                        <span class="dot"></span>
                    </div>
                </div>
            `;
            container.appendChild(indicator);
            scrollToBottom();
        }
        
        function showRetryButton() {
            const container = document.getElementById('chatMessages');
            const retryDiv = document.createElement('div');
            retryDiv.className = 'message bot';
            retryDiv.innerHTML = `
                <div class="message-avatar"><i class="fas fa-robot"></i></div>
                <div class="message-content">
                    <div class="message-text">
                        <p>عذراً، حدث خطأ في الاتصال</p>
                        <button class="retry-btn" onclick="retryLastMessage()">
                            <i class="fas fa-redo"></i> إعادة المحاولة
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(retryDiv);
            scrollToBottom();
        }
        
        function retryLastMessage() {
            if (lastMessage) {
                generateBotResponse(lastMessage);
            }
        }
        
        async function generateBotResponse(question) {
            showTypingIndicator();
            
            try {
                const response = await fetch('chat_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message: question })
                });
                
                const data = await response.json();
                
                document.getElementById('typingIndicator')?.remove();
                
                if (data.reply) {
                    addMessage('bot', data.reply);
                    
                    // تحديث قائمة المحادثات بدون إعادة تحميل
                  
                } else {
                    showRetryButton();
                }
            } catch (error) {
                document.getElementById('typingIndicator')?.remove();
                showRetryButton();
            } finally {
                isWaitingForResponse = false;
                disableInputs(false);
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('messageInput').focus();
            
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && document.getElementById('voiceInputSection').style.display === 'flex') {
                    stopVoiceInput();
                    document.getElementById('voiceInputSection').style.display = 'none';
                    document.querySelector('.text-input-wrapper').style.display = 'flex';
                }
            });
            
            // تحميل محادثة محددة إذا كان في parameter
            const urlParams = new URLSearchParams(window.location.search);
            const loadId = urlParams.get('load');
            if (loadId) {
                loadConversation(loadId);
            }
        });
        
        function scrollToBottom() {
            const container = document.getElementById('chatMessages');
            container.scrollTop = container.scrollHeight;
        }
    </script>
</body>
</html>