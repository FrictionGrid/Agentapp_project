<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>

<body>
    <canvas class="star-bg"></canvas>
    <div class="planet-glow"></div>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-logo">MAR AI</div>
            <nav class="sidebar-nav">
                <a href="#">Dashboard</a>
                <a href="#"class="active">Emails</a>
                <a href="#">Analytics</a>
                <a href="#">Account</a>
                <a href="#">Logout</a>
            </nav>
        </aside>
     <!-- Main -->
    <div class="dashboard-main">
      <!-- Chatbot -->
      <div class="chat-container">
        <div class="chat-window" id="chatWindow">
          <div class="chat-header">
            <span>MAR AI Bot</span>
            <button class="chat-close">✖</button>
          </div>
          <div class="chat-messages" id="chatMessages">
            <div class="message bot">สวัสดี! มีอะไรให้ช่วยไหม?</div>
          </div>
          <div class="chat-input">
            <textarea id="messageInput" class="message-input" rows="1" placeholder="พิมพ์ข้อความ..."></textarea>
            <button id="sendBtn" class="send-btn">ส่ง</button>
          </div>
        </div>
          <button class="chat-toggle">💬</button>
      </div>

      <div class="dashboard-content">
        @yield('content')
      </div>
    </div>
  </div>

  <script>
    // ----------- Chatbot JS -------------
    const chatMessages = document.getElementById('chatMessages');
    const messageInput = document.getElementById('messageInput');
    const sendBtn = document.getElementById('sendBtn');

    // สร้าง session id
    let sessionId = localStorage.getItem('chat_session_id') || sessionStorage.getItem('chat_session_id');
    if (!sessionId) {
      sessionId = 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
      localStorage.setItem('chat_session_id', sessionId);
      sessionStorage.setItem('chat_session_id', sessionId);
    }
    if (localStorage.getItem('chat_session_id') !== sessionStorage.getItem('chat_session_id')) {
      sessionStorage.setItem('chat_session_id', sessionId);
    }

    function appendMessage(text, sender) {
      const div = document.createElement('div');
      div.textContent = text;
      div.className = 'message ' + sender; // user หรือ bot
      chatMessages.appendChild(div);
      chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    async function sendMessage() {
      const text = messageInput.value.trim();
      if (!text) return;
      appendMessage(text, 'user');
      messageInput.value = '';
      appendMessage('กำลังคิด...', 'bot');

      try {
        const response = await fetch('/chat', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
          },
          body: JSON.stringify({
            message: text,
            session_id: sessionId
          })
        });

        const data = await response.json();
        chatMessages.lastChild.remove(); // ลบ "กำลังคิด..."
        appendMessage(data.reply, 'bot');
        
        // Update email list if drafts are provided
        if (data.drafts && Array.isArray(data.drafts) && window.updateEmailList) {
          window.updateEmailList(data.drafts);
        }
      } catch (error) {
        chatMessages.lastChild.textContent = 'เกิดข้อผิดพลาดในการเชื่อมต่อ';
      }
    }

    sendBtn.addEventListener('click', sendMessage);
    messageInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
      }
    });

    // ----------- Toggle Chat -------------
    const chatToggle = document.querySelector('.chat-toggle');
    const chatWindow = document.querySelector('.chat-window');
    const chatClose = document.querySelector('.chat-close');

    chatToggle.addEventListener('click', () => {
      chatWindow.style.display = 'flex';
      chatToggle.style.display = 'none';
    });

    chatClose.addEventListener('click', () => {
      chatWindow.style.display = 'none';
      chatToggle.style.display = 'block';
    });
  </script>
  @stack('scripts')
</body>
</html>