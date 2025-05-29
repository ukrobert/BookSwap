document.addEventListener('DOMContentLoaded', function() {
    // Попытка получить элементы DOM. Они могут быть null, если chat_widget.php еще не загружен
    // или если chat.js загружается раньше HTML виджета.
    let chatWidgetContainer = document.getElementById('chat-widget-container');
    let chatToggleButton = document.getElementById('chat-toggle-button');
    let chatGlobalUnreadBadge = document.getElementById('chat-global-unread-badge');
    let chatWindow = document.getElementById('chat-window');
    let chatHeader = document.getElementById('chat-header');
    let chatWindowTitle = document.getElementById('chat-window-title');
    let chatCloseButton = document.getElementById('chat-close-button');
    let chatBackButton = document.getElementById('chat-back-button');
    
    let chatBody = document.getElementById('chat-body');
    let conversationListDiv = document.getElementById('chat-conversation-list');
    let messageAreaDiv = document.getElementById('chat-message-area');
    let noConversationSelectedDiv = document.getElementById('chat-no-conversation-selected');
    
    let messagesDisplayDiv = document.getElementById('chat-messages-display');
    let messageForm = document.getElementById('chat-message-form');
    let messageInput = document.getElementById('chat-message-input');
    let sendButton = document.getElementById('chat-send-button');

    let convListSpinner = null; 
    let msgDisplaySpinner = null;

    // Функция для инициализации/повторной инициализации ссылок на DOM элементы
    function initializeChatDOMElements() {
        chatWidgetContainer = document.getElementById('chat-widget-container');
        chatToggleButton = document.getElementById('chat-toggle-button');
        chatGlobalUnreadBadge = document.getElementById('chat-global-unread-badge');
        chatWindow = document.getElementById('chat-window');
        chatHeader = document.getElementById('chat-header');
        chatWindowTitle = document.getElementById('chat-window-title');
        chatCloseButton = document.getElementById('chat-close-button');
        chatBackButton = document.getElementById('chat-back-button');
        
        chatBody = document.getElementById('chat-body');
        conversationListDiv = document.getElementById('chat-conversation-list');
        messageAreaDiv = document.getElementById('chat-message-area');
        noConversationSelectedDiv = document.getElementById('chat-no-conversation-selected');
        
        messagesDisplayDiv = document.getElementById('chat-messages-display');
        messageForm = document.getElementById('chat-message-form');
        messageInput = document.getElementById('chat-message-input');
        sendButton = document.getElementById('chat-send-button');

        convListSpinner = conversationListDiv ? conversationListDiv.querySelector('.loading-spinner') : null;
        msgDisplaySpinner = messagesDisplayDiv ? messagesDisplayDiv.querySelector('.loading-spinner') : null;

        // Перепривязка обработчиков событий, если элементы были переинициализированы
        // Важно, чтобы это не приводило к дублированию обработчиков, если элементы уже были.
        // Для простоты, предполагаем, что если элементы есть, обработчики тоже.
        // Более надежно было бы сначала удалять старые обработчики.
        if (chatToggleButton && !chatToggleButton.hasAttribute('data-listener-attached')) {
             chatToggleButton.addEventListener('click', () => _internalToggleChatWindow(null, false));
             chatToggleButton.setAttribute('data-listener-attached', 'true');
        }
        if (chatCloseButton && !chatCloseButton.hasAttribute('data-listener-attached')) {
            chatCloseButton.addEventListener('click', () => _internalToggleChatWindow(false, false));
            chatCloseButton.setAttribute('data-listener-attached', 'true');
        }
        if (chatBackButton && !chatBackButton.hasAttribute('data-listener-attached')) {
            chatBackButton.addEventListener('click', showConversationList);
            chatBackButton.setAttribute('data-listener-attached', 'true');
        }
        if (messageForm && !messageForm.hasAttribute('data-listener-attached')) {
            messageForm.addEventListener('submit', function(e) {
                e.preventDefault();
                sendMessage();
            });
            messageForm.setAttribute('data-listener-attached', 'true');
        }
    }
    
    initializeChatDOMElements(); // Первая попытка инициализации

    let currentOpenChatUserID = null;
    let currentOpenChatUserName = '';
    let lastKnownMessageIdForActiveChat = 0;
    let conversationPollingTimer = null;
    let messagePollingTimer = null;
    const POLLING_INTERVAL = 5000; 
    let isChatManuallyOpened = false;

    if (!chatToggleButton || !chatWindow) {
        console.warn("Initial Chat UI elements not found. Will try to re-initialize on demand.");
    }


    function showSpinner(spinnerElement) {
        if(spinnerElement) spinnerElement.classList.remove('hidden');
    }
    function hideSpinner(spinnerElement) {
         if(spinnerElement) spinnerElement.classList.add('hidden');
    }

    const _internalToggleChatWindow = function(forceShow = null, calledFromInitiate = false) {
        if (!chatWindow || !chatToggleButton) { // Повторная проверка/инициализация
            initializeChatDOMElements();
            if (!chatWindow || !chatToggleButton) {
                 console.error("Cannot toggle chat: critical UI elements still missing.");
                 return;
            }
        }

        const isHidden = chatWindow.classList.contains('hidden');
        if (forceShow === true || isHidden) {
            chatWindow.classList.remove('hidden');
            chatToggleButton.classList.add('hidden'); 
            isChatManuallyOpened = !calledFromInitiate; 

            if (!calledFromInitiate && (!conversationListDiv.querySelector('.chat-conversation-item') && !conversationListDiv.querySelector('p[style*="text-align:center"]'))) {
                loadConversations();
            } else if (calledFromInitiate && (!conversationListDiv.children || conversationListDiv.children.length <= 1) ) { // Проверка на пустой список
                 loadConversations(); 
            }
            
            startConversationPolling();
            if (currentOpenChatUserID && !calledFromInitiate) {
                startMessagePolling();
            }
        } else { 
            chatWindow.classList.add('hidden');
            chatToggleButton.classList.remove('hidden');
            stopConversationPolling();
            stopMessagePolling();
            isChatManuallyOpened = false;
        }
    };
    
    window.initiateChatWithUser = function(userId, userName) {
        initializeChatDOMElements(); // Убедимся, что DOM элементы актуальны
        
        if (!chatWindow) { 
            console.error("Chat window element not found even after re-init. Cannot initiate chat.");
            alert("Čata funkcionalitāte pašlaik nav pieejama.");
            return;
        }
        if (!userId || !userName) {
            console.error("User ID or User Name not provided for chat initiation.");
            return;
        }
        const myUserIdFromBody = document.body.dataset.currentUserId;
        if (!myUserIdFromBody || myUserIdFromBody === '0') {
            alert("Lūdzu, pieslēdzieties, lai sāktu sarunu.");
            return;
        }
        if (parseInt(myUserIdFromBody) === parseInt(userId)) {
            alert("Jūs nevarat sākt sarunu ar sevi.");
            return;
        }

        _internalToggleChatWindow(true, true); 

        if (typeof openConversation === 'function') {
            openConversation(userId, userName);
        } else {
            console.error("openConversation function is not defined or accessible.");
        }
    };

    function loadConversations() {
        if (!conversationListDiv) return;
        showSpinner(convListSpinner);
        if (noConversationSelectedDiv) noConversationSelectedDiv.classList.add('hidden');
        
        fetch('chat_api.php?action=get_conversations')
            .then(response => response.json())
            .then(data => {
                hideSpinner(convListSpinner);
                if (data.success && data.conversations) {
                    renderConversationList(data.conversations);
                    updateGlobalUnreadBadge(data.conversations);
                } else {
                    conversationListDiv.innerHTML = `<p style="padding:10px; text-align:center; color:grey;">${data.message || 'Neizdevās ielādēt sarunas.'}</p>`;
                    console.error("Failed to load conversations:", data.message);
                }
            })
            .catch(error => {
                hideSpinner(convListSpinner);
                conversationListDiv.innerHTML = `<p style="padding:10px; text-align:center; color:grey;">Tīkla kļūda ielādējot sarunas.</p>`;
                console.error("Error fetching conversations:", error);
            });
    }

    function renderConversationList(conversations) {
        if (!conversationListDiv) return;
        const existingItems = conversationListDiv.querySelectorAll('.chat-conversation-item, p[style*="text-align:center"]');
        existingItems.forEach(item => item.remove());

        if (conversations.length === 0) {
            conversationListDiv.insertAdjacentHTML('beforeend', `<p style="padding:10px; text-align:center; color:grey;">Jums vēl nav sarunu.</p>`);
            if (noConversationSelectedDiv && messageAreaDiv && messageAreaDiv.classList.contains('hidden')) {
                 noConversationSelectedDiv.classList.remove('hidden');
            }
            return;
        }
        if (noConversationSelectedDiv) noConversationSelectedDiv.classList.remove('hidden');


        conversations.forEach(conv => {
            const item = document.createElement('div');
            item.className = 'chat-conversation-item';
            item.dataset.userId = conv.LietotajsID;
            item.dataset.userName = conv.Lietotajvards;
             if (currentOpenChatUserID && parseInt(currentOpenChatUserID) === parseInt(conv.LietotajsID)) {
                item.classList.add('active-conversation');
            }

            const initials = conv.Lietotajvards ? conv.Lietotajvards.substring(0, 1).toUpperCase() : 'U';
            const avatarPath = conv.ProfilaAttels;
            let avatarHtml = `<span class="placeholder-initials">${initials}</span>`;
            if (avatarPath) {
                if (avatarPath.startsWith('http') || avatarPath.startsWith('https') || avatarPath.startsWith('//') || avatarPath.startsWith('uploads/')) {
                     avatarHtml = `<img src="${avatarPath}?t=${new Date().getTime()}" alt="${conv.Lietotajvards}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                  <span class="placeholder-initials" style="display:none;">${initials}</span>`;
                }
            }
            
            let lastMsgTime = '';
            if (conv.lastMessageTimestamp) {
                try {
                    const date = new Date(conv.lastMessageTimestamp.replace(' ', 'T')+'Z'); // Ensure ISO 8601 with Z for UTC or handle timezone
                    const today = new Date();
                    if (date.toDateString() === today.toDateString()) {
                        lastMsgTime = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                    } else {
                        lastMsgTime = date.toLocaleDateString([], { day: 'numeric', month: 'short' });
                    }
                } catch (e) { console.warn("Error parsing date:", conv.lastMessageTimestamp, e); lastMsgTime = "N/A"; }
            }

            item.innerHTML = `
                <div class="conversation-item-avatar">${avatarHtml}</div>
                <div class="conversation-item-details">
                    <div class="conversation-item-name">${conv.Lietotajvards || 'Nezināms'}</div>
                    <div class="conversation-item-last-message">${conv.lastMessageText ? escapeHtml(conv.lastMessageText.substring(0, 25) + (conv.lastMessageText.length > 25 ? '...' : '')) : '<i>Nav ziņu</i>'}</div>
                </div>
                <div class="conversation-item-meta">
                    <div class="conversation-item-time">${lastMsgTime}</div>
                    ${conv.unreadCount > 0 ? `<div class="conversation-item-unread-badge" id="unread-badge-${conv.LietotajsID}">${conv.unreadCount > 9 ? '9+' : conv.unreadCount}</div>` : ''}
                </div>
            `;
            item.addEventListener('click', () => openConversation(conv.LietotajsID, conv.Lietotajvards));
            conversationListDiv.appendChild(item);
        });
    }
    
    function updateGlobalUnreadBadge(conversations = null) {
        if (!chatGlobalUnreadBadge) return;
        let totalUnread = 0;
        if (conversations) { 
            conversations.forEach(conv => {
                totalUnread += parseInt(conv.unreadCount || 0);
            });
        }
        updateGlobalUnreadBadgeBasedOnCount(totalUnread);
    }

    function openConversation(userId, userName) {
        currentOpenChatUserID = userId;
        currentOpenChatUserName = userName;
        lastKnownMessageIdForActiveChat = 0; 

        if(chatWindowTitle) chatWindowTitle.textContent = userName;
        if(conversationListDiv) conversationListDiv.classList.add('hidden');
        if(noConversationSelectedDiv) noConversationSelectedDiv.classList.add('hidden');
        if(messageAreaDiv) messageAreaDiv.classList.remove('hidden');
        if(chatBackButton) chatBackButton.classList.remove('hidden');
        
        if(messageInput) { messageInput.disabled = false; messageInput.focus(); }
        if(sendButton) sendButton.disabled = false;
        

        if(messagesDisplayDiv) messagesDisplayDiv.innerHTML = ''; 
        showSpinner(msgDisplaySpinner);
        loadMessages(userId, true); 
        startMessagePolling();

        if (conversationListDiv) {
            conversationListDiv.querySelectorAll('.chat-conversation-item').forEach(item => {
                item.classList.remove('active-conversation');
                if (parseInt(item.dataset.userId) === parseInt(userId)) {
                    item.classList.add('active-conversation');
                }
            });
        }
    }

    function showConversationList() {
        currentOpenChatUserID = null;
        currentOpenChatUserName = '';
        stopMessagePolling();

        if(chatWindowTitle) chatWindowTitle.textContent = 'Sarunas';
        if(messageAreaDiv) messageAreaDiv.classList.add('hidden');
        if(chatBackButton) chatBackButton.classList.add('hidden');
        if(conversationListDiv) conversationListDiv.classList.remove('hidden');
        
        if (noConversationSelectedDiv && conversationListDiv && conversationListDiv.children.length <=1) {
            noConversationSelectedDiv.classList.remove('hidden');
        } else if (noConversationSelectedDiv && conversationListDiv && conversationListDiv.children.length > 1) {
             noConversationSelectedDiv.classList.add('hidden'); // Hide if there are conversations
        }


        if(messageInput) messageInput.disabled = true;
        if(sendButton) sendButton.disabled = true;
        
        loadConversations(); 
    }

    function loadMessages(userId, markAsReadInitial = false, isPoll = false) {
        if (!messagesDisplayDiv) return;
        if (!isPoll && markAsReadInitial) showSpinner(msgDisplaySpinner); 
        
        let url = `chat_api.php?action=get_messages&with_user_id=${userId}`;
        if (isPoll) {
            url = `chat_api.php?action=check_new_data&active_chat_partner_id=${userId}&last_message_id=${lastKnownMessageIdForActiveChat}`;
        }

        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (!isPoll || !markAsReadInitial) hideSpinner(msgDisplaySpinner);
                if (data.success) {
                    let messagesToRender = [];
                    if (isPoll && data.new_messages_active_chat) { 
                        messagesToRender = data.new_messages_active_chat;
                    } else if (!isPoll && data.messages) { 
                        messagesToRender = data.messages;
                        if (markAsReadInitial) { 
                           const badge = document.getElementById(`unread-badge-${userId}`);
                           if (badge) badge.classList.add('hidden');
                           let totalUnread = 0;
                           if(conversationListDiv){
                                conversationListDiv.querySelectorAll('.chat-conversation-item').forEach(item => {
                                    const itemBadge = item.querySelector('.conversation-item-unread-badge');
                                    if (itemBadge && !itemBadge.classList.contains('hidden') && item.dataset.userId != userId) {
                                        totalUnread += parseInt(itemBadge.textContent) || 0;
                                    }
                               });
                           }
                           updateGlobalUnreadBadgeBasedOnCount(totalUnread);
                        }
                    }
                    
                    if (messagesToRender.length > 0) {
                        if (!isPoll) messagesDisplayDiv.innerHTML = ''; 
                        messagesToRender.forEach(msg => appendMessage(msg));
                        if (messagesDisplayDiv.children.length > 0) { 
                           const placeholder = messagesDisplayDiv.querySelector('p[style*="text-align:center"]');
                           if (placeholder) placeholder.remove();
                        }
                        scrollToBottom(messagesDisplayDiv);
                        if (messagesToRender.length > 0) { // Only update if new messages were actually rendered
                            lastKnownMessageIdForActiveChat = messagesToRender[messagesToRender.length - 1].ZinojumaID;
                        }
                    } else if (!isPoll && messagesToRender.length === 0 && messagesDisplayDiv.innerHTML === '') {
                         messagesDisplayDiv.innerHTML = '<p style="text-align:center; color:grey; padding-top:20px;">Sāciet sarunu!</p>';
                    }

                    if (isPoll && data.unread_counts) { 
                        updateUnreadBadgesInList(data.unread_counts);
                    }

                } else {
                    if (!isPoll) messagesDisplayDiv.innerHTML = `<p style="text-align:center; color:red;">${data.message || 'Neizdevās ielādēt ziņas.'}</p>`;
                    console.error("Failed to load messages:", data.message);
                }
            })
            .catch(error => {
                if (!isPoll || !markAsReadInitial) {
                    hideSpinner(msgDisplaySpinner);
                    if (messagesDisplayDiv) messagesDisplayDiv.innerHTML = `<p style="text-align:center; color:red;">Tīkla kļūda ielādējot ziņas.</p>`;
                }
                console.error("Error fetching messages:", error);
            });
    }
    
    function appendMessage(msg) {
        if (!messagesDisplayDiv) return;
        const placeholder = messagesDisplayDiv.querySelector('p[style*="text-align:center"]');
        if (placeholder) placeholder.remove();

        const messageDiv = document.createElement('div');
        messageDiv.classList.add('chat-message');
        const myUserId = parseInt(document.body.dataset.currentUserId || 0);

        if (parseInt(msg.SutitajsID) === myUserId) {
            messageDiv.classList.add('sent');
        } else {
            messageDiv.classList.add('received');
        }
        const cleanText = escapeHtml(msg.Teksts);
        let timeDisplay = "N/A";
        if (msg.NosutisanasLaiksFormatted) {
            timeDisplay = msg.NosutisanasLaiksFormatted;
        } else if (msg.NosutisanasLaiks) {
            try {
                 const date = new Date(msg.NosutisanasLaiks.replace(' ', 'T')+'Z');
                 timeDisplay = date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            } catch(e) { /* ignore */}
        }

        messageDiv.innerHTML = `
            <div class="message-bubble">${cleanText.replace(/\n/g, '<br>')}</div>
            <div class="message-timestamp">${timeDisplay}</div>
        `;
        messagesDisplayDiv.appendChild(messageDiv);
        
        if (messagesDisplayDiv.scrollHeight - messagesDisplayDiv.scrollTop - messagesDisplayDiv.clientHeight < 150) { // Increased threshold
            scrollToBottom(messagesDisplayDiv);
        }
    }

    function sendMessage() {
        if (!messageInput || !sendButton) return;
        const text = messageInput.value.trim();
        if (!text || !currentOpenChatUserID) return;

        sendButton.disabled = true; 

        const formData = new FormData();
        formData.append('action', 'send_message');
        formData.append('receiver_id', currentOpenChatUserID);
        formData.append('text', text);

        fetch('chat_api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.sent_message) {
                appendMessage(data.sent_message); 
                messageInput.value = '';
                lastKnownMessageIdForActiveChat = data.sent_message.ZinojumaID;
            } else {
                // showToast is not defined here, use alert or implement a similar UI notification
                alert(data.message || 'Kļūda sūtot ziņu');
                console.error("Failed to send message:", data.message);
            }
        })
        .catch(error => {
            alert('Tīkla kļūda sūtot ziņu.');
            console.error("Error sending message:", error);
        })
        .finally(() => {
            sendButton.disabled = false;
            if (messageInput) messageInput.focus();
        });
    }

    function startMessagePolling() {
        stopMessagePolling(); 
        if (!currentOpenChatUserID) return;
        messagePollingTimer = setInterval(() => {
            if (currentOpenChatUserID && chatWindow && !chatWindow.classList.contains('hidden') && messageAreaDiv && !messageAreaDiv.classList.contains('hidden')) {
                 loadMessages(currentOpenChatUserID, false, true); 
            }
        }, POLLING_INTERVAL);
    }

    function stopMessagePolling() {
        if (messagePollingTimer) {
            clearInterval(messagePollingTimer);
            messagePollingTimer = null;
        }
    }
    
    function startConversationPolling() {
        stopConversationPolling();
        conversationPollingTimer = setInterval(() => {
            if (chatWindow && !chatWindow.classList.contains('hidden')) { 
                pollConversationsData();
            }
        }, POLLING_INTERVAL * 2); 
    }

    function stopConversationPolling() {
         if (conversationPollingTimer) {
            clearInterval(conversationPollingTimer);
            conversationPollingTimer = null;
        }
    }

    function pollConversationsData() {
        // Always fetch, even if active_chat_partner_id is null for unread_counts
        const activePartnerParam = currentOpenChatUserID ? `&active_chat_partner_id=${currentOpenChatUserID}` : '';
        fetch(`chat_api.php?action=check_new_data&last_message_id=${lastKnownMessageIdForActiveChat}${activePartnerParam}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.unread_counts) {
                        updateUnreadBadgesInList(data.unread_counts);
                    }
                    if (currentOpenChatUserID && data.new_messages_active_chat && data.new_messages_active_chat.length > 0) {
                        data.new_messages_active_chat.forEach(msg => appendMessage(msg));
                        lastKnownMessageIdForActiveChat = data.new_messages_active_chat[data.new_messages_active_chat.length - 1].ZinojumaID;
                    }
                }
            })
            .catch(error => console.error('Error polling conversation data:', error));
    }
    
    function updateUnreadBadgesInList(unreadCounts) {
        if (!conversationListDiv) return;
        let totalGlobalUnread = 0;
        
        conversationListDiv.querySelectorAll('.chat-conversation-item').forEach(item => {
            const userId = item.dataset.userId;
            const badge = item.querySelector(`#unread-badge-${userId}`);
            const count = unreadCounts[userId] || 0;

            if (badge) {
                if (count > 0) {
                    badge.textContent = count > 9 ? '9+' : count;
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }
            } else if (count > 0) { 
                const metaDiv = item.querySelector('.conversation-item-meta');
                if (metaDiv) {
                    const newBadge = document.createElement('div');
                    newBadge.className = 'conversation-item-unread-badge';
                    newBadge.id = `unread-badge-${userId}`;
                    newBadge.textContent = count > 9 ? '9+' : count;
                    metaDiv.appendChild(newBadge);
                }
            }
            // Only add to total if it's NOT the currently open chat
            if (parseInt(userId) !== parseInt(currentOpenChatUserID)) { 
                 totalGlobalUnread += count;
            }
        });
        
        for (const userId in unreadCounts) {
            if (!conversationListDiv.querySelector(`.chat-conversation-item[data-user-id="${userId}"]`)) {
                 if (parseInt(userId) !== parseInt(currentOpenChatUserID)) { // And it's not the active chat
                    totalGlobalUnread += (unreadCounts[userId] || 0);
                 }
            }
        }
        updateGlobalUnreadBadgeBasedOnCount(totalGlobalUnread);
    }
    
    function updateGlobalUnreadBadgeBasedOnCount(count) {
        if (!chatGlobalUnreadBadge) return;
        if (count > 0) {
            chatGlobalUnreadBadge.textContent = count > 9 ? '9+' : count;
            chatGlobalUnreadBadge.classList.remove('hidden');
        } else {
            chatGlobalUnreadBadge.classList.add('hidden');
        }
    }

    function scrollToBottom(element) {
        if(element) element.scrollTop = element.scrollHeight;
    }
    
    function escapeHtml(unsafe) {
        if (unsafe === null || typeof unsafe === 'undefined') return '';
        // Более мягкое экранирование, чтобы не ломать теги <br> которые мы сами вставили
        return unsafe.toString()
             .replace(/&/g, "&") // Должно быть первым
             .replace(/</g, "<")
             .replace(/>/g, ">")
             .replace(/"/g, '"')
             .replace(/'/g, "'");
    }

    const myUserIdFromBody = document.body.dataset.currentUserId;
    if (!myUserIdFromBody || myUserIdFromBody === '0') {
        if(chatWidgetContainer) chatWidgetContainer.classList.add('hidden'); 
        return; 
    } else {
        const urlParams = new URLSearchParams(window.location.search);
        const chatWith = urlParams.get('chat_with'); 
        const chatName = urlParams.get('chat_name'); 

        if (chatWith && chatName) {
            const newUrlSearch = urlParams.toString().replace(/&?chat_with=[^&]*/g, '').replace(/&?chat_name=[^&]*/g, '').replace(/^&|&$/g, '');
            const newUrl = window.location.pathname + (newUrlSearch ? '?' + newUrlSearch : '');
            window.history.replaceState({}, document.title, newUrl);

            setTimeout(() => {
                 if (typeof window.initiateChatWithUser === 'function') {
                    window.initiateChatWithUser(parseInt(chatWith), decodeURIComponent(chatName));
                }
            }, 150); // Небольшая задержка для гарантии полной загрузки DOM
        } else if (chatWindow && chatWindow.classList.contains('hidden')) { // Только если чат не открыт уже (например, через URL)
             pollConversationsData(); // Initial poll for global unread badge if not opening specific chat
        }
    }
});