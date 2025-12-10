CREATE DATABASE IF NOT EXISTS tawkto_chat;
USE tawkto_chat;

CREATE TABLE IF NOT EXISTS chat_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    visitor_id VARCHAR(100) NOT NULL,
    visitor_name VARCHAR(100),
    visitor_email VARCHAR(100),
    message TEXT NOT NULL,
    agent_response TEXT,
    chat_status ENUM('open', 'closed', 'pending') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS agents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    status ENUM('online', 'offline', 'busy') DEFAULT 'offline',
    last_active TIMESTAMP
);

-- Insert sample agent
INSERT INTO agents (name, email, status) VALUES ('Support Agent', 'agent@example.com', 'online');