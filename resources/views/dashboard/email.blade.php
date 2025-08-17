@extends('layout.layout')
@section('title')
    Nexus Ai
@endsection
@section('topic')
    Email AI
@endsection
@section('content')
  <div class="box_content ">
    <div class="email-list-title">Emails ...</div>
    <div class="email-list-scroll" id="emailList"></div>
</div>
<div class="email-detail-panel">
    <div class="email-detail-inner">
        <div>
            <div class="email-detail-label">To:</div>
            <div class="email-detail-value" id="emailTo"></div>
            <div class="email-detail-label">Subject:</div>
            <div class="email-detail-value subject" id="emailSubject"></div>
            <div class="email-detail-label">Content:</div>
            <textarea class="email-detail-value content" id="emailContent"  rows="8"></textarea>
        </div>
        <button class="confirm-btn" id="confirmBtn">Confirm</button>
    </div>
</div>


@endsection
