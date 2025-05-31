document.addEventListener('DOMContentLoaded', function() {
    const chatWidgetContainer = document.getElementById('chat-widget-container');
    const chatToggleButton = document.getElementById('chat-toggle-button');
    const chatGlobalUnreadBadge = document.getElementById('chat-global-unread-badge');
    const chatWindow = document.getElementById('chat-window');
    const chatHeader = document.getElementById('chat-header');
    const chatWindowTitle = document.getElementById('chat-window-title');
    const chatCloseButton = document.getElementById('chat-close-button');
    const chatBackButton = document.getElementById('chat-back-button');
    
    const chatBody = document.getElementById('chat-body');
    const conversationListDiv = document.getElementById('chat-conversation-list');
    const messageAreaDiv = document.getElementById('chat-message-area');
    const noConversationSelectedDiv = document.getElementById('chat-no-conversation-selected');
    
    const messagesDisplayDiv = document.getElementById('chat-messages-display');
    const messageForm = document.getElementById('chat-message-form');
    const messageInput = document.getElementById('chat-message-input');
    const sendButton = document.getElementById('chat-send-button');

    const convListSpinner = conversationListDiv ? conversationListDiv.querySelector('.loading-spinner') : null;
    const msgDisplaySpinner = messagesDisplayDiv ? messagesDisplayDiv.querySelector('.loading-spinner') : null;

    let currentOpenChatUserID = null;
    let currentOpenChatUserName = '';
    let lastKnownMessageIdForActiveChat = 0;
    let conversationPollingTimer = null;
    let messagePollingTimer = null;
    const POLLING_INTERVAL = 5000; 
    let isChatManuallyOpened = false; 

    if (!chatToggleButton || !chatWindow) {
        console.warn("Chat UI elements not found. Chat widget might not function correctly.");
    }

    if (chatToggleButton) chatToggleButton.addEventListener('click', () => _internalToggleChatWindow(null, false));
    if (chatCloseButton) chatCloseButton.addEventListener('click', () => _internalToggleChatWindow(false, false));
    if (chatBackButton) chatBackButton.addEventListener('click', showConversationList);
    
    if (messageForm) {
        messageForm.addEventListener('submit', function(e) {
            e.preventDefault();
            sendMessage();
        });
    }
    if (messageInput) { // Разблокировать поле ввода, если оно неактивно и пользователь кликнул
        messageInput.addEventListener('focus', () => {
            if(messageInput.disabled && currentOpenChatUserID) { // Если чат выбран, но поле заблокировано
                 messageInput.disabled = false;
                 if(sendButton) sendButton.disabled = false;
            }
        });
    }


    function showSpinner(spinnerElement) {
        if(spinnerElement) spinnerElement.classList.remove('hidden');
    }
    function hideSpinner(spinnerElement) {
         if(spinnerElement) spinnerElement.classList.add('hidden');
    }

    const _internalToggleChatWindow = function(forceShow = null, calledFromInitiate = false) {
        if (!chatWindow || !chatToggleButton) return; 

        const isHidden = chatWindow.classList.contains('hidden');
        if (forceShow === true || isHidden) {
            chatWindow.classList.remove('hidden');
            chatToggleButton.classList.add('hidden'); 
            isChatManuallyOpened = !calledFromInitiate;

            if (!calledFromInitiate && (!conversationListDiv.querySelector('.chat-conversation-item') && !conversationListDiv.querySelector('p[style*="text-align:center"]'))) {
                showConversationListView(); // Показывает список (или плейсхолдер, если список пуст)
            } else if (calledFromInitiate && conversationListDiv.children.length <= 1 ) {
                 loadConversations();
            } else if (conversationListDiv.children.length <=1 && !currentOpenChatUserID) { // Если открыли, а списка нет и чат не выбран
                 showConversationListView();
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
        if (!chatWindow) { 
            console.error("Chat window element not found. Cannot initiate chat.");
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
                     if (data.conversations.length === 0 && !currentOpenChatUserID) {
                        if (noConversationSelectedDiv) noConversationSelectedDiv.classList.remove('hidden');
                    }
                } else {
                    conversationListDiv.innerHTML = `<p style="padding:10px; text-align:center; color:grey;">${data.message || 'Neizdevās ielādēt sarunas.'}</p>`;
                    if (noConversationSelectedDiv && !currentOpenChatUserID) noConversationSelectedDiv.classList.remove('hidden');
                }
            })
            .catch(error => {
                hideSpinner(convListSpinner);
                conversationListDiv.innerHTML = `<p style="padding:10px; text-align:center; color:grey;">Tīkla kļūda ielādējot sarunas.</p>`;
                if (noConversationSelectedDiv && !currentOpenChatUserID) noConversationSelectedDiv.classList.remove('hidden');
            });
    }

    function renderConversationList(conversations) {
        if (!conversationListDiv) return;
        const existingItems = conversationListDiv.querySelectorAll('.chat-conversation-item, p[style*="text-align:center"]');
        existingItems.forEach(item => item.remove());

        if (conversations.length === 0) {
            conversationListDiv.insertAdjacentHTML('beforeend', `<p style="padding:10px; text-align:center; color:grey;">Jums vēl nav sarunu.</p>`);
            return;
        }

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
                const date = new Date(conv.lastMessageTimestamp); const today = new Date();
                if (date.toDateString() === today.toDateString()) lastMsgTime = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                else lastMsgTime = date.toLocaleDateString([], { day: 'numeric', month: 'short' });
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
                </div>`;
            item.addEventListener('click', () => openConversation(conv.LietotajsID, conv.Lietotajvards));
            conversationListDiv.appendChild(item);
        });
    }
    
    function updateGlobalUnreadBadge(conversations = null) {
        if (!chatGlobalUnreadBadge) return;
        let totalUnread = 0;
        if (conversations) { 
            conversations.forEach(conv => { totalUnread += parseInt(conv.unreadCount || 0); });
        }
        updateGlobalUnreadBadgeBasedOnCount(totalUnread);
    }

    function showConversationListView() { // Новая функция
        if(chatWindowTitle) chatWindowTitle.textContent = 'Sarunas';
        if(messageAreaDiv) messageAreaDiv.classList.add('hidden');
        if(chatBackButton) chatBackButton.classList.add('hidden');
        if(chatHeader) chatHeader.classList.remove('message-view');
        if(conversationListDiv) conversationListDiv.classList.remove('hidden');
        
        if(messageInput) messageInput.disabled = true;
        if(sendButton) sendButton.disabled = true;
        
        if (conversationListDiv && conversationListDiv.children.length <= 1) { // <=1 to account for spinner
            if(noConversationSelectedDiv) noConversationSelectedDiv.classList.remove('hidden');
             loadConversations(); // Load if empty
        } else if (conversationListDiv && conversationListDiv.children.length > 1) {
             if(noConversationSelectedDiv) noConversationSelectedDiv.classList.add('hidden'); // Hide if list has items
        }
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
        if(chatHeader) chatHeader.classList.add('message-view'); // Для стилизации заголовка
        
        if(messageInput) messageInput.disabled = false;
        if(sendButton) sendButton.disabled = false;
        if(messageInput) messageInput.focus();

        if(messagesDisplayDiv) messagesDisplayDiv.innerHTML = ''; 
        showSpinner(msgDisplaySpinner);
        loadMessages(userId, true); 
        startMessagePolling();

        conversationListDiv.querySelectorAll('.chat-conversation-item').forEach(item => {
            item.classList.remove('active-conversation');
            if (parseInt(item.dataset.userId) === parseInt(userId)) {
                item.classList.add('active-conversation');
            }
        });
    }

    function showConversationList() { // Вызывается по кнопке "Назад"
        currentOpenChatUserID = null;
        currentOpenChatUserName = '';
        stopMessagePolling();
        showConversationListView(); // Используем новую функцию
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
                           conversationListDiv.querySelectorAll('.chat-conversation-item').forEach(item => {
                                const itemBadge = item.querySelector('.conversation-item-unread-badge');
                                if (itemBadge && !itemBadge.classList.contains('hidden') && item.dataset.userId != userId) {
                                    totalUnread += parseInt(itemBadge.textContent) || 0;
                                }
                           });
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
                        if (messagesToRender.length > 0) { // Убедимся, что есть сообщения для обновления ID
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
                }
            })
            .catch(error => {
                if (!isPoll || !markAsReadInitial) {
                    hideSpinner(msgDisplaySpinner);
                    if (messagesDisplayDiv) messagesDisplayDiv.innerHTML = `<p style="text-align:center; color:red;">Tīkla kļūda ielādējot ziņas.</p>`;
                }
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
        messageDiv.innerHTML = `
            <div class="message-bubble">${cleanText.replace(/\n/g, '<br>')}</div>
            <div class="message-timestamp">${msg.NosutisanasLaiksFormatted || new Date(msg.NosutisanasLaiks).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>
        `;
        messagesDisplayDiv.appendChild(messageDiv);
        if (messagesDisplayDiv.scrollHeight - messagesDisplayDiv.scrollTop - messagesDisplayDiv.clientHeight < 150) { // Увеличил порог для автоскролла
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
        fetch('chat_api.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.sent_message) {
                appendMessage(data.sent_message); 
                messageInput.value = '';
                lastKnownMessageIdForActiveChat = data.sent_message.ZinojumaID;
            } else { showToast(data.message || 'Kļūda sūtot ziņu', 'error'); }
        })
        .catch(error => { showToast('Tīkla kļūda sūtot ziņu.', 'error'); })
        .finally(() => { sendButton.disabled = false; if (messageInput) messageInput.focus(); });
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
        if (messagePollingTimer) { clearInterval(messagePollingTimer); messagePollingTimer = null; }
    }
    
    function startConversationPolling() {
        stopConversationPolling();
        conversationPollingTimer = setInterval(() => {
            if (chatWindow && !chatWindow.classList.contains('hidden')) { pollConversationsData(); }
        }, POLLING_INTERVAL * 2); 
    }

    function stopConversationPolling() {
         if (conversationPollingTimer) { clearInterval(conversationPollingTimer); conversationPollingTimer = null; }
    }

    function pollConversationsData() {
        fetch(`chat_api.php?action=check_new_data&last_message_id=${lastKnownMessageIdForActiveChat}&active_chat_partner_id=${currentOpenChatUserID || 'null'}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.unread_counts) updateUnreadBadgesInList(data.unread_counts);
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
            const userId = item.dataset.userId; const badge = item.querySelector(`#unread-badge-${userId}`); const count = unreadCounts[userId] || 0;
            if (badge) {
                if (count > 0) { badge.textContent = count > 9 ? '9+' : count; badge.classList.remove('hidden'); } 
                else { badge.classList.add('hidden'); }
            } else if (count > 0) { 
                const metaDiv = item.querySelector('.conversation-item-meta');
                if (metaDiv) {
                    const newBadge = document.createElement('div'); newBadge.className = 'conversation-item-unread-badge'; newBadge.id = `unread-badge-${userId}`;
                    newBadge.textContent = count > 9 ? '9+' : count; metaDiv.appendChild(newBadge);
                }
            }
            if (parseInt(userId) !== parseInt(currentOpenChatUserID)) totalGlobalUnread += count;
        });
        for (const userId in unreadCounts) {
            if (!document.getElementById(`unread-badge-${userId}`)) {
                 if (parseInt(userId) !== parseInt(currentOpenChatUserID)) totalGlobalUnread += unreadCounts[userId];
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

    function scrollToBottom(element) { if(element) element.scrollTop = element.scrollHeight; }
    function escapeHtml(unsafe) { if (unsafe === null || typeof unsafe === 'undefined') return ''; return unsafe.toString().replace(/&/g, "&").replace(/</g, "<").replace(/>/g, ">").replace(/"/g, '"').replace(/'/g, "'"); }

    const myUserIdFromBody = document.body.dataset.currentUserId;
    if (!myUserIdFromBody || myUserIdFromBody === '0') {
        if(chatWidgetContainer) chatWidgetContainer.classList.add('hidden'); 
        return; 
    } else {
        const urlParams = new URLSearchParams(window.location.search);
        const chatWith = urlParams.get('chat_with'); const chatName = urlParams.get('chat_name');
        if (chatWith && chatName) {
            const newUrl = window.location.pathname + window.location.search.replace(/&?chat_with=[^&]*/g, '').replace(/&?chat_name=[^&]*/g, '');
            window.history.replaceState({}, document.title, newUrl.replace(/\?&/, '?').replace(/\?$/, ''));
            setTimeout(() => {
                 if (typeof window.initiateChatWithUser === 'function') {
                    window.initiateChatWithUser(parseInt(chatWith), decodeURIComponent(chatName));
                }
            }, 100); 
        } else {
            pollConversationsData(); 
        }
    }
});