@extends('Layout')

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
