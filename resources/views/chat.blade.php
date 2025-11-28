<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel WebSocket Chat</title>
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: Arial, sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .chat-container {
        width: 500px;
        height: 600px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        display: flex;
        flex-direction: column;
    }

    .chat-header {
        background: #667eea;
        color: white;
        padding: 20px;
        border-radius: 10px 10px 0 0;
        text-align: center;
        font-size: 20px;
        font-weight: bold;
    }

    .chat-messages {
        flex: 1;
        padding: 20px;
        overflow-y: auto;
        background: #f5f5f5;
    }

    .message {
        margin-bottom: 15px;
        padding: 10px 15px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .message-user {
        font-weight: bold;
        color: #667eea;
        margin-bottom: 5px;
    }

    .message-text {
        color: #333;
    }

    .message-time {
        font-size: 11px;
        color: #999;
        margin-top: 5px;
    }

    .chat-input {
        padding: 20px;
        background: white;
        border-radius: 0 0 10px 10px;
        border-top: 1px solid #ddd;
    }

    .input-group {
        display: flex;
        gap: 10px;
        margin-bottom: 10px;
    }

    input[type="text"] {
        flex: 1;
        padding: 12px;
        border: 2px solid #ddd;
        border-radius: 5px;
        font-size: 14px;
        outline: none;
    }

    input[type="text"]:focus {
        border-color: #667eea;
    }

    button {
        padding: 12px 25px;
        background: #667eea;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
        font-weight: bold;
        transition: background 0.3s;
    }

    button:hover {
        background: #5568d3;
    }

    button:active {
        transform: scale(0.98);
    }

    #userName {
        width: 150px;
    }

    .status {
        text-align: center;
        padding: 5px;
        font-size: 12px;
        color: #666;
    }

    .status.connected {
        color: #28a745;
    }

    .status.disconnected {
        color: #dc3545;
    }
    </style>
</head>

<body>
    <div class="chat-container">
        <div class="chat-header">
            💬 Real-time Chat
        </div>
        <div class="status" id="status">Menghubungkan...</div>
        <div class="chat-messages" id="messages"></div>
        <div class="chat-input">
            <div class="input-group">
                <input type="text" id="userName" placeholder="Loading..." value="">
            </div>
            <div class="input-group">
                <input type="text" id="messageInput" placeholder="Ketik pesan...">
                <button onclick="sendMessage()">Kirim</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/pusher-js@8.3.0/dist/web/pusher.min.js"></script>

    <script>
    // Get random user name from API
    fetch('https://randomuser.me/api/')
        .then(response => response.json())
        .then(data => {
            const user = data.results[0];
            const fullName = `${user.name.first} ${user.name.last}`;
            document.getElementById('userName').value = fullName;
            document.getElementById('userName').placeholder = 'Nama kamu...';
        })
        .catch(() => {
            document.getElementById('userName').value = 'User' + Math.floor(Math.random() * 1000);
            document.getElementById('userName').placeholder = 'Nama kamu...';
        });

    // Setup Laravel Echo
    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: 'local-key',
        cluster: 'mt1',
        wsHost: 'ws.clenicapp.com',
        wsPort: 6001,
        forceTLS: false,
        disableStats: true,
        enabledTransports: ['ws', 'wss']
    });

    // Load messages saat halaman dimuat
    loadMessages();

    // Update status saat koneksi berhasil
    Echo.connector.pusher.connection.bind('connected', () => {
        document.getElementById('status').textContent = 'Terhubung';
        document.getElementById('status').className = 'status connected';
    });

    // Update status saat koneksi terputus
    Echo.connector.pusher.connection.bind('disconnected', () => {
        document.getElementById('status').textContent = 'Terputus';
        document.getElementById('status').className = 'status disconnected';
    });

    // Update status saat error
    Echo.connector.pusher.connection.bind('error', () => {
        document.getElementById('status').textContent = 'Error koneksi';
        document.getElementById('status').className = 'status disconnected';
    });

    // Listen channel chat
    Echo.channel('chat')
        .listen('MessageSent', (e) => {
            appendMessage(e.message);
            scrollToBottom();
        });

    function loadMessages() {
        fetch('/api/chat/messages')
            .then(response => response.json())
            .then(messages => {
                messages.reverse().forEach(message => {
                    appendMessage(message);
                });
                scrollToBottom();
            });
    }

    function sendMessage() {
        const userName = document.getElementById('userName').value;
        const messageInput = document.getElementById('messageInput');
        const message = messageInput.value;

        if (!message.trim() || !userName.trim()) {
            alert('Nama dan pesan tidak boleh kosong!');
            return;
        }

        fetch('/api/chat/send', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    user: userName,
                    message: message
                })
            })
            .then(response => response.json())
            .then(data => {
                // Pesan akan muncul dari WebSocket broadcast, tidak perlu append di sini
                messageInput.value = '';
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Gagal mengirim pesan!');
            });
    }

    function appendMessage(message) {
        const messagesDiv = document.getElementById('messages');
        const messageElement = document.createElement('div');
        messageElement.className = 'message';

        const time = new Date(message.created_at).toLocaleTimeString('id-ID', {
            hour: '2-digit',
            minute: '2-digit'
        });

        messageElement.innerHTML = `
                <div class="message-user">${message.user}</div>
                <div class="message-text">${message.message}</div>
                <div class="message-time">${time}</div>
            `;

        messagesDiv.appendChild(messageElement);
    }

    function scrollToBottom() {
        const messagesDiv = document.getElementById('messages');
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
    }

    // Enter key untuk kirim pesan
    document.getElementById('messageInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });
    </script>
</body>

</html>