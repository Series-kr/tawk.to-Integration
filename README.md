# Tawk.to Web Messaging Integration

A complete web application that integrates Tawk.to's chat API with a custom admin dashboard for managing customer interactions.

## Problem Statement

Many businesses need a customer support system that:
1. Provides real-time chat functionality on their website
2. Allows customer service agents to manage conversations efficiently
3. Stores chat history for future reference and analysis
4. Offers a customizable interface that matches their brand
5. Works across all devices without requiring additional software

## Solution

This project provides a comprehensive solution by:

1. **Frontend Integration**: Embedding Tawk.to's chat widget with custom event handling
2. **Custom Dashboard**: Building an admin panel to view, manage, and respond to chats
3. **Database Storage**: Saving chat history and customer information in MySQL
4. **RESTful API**: Creating PHP endpoints for data management
5. **Responsive Design**: Using Bootstrap 5 for mobile-friendly interfaces

## Features

### For Customers:
- Real-time chat with support agents
- Contact form for offline messages
- Visitor identification and chat history
- Mobile-responsive interface

### For Agents/Admins:
- Dashboard with chat statistics
- View and manage all conversations
- Update chat status (open/closed/pending)
- Send responses to visitors
- Live updates for new messages

## Demo

### Live Demo Setup:

1. **Landing Page**: 
   - Clean, professional design with chat widget
   - Contact form for sending messages offline
   - Information about support services

2. **Chat Widget**:
   - Integrated Tawk.to widget in bottom-right corner
   - Custom event handling for chat tracking
   - Visitor information collection

3. **Admin Dashboard**:
   - Real-time statistics on total, open, and closed chats
   - Recent chats table with status indicators
   - Detailed chat view with conversation history
   - Ability to respond and close chats

### Screenshots:
1. Landing page with chat widget
2. Admin dashboard with statistics
3. Chat details modal
4. Mobile-responsive views

## Installation

### Prerequisites:
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Tawk.to account (free tier available)

### Setup Steps:

1. **Clone the repository:**
   ```bash
   git clone https://github.com/yourusername/tawkto-integration.git
   cd tawkto-integration