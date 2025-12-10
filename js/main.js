// Tawk.to API Customization
document.addEventListener('DOMContentLoaded', function() {
    // Wait for Tawk.to to load
    setTimeout(initializeTawkIntegration, 1500);
});

function initializeTawkIntegration() {
    if (typeof Tawk_API === 'undefined') {
        console.log('Waiting for Tawk.to to load...');
        setTimeout(initializeTawkIntegration, 500);
        return;
    }

    console.log('Tawk.to API loaded');
    
    // Store visitor data globally
    window.tawkVisitorData = {
        name: '',
        email: '',
        phone: '',
        id: getVisitorId(),
        page_url: window.location.href,
        ip_address: '' // Will be captured server-side
    };

    // Custom event handlers
    Tawk_API.onLoad = function() {
        console.log('Tawk.to widget loaded');
        
        // Try to get visitor info from Tawk.to
        Tawk_API.getVisitorData(function(visitorData) {
            if (visitorData && visitorData.name) {
                window.tawkVisitorData.name = visitorData.name;
                window.tawkVisitorData.email = visitorData.email || '';
                console.log('Got visitor data from Tawk:', visitorData);
            }
        });
    };

    Tawk_API.onChatMaximized = function() {
        console.log('Chat maximized');
        saveChatEvent('chat_maximized');
    };

    Tawk_API.onChatStarted = function() {
        console.log('Chat started with agent');
        saveChatEvent('chat_started');
        
        // Save chat start with visitor info
        saveChatToDatabase({
            event_type: 'chat_started',
            message: 'Visitor started chat',
            visitor_data: window.tawkVisitorData
        });
    };

    Tawk_API.onChatMessageVisitor = function(message) {
        console.log('Visitor sent message:', message);
        
        // Save message with visitor info
        saveChatToDatabase({
            event_type: 'visitor_message',
            message: message,
            visitor_data: window.tawkVisitorData
        });
    };

    Tawk_API.onChatMessageAgent = function(message) {
        console.log('Agent sent message:', message);
        
        saveChatToDatabase({
            event_type: 'agent_message',
            message: message,
            sender: 'agent',
            visitor_data: window.tawkVisitorData
        });
    };

    Tawk_API.onChatEnded = function() {
        console.log('Chat ended');
        saveChatEvent('chat_ended');
    };

    // Custom function to set visitor info from contact form
    window.setTawkVisitorInfo = function(name, email) {
        if (name) window.tawkVisitorData.name = name;
        if (email) window.tawkVisitorData.email = email;
        
        // Also update in Tawk.to widget
        Tawk_API.setAttributes({
            'name': name || 'Website Visitor',
            'email': email || ''
        }, function(error) {
            if (!error) {
                console.log('Visitor info updated in Tawk.to');
            }
        });
    };
}

// Generate or retrieve visitor ID
function getVisitorId() {
    let visitorId = localStorage.getItem('tawk_visitor_id');
    if (!visitorId) {
        visitorId = 'visitor_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        localStorage.setItem('tawk_visitor_id', visitorId);
    }
    return visitorId;
}

// Save chat events
function saveChatEvent(eventType) {
    const data = {
        event_type: eventType,
        visitor_id: getVisitorId(),
        timestamp: new Date().toISOString()
    };

    console.log('Chat event:', eventType, data);
}

// Save chat data to database
function saveChatToDatabase(chatData) {
    // Add timestamp if not present
    if (!chatData.timestamp) {
        chatData.timestamp = new Date().toISOString();
    }
    
    // Add visitor ID if not present
    if (!chatData.visitor_id) {
        chatData.visitor_id = getVisitorId();
    }

    fetch('api/save-chat.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(chatData)
    })
    .then(response => response.json())
    .then(data => {
        console.log('Chat saved to database:', data);
    })
    .catch(error => {
        console.error('Error saving chat:', error);
    });
}

// Show contact form modal
function showContactForm() {
    const modal = new bootstrap.Modal(document.getElementById('contactModal'));
    modal.show();
}

// Handle contact form submission
document.getElementById('contactForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = {
        name: document.getElementById('name').value,
        email: document.getElementById('email').value,
        message: document.getElementById('message').value,
        type: 'contact_form'
    };

    // Set visitor info in Tawk.to
    if (window.setTawkVisitorInfo) {
        window.setTawkVisitorInfo(formData.name, formData.email);
    }

    // Save contact form submission
    fetch('api/save-chat.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            ...formData,
            visitor_data: {
                name: formData.name,
                email: formData.email,
                id: getVisitorId(),
                source: 'contact_form'
            }
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Thank you for your message! We\'ll get back to you soon.');
            bootstrap.Modal.getInstance(document.getElementById('contactModal')).hide();
            document.getElementById('contactForm').reset();
            
            // Optionally open chat
            if (Tawk_API && Tawk_API.maximize) {
                setTimeout(() => {
                    if (confirm('Would you like to chat with us now?')) {
                        Tawk_API.maximize();
                    }
                }, 1000);
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('There was an error sending your message. Please try again.');
    });
});

// Custom chat functions
function startChatWithAgent(agentId) {
    Tawk_API.maximize();
    if (agentId) {
        Tawk_API.setAttributes({
            'agent_preference': agentId
        }, function(error) {});
    }
}

// Function to manually set visitor info
function setVisitorInfo(name, email) {
    if (window.setTawkVisitorInfo) {
        window.setTawkVisitorInfo(name, email);
    }
}

// Add to js/main.js
function startChatWithInfo() {
    const name = prompt("Please enter your name to start chat:");
    const email = prompt("Please enter your email:");
    
    if (name && email) {
        setVisitorInfo(name, email);
        Tawk_API.maximize();
    } else {
        alert("Please provide name and email to start chat.");
    }
}