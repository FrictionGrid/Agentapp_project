@extends('layout.layout')

@section('title', 'AI Chat Agent Dashboard')

@section('content')
    <!-- Main -->
    <div class="dashboard-main">
        <div class="dashboard-content">
            <!-- Email List -->
            <div class="email-list-panel">
                <div class="email-list-title">
                    Draft Emails <span id="emailCount" style="color:#8e97ac;font-size:1rem;font-weight:400;">(0)</span>
                </div>
                <div class="email-list-scroll" id="emailList"></div>
            </div>

            <!-- Email Detail -->
            <div class="email-detail-panel">
                <div class="email-detail-inner">
                    <div>
                        <div class="email-detail-label">To:</div>
                        <div id="emailTo" class="email-recipients"></div>

                        <div class="email-detail-label">Subject:</div>
                        <input type="text" class="email-detail-value subject" id="emailSubject">

                        <div class="email-detail-label">Content:</div>
                        <textarea class="email-detail-value content" id="emailContent" rows="8"></textarea>
                    </div>
                    <div class="email-actions">
                        <button class="update-btn" id="updateBtn" style="display: none;">Update</button>
                        <button class="confirm-btn" id="confirmBtn">Send Email</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", () => {
    let emails = @json($initialDrafts ?? []);
    let currentEmailId = null;
    
    const emailListDiv = document.getElementById('emailList');
    const emailCountSpan = document.getElementById('emailCount');

    // ฟังก์ชันสำหรับโหลดข้อมูล drafts จาก server
    async function loadDrafts() {
        try {
            const response = await fetch('/emails');
            if (response.ok) {
                const drafts = await response.json();
                emails = drafts;
                updateEmailCount();
                if (emails.length > 0) {
                    renderList(0);
                    renderDetail(0);
                } else {
                    renderEmptyState();
                }
            }
        } catch (error) {
            console.error('Error loading drafts:', error);
        }
    }

    function updateEmailCount() {
        if (emailCountSpan) {
            emailCountSpan.textContent = `(${emails.length})`;
        }
    }

    function renderList(selectedIdx = 0) {
        if (emails.length === 0) {
            renderEmptyState();
            return;
        }

        emailListDiv.innerHTML = "";
        emails.forEach((email, idx) => {
            const item = document.createElement('div');
            item.className = 'email-list-item' + (idx === selectedIdx ? ' selected' : '');
            item.tabIndex = 0;
            
            // Format recipients
            const recipients = email.contacts && email.contacts.length > 0 
                ? email.contacts.map(c => c.email).join(', ')
                : 'No recipients';
            
            // Format time
            const timeAgo = formatTimeAgo(email.created_at);
            
            item.innerHTML = `
                <div style="display:flex;flex-direction:column;">
                    <span class="email-to">${recipients}</span>
                    <span class="email-subject">${email.subject || 'No Subject'}</span>
                </div>
                <span class="email-time">${timeAgo}</span>
            `;
            
            item.onclick = () => { renderList(idx); renderDetail(idx); };
            item.onkeydown = (ev) => {
                if (ev.key === "Enter" || ev.key === " ") {
                    renderList(idx); renderDetail(idx);
                }
            };
            emailListDiv.appendChild(item);
        });
    }

    function renderDetail(idx = 0) {
        if (emails.length === 0) return;
        
        const email = emails[idx];
        currentEmailId = email.id;
        
        // Display recipients
        const emailToDiv = document.getElementById('emailTo');
        if (email.contacts && email.contacts.length > 0) {
            emailToDiv.innerHTML = email.contacts.map(contact => 
                `<span class="recipient-tag">${contact.email} (${contact.first_name} ${contact.last_name})</span>`
            ).join('');
        } else {
            emailToDiv.innerHTML = '<span class="no-recipients">No recipients</span>';
        }
        
        document.getElementById('emailSubject').value = email.subject || '';
        document.getElementById('emailContent').value = email.body || '';
        
        // Show update button if email is selected
        const updateBtn = document.getElementById('updateBtn');
        if (updateBtn) {
            updateBtn.style.display = 'inline-block';
        }
    }

    function renderEmptyState() {
        emailListDiv.innerHTML = '<div class="empty-state">ยังไม่มี draft email<br>ลองสั่ง agent สร้างอีเมลดู!</div>';
        document.getElementById('emailTo').innerHTML = '<span class="no-recipients">-</span>';
        document.getElementById('emailSubject').value = '';
        document.getElementById('emailContent').value = '';
        
        const updateBtn = document.getElementById('updateBtn');
        if (updateBtn) {
            updateBtn.style.display = 'none';
        }
    }

    function formatTimeAgo(dateString) {
        if (!dateString) return '';
        
        const date = new Date(dateString);
        const now = new Date();
        const diff = now - date;
        
        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);
        const days = Math.floor(diff / 86400000);
        
        if (minutes < 1) return 'เมื่อสักครู่';
        if (minutes < 60) return `${minutes}m ago`;
        if (hours < 24) return `${hours}h ago`;
        return `${days}d ago`;
    }

    // Update email function
    async function updateEmail() {
        if (!currentEmailId) return;
        
        const subject = document.getElementById('emailSubject').value;
        const body = document.getElementById('emailContent').value;
        
        try {
            const response = await fetch(`/emails/${currentEmailId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ subject, body })
            });
            
            if (response.ok) {
                const data = await response.json();
                // Reload drafts to get updated data
                await loadDrafts();
                alert('อัปเดตอีเมลเรียบร้อยแล้ว');
            } else {
                alert('เกิดข้อผิดพลาดในการอัปเดต');
            }
        } catch (error) {
            console.error('Error updating email:', error);
            alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
        }
    }

    // Expose function to global scope for chatbot integration
    window.updateEmailList = function(newDrafts) {
        emails = newDrafts;
        updateEmailCount();
        if (emails.length > 0) {
            renderList(0);
            renderDetail(0);
        } else {
            renderEmptyState();
        }
    };

    // Initial render
    updateEmailCount();
    if (emails.length > 0) {
        renderList(0);
        renderDetail(0);
    } else {
        renderEmptyState();
    }

    // Event listeners
    const updateBtn = document.getElementById('updateBtn');
    if (updateBtn) {
        updateBtn.onclick = updateEmail;
    }

    document.getElementById('confirmBtn').onclick = async function () {
        if (!currentEmailId) {
            alert('กรุณาเลือกอีเมลที่ต้องการส่ง');
            return;
        }

        const confirmBtn = document.getElementById('confirmBtn');
        const originalText = confirmBtn.textContent;
        
        if (confirm('ต้องการส่งอีเมลนี้หรือไม่?')) {
            try {
                confirmBtn.textContent = 'กำลังส่ง...';
                confirmBtn.disabled = true;

                const response = await fetch(`/emails/${currentEmailId}/send`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();
                
                if (data.success) {
                    alert(data.message);
                } else {
                    alert('เกิดข้อผิดพลาด: ' + data.message);
                }
            } catch (error) {
                console.error('Error sending email:', error);
                alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
            } finally {
                confirmBtn.textContent = originalText;
                confirmBtn.disabled = false;
            }
        }
    };
});
</script>
@endpush
