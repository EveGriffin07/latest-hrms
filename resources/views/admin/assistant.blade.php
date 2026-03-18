<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HRMS Assistant - HRMS</title>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
    
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    
    <style>
        /* Scoped Modern Assistant Redesign CSS */
        .assistant-page {
            --primary-blue: #2563eb;
            --primary-hover: #1d4ed8;
            --bg-gray: #f9fafb;
            --border-color: #e5e7eb;
            --text-dark: #1f2937;
            --text-muted: #6b7280;
            
            font-family: 'Poppins', sans-serif;
            margin-top: 20px;
        }

        .assistant-container {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 24px;
            align-items: start;
        }

        /* Chat Section */
        .chat-section {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            display: flex;
            flex-direction: column;
            height: 75vh;
            overflow: hidden;
            border: 1px solid var(--border-color);
        }

        .chat-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
        }

        .chat-title h2 { margin: 0; font-size: 1.25rem; color: var(--text-dark); font-weight: 600; }
        .chat-title p { margin: 4px 0 0 0; font-size: 0.875rem; color: var(--text-muted); }

        .chat-meta { display: flex; gap: 10px; }
        .assistant-badge {
            font-size: 0.75rem;
            padding: 4px 10px;
            border-radius: 9999px;
            background: #f3f4f6;
            color: var(--text-muted);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-weight: 500;
        }
        .assistant-badge.online i { color: #10b981; font-size: 0.5rem; }

        .chat-window {
            flex-grow: 1;
            overflow-y: auto;
            padding: 24px;
            background: #fcfcfc;
        }

        .chat-messages {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        /* Message Bubbles */
        .msg {
            display: flex;
            gap: 12px;
            max-width: 85%;
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .msg.user-msg { align-self: flex-end; flex-direction: row-reverse; }
        .msg.ai-msg { align-self: flex-start; }

        .avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: white;
            font-size: 1rem;
        }
        .user-msg .avatar { background: #6b7280; }
        .ai-msg .avatar { background: var(--primary-blue); }

        .msg-bubble {
            padding: 12px 16px;
            border-radius: 16px;
            font-size: 0.95rem;
            line-height: 1.5;
            word-wrap: break-word;
        }

        /* Markdown Spacing Fixes */
        .msg-bubble p:first-child { margin-top: 0; }
        .msg-bubble p:last-child { margin-bottom: 0; }
        .msg-bubble ul, .msg-bubble ol { margin: 8px 0; padding-left: 20px; }
        .msg-bubble pre { background: #f3f4f6; padding: 10px; border-radius: 8px; overflow-x: auto; }
        .msg-bubble code { font-family: monospace; background: #f3f4f6; padding: 2px 4px; border-radius: 4px; font-size: 0.9em; }

        .user-msg .msg-bubble {
            background: var(--primary-blue);
            color: white;
            border-bottom-right-radius: 4px;
            box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);
        }

        .ai-msg .msg-bubble {
            background: #ffffff;
            color: var(--text-dark);
            border: 1px solid var(--border-color);
            border-bottom-left-radius: 4px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }

        /* Input Area */
        .chat-input-row {
            padding: 20px;
            background: #ffffff;
            border-top: 1px solid var(--border-color);
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .chat-input-row input {
            flex-grow: 1;
            padding: 14px 24px;
            border: 1px solid var(--border-color);
            border-radius: 9999px;
            font-size: 0.95rem;
            outline: none;
            transition: all 0.2s ease;
            background: var(--bg-gray);
            font-family: 'Poppins', sans-serif;
        }

        .chat-input-row input:focus {
            border-color: var(--primary-blue);
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .chat-input-row button {
            background: var(--primary-blue);
            color: white;
            border: none;
            border-radius: 50%;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.2s ease;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .chat-input-row button:hover { background: var(--primary-hover); transform: scale(1.05); }
        .chat-input-row button:disabled { background: #9ca3af; cursor: not-allowed; transform: none; }

        /* Right Sidebar */
        .right-sidebar { display: flex; flex-direction: column; gap: 20px; }
        .sidebar-card {
            background: #ffffff;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .sidebar-card h4 { margin-top: 0; margin-bottom: 16px; font-size: 1.05rem; color: var(--text-dark); }
        .sidebar-card button {
            display: block; width: 100%; text-align: left; padding: 10px 12px;
            margin-bottom: 8px; border: none; background: var(--bg-gray);
            border-radius: 8px; cursor: pointer; color: var(--text-dark);
            transition: background 0.2s; font-size: 0.9rem; font-family: 'Poppins', sans-serif;
        }
        .sidebar-card button i { margin-right: 8px; color: var(--primary-blue); width: 16px; text-align: center; }
        .sidebar-card button:hover { background: #e5e7eb; }
        
        .sidebar-card ul { padding-left: 20px; margin: 0; color: var(--text-muted); font-size: 0.9rem; line-height: 1.6; }
        .sidebar-card p { margin: 0; color: var(--text-muted); font-size: 0.9rem; line-height: 1.5; }

        /* Quick actions under header */
        .assistant-welcome { padding: 20px 24px 0 24px; background: #fcfcfc; }
        .quick-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px; }
        .quick-actions button {
            background: #ffffff; border: 1px solid var(--border-color); padding: 8px 16px;
            border-radius: 999px; cursor: pointer; font-size: 0.85rem; color: var(--text-dark);
            transition: all 0.2s; box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            font-family: 'Poppins', sans-serif;
        }
        .quick-actions button i { color: var(--primary-blue); margin-right: 6px; }
        .quick-actions button:hover { border-color: var(--primary-blue); color: var(--primary-blue); transform: translateY(-1px); }
    </style>
</head>

<body>
<header>
    <div class="title">Web-Based HRMS</div>
    <div class="user-info">
        <a href="{{ route('admin.profile') }}" style="text-decoration: none; color: inherit;">
            <i class="fa-regular fa-bell"></i> &nbsp; HR Admin
        </a>
    </div>
</header>

<div class="container">
    @include('admin.layout.sidebar')

    <main>
        <div class="breadcrumb">Home > AI Assistant</div>
        
        <div class="assistant-page">
            <div class="assistant-container">

                {{-- LEFT: Chat Area --}}
                <section class="chat-section">
                    <header class="chat-header">
                        <div class="chat-title">
                            <h2>HR Assistant</h2>
                            <p>Your virtual helper for daily HR tasks and queries</p>
                        </div>
                        <div class="chat-meta">
                            <span class="assistant-badge online">
                                <i class="fa-solid fa-circle"></i> Online
                            </span>
                            <span class="assistant-badge">
                                <i class="fa-solid fa-shield-halved"></i> Internal Data
                            </span>
                        </div>
                    </header>

                    {{-- Chat window --}}
                    <div class="chat-window" id="chatWindow">
                        
                        <div class="assistant-welcome">
                            <span style="font-size: 0.85rem; color: #6b7280; font-weight: 500;">SUGGESTED ACTIONS</span>
                            <div class="quick-actions">
                                <button type="button" onclick="document.getElementById('chatInput').value='What is the company policy on annual leave?';"><i class="fa-solid fa-book"></i> Check Leave Policy</button>
                                <button type="button" onclick="document.getElementById('chatInput').value='Give me the attendance summary from 2026-02-01 to 2026-02-28';"><i class="fa-solid fa-calendar-check"></i> Monthly Attendance</button>
                            </div>
                        </div>

                        <div class="chat-messages" id="chatMessages" style="margin-top: 24px;">
                            {{-- Initial AI Message --}}
                            <div class="msg ai-msg">
                                <div class="avatar"><i class="fa-solid fa-robot"></i></div>
                                <div class="msg-bubble">
                                    Hello! I am your HRMS Assistant. How can I help you today? You can ask me about employee attendance, job requirements, or company policies.
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Input row --}}
                    <form class="chat-input-row" id="chatForm">
                        @csrf
                        <input
                            type="text"
                            id="chatInput"
                            name="message"
                            placeholder="Message HR Assistant..."
                            autocomplete="off"
                            required
                        >
                        <button type="submit" id="sendBtn">
                            <i class="fa-solid fa-paper-plane"></i>
                        </button>
                    </form>
                </section>

                {{-- RIGHT: Sidebar --}}
                <aside class="right-sidebar">
                    <div class="sidebar-card">
                        <h4>Quick Navigation</h4>
                        <button type="button" onclick="window.location='{{ route('admin.dashboard') }}'">
                            <i class="fa-solid fa-gauge-high"></i> Dashboard
                        </button>
                        <button type="button" onclick="window.location='{{ route('admin.recruitment.index') }}'">
                            <i class="fa-solid fa-briefcase"></i> Recruitment
                        </button>
                        <button type="button" onclick="window.location='{{ route('admin.training') }}'">
                            <i class="fa-solid fa-chalkboard-user"></i> Training
                        </button>
                        <button type="button" onclick="window.location='{{ route('admin.appraisal') }}'">
                            <i class="fa-solid fa-chart-line"></i> Appraisal
                        </button>
                    </div>

                    <div class="sidebar-card">
                        <h4>Capabilities</h4>
                        <ul>
                            <li>Search Knowledge Base FAQs</li>
                            <li>Summarize Attendance Data</li>
                            <li>Retrieve Job Post Requirements</li>
                            <li>Check Employee Leave Balances</li>
                        </ul>
                    </div>
                </aside>

            </div>
        </div>
    </main>
</div>

<script>
(function () {
    const chatForm = document.getElementById('chatForm');
    const chatInput = document.getElementById('chatInput');
    const chatMessages = document.getElementById('chatMessages');
    const chatWindow = document.getElementById('chatWindow');
    const sendBtn = document.getElementById('sendBtn');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // THIS IS THE NEW MEMORY ARRAY
    let chatHistory = []; 

    function appendMessage(text, who) {
        const wrapper = document.createElement('div');
        wrapper.className = 'msg ' + (who === 'user' ? 'user-msg' : 'ai-msg');

        // Add Avatar
        const avatar = document.createElement('div');
        avatar.className = 'avatar';
        avatar.innerHTML = who === 'user' ? '<i class="fa-solid fa-user"></i>' : '<i class="fa-solid fa-robot"></i>';
        
        const bubble = document.createElement('div');
        bubble.className = 'msg-bubble';
        
        // Parse markdown ONLY for AI messages. User messages stay as plain text.
        if (who === 'ai') {
            bubble.innerHTML = marked.parse(text);
        } else {
            bubble.textContent = text;
        }

        wrapper.appendChild(avatar);
        wrapper.appendChild(bubble);
        chatMessages.appendChild(wrapper);
        
        // Auto-scroll to bottom
        chatWindow.scrollTop = chatWindow.scrollHeight;

        return wrapper;
    }

    function setLoading(isLoading) {
        sendBtn.disabled = isLoading;
        chatInput.disabled = isLoading;
        if(isLoading) {
            sendBtn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i>';
        } else {
            sendBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i>';
            chatInput.focus();
        }
    }

    chatForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        const message = chatInput.value.trim();
        if (!message) return;

        appendMessage(message, 'user');
        chatInput.value = '';

        const typingEl = appendMessage('Thinking...', 'ai');
        setLoading(true);

        // Extract the last 6 messages to keep the context size manageable
        const historyToSend = chatHistory.slice(-6);
        
        // Now record the user's current message into memory
        chatHistory.push({ role: 'user', content: message });

        try {
            const res = await fetch('/admin/assistant/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                // SEND BOTH MESSAGE AND HISTORY TO BACKEND
                body: JSON.stringify({ 
                    message: message,
                    history: historyToSend
                })
            });

            const data = await res.json();

            if (!res.ok) {
                typingEl.querySelector('.msg-bubble').innerHTML =
                    (data && data.reply) ? marked.parse(data.reply) : ('Error: ' + res.status);
                return;
            }

            const finalReplyText = (data && data.reply) ? String(data.reply) : '(no response from AI)';
            typingEl.querySelector('.msg-bubble').innerHTML = marked.parse(finalReplyText);

            // Record the AI's response into memory so it doesn't forget what it just said
            chatHistory.push({ role: 'assistant', content: finalReplyText });

        } catch (err) {
            typingEl.querySelector('.msg-bubble').textContent = 'Network error. Please check your local server.';
        } finally {
            setLoading(false);
            chatWindow.scrollTop = chatWindow.scrollHeight;
        }
    });
})();
</script>

</body>
</html>