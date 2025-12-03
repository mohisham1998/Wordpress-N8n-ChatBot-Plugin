// ==========================================
// CONFIGURATION - Version 5.4
// ==========================================
window.ChatWidgetConfig = {
    version: '5.4',
    webhook: {
        url: 'https://n8n.automize.sa/webhook/dce36cfa-fa89-4bad-894e-48b2344571e6/chat',
        route: 'general',
        enabled: true
    },
    style: {
        primaryColor: '#4ECDC4',
        secondaryColor: '#44A08D',
        position: 'right'
    },
    geolocation: {
        enabled: true,           // Enable location tracking
        askOnFirstMessage: true, // Ask for GPS only when user sends first message
        fallbackToIP: true       // Use IP geolocation as fallback
    },
    welcomeMessage: 'مرحبًا بك في أوتومايز، شريكك الذكي في الأتمتة والتحوّل الرقمي.<br>يسعدني مساعدتك اليوم 🤖<br>كيف يمكنني خدمتك؟',
    quickReplies: [
        { icon: '💼', text: 'أريد أتمتة سير العمل (Workflow & RPA)' },
        { icon: '🧠', text: 'أريد وكيل ذكاء اصطناعي (AI Agent)' },
        { icon: '⚙️', text: 'أبحث عن نظام إدارة (Odoo ERP)' },
        { icon: '🌐', text: 'أحتاج تطوير موقع إلكتروني' },
        { icon: '❓', text: 'أود معرفة المزيد عن الشركة' },
        { icon: '📞', text: 'تواصل مع فريق AutoMize' }
    ]
};

// Unique tab ID for this browser tab (persists across page refreshes within same tab)
window.tabSessionId = null;
window.locationCaptured = false;

// Log version on load
console.log('%c🤖 Automize Chatbot v5.4', 'color: #4ECDC4; font-size: 16px; font-weight: bold;');
console.log('%cWebhook: ' + (window.ChatWidgetConfig.webhook.enabled ? 'Enabled' : 'Disabled'), 'color: #44A08D; font-size: 12px;');
console.log('%cGeolocation: ' + (window.ChatWidgetConfig.geolocation.enabled ? 'Enabled' : 'Disabled'), 'color: #44A08D; font-size: 12px;');
console.log('%cTimestamp: ' + new Date().toLocaleString('en-GB'), 'color: #94a3b8; font-size: 12px;');

// Track if this is the first message in session
window.isFirstMessage = true;

// ==========================================
// HELPER FUNCTIONS
// ==========================================

// Get WordPress AJAX config
function getWPConfig() {
    // Check for WordPress localized script data
    if (typeof automizeChatConfig !== 'undefined') {
        return automizeChatConfig;
    }
    
    // Fallback: try to detect WordPress AJAX URL from page
    const wpAdminUrl = document.querySelector('link[href*="wp-admin"]');
    if (wpAdminUrl) {
        const baseUrl = wpAdminUrl.href.split('/wp-admin')[0];
        return {
            ajaxUrl: baseUrl + '/wp-admin/admin-ajax.php',
            nonce: '',
            sessionId: null
        };
    }
    
    // Last resort fallback
    return {
        ajaxUrl: '/wp-admin/admin-ajax.php',
        nonce: '',
        sessionId: null
    };
}

// Generate or retrieve unique chat ID - UNIQUE PER BROWSER TAB
function getChatId() {
    // Use window.name to persist session ID within the same tab across page navigations
    // window.name persists within a tab but is unique per tab/window
    
    // Check if we already have a tab session ID in memory
    if (window.tabSessionId) {
        return window.tabSessionId;
    }
    
    // Try to get from window.name (persists across page loads in same tab)
    let tabData = null;
    try {
        if (window.name && window.name.startsWith('{')) {
            tabData = JSON.parse(window.name);
        }
    } catch (e) {
        tabData = null;
    }
    
    if (tabData && tabData.automizeChatId && tabData.automizeChatId.startsWith('chat_')) {
        window.tabSessionId = tabData.automizeChatId;
        console.log('[v5.2] Restored Chat ID from tab:', window.tabSessionId);
        return window.tabSessionId;
    }
    
    // Generate a new unique session ID for this tab
    const newChatId = "chat_" + Math.random().toString(36).substr(2, 9) + "_" + Date.now();
    window.tabSessionId = newChatId;
    
    // Store in window.name for persistence within this tab
    const newTabData = tabData || {};
    newTabData.automizeChatId = newChatId;
    window.name = JSON.stringify(newTabData);
    
    console.log('[v5.1] Created new Chat ID for tab:', newChatId);
    
    // Start session in WordPress
    startSessionInWP(newChatId);
    
    return newChatId;
}

// Start session in WordPress
function startSessionInWP(sessionId) {
    const wpConfig = getWPConfig();
    
    const formData = new FormData();
    formData.append('action', 'automize_start_session');
    formData.append('nonce', wpConfig.nonce || '');
    formData.append('session_id', sessionId);
    
    fetch(wpConfig.ajaxUrl, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        console.log('[v5.2] Session started:', data);
        // Location will be captured on first message, not here
    })
    .catch(error => {
        console.error('[v5.2] Error starting session:', error);
    });
}

// ==========================================
// GEOLOCATION FUNCTIONS
// ==========================================

// Request location when chat widget opens
function requestLocationPermission() {
    if (window.locationCaptured) {
        console.log('[v5.3] Location already captured, skipping');
        return;
    }
    
    if (!window.ChatWidgetConfig.geolocation.enabled) {
        console.log('[v5.3] Geolocation disabled in config');
        return;
    }
    
    // Check if geolocation is available
    if (!('geolocation' in navigator)) {
        console.log('[v5.3] Geolocation not supported by browser');
        return;
    }
    
    console.log('[v5.3] 📍 Requesting GPS location permission...');
    
    // Get session ID first (ensure session exists)
    const sessionId = getChatId();
    console.log('[v5.3] Session ID:', sessionId);
    
    navigator.geolocation.getCurrentPosition(
        function(position) {
            const lat = position.coords.latitude;
            const lon = position.coords.longitude;
            
            console.log('[v5.3] ✅ GPS Location granted!');
            console.log('[v5.3] Coordinates:', lat, lon);
            
            // Send to WordPress for reverse geocoding
            updateLocationInWP(sessionId, lat, lon);
            window.locationCaptured = true;
        },
        function(error) {
            console.log('[v5.3] ❌ GPS denied or error:', error.code, error.message);
            // IP-based location is already captured on session start
            window.locationCaptured = true;
        },
        {
            enableHighAccuracy: false,
            timeout: 10000,
            maximumAge: 300000 // Cache for 5 minutes
        }
    );
}

// Update location in WordPress (server-side reverse geocoding)
function updateLocationInWP(sessionId, latitude, longitude) {
    const wpConfig = getWPConfig();
    
    console.log('[v5.3] 📤 Sending location to WordPress...');
    console.log('[v5.3] AJAX URL:', wpConfig.ajaxUrl);
    console.log('[v5.3] Session:', sessionId);
    console.log('[v5.3] Lat/Lon:', latitude, longitude);
    
    const formData = new FormData();
    formData.append('action', 'automize_update_location');
    formData.append('nonce', wpConfig.nonce || '');
    formData.append('session_id', sessionId);
    formData.append('latitude', latitude.toString());
    formData.append('longitude', longitude.toString());
    
    fetch(wpConfig.ajaxUrl, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(response => {
        console.log('[v5.3] Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('[v5.3] Response data:', data);
        if (data.success) {
            console.log('[v5.3] ✅ Location saved to database!');
            if (data.data && data.data.location) {
                console.log('[v5.3] 📍 Location:', data.data.location.city + ', ' + data.data.location.country);
            }
        } else {
            console.error('[v5.3] ❌ Failed to update location:', data.message || data);
        }
    })
    .catch(error => {
        console.error('[v5.4] ❌ Error sending location:', error);
    });
}

// Track if session has messages (only close sessions that had interaction)
window.sessionHasMessages = false;

// Update session status in WordPress
function updateSessionStatus(status) {
    // Only update if we have a session and it had messages
    if (!window.tabSessionId || !window.sessionHasMessages) {
        console.log('[v5.4] No session to update or no messages sent');
        return;
    }
    
    const wpConfig = getWPConfig();
    const sessionId = window.tabSessionId;
    
    console.log('[v5.4] 📤 Updating session status to:', status);
    
    // Use sendBeacon for reliability when page is closing
    const data = new FormData();
    data.append('action', 'automize_update_session_status');
    data.append('nonce', wpConfig.nonce || '');
    data.append('session_id', sessionId);
    data.append('status', status);
    
    // Try sendBeacon first (works even when page is closing)
    if (navigator.sendBeacon) {
        const blob = new Blob([new URLSearchParams({
            action: 'automize_update_session_status',
            nonce: wpConfig.nonce || '',
            session_id: sessionId,
            status: status
        }).toString()], { type: 'application/x-www-form-urlencoded' });
        
        const sent = navigator.sendBeacon(wpConfig.ajaxUrl, blob);
        console.log('[v5.4] sendBeacon result:', sent);
    } else {
        // Fallback to fetch
        fetch(wpConfig.ajaxUrl, {
            method: 'POST',
            body: data,
            credentials: 'same-origin',
            keepalive: true
        }).catch(err => console.log('[v5.4] Status update error:', err));
    }
}

// Save message to WordPress
function saveMessageToWP(message, sender, quickReplies = null, payload = null) {
    const wpConfig = getWPConfig();
    const sessionId = getChatId();
    
    // Mark that this session has had messages
    window.sessionHasMessages = true;
    
    console.log('[v5.4] Saving message to WordPress:', {
        ajaxUrl: wpConfig.ajaxUrl,
        sessionId: sessionId,
        sender: sender,
        message: message.substring(0, 50) + '...'
    });
    
    const formData = new FormData();
    formData.append('action', 'automize_save_message');
    formData.append('nonce', wpConfig.nonce || '');
    formData.append('session_id', sessionId);
    formData.append('sender', sender);
    formData.append('message', message);
    
    if (quickReplies) {
        formData.append('quick_replies', JSON.stringify(quickReplies));
    }
    
    if (payload) {
        formData.append('payload', payload);
    }
    
    fetch(wpConfig.ajaxUrl, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(response => {
        console.log('[v5.2] AJAX Response status:', response.status);
        return response.json();
    })
    .then(data => {
        if (data.success) {
            console.log('[v5.2] ✅ Message saved! ID:', data.data.message_id);
        } else {
            console.error('[v5.2] ❌ Failed to save message:', data);
        }
    })
    .catch(error => {
        console.error('[v5.2] ❌ Error saving message:', error);
    });
}

// Get current time formatted
function getCurrentTime() {
    const now = new Date();
    return now.toLocaleTimeString('ar-SA', { hour: '2-digit', minute: '2-digit' });
}

// Create message element
function createMessageElement(text, isBot = false) {
    const messageDiv = document.createElement('div');
    messageDiv.className = isBot ? 'chat-message bot-message' : 'chat-message user-message';
    
    const avatar = document.createElement('div');
    avatar.className = isBot ? 'message-avatar bot-avatar' : 'message-avatar user-avatar';
    avatar.textContent = isBot ? '🤖' : 'أنت';
    
    const contentWrapper = document.createElement('div');
    contentWrapper.className = 'message-content-wrapper';
    
    const bubble = document.createElement('div');
    bubble.className = 'message-bubble';
    
    // Use innerHTML for bot messages to render HTML, textContent for user messages
    if (isBot) {
        bubble.innerHTML = text;
    } else {
        bubble.textContent = text;
    }
    
    const time = document.createElement('div');
    time.className = 'message-timestamp';
    time.textContent = getCurrentTime();
    
    contentWrapper.appendChild(bubble);
    contentWrapper.appendChild(time);
    
    messageDiv.appendChild(avatar);
    messageDiv.appendChild(contentWrapper);
    
    return messageDiv;
}

// Add message to chat
function addMessage(text, isBot = false, saveToWP = true) {
    const chatBody = document.getElementById('chat-widget-body');
    const messageElement = createMessageElement(text, isBot);
    chatBody.appendChild(messageElement);
    chatBody.scrollTop = chatBody.scrollHeight;
    
    // Save to WordPress
    if (saveToWP) {
        saveMessageToWP(text, isBot ? 'bot' : 'user');
    }
}

// Show typing indicator
function showTyping() {
    const chatBody = document.getElementById('chat-widget-body');
    
    const typingDiv = document.createElement('div');
    typingDiv.className = 'typing-indicator';
    typingDiv.id = 'typing-indicator-message';
    
    const avatar = document.createElement('div');
    avatar.className = 'message-avatar bot-avatar';
    avatar.textContent = '🤖';
    
    const contentWrapper = document.createElement('div');
    contentWrapper.className = 'message-content-wrapper';
    
    const bubble = document.createElement('div');
    bubble.className = 'typing-bubble';
    
    const dotsContainer = document.createElement('div');
    dotsContainer.className = 'typing-dots';
    
    for (let i = 0; i < 3; i++) {
        const dot = document.createElement('div');
        dot.className = 'typing-dot';
        dotsContainer.appendChild(dot);
    }
    
    bubble.appendChild(dotsContainer);
    contentWrapper.appendChild(bubble);
    
    typingDiv.appendChild(avatar);
    typingDiv.appendChild(contentWrapper);
    
    chatBody.appendChild(typingDiv);
    chatBody.scrollTop = chatBody.scrollHeight;
}

// Hide typing indicator
function hideTyping() {
    const typingIndicator = document.getElementById('typing-indicator-message');
    if (typingIndicator) {
        typingIndicator.remove();
    }
}

// Send message to webhook
function sendMessage(message, payload = null) {
    if (!window.ChatWidgetConfig.webhook.enabled) {
        console.warn('Webhook is disabled');
        addMessage('الرد التلقائي: تم استلام رسالتك بنجاح!', true);
        return;
    }
    
    showTyping();
    
    const chatId = getChatId();
    
    // Prepare request body
    const requestBody = {
        chatId: chatId,
        message: message,
        route: window.ChatWidgetConfig.webhook.route
    };
    
    // Add payload if provided (for quick reply buttons)
    if (payload) {
        requestBody.payload = payload;
    }
    
    fetch(window.ChatWidgetConfig.webhook.url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(requestBody)
    })
    .then(response => response.json())
    .then(data => {
        hideTyping();
        
        // Handle new JSON response format
        console.log('Webhook response:', data);
        console.log('Response type:', typeof data);
        console.log('Is array:', Array.isArray(data));
        
        let responseData = null;
        
        // Handle both array and object responses
        if (Array.isArray(data) && data.length > 0) {
            responseData = data[0];
        } else if (typeof data === 'object' && data !== null) {
            // Response is a direct object
            responseData = data;
        }
        
        if (responseData) {
            console.log('Response data:', responseData);
            
            // Extract message, question, and quick_replies directly from response
            let messageText = '';
            let questionText = '';
            let quickReplies = [];
            
            // Check for direct properties first (your current format)
            if (responseData.message) {
                // If message is a string (could be plain text or JSON)
                if (typeof responseData.message === 'string') {
                    // Try to parse as JSON first
                    try {
                        const parsedMessage = JSON.parse(responseData.message);
                        messageText = parsedMessage.text || parsedMessage.message || '';
                        questionText = parsedMessage.question || '';
                        quickReplies = parsedMessage.quick_replies || [];
                    } catch (e) {
                        // Not JSON, use as plain text
                        messageText = responseData.message;
                    }
                } else if (typeof responseData.message === 'object') {
                    // Message is already an object
                    messageText = responseData.message.text || responseData.message;
                }
            }
            
            // Get question from top level (your current format)
            if (!questionText && responseData.question) {
                questionText = responseData.question;
            }
            
            // Get quick_replies from top level (your current format)
            if (quickReplies.length === 0 && Array.isArray(responseData.quick_replies)) {
                quickReplies = responseData.quick_replies;
            }
            
            console.log('Final messageText:', messageText);
            console.log('Final questionText:', questionText);
            console.log('Final quickReplies:', quickReplies);
            
            // Display message
            if (messageText) {
                addMessage(messageText, true);
            }
            
            // Display question if exists (with delay)
            if (questionText) {
                setTimeout(() => {
                    addMessage(questionText, true);
                }, 800);
            }
            
            // Display dynamic quick replies if exist (with delay and animation)
            if (quickReplies && quickReplies.length > 0) {
                setTimeout(() => {
                    showDynamicQuickReplies(quickReplies);
                }, questionText ? 1600 : 800);
            }
            
            // If nothing was displayed, show error
            if (!messageText && !questionText) {
                addMessage('عذراً، لم أتمكن من الفهم. حاول مرة أخرى.', true);
            }
        } else {
            // Fallback for old response format or unexpected format
            console.log('Could not extract response data');
            const botResponse = data.output || data.message || 'عذراً، لم أتمكن من الفهم. حاول مرة أخرى.';
            addMessage(botResponse, true);
        }
    })
    .catch(error => {
        hideTyping();
        console.error('Webhook error:', error);
        addMessage('عذراً، حدث خطأ في الاتصال. يرجى المحاولة مرة أخرى.', true);
    });
}

// Open chat widget
function openChat() {
    const chatContainer = document.getElementById('chat-widget-container');
    const chatButton = document.getElementById('chat-widget-button');
    
    console.log('[v5.3] Opening chat widget');
    
    if (chatContainer) {
        chatContainer.style.display = 'flex';
    }
    if (chatButton) {
        chatButton.style.display = 'none';
        chatButton.setAttribute('aria-hidden', 'true');
    }
    
    // Request location permission when chat opens
    requestLocationPermission();
}

// Close chat widget
function closeChat() {
    const chatContainer = document.getElementById('chat-widget-container');
    const chatButton = document.getElementById('chat-widget-button');
    
    console.log('[v5.4] Closing chat widget');
    
    // Update session status to completed when user closes the chat
    updateSessionStatus('completed');
    
    if (chatContainer) {
        chatContainer.style.display = 'none';
    }
    if (chatButton) {
        // Ensure button content exists (fix for mobile)
        if (!chatButton.querySelector('.chat-button-pulse')) {
            const pulse = document.createElement('div');
            pulse.className = 'chat-button-pulse';
            chatButton.appendChild(pulse);
            console.log('Recreated pulse element');
        }
        if (!chatButton.querySelector('.button-icon')) {
            const icon = document.createElement('span');
            icon.className = 'button-icon';
            // Use WordPress emoji SVG image for consistency across all devices
            icon.innerHTML = '<img draggable="false" role="img" class="emoji" alt="💬" src="https://s.w.org/images/core/emoji/16.0.1/svg/1f4ac.svg">';
            chatButton.appendChild(icon);
            console.log('Recreated icon element with WordPress emoji');
        }
        
        // Show button with important flags
        chatButton.style.display = 'flex';
        chatButton.style.setProperty('display', 'flex', 'important');
        chatButton.style.visibility = 'visible';
        chatButton.style.opacity = '1';
        chatButton.setAttribute('aria-hidden', 'false');
        
        console.log('Button display after closing:', chatButton.style.display);
    }
}

// Handle quick reply click
function handleQuickReply(text, payload = null) {
    // Location is now requested when chat opens
    
    // Remove all dynamic quick replies from chat
    const dynamicReplies = document.querySelectorAll('.dynamic-quick-replies');
    dynamicReplies.forEach(container => {
        container.remove();
    });
    
    // Also hide static quick replies if visible
    const quickRepliesContainer = document.getElementById('quick-replies');
    if (quickRepliesContainer) {
        quickRepliesContainer.style.display = 'none';
    }
    
    // Add message and save with payload
    const chatBody = document.getElementById('chat-widget-body');
    const messageElement = createMessageElement(text, false);
    chatBody.appendChild(messageElement);
    chatBody.scrollTop = chatBody.scrollHeight;
    
    // Save to WordPress with payload
    saveMessageToWP(text, 'user', null, payload);
    
    sendMessage(text, payload);
}

// Show dynamic quick replies from webhook response
function showDynamicQuickReplies(replies) {
    const chatBody = document.getElementById('chat-widget-body');
    
    // Remove any existing dynamic quick replies
    const existingReplies = document.querySelectorAll('.dynamic-quick-replies');
    existingReplies.forEach(container => container.remove());
    
    // Create container for dynamic quick replies
    const repliesContainer = document.createElement('div');
    repliesContainer.className = 'quick-replies dynamic-quick-replies';
    repliesContainer.style.animation = 'fade-in-up 0.6s ease';
    
    // Create buttons
    replies.forEach((reply, index) => {
        const button = document.createElement('button');
        button.className = 'quick-reply-btn';
        button.setAttribute('data-text', reply.title);
        button.setAttribute('data-payload', reply.payload || '');
        
        // Add icon (you can customize based on payload or use a default)
        const icon = document.createElement('span');
        icon.className = 'reply-icon';
        icon.textContent = getIconForPayload(reply.payload);
        
        // Add text
        const text = document.createElement('span');
        text.textContent = reply.title;
        
        button.appendChild(icon);
        button.appendChild(text);
        
        // Animation delay - smoother and slower
        button.style.animationDelay = `${0.4 + (index * 0.2)}s`;
        button.style.animationFillMode = 'both';
        
        // Click handler
        button.addEventListener('click', function() {
            const btnText = this.getAttribute('data-text');
            const btnPayload = this.getAttribute('data-payload');
            handleQuickReply(btnText, btnPayload);
        });
        
        repliesContainer.appendChild(button);
    });
    
    // Add to chat body
    chatBody.appendChild(repliesContainer);
    chatBody.scrollTop = chatBody.scrollHeight;
}

// Get icon based on payload (you can customize this)
function getIconForPayload(payload) {
    const iconMap = {
        // Business types
        'BUSINESS': '🏢',
        'NON_PROFIT': '🤝',
        'EDUCATIONAL': '🎓',
        'OTHER': '💼',
        
        // Yes/No responses
        'YES': '✅',
        'NO': '❌',
        'SHOW_EXAMPLE': '🎯',
        'DEMO': '🎯',
        
        // Contact & Communication
        'CONTACT': '📞',
        'SPEAK_WITH_EXPERT': '👨‍💼',
        'TALK_TO_EXPERT': '👨‍💼',
        'CALL': '📞',
        'EMAIL': '📧',
        
        // Information
        'INFO': 'ℹ️',
        'MORE_INFO': 'ℹ️',
        'LEARN_MORE': '📚',
        'DETAILS': '📋',
        
        // Services
        'WORKFLOW': '⚙️',
        'AI_AGENT': '🤖',
        'ERP': '💻',
        'WEBSITE': '🌐',
        'AUTOMATION': '🔄',
        
        // Actions
        'BOOK': '📅',
        'SCHEDULE': '🗓️',
        'START': '🚀',
        'NEXT': '➡️',
        'BACK': '⬅️',
        'SKIP': '⏭️'
    };
    
    return iconMap[payload] || '�';  // Default to lightbulb emoji
}

// ==========================================
// INITIALIZE ON DOM READY
// ==========================================
document.addEventListener('DOMContentLoaded', function() {
    
    // ==========================================
    // CHAT BUTTON CLICK HANDLER
    // ==========================================
    const chatButton = document.getElementById('chat-widget-button');
    if (chatButton) {
        chatButton.addEventListener('click', openChat);
    }
    
    // ==========================================
    // CLOSE BUTTON HANDLER
    // ==========================================
    const closeButton = document.querySelector('.chat-close-btn');
    if (closeButton) {
        closeButton.addEventListener('click', closeChat);
        // Add touch support for mobile
        closeButton.addEventListener('touchend', function(e) {
            e.preventDefault();
            closeChat();
        });
    }
    
    // ==========================================
    // SEND MESSAGE HANDLER
    // ==========================================
    const sendButton = document.getElementById('chat-widget-send');
    const inputField = document.getElementById('chat-widget-input');
    
    if (sendButton && inputField) {
        sendButton.addEventListener('click', function() {
            const message = inputField.value.trim();
            
            if (message === '') return;
            
            // Location is requested when chat opens, not on message
            
            // Hide quick replies after first message
            const quickRepliesContainer = document.getElementById('quick-replies');
            if (quickRepliesContainer) {
                quickRepliesContainer.style.display = 'none';
            }
            
            // Add user message
            addMessage(message, false);
            
            // Clear input
            inputField.value = '';
            
            // Send to webhook
            sendMessage(message);
        });
        
        // ==========================================
        // ENTER KEY SUPPORT
        // ==========================================
        inputField.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                sendButton.click();
            }
        });
        
        // ==========================================
        // INPUT VALIDATION
        // ==========================================
        inputField.addEventListener('input', function() {
            if (inputField.value.trim() === '') {
                sendButton.disabled = true;
            } else {
                sendButton.disabled = false;
            }
        });
    }
    
    // ==========================================
    // QUICK REPLY BUTTONS
    // ==========================================
    const quickReplyButtons = document.querySelectorAll('.quick-reply-btn');
    quickReplyButtons.forEach(button => {
        button.addEventListener('click', function() {
            const text = button.getAttribute('data-text');
            if (text) {
                handleQuickReply(text);
            }
        });
    });
    
    // ==========================================
    // ADD WELCOME MESSAGE
    // ==========================================
    setTimeout(() => {
        const chatBody = document.getElementById('chat-widget-body');
        const welcomeMessage = createMessageElement(window.ChatWidgetConfig.welcomeMessage, true);
        
        // Insert welcome message before quick replies
        const quickReplies = document.getElementById('quick-replies');
        if (quickReplies) {
            chatBody.insertBefore(welcomeMessage, quickReplies);
        } else {
            chatBody.appendChild(welcomeMessage);
        }
        
        chatBody.scrollTop = chatBody.scrollHeight;
    }, 600);
    
    // ==========================================
    // HANDLE PAGE UNLOAD (Tab Close / Navigate Away)
    // ==========================================
    window.addEventListener('beforeunload', function(e) {
        // Update session status when user closes tab or navigates away
        updateSessionStatus('completed');
    });
    
    // Also handle visibility change (when tab becomes hidden)
    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'hidden') {
            // User switched tabs or minimized - could mark as inactive
            // But we'll only mark completed on actual close via beforeunload
            console.log('[v5.4] Tab hidden - session still active');
        }
    });
});
