# AutoMize Branded Chatbot - WordPress Plugin

<p align="center">
  <img src="https://img.shields.io/badge/version-7.0-blue.svg" alt="Version 7.0">
  <img src="https://img.shields.io/badge/WordPress-6.0%2B-green.svg" alt="WordPress 6.0+">
  <img src="https://img.shields.io/badge/PHP-7.4%2B-purple.svg" alt="PHP 7.4+">
  <img src="https://img.shields.io/badge/n8n-Compatible-orange.svg" alt="n8n Compatible">
  <img src="https://img.shields.io/badge/license-MIT-lightgrey.svg" alt="MIT License">
</p>

A modern, animated chatbot widget for WordPress with full n8n integration. Features a beautiful RTL-ready UI with gradient animations, real-time admin dashboard, and comprehensive chat management.

## ✨ Features

### 🤖 Frontend Chatbot Widget
- **Modern Animated UI** - Gradient backgrounds, floating particles, smooth transitions
- **RTL Support** - Full Arabic/RTL language support with Cairo font
- **Quick Replies** - Dynamic quick reply buttons from n8n workflow
- **Typing Indicators** - Animated typing dots when bot is responding
- **Session Per Tab** - Unique chat session for each browser tab
- **Geolocation** - GPS and IP-based location tracking
- **Mobile Responsive** - Fully responsive design for all devices

### 📊 Admin Dashboard
- **Real-time Updates** - Live AJAX polling for new messages
- **Chat Management** - View, filter, search, and delete conversations
- **Status Tracking** - Active, Completed, Lead, Abandoned statuses
- **Location Display** - Country flags and city/country information
- **Statistics Cards** - Total sessions, messages, leads, today's chats
- **Export to CSV** - Download chat data for analysis
- **Toast Notifications** - Real-time alerts for new messages

### 🔗 n8n Integration
- **Webhook Support** - Send/receive messages via n8n workflows
- **REST API** - Full REST API for external integrations
- **Quick Replies** - Dynamic buttons from chatbot flow
- **Lead Capture** - Automatic contact info extraction

## 📁 Project Structure

```
custom-chat-bot/
├── automize-chatbot.php          # Main plugin file
├── ChatBot.json                  # n8n workflow template
│
├── assets/
│   ├── admin/
│   │   ├── css/admin-chat.css    # Admin dashboard styles
│   │   └── js/admin-chat.js      # Admin JavaScript
│   └── frontend/
│       ├── css/chatbot.css       # Chatbot widget styles
│       └── js/chatbot.js         # Chatbot JavaScript
│
├── includes/
│   ├── core/                     # Core components
│   │   ├── class-autoloader.php         # PSR-4 style autoloader
│   │   ├── class-database.php           # Database schema & operations
│   │   ├── class-session-repository.php # Session CRUD operations
│   │   ├── class-message-repository.php # Message CRUD operations
│   │   ├── class-statistics.php         # Analytics & statistics
│   │   └── class-geolocation.php        # IP/GPS geolocation
│   │
│   ├── admin/                    # Admin components
│   │   ├── class-admin-controller.php   # Main admin controller
│   │   ├── class-admin-page-chats.php   # Chats page controller
│   │   └── class-admin-page-stats.php   # Stats page controller
│   │
│   ├── frontend/                 # Frontend components
│   │   └── class-frontend.php           # Chatbot widget controller
│   │
│   └── api/                      # API handlers
│       ├── class-rest-api.php           # REST API endpoints
│       └── class-ajax.php               # AJAX request handlers
│
└── templates/                    # View templates
    ├── admin-chats.php           # Admin chats page HTML
    ├── admin-stats.php           # Admin stats page HTML
    └── chatbot-widget.php        # Frontend chatbot widget HTML
```

## 🚀 Installation

### Method 1: Direct Upload
1. Download the latest release
2. Upload to `/wp-content/plugins/custom-chat-bot/`
3. Activate the plugin through WordPress admin
4. Configure your n8n webhook URL in `assets/frontend/js/chatbot.js`

### Method 2: Git Clone
```bash
cd /path/to/wordpress/wp-content/plugins/
git clone https://github.com/mohisham1998/Wordpress-N8n-ChatBot-Plugin.git custom-chat-bot
```

## ⚙️ Configuration

### Webhook Configuration
Edit `assets/frontend/js/chatbot.js` and update the webhook URL:

```javascript
window.ChatWidgetConfig = {
    webhook: {
        url: 'https://your-n8n-instance.com/webhook/your-webhook-id/chat',
        route: 'general',
        enabled: true
    },
    // ... other config
};
```

### n8n Workflow Setup
1. Import `ChatBot.json` into your n8n instance
2. Configure the webhook node with your desired endpoint
3. Customize the chatbot responses and flow

## 🔌 API Reference

### REST Endpoints

#### Save Message
```http
POST /wp-json/automize-chat/v1/message
Content-Type: application/json

{
    "session_id": "chat_abc123",
    "sender": "user|bot",
    "message": "Hello!",
    "quick_replies": [{"title": "Option 1", "payload": "OPT1"}]
}
```

#### Webhook (from n8n)
```http
POST /wp-json/automize-chat/v1/webhook
Content-Type: application/json

{
    "session_id": "chat_abc123",
    "response": {
        "text": "Bot response",
        "question": "Follow-up question?",
        "quick_replies": [...]
    }
}
```

### AJAX Actions
| Action | Description |
|--------|-------------|
| `automize_start_session` | Start a new chat session |
| `automize_save_message` | Save a message |
| `automize_update_location` | Update session location |
| `automize_update_session_status` | Update session status |
| `automize_get_chats_list` | Get paginated chat list (admin) |
| `automize_get_chat` | Get single chat with messages (admin) |
| `automize_delete_chats` | Delete selected chats (admin) |
| `automize_export_chats` | Export chats to CSV (admin) |

## 🗄️ Database Schema

### Sessions Table (`wp_automize_chat_sessions`)
| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT | Primary key |
| `session_id` | VARCHAR(100) | Unique session identifier |
| `visitor_name` | VARCHAR(255) | Visitor name (if captured) |
| `visitor_email` | VARCHAR(255) | Visitor email (if captured) |
| `visitor_phone` | VARCHAR(50) | Visitor phone (if captured) |
| `visitor_ip` | VARCHAR(45) | Visitor IP address |
| `visitor_country` | VARCHAR(100) | Country name |
| `visitor_country_code` | VARCHAR(5) | ISO country code |
| `visitor_city` | VARCHAR(100) | City name |
| `visitor_latitude` | DECIMAL(10,8) | GPS latitude |
| `visitor_longitude` | DECIMAL(11,8) | GPS longitude |
| `location_source` | ENUM | 'ip', 'gps', or 'manual' |
| `status` | ENUM | 'active', 'completed', 'lead', 'abandoned' |
| `messages_count` | INT | Total message count |
| `started_at` | DATETIME | Session start time |
| `last_message_at` | DATETIME | Last message time |

### Messages Table (`wp_automize_chat_messages`)
| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT | Primary key |
| `session_id` | VARCHAR(100) | Reference to session |
| `sender` | ENUM | 'user' or 'bot' |
| `message` | LONGTEXT | Message content |
| `quick_replies` | LONGTEXT | JSON quick replies |
| `payload` | VARCHAR(255) | Button payload |
| `created_at` | DATETIME | Message timestamp |

## 🎨 Customization

### Changing Colors
Edit CSS variables in `assets/frontend/css/chatbot.css`:

```css
#chat-widget-button,
#chat-widget-container {
    --primary-color: #4ECDC4;
    --secondary-color: #44A08D;
    --slate-900: #0f172a;
    /* ... */
}
```

### Changing Quick Replies
Edit default quick replies in `assets/frontend/js/chatbot.js`:

```javascript
quickReplies: [
    { icon: '💼', text: 'Your option 1' },
    { icon: '🧠', text: 'Your option 2' },
    // ...
]
```

## 📋 Requirements

- **WordPress:** 6.0 or higher
- **PHP:** 7.4 or higher
- **MySQL:** 5.7 or higher
- **n8n:** Any version with webhook support

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👨‍💻 Author

**Mohamed Hisham** - [AutoMize](https://automize.sa)

---

<p align="center">
  Made with ❤️ by <a href="https://automize.sa">AutoMize</a>
</p>
