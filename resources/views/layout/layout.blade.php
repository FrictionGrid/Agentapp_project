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
            <div class="sidebar-logo">Nexus</div>
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
            <!-- ตัวอย่างแชทบอท UI -->
            <div class="chat-container">
                <div class="chat-window"
                    style="display: flex; flex-direction: column; height: 300px; border: 1px solid #ccc; padding: 10px; overflow-y: auto;"
                    id="chatMessages">
                    <div class="message bot">สวัสดี! มีอะไรให้ช่วยไหม?</div>
                </div>
                <textarea id="messageInput" rows="2" placeholder="พิมพ์ข้อความ..."></textarea>
                <button id="sendBtn">ส่ง</button>
            </div>
            <div class="dashboard-content">
                @yield('content')
            </div>
        </div>
    </div>

    <div class="chat-container">
        <div class="chat-window" style="display: none;">
            <div class="chat-header">
                <span>MAR AI Bot</span>
                <button class="chat-close">✕</button>
            </div>
            <div class="chat-messages">
                <div class="message bot">สวัสดี! มีอะไรให้ช่วยไหม?</div>
            </div>
            <div class="chat-input">
                <textarea class="message-input" rows="1" placeholder="พิมพ์ข้อความ..."></textarea>
                <button class="send-btn">ส่ง</button>
            </div>
        </div>
        <button class="chat-toggle">💬</button>
    </div>
    <script>
        const canvas = document.querySelector('.star-bg');
        const ctx = canvas.getContext('2d');
        let w, h, stars;

        function resize() {
            w = canvas.width = window.innerWidth;
            h = canvas.height = window.innerHeight;
            stars = [];
            for (let i = 0; i < 120; i++) {
                stars.push({
                    x: Math.random() * w,
                    y: Math.random() * h,
                    z: Math.random() * 1 + 0.5,
                    r: Math.random() * 1.1 + 0.5,
                    a: Math.random() * 0.7 + 0.35,
                    vx: (Math.random() - 0.5) * 0.03,
                    vy: (Math.random() - 0.5) * 0.03
                });
            }
        }
        let shootingStar = {
            x: 0,
            y: 0,
            len: 0,
            speed: 0,
            angle: 0,
            alpha: 0
        };

        function randomizeShootingStar() {
            shootingStar.x = Math.random() * w * 0.8 + w * 0.1;
            shootingStar.y = Math.random() * h * 0.5 + h * 0.1;
            shootingStar.len = Math.random() * 110 + 50;
            shootingStar.speed = Math.random() * 2 + 1.2;
            shootingStar.angle = Math.random() * Math.PI / 5 + Math.PI / 8;
            shootingStar.alpha = 1;
        }

        function draw() {
            ctx.clearRect(0, 0, w, h);
            for (const s of stars) {
                ctx.save();
                ctx.globalAlpha = s.a;
                ctx.shadowColor = '#fff';
                ctx.shadowBlur = 7 * s.z;
                ctx.beginPath();
                ctx.arc(s.x, s.y, s.r * s.z, 0, Math.PI * 2);
                ctx.fillStyle = Math.random() > 0.82 ? '#e600e6' : '#b6eaff';
                ctx.fill();
                ctx.restore();
                s.x += s.vx * s.z;
                s.y += s.vy * s.z;
                if (s.x < 0) s.x = w;
                if (s.x > w) s.x = 0;
                if (s.y < 0) s.y = h;
                if (s.y > h) s.y = 0;
            }
            if (shootingStar.alpha > 0) {
                ctx.save();
                ctx.globalAlpha = shootingStar.alpha;
                ctx.strokeStyle = "#e600e6";
                ctx.shadowColor = "#2ec7ff";
                ctx.shadowBlur = 15;
                ctx.lineWidth = 2.2;
                ctx.beginPath();
                ctx.moveTo(shootingStar.x, shootingStar.y);
                ctx.lineTo(
                    shootingStar.x - shootingStar.len * Math.cos(shootingStar.angle),
                    shootingStar.y - shootingStar.len * Math.sin(shootingStar.angle)
                );
                ctx.stroke();
                ctx.restore();
                shootingStar.x += shootingStar.speed * Math.cos(shootingStar.angle);
                shootingStar.y += shootingStar.speed * Math.sin(shootingStar.angle);
                shootingStar.alpha -= 0.006;
            } else {
                if (Math.random() < 0.037) randomizeShootingStar();
            }
            requestAnimationFrame(draw);
        }
        window.addEventListener('resize', resize);
        resize();
        draw();

        // ----------- Chatbot JS -------------
        const chatMessages = document.getElementById('chatMessages');
        const messageInput = document.getElementById('messageInput');
        const sendBtn = document.getElementById('sendBtn');
     
   

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
                        message: text
                    })
                });

                const data = await response.json();

                // ลบข้อความ "กำลังคิด..."
                chatMessages.lastChild.remove();

                appendMessage(data.reply, 'bot');
            } catch (error) {
                chatMessages.lastChild.textContent = 'เกิดข้อผิดพลาดในการเชื่อมต่อ';
            }
        }

        sendBtn.addEventListener('click', sendMessage);
        messageInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        // ----------- Email JS -------------
        // เรียกดึงอีเมลลิสต์
async function fetchEmailList() {
    const res = await fetch('/emails');
    const emails = await res.json();
    const listDiv = document.getElementById('emailList');
    listDiv.innerHTML = '';
    emails.forEach(email => {
        const item = document.createElement('div');
        item.className = 'email-list-item';
        item.innerHTML = `<b>${email.subject || '(No subject)'}</b>
        <span style="color:#888;font-size:0.9em;">ส่งถึง: ${email.contacts.map(c=>c.email).join(', ')}</span>`;
        item.onclick = () => loadEmailDetail(email.id);
        listDiv.appendChild(item);
    });
}

// เรียกดึงรายละเอียดอีเมล
async function loadEmailDetail(emailId) {
    const res = await fetch(`/emails/${emailId}`);
    const email = await res.json();
    document.getElementById('emailTo').innerText = email.contacts.map(c => c.email).join(', ');
    document.getElementById('emailSubject').innerHTML = `<input type="text" id="subjectEdit" value="${email.subject}"/>`;
    document.getElementById('emailContent').value = email.body;
    // save event
    document.getElementById('subjectEdit').onchange = () => saveEmail(email.id);
    document.getElementById('emailContent').onchange = () => saveEmail(email.id);
    document.getElementById('confirmBtn').onclick = () => confirmEmail(email.id);
}

// อัพเดต subject/body
async function saveEmail(emailId) {
    const subject = document.getElementById('subjectEdit').value;
    const body = document.getElementById('emailContent').value;
    await fetch(`/emails/${emailId}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ subject, body })
    });
}

// ยืนยัน (ส่งอีเมล)
async function confirmEmail(emailId) {
    await fetch(`/emails/${emailId}/confirm`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken }
    });
    alert('ยืนยันส่งอีเมลแล้ว!');
    fetchEmailList();
}

    </script>
</body>

</html>
