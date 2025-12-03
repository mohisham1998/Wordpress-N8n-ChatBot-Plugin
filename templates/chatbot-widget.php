<?php
/**
 * Chatbot Widget Template
 *
 * Frontend chatbot HTML markup.
 *
 * @package AutoMize_Chatbot
 * @since 7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<!-- Automize Chatbot Widget -->

<!-- Chat Widget Button (Floating) -->
<button id="chat-widget-button" aria-label="فتح الدردشة">
    <div class="chat-button-pulse"></div>
    <span class="button-icon">💬</span>
</button>

<!-- Chat Widget Container -->
<div id="chat-widget-container">
    
    <!-- Chat Header -->
    <div id="chat-widget-header">
        <div class="header-pattern"></div>
        
        <div class="header-left">
            <button class="chat-close-btn" aria-label="إغلاق الدردشة">
                ✖
            </button>
            
            <div class="header-avatar">
                <span class="avatar-icon">🤖</span>
            </div>
        </div>
        
        <div class="header-content">
            <h2 class="header-title">مساعد أوتومايز</h2>
            <div class="header-status">
                <span>متصل</span>
                <div class="status-indicator"></div>
            </div>
        </div>
    </div>

    <!-- Chat Body / Messages Area -->
    <div id="chat-widget-body">
        <!-- Floating particles for background animation -->
        <div class="chat-particle"></div>
        <div class="chat-particle"></div>
        <div class="chat-particle"></div>
        <div class="chat-particle"></div>
        <div class="chat-particle"></div>
        <div class="chat-particle"></div>
        <div class="chat-particle"></div>
        <div class="chat-particle"></div>
        
        <!-- Messages will be added dynamically here -->
        
        <!-- Quick Replies (shown initially) -->
        <div class="quick-replies" id="quick-replies">
            <button class="quick-reply-btn" data-text="ما هي خدماتكم؟">
                <span class="reply-icon">💼</span>
                <span>ما هي خدماتكم؟</span>
            </button>
            <button class="quick-reply-btn" data-text="كيف يمكنني المساعدة؟">
                <span class="reply-icon">❓</span>
                <span>كيف يمكنني المساعدة؟</span>
            </button>
            <button class="quick-reply-btn" data-text="أسعار الباقات">
                <span class="reply-icon">💰</span>
                <span>أسعار الباقات</span>
            </button>
            <button class="quick-reply-btn" data-text="تواصل معنا">
                <span class="reply-icon">📞</span>
                <span>تواصل معنا</span>
            </button>
        </div>
    </div>

    <!-- Chat Footer / Input Area -->
    <div id="chat-widget-footer">
        <div class="footer-gradient-line"></div>
        <div class="input-wrapper">
            <input 
                type="text" 
                id="chat-widget-input" 
                placeholder="اكتب رسالتك..."
                aria-label="رسالة الدردشة"
            />
            <button id="chat-widget-send" aria-label="إرسال" disabled>
                <span class="send-icon">➤</span>
            </button>
        </div>
    </div>
</div>
