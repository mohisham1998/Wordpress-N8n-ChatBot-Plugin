/**
 * AutoMize Chat Admin JavaScript
 * تفاعلات واجهة إدارة المحادثات
 * 
 * @package AutoMize_Chatbot
 * @version 1.0
 */

(function($) {
    'use strict';

    // ==========================================
    // المتغيرات العامة
    // ==========================================
    const AutoMizeChatAdmin = {
        
        // عناصر DOM
        elements: {
            modal: null,
            modalOverlay: null,
            messagesContainer: null,
            infoBar: null,
            selectAll: null,
            checkboxes: null,
            deleteSelectedBtn: null,
            exportBtn: null,
            statusDropdownWrapper: null,
            statusDropdownTrigger: null,
            statusDropdownMenu: null,
            currentSessionId: null,
            chatsTableBody: null
        },
        
        // Real-time update settings
        polling: {
            interval: null,
            lastCheck: null,
            pollRate: 5000, // 5 seconds
            isActive: true
        },

        // ==========================================
        // التهيئة
        // ==========================================
        init: function() {
            this.cacheElements();
            this.bindEvents();
            this.startPolling();
        },

        // تخزين العناصر
        cacheElements: function() {
            this.elements.modal = $('#chat-modal');
            this.elements.modalOverlay = this.elements.modal.find('.modal-overlay');
            this.elements.messagesContainer = $('#chat-messages-container');
            this.elements.infoBar = $('#chat-info-bar');
            this.elements.selectAll = $('#select-all-chats');
            this.elements.checkboxes = $('.chat-checkbox');
            this.elements.deleteSelectedBtn = $('#delete-selected-btn');
            this.elements.exportBtn = $('#export-btn');
            this.elements.statusSelect = $('#change-status-select');
            this.elements.chatsTableBody = $('.automize-chats-table tbody');
        },

        // ربط الأحداث
        bindEvents: function() {
            const self = this;

            // عرض المحادثة
            $(document).on('click', '.view-chat-btn', function() {
                const sessionId = $(this).data('session-id');
                self.openChat(sessionId);
            });

            // إغلاق Modal
            $(document).on('click', '.modal-close, .modal-overlay, #close-modal-btn', function() {
                self.closeModal();
            });

            // إغلاق بـ Escape
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && self.elements.modal.is(':visible')) {
                    self.closeModal();
                }
            });

            // تحديد الكل
            this.elements.selectAll.on('change', function() {
                const isChecked = $(this).is(':checked');
                $('.chat-checkbox').prop('checked', isChecked);
            });

            // تحديث حالة "تحديد الكل"
            $(document).on('change', '.chat-checkbox', function() {
                const totalCheckboxes = $('.chat-checkbox').length;
                const checkedCheckboxes = $('.chat-checkbox:checked').length;
                self.elements.selectAll.prop('checked', totalCheckboxes === checkedCheckboxes);
            });

            // حذف محادثة واحدة
            $(document).on('click', '.delete-chat-btn', function() {
                const sessionId = $(this).data('session-id');
                if (confirm(automizeChat.strings.confirmDelete)) {
                    self.deleteChats([sessionId]);
                }
            });

            // حذف المحادثات المحددة
            this.elements.deleteSelectedBtn.on('click', function() {
                const selectedIds = self.getSelectedIds();
                if (selectedIds.length === 0) {
                    alert(automizeChat.strings.noSelection);
                    return;
                }
                if (confirm(automizeChat.strings.confirmDelete)) {
                    self.deleteChats(selectedIds);
                }
            });

            // تصدير CSV
            this.elements.exportBtn.on('click', function() {
                self.exportChats();
            });

            // تغيير حالة المحادثة
            this.elements.statusSelect.on('change', function() {
                const status = $(this).val();
                if (status && self.elements.currentSessionId) {
                    self.updateChatStatus(self.elements.currentSessionId, status);
                }
            });
        },

        // ==========================================
        // الوظائف الرئيسية
        // ==========================================

        // فتح المحادثة
        openChat: function(sessionId) {
            const self = this;
            
            this.elements.currentSessionId = sessionId;
            this.elements.messagesContainer.html('<div class="automize-loading">' + automizeChat.strings.loading + '</div>');
            this.elements.infoBar.html('');
            this.elements.modal.fadeIn(200);
            $('body').css('overflow', 'hidden');

            $.ajax({
                url: automizeChat.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'automize_get_chat',
                    nonce: automizeChat.nonce,
                    session_id: sessionId
                },
                success: function(response) {
                    if (response.success) {
                        self.renderChat(response.data);
                    } else {
                        self.elements.messagesContainer.html(
                            '<div class="empty-state"><p>' + response.data.message + '</p></div>'
                        );
                    }
                },
                error: function() {
                    self.elements.messagesContainer.html(
                        '<div class="empty-state"><p>' + automizeChat.strings.error + '</p></div>'
                    );
                }
            });
        },

        // عرض المحادثة
        renderChat: function(data) {
            const session = data.session;
            const messages = data.messages;
            const self = this;

            // Analyze conversation for summary and sentiment
            const analysis = this.analyzeConversation(messages);

            // Build enhanced info bar with two columns
            let infoHtml = '<div class="chat-info-grid">';
            
            // Right column - Session Info
            infoHtml += '<div class="chat-info-column info-details">';
            
            infoHtml += '<h4>معلومات الجلسة</h4>';
            
            // Date and Time - formatted nicely
            infoHtml += '<div class="info-row">';
            infoHtml += '<div class="info-block">';
            infoHtml += '<span class="info-icon">📅</span>';
            infoHtml += '<div class="info-content">';
            infoHtml += '<span class="info-label">التاريخ</span>';
            infoHtml += '<span class="info-value">' + this.formatDateOnly(session.started_at) + '</span>';
            infoHtml += '</div></div>';
            
            infoHtml += '<div class="info-block">';
            infoHtml += '<span class="info-icon">🕐</span>';
            infoHtml += '<div class="info-content">';
            infoHtml += '<span class="info-label">الوقت</span>';
            infoHtml += '<span class="info-value">' + this.formatTimeOnly(session.started_at) + '</span>';
            infoHtml += '</div></div>';
            
            infoHtml += '<div class="info-block">';
            infoHtml += '<span class="info-icon">💬</span>';
            infoHtml += '<div class="info-content">';
            infoHtml += '<span class="info-label">الرسائل</span>';
            infoHtml += '<span class="info-value">' + messages.length + '</span>';
            infoHtml += '</div></div>';
            infoHtml += '</div>'; // info-row
            
            infoHtml += '</div>'; // info-details
            
            // Left column - Summary & Sentiment
            infoHtml += '<div class="chat-info-column info-summary">';
            infoHtml += '<h4>ملخص المحادثة</h4>';
            
            // User interests/topics
            infoHtml += '<div class="summary-section">';
            infoHtml += '<div class="summary-label">اهتمامات الزائر</div>';
            infoHtml += '<div class="summary-tags">';
            if (analysis.interests.length > 0) {
                analysis.interests.forEach(function(interest) {
                    infoHtml += '<span class="interest-tag">' + interest + '</span>';
                });
            } else {
                infoHtml += '<span class="no-data">لم يتم تحديد اهتمامات</span>';
            }
            infoHtml += '</div></div>';
            
            // Sentiment Analysis
            infoHtml += '<div class="summary-section">';
            infoHtml += '<div class="summary-label">تحليل المشاعر</div>';
            infoHtml += '<div class="sentiment-display sentiment-' + analysis.sentiment.type + '">';
            infoHtml += '<span class="sentiment-icon">' + analysis.sentiment.icon + '</span>';
            infoHtml += '<span class="sentiment-text">' + analysis.sentiment.label + '</span>';
            infoHtml += '<div class="sentiment-bar"><div class="sentiment-fill" style="width: ' + analysis.sentiment.score + '%;"></div></div>';
            infoHtml += '</div></div>';
            
            // Engagement level
            infoHtml += '<div class="summary-section">';
            infoHtml += '<div class="summary-label">مستوى التفاعل</div>';
            infoHtml += '<div class="engagement-display">';
            infoHtml += '<div class="engagement-meter">';
            for (let i = 1; i <= 5; i++) {
                infoHtml += '<span class="engagement-dot ' + (i <= analysis.engagement ? 'active' : '') + '"></span>';
            }
            infoHtml += '</div>';
            infoHtml += '<span class="engagement-label">' + analysis.engagementLabel + '</span>';
            infoHtml += '</div></div>';
            
            infoHtml += '</div>'; // info-summary
            infoHtml += '</div>'; // chat-info-grid

            this.elements.infoBar.html(infoHtml);
            
            // Set status select to current session status
            this.elements.statusSelect.val(session.status);

            // عرض الرسائل
            let messagesHtml = '';

            if (messages.length === 0) {
                messagesHtml = '<div class="empty-state"><p>لا توجد رسائل</p></div>';
            } else {
                messages.forEach(function(msg) {
                    const isUser = msg.sender === 'user';
                    const senderLabel = isUser ? 'المستخدم' : 'البوت';
                    const senderIcon = isUser ? '👤' : '🤖';
                    
                    messagesHtml += '<div class="chat-message ' + (isUser ? 'user-message' : 'bot-message') + '">';
                    messagesHtml += '<div class="message-header">';
                    messagesHtml += '<span class="message-icon">' + senderIcon + '</span>';
                    messagesHtml += '<span class="message-sender">' + senderLabel + '</span>';
                    messagesHtml += '<span class="message-time">' + self.formatTimeOnly(msg.created_at) + '</span>';
                    messagesHtml += '</div>';
                    messagesHtml += '<div class="message-bubble">' + self.formatMessage(msg.message) + '</div>';
                    
                    // Quick Replies
                    if (msg.quick_replies && msg.quick_replies.length > 0) {
                        messagesHtml += '<div class="message-quick-replies">';
                        msg.quick_replies.forEach(function(reply) {
                            messagesHtml += '<span class="quick-reply-tag">' + self.escapeHtml(reply.title) + '</span>';
                        });
                        messagesHtml += '</div>';
                    }
                    
                    messagesHtml += '</div>';
                });
            }

            this.elements.messagesContainer.html(messagesHtml);
            
            // Scroll to bottom
            this.elements.messagesContainer.scrollTop(this.elements.messagesContainer[0].scrollHeight);
        },
        
        // Analyze conversation for summary and sentiment
        analyzeConversation: function(messages) {
            const userMessages = messages.filter(m => m.sender === 'user');
            const allUserText = userMessages.map(m => m.message).join(' ');
            
            // Extract interests based on keywords
            const interestKeywords = {
                'أتمتة': 'أتمتة العمليات',
                'Workflow': 'سير العمل',
                'RPA': 'الأتمتة الروبوتية',
                'حجز': 'حجز جلسة',
                'جلسة': 'استشارة',
                'تحليل': 'تحليل البيانات',
                'SAP': 'أنظمة SAP',
                'Odoo': 'نظام Odoo',
                'Zoho': 'منصة Zoho',
                'تكامل': 'تكامل الأنظمة',
                'فواتير': 'إدارة الفواتير',
                'مخزون': 'إدارة المخزون',
                'موظفين': 'إدارة الموظفين',
                'تقارير': 'التقارير',
                'ذكاء اصطناعي': 'الذكاء الاصطناعي',
                'AI': 'الذكاء الاصطناعي',
                'chatbot': 'الشات بوت',
                'بوت': 'الشات بوت'
            };
            
            const interests = [];
            for (const [keyword, label] of Object.entries(interestKeywords)) {
                if (allUserText.toLowerCase().includes(keyword.toLowerCase()) && !interests.includes(label)) {
                    interests.push(label);
                }
            }
            
            // Limit to top 4 interests
            const topInterests = interests.slice(0, 4);
            
            // Sentiment Analysis
            const positiveWords = ['نعم', 'أريد', 'ممتاز', 'رائع', 'شكرا', 'شكراً', 'جيد', 'موافق', 'احجز', 'اريد', 'أحتاج', 'مهتم'];
            const negativeWords = ['لا', 'لاحقا', 'لاحقاً', 'غير', 'سيء', 'مشكلة', 'صعب', 'لن'];
            
            let positiveCount = 0;
            let negativeCount = 0;
            
            positiveWords.forEach(word => {
                const regex = new RegExp(word, 'gi');
                const matches = allUserText.match(regex);
                if (matches) positiveCount += matches.length;
            });
            
            negativeWords.forEach(word => {
                const regex = new RegExp(word, 'gi');
                const matches = allUserText.match(regex);
                if (matches) negativeCount += matches.length;
            });
            
            let sentiment = { type: 'neutral', icon: '😐', label: 'محايد', score: 50 };
            
            if (positiveCount > negativeCount + 1) {
                const score = Math.min(50 + (positiveCount - negativeCount) * 10, 100);
                sentiment = { type: 'positive', icon: '😊', label: 'إيجابي', score: score };
            } else if (negativeCount > positiveCount + 1) {
                const score = Math.max(50 - (negativeCount - positiveCount) * 10, 10);
                sentiment = { type: 'negative', icon: '😔', label: 'سلبي', score: score };
            }
            
            // Engagement level (1-5)
            const messageCount = userMessages.length;
            let engagement = 1;
            let engagementLabel = 'منخفض';
            
            if (messageCount >= 8) {
                engagement = 5;
                engagementLabel = 'مرتفع جداً';
            } else if (messageCount >= 6) {
                engagement = 4;
                engagementLabel = 'مرتفع';
            } else if (messageCount >= 4) {
                engagement = 3;
                engagementLabel = 'متوسط';
            } else if (messageCount >= 2) {
                engagement = 2;
                engagementLabel = 'منخفض';
            }
            
            return {
                interests: topInterests,
                sentiment: sentiment,
                engagement: engagement,
                engagementLabel: engagementLabel
            };
        },
        
        // Extract contact information from messages content (email and phone only)
        // Name will be provided by n8n/AI Agent via webhook
        extractContactFromMessages: function(messages) {
            let email = null;
            let phone = null;
            
            // Regex patterns for contact info
            const emailRegex = /[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/g;
            const phoneRegex = /(?:\+?[0-9]{1,4}[\s-]?)?(?:\(?[0-9]{2,4}\)?[\s-]?)?[0-9]{6,10}/g;
            
            for (let i = 0; i < messages.length; i++) {
                const msg = messages[i];
                const text = msg.message || '';
                
                // Extract from user messages only
                if (msg.sender === 'user') {
                    // Extract email
                    if (!email) {
                        const emailMatch = text.match(emailRegex);
                        if (emailMatch) {
                            email = emailMatch[0];
                        }
                    }
                    
                    // Extract phone
                    if (!phone) {
                        const phoneMatch = text.match(phoneRegex);
                        if (phoneMatch) {
                            const validPhone = phoneMatch.find(p => {
                                const digits = p.replace(/\D/g, '');
                                return digits.length >= 8 && digits.length <= 15;
                            });
                            if (validPhone) {
                                phone = validPhone;
                            }
                        }
                    }
                }
            }
            
            return { name: null, email, phone };
        },
        
        // Format date only (DD/MM/YYYY)
        formatDateOnly: function(datetime) {
            if (!datetime) return '';
            const date = new Date(datetime);
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            return day + '/' + month + '/' + year;
        },
        
        // Format time only (h:mm AM/PM)
        formatTimeOnly: function(datetime) {
            if (!datetime) return '';
            const date = new Date(datetime);
            let hours = date.getHours();
            const minutes = String(date.getMinutes()).padStart(2, '0');
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12;
            return hours + ':' + minutes + ' ' + ampm;
        },

        // إغلاق Modal
        closeModal: function() {
            this.elements.modal.fadeOut(200);
            this.elements.currentSessionId = null;
            $('body').css('overflow', '');
        },

        // حذف محادثات
        deleteChats: function(sessionIds) {
            const self = this;

            $.ajax({
                url: automizeChat.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'automize_delete_chats',
                    nonce: automizeChat.nonce,
                    session_ids: sessionIds
                },
                success: function(response) {
                    if (response.success) {
                        // إزالة الصفوف من الجدول
                        sessionIds.forEach(function(id) {
                            $('tr[data-session-id="' + id + '"]').fadeOut(300, function() {
                                $(this).remove();
                            });
                        });
                        
                        // إغلاق Modal إذا مفتوح
                        if (self.elements.modal.is(':visible')) {
                            self.closeModal();
                        }
                    } else {
                        alert(response.data.message || automizeChat.strings.error);
                    }
                },
                error: function() {
                    alert(automizeChat.strings.error);
                }
            });
        },

        // تصدير CSV
        exportChats: function() {
            const self = this;

            $.ajax({
                url: automizeChat.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'automize_export_chats',
                    nonce: automizeChat.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.downloadCSV(response.data.data, 'automize-chats.csv');
                    } else {
                        alert(response.data.message || automizeChat.strings.error);
                    }
                },
                error: function() {
                    alert(automizeChat.strings.error);
                }
            });
        },

        // تحديث حالة المحادثة
        updateChatStatus: function(sessionId, status) {
            const self = this;
            
            // Get status labels for toast
            const statusLabels = {
                'active': 'نشط',
                'completed': 'مكتمل',
                'lead': 'عميل محتمل',
                'abandoned': 'متروك'
            };

            $.ajax({
                url: automizeChat.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'automize_update_chat_status',
                    nonce: automizeChat.nonce,
                    session_id: sessionId,
                    status: status
                },
                success: function(response) {
                    if (response.success) {
                        // تحديث Badge في الجدول
                        const row = $('tr[data-session-id="' + sessionId + '"]');
                        const badge = row.find('.status-badge');
                        badge.removeClass('status-active status-completed status-lead status-abandoned');
                        badge.addClass('status-' + status);
                        badge.text(response.data.label);
                        
                        // Show success toast
                        self.showToast('تم تغيير حالة المحادثة إلى: ' + statusLabels[status], status);
                    } else {
                        self.showToast('حدث خطأ أثناء تغيير الحالة', 'error');
                    }
                },
                error: function() {
                    self.showToast('حدث خطأ أثناء تغيير الحالة', 'error');
                }
            });
        },
        
        // Show toast notification
        showToast: function(message, type) {
            const container = $('#automize-toast-container');
            
            const icons = {
                'active': '🟢',
                'completed': '🔵',
                'lead': '🟡',
                'abandoned': '⚫',
                'error': '❌',
                'success': '✅'
            };
            
            const icon = icons[type] || '✅';
            
            const toast = $(`
                <div class="automize-toast toast-${type}">
                    <span class="toast-icon">${icon}</span>
                    <span class="toast-message">${message}</span>
                </div>
            `);
            
            container.append(toast);
            
            // Auto remove after 3 seconds
            setTimeout(function() {
                toast.addClass('toast-hiding');
                setTimeout(function() {
                    toast.remove();
                }, 300);
            }, 3000);
        },

        // ==========================================
        // الوظائف المساعدة
        // ==========================================

        // الحصول على IDs المحددة
        getSelectedIds: function() {
            const ids = [];
            $('.chat-checkbox:checked').each(function() {
                ids.push($(this).val());
            });
            return ids;
        },

        // تحويل HTML entities
        escapeHtml: function(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        // تنسيق الرسالة (تحويل \n إلى <br>)
        formatMessage: function(message) {
            if (!message) return '';
            return this.escapeHtml(message).replace(/\n/g, '<br>');
        },

        // تنسيق التاريخ - UK Format with 12-hour time (DD/MM/YYYY h:mm AM/PM)
        formatDate: function(datetime) {
            if (!datetime) return '';
            const date = new Date(datetime);
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            let hours = date.getHours();
            const minutes = String(date.getMinutes()).padStart(2, '0');
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12; // 0 should be 12
            return day + '/' + month + '/' + year + ' ' + hours + ':' + minutes + ' ' + ampm;
        },

        // تنسيق الوقت - 12 hour format with AM/PM
        formatTime: function(datetime) {
            if (!datetime) return '';
            const date = new Date(datetime);
            let hours = date.getHours();
            const minutes = String(date.getMinutes()).padStart(2, '0');
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12;
            return hours + ':' + minutes + ' ' + ampm;
        },

        // تحميل CSV
        downloadCSV: function(data, filename) {
            // تحويل البيانات إلى CSV
            let csv = '\uFEFF'; // BOM for UTF-8
            
            data.forEach(function(row) {
                const values = row.map(function(value) {
                    // Escape quotes and wrap in quotes
                    value = String(value).replace(/"/g, '""');
                    return '"' + value + '"';
                });
                csv += values.join(',') + '\n';
            });

            // إنشاء رابط التحميل
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            
            link.setAttribute('href', url);
            link.setAttribute('download', filename);
            link.style.visibility = 'hidden';
            
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        },
        
        // ==========================================
        // Real-time Updates (Polling)
        // ==========================================
        
        // Start polling for updates
        startPolling: function() {
            const self = this;
            
            // Get initial server time via first AJAX call
            this.polling.lastCheck = '';
            
            console.log('[AutoMize Chat] Starting real-time polling (every ' + this.polling.pollRate/1000 + 's)');
            
            // Do an immediate check
            this.checkForUpdates();
            
            // Start the polling interval
            this.polling.interval = setInterval(function() {
                if (self.polling.isActive) {
                    self.checkForUpdates();
                }
            }, this.polling.pollRate);
            
            // Stop polling when page is hidden
            document.addEventListener('visibilitychange', function() {
                self.polling.isActive = !document.hidden;
                if (!document.hidden) {
                    console.log('[AutoMize Chat] Page visible - resuming polling');
                    self.checkForUpdates(); // Immediate check when page becomes visible
                }
            });
        },
        
        // Check for updates
        checkForUpdates: function() {
            const self = this;
            
            $.ajax({
                url: automizeChat.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'automize_get_chat_updates',
                    nonce: automizeChat.nonce,
                    last_check: this.polling.lastCheck,
                    current_session_id: this.elements.currentSessionId || ''
                },
                success: function(response) {
                    if (response.success) {
                        // Update last check time from server
                        self.polling.lastCheck = response.data.server_time;
                        
                        // Update table rows if there are updates
                        if (response.data.updated_sessions && response.data.updated_sessions.length > 0) {
                            console.log('[AutoMize Chat] Found ' + response.data.updated_sessions.length + ' updated sessions');
                            self.updateTableRows(response.data.updated_sessions);
                        }
                        
                        // Update modal messages if open and there are new messages
                        if (response.data.new_messages && response.data.new_messages.length > 0 && self.elements.modal.is(':visible')) {
                            console.log('[AutoMize Chat] Found ' + response.data.new_messages.length + ' new messages');
                            self.appendNewMessages(response.data.new_messages);
                        }
                    } else {
                        console.warn('[AutoMize Chat] Polling error:', response.data);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('[AutoMize Chat] AJAX error:', status, error);
                }
            });
        },
        
        // Update existing table rows or add new ones
        updateTableRows: function(sessions) {
            const self = this;
            
            sessions.forEach(function(session) {
                const existingRow = $('tr[data-session-id="' + session.session_id + '"]');
                
                if (existingRow.length > 0) {
                    // Update existing row
                    self.updateRowData(existingRow, session);
                } else {
                    // Add new row at the top
                    self.addNewRow(session);
                }
            });
        },
        
        // Update data in existing row
        updateRowData: function(row, session) {
            // Update message count
            row.find('.message-count').text(session.messages_count);
            
            // Update last message
            const lastMsgEl = row.find('.last-message');
            if (lastMsgEl.length > 0) {
                lastMsgEl.attr('title', session.last_message_full).text(session.last_message);
            } else if (session.last_message) {
                row.find('.column-messages').append(
                    '<div class="last-message" title="' + this.escapeHtml(session.last_message_full) + '">' + 
                    this.escapeHtml(session.last_message) + '</div>'
                );
            }
            
            // Update status
            const statusBadge = row.find('.status-badge');
            statusBadge.removeClass('status-active status-completed status-lead status-abandoned')
                       .addClass('status-' + session.status)
                       .text(session.status_label);
            
            // Update location if changed
            const locationCell = row.find('.column-location');
            if (session.visitor_city || session.visitor_country_code) {
                locationCell.html(
                    '<div class="location-wrapper">' +
                        '<span class="location-flag" title="' + this.escapeHtml(session.visitor_country) + '">' +
                            session.country_flag +
                        '</span>' +
                        '<span class="location-city">' + this.escapeHtml(session.visitor_city || session.visitor_country) + '</span>' +
                    '</div>'
                );
            }
            
            // Highlight updated row briefly
            row.addClass('row-updated');
            setTimeout(function() {
                row.removeClass('row-updated');
            }, 2000);
        },
        
        // Add new row to table
        addNewRow: function(session) {
            const self = this;
            
            // Build location HTML
            let locationHtml = '<span class="location-unknown">غير معروف</span>';
            if (session.visitor_city || session.visitor_country_code) {
                locationHtml = '<div class="location-wrapper">' +
                    '<span class="location-flag" title="' + this.escapeHtml(session.visitor_country) + '">' +
                        session.country_flag +
                    '</span>' +
                    '<span class="location-city">' + this.escapeHtml(session.visitor_city || session.visitor_country) + '</span>' +
                '</div>';
            }
            
            const newRow = $(`
                <tr data-session-id="${session.session_id}" class="new-row">
                    <th class="check-column">
                        <input type="checkbox" class="chat-checkbox" value="${session.session_id}">
                    </th>
                    <td class="column-session">
                        <code class="session-id">${session.session_id_short}</code>
                    </td>
                    <td class="column-location">
                        ${locationHtml}
                    </td>
                    <td class="column-messages">
                        <span class="message-count">${session.messages_count}</span>
                        ${session.last_message ? '<div class="last-message" title="' + this.escapeHtml(session.last_message_full) + '">' + this.escapeHtml(session.last_message) + '</div>' : ''}
                    </td>
                    <td class="column-status">
                        <span class="status-badge status-${session.status}">
                            ${session.status_label}
                        </span>
                    </td>
                    <td class="column-date">
                        <div class="date-info">
                            <span class="date-started">${this.formatDate(session.started_at)}</span>
                        </div>
                    </td>
                    <td class="column-actions">
                        <button type="button" class="button view-chat-btn" data-session-id="${session.session_id}">
                            <span class="dashicons dashicons-visibility"></span>
                            عرض
                        </button>
                        <button type="button" class="button button-link-delete delete-chat-btn" data-session-id="${session.session_id}">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </td>
                </tr>
            `);
            
            // Remove "no items" row if exists
            this.elements.chatsTableBody.find('.no-items').closest('tr').remove();
            
            // Prepend to table
            this.elements.chatsTableBody.prepend(newRow);
            
            // Animate new row
            setTimeout(function() {
                newRow.removeClass('new-row');
            }, 2000);
        },
        
        // Append new messages to modal
        appendNewMessages: function(messages) {
            const self = this;
            const container = this.elements.messagesContainer;
            
            messages.forEach(function(msg) {
                const messageClass = msg.sender_type === 'user' ? 'message-user' : 'message-bot';
                const senderLabel = msg.sender_type === 'user' ? 'الزائر' : 'البوت';
                
                const messageHtml = `
                    <div class="chat-message ${messageClass} new-message">
                        <div class="message-header">
                            <span class="sender-type">${senderLabel}</span>
                            <span class="message-time">${self.formatTime(msg.created_at)}</span>
                        </div>
                        <div class="message-content">${self.escapeHtml(msg.message)}</div>
                    </div>
                `;
                
                container.append(messageHtml);
            });
            
            // Scroll to bottom
            container.scrollTop(container[0].scrollHeight);
            
            // Remove animation class after animation
            setTimeout(function() {
                container.find('.new-message').removeClass('new-message');
            }, 1000);
        },
        
        // Helper: Escape HTML
        escapeHtml: function(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    // ==========================================
    // التشغيل عند جهوزية DOM
    // ==========================================
    $(document).ready(function() {
        AutoMizeChatAdmin.init();
    });

})(jQuery);
