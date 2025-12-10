// Dashboard functionality
document.addEventListener('DOMContentLoaded', function() {
    // Set up live updates if enabled
    const liveUpdates = document.getElementById('liveUpdates');
    let updateInterval;
    
    function startLiveUpdates() {
        updateInterval = setInterval(updateDashboard, 30000); // Update every 30 seconds
    }
    
    function stopLiveUpdates() {
        clearInterval(updateInterval);
    }
    
    liveUpdates.addEventListener('change', function() {
        if (this.checked) {
            startLiveUpdates();
        } else {
            stopLiveUpdates();
        }
    });
    
    // Start live updates initially
    startLiveUpdates();
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Refresh dashboard data
function refreshDashboard() {
    updateDashboard();
    showNotification('Dashboard refreshed successfully', 'success');
}

// Update dashboard data
async function updateDashboard() {
    try {
        const response = await fetch('api/get-chats.php?limit=10');
        const data = await response.json();
        
        if (data.success) {
            updateChatTable(data.chats);
            updateStats(data.stats);
        }
    } catch (error) {
        console.error('Error updating dashboard:', error);
    }
}

// Update chat table
function updateChatTable(chats) {
    const tbody = document.getElementById('chatTableBody');
    tbody.innerHTML = '';
    
    chats.forEach(chat => {
        const row = document.createElement('tr');
        
        const statusColor = chat.chat_status === 'open' ? 'warning' : 
                           chat.chat_status === 'closed' ? 'success' : 'secondary';
        
        row.innerHTML = `
            <td>
                <div class="d-flex align-items-center">
                    <div class="avatar-sm bg-light rounded-circle d-flex align-items-center justify-content-center me-2">
                        <i class="bi bi-person"></i>
                    </div>
                    <div>
                        <div class="fw-bold">${chat.visitor_name || 'Anonymous'}</div>
                        <small class="text-muted">${chat.visitor_email || 'No email'}</small>
                    </div>
                </div>
            </td>
            <td>
                <div class="message-preview">
                    ${chat.message.substring(0, 50)}...
                </div>
            </td>
            <td>
                <span class="badge bg-${statusColor}">
                    ${chat.chat_status.charAt(0).toUpperCase() + chat.chat_status.slice(1)}
                </span>
            </td>
            <td>
                ${formatDate(chat.created_at)}
            </td>
            <td>
                <button class="btn btn-sm btn-outline-primary" onclick="viewChat(${chat.id})">
                    <i class="bi bi-eye"></i> View
                </button>
                <button class="btn btn-sm btn-outline-success" onclick="updateStatus(${chat.id}, 'closed')">
                    <i class="bi bi-check"></i> Close
                </button>
            </td>
        `;
        
        tbody.appendChild(row);
    });
}

// Update statistics
function updateStats(stats) {
    // Update stats cards if they exist
    document.querySelectorAll('.stat-card .card-title').forEach((el, index) => {
        switch(index) {
            case 0:
                el.textContent = stats.total_chats;
                break;
            case 1:
                el.textContent = stats.open_chats;
                break;
            case 2:
                el.textContent = stats.closed_chats;
                break;
        }
    });
}

// View chat details
async function viewChat(chatId) {
    try {
        const response = await fetch(`api/get-chats.php?id=${chatId}`);
        const data = await response.json();
        
        if (data.success && data.chat) {
            const chat = data.chat;
            const modalBody = document.getElementById('chatDetails');
            
            modalBody.innerHTML = `
                <div class="chat-details">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6>Visitor Information</h6>
                            <p><strong>Name:</strong> ${chat.visitor_name || 'Not provided'}</p>
                            <p><strong>Email:</strong> ${chat.visitor_email || 'Not provided'}</p>
                            <p><strong>Visitor ID:</strong> ${chat.visitor_id}</p>
                        </div>
                        <div class="col-md-6">
                            <h6>Chat Information</h6>
                            <p><strong>Status:</strong> 
                                <span class="badge bg-${chat.chat_status === 'open' ? 'warning' : 'success'}">
                                    ${chat.chat_status.toUpperCase()}
                                </span>
                            </p>
                            <p><strong>Started:</strong> ${formatDate(chat.created_at)}</p>
                            <p><strong>Last Updated:</strong> ${formatDate(chat.updated_at)}</p>
                        </div>
                    </div>
                    
                    <div class="chat-messages mb-4">
                        <h6>Messages</h6>
                        <div class="message-bubble visitor">
                            <div class="message-header">
                                <strong>Visitor</strong>
                                <small class="text-muted">${formatDate(chat.created_at)}</small>
                            </div>
                            <div class="message-content">
                                ${chat.message}
                            </div>
                        </div>
                        
                        ${chat.agent_response ? `
                        <div class="message-bubble agent">
                            <div class="message-header">
                                <strong>Agent Response</strong>
                                <small class="text-muted">${formatDate(chat.updated_at)}</small>
                            </div>
                            <div class="message-content">
                                ${chat.agent_response}
                            </div>
                        </div>
                        ` : ''}
                    </div>
                    
                    <div class="response-section">
                        <h6>Respond to Visitor</h6>
                        <div class="input-group">
                            <textarea id="agentResponse" class="form-control" rows="3" placeholder="Type your response here..."></textarea>
                            <button class="btn btn-primary" onclick="sendResponse(${chat.id})">Send Response</button>
                        </div>
                    </div>
                </div>
            `;
            
            const modal = new bootstrap.Modal(document.getElementById('chatModal'));
            modal.show();
        }
    } catch (error) {
        console.error('Error loading chat:', error);
        showNotification('Error loading chat details', 'error');
    }
}

// Update chat status
async function updateStatus(chatId, status) {
    try {
        const response = await fetch('api/update-status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                chat_id: chatId,
                status: status
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Chat status updated successfully', 'success');
            updateDashboard(); // Refresh the dashboard
        } else {
            showNotification('Error updating status', 'error');
        }
    } catch (error) {
        console.error('Error updating status:', error);
        showNotification('Error updating status', 'error');
    }
}

// Send agent response
async function sendResponse(chatId) {
    const responseText = document.getElementById('agentResponse').value;
    
    if (!responseText.trim()) {
        showNotification('Please enter a response', 'warning');
        return;
    }
    
    try {
        const response = await fetch('api/update-status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                chat_id: chatId,
                agent_response: responseText,
                status: 'closed' // Close chat after response
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Response sent successfully', 'success');
            document.getElementById('agentResponse').value = '';
            updateDashboard();
        }
    } catch (error) {
        console.error('Error sending response:', error);
        showNotification('Error sending response', 'error');
    }
}

// Load all chats
async function loadChats() {
    try {
        const response = await fetch('api/get-chats.php?all=true');
        const data = await response.json();
        
        if (data.success) {
            // Navigate to chats view and display all chats
            window.location.hash = 'chats';
            // You would update the main content to show all chats here
        }
    } catch (error) {
        console.error('Error loading chats:', error);
    }
}

// Utility functions
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification alert alert-${type} alert-dismissible fade show`;
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Add styles
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    `;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Remove after 5 seconds
    setTimeout(() => {
        notification.remove();
    }, 5000);
}