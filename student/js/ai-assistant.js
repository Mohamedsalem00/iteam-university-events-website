/**
 * AI Assistant component that can be loaded on any page
 * This script injects the AI chat interface and functionality into the page
 */

(function() {
  // Check if AI assistant is already loaded to prevent duplication
  if (document.getElementById('ai-assistant-container')) {
    return;
  }
// Fix the AI_API_PATH initialization to handle different page contexts
let AI_API_PATH;

// Try to use CONFIG if available
if (typeof CONFIG !== 'undefined' && CONFIG.AI_API_PATH) {
  AI_API_PATH = CONFIG.AI_API_PATH;
  console.log('Using AI_API_PATH from CONFIG:', AI_API_PATH);
} else {
  // Determine path based on current URL
  const currentPath = window.location.pathname;
  
  // Check if we're in the student section or main index
  if (currentPath.includes('/student/')) {
    AI_API_PATH = '/iteam-university-website/student/ai/ai_assist.php';
  } else {
    // When on main index or other non-student pages
    AI_API_PATH = '/iteam-university-website/student/ai/ai_assist.php';
  }
  console.log('Determined AI_API_PATH based on page location:', AI_API_PATH);
}
  
  // Create container for the AI assistant
  const assistantContainer = document.createElement('div');
  assistantContainer.id = 'ai-assistant-container';
  
  // Insert HTML for the button and chat modal
  assistantContainer.innerHTML = `
    <!-- Floating Button with Tooltip -->
    <div class="fixed bottom-6 right-6 z-50 group">
      <!-- Tooltip -->
      <div class="absolute bottom-full right-0 mb-2 w-auto p-2 bg-black text-white text-sm rounded-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-opacity duration-300">
        CampusConnect AI
      </div>
      
      <!-- Button -->
      <button id="assistantBtn" aria-label="Open CampusConnect AI" 
        class="bg-theme-primary hover:bg-theme-primary-hover dark:bg-theme-secondary dark:hover:bg-theme-primary-hover text-white p-3.5 rounded-full shadow-lg flex items-center justify-center transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-theme-primary dark:focus:ring-offset-gray-900">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
        </svg>
      </button>
    </div>

    <!-- Chat Modal -->
    <div id="assistantModal" class="fixed inset-0 z-40 hidden">    
      <!-- Overlay for mobile -->
      <div class="md:hidden absolute inset-0 bg-black bg-opacity-50" id="modalOverlay"></div>
      
      <!-- Modal Container - desktop: floating box, mobile: bottom sheet -->
      <div id="modalContainer" class="absolute flex flex-col overflow-hidden 
        md:w-96 md:h-[500px] md:max-h-[80vh] md:bottom-20 md:right-6 md:left-auto md:rounded-xl
        w-full h-[85vh] bottom-0 left-0 right-0 rounded-t-xl
        bg-white dark:bg-dashboard-card-bg shadow-2xl border border-dashboard-border dark:border-dashboard-border
        transition-all duration-300 ease-in-out animate-slide-up">
        
        <div id="modalHeader" class="flex items-center justify-between p-4 border-b border-dashboard-border dark:border-dashboard-border bg-gradient-to-r from-theme-primary to-theme-secondary dark:from-theme-secondary dark:to-theme-primary text-white">
          <div class="md:hidden absolute left-1/2 -translate-x-1/2 top-1.5 w-10 h-1 bg-white/50 rounded-full"></div>
          
          <div class="flex items-center">
            <div class="bg-white/20 p-1.5 rounded-lg">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <polygon points="10 8 16 12 10 16 10 8"></polygon>
              </svg>
            </div>
            <h2 class="text-lg font-semibold ml-2">CampusConnect AI</h2>
          </div>
          
          <div class="flex items-center space-x-2">
            <!-- Close button -->
            <button id="closeModal" class="p-1 rounded-full hover:bg-white/20 transition-colors">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
          </div>
        </div>
        
        <!-- Improved Chat Messages Container with subtle pattern background -->
        <div id="chatContainer" class="flex-1 overflow-y-auto p-4 space-y-4 no-scrollbar bg-dashboard-bg dark:bg-dashboard-bg bg-pattern">
          <!-- Welcome Message -->
          <div class="flex">
            <div class="flex-shrink-0 mr-3">
              <div class="h-9 w-9 rounded-full bg-theme-primary/20 dark:bg-theme-primary/30 flex items-center justify-center text-theme-primary dark:text-theme-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10"></circle>
                  <polygon points="10 8 16 12 10 16 10 8"></polygon>
                </svg>
              </div>
            </div>
            <div class="bg-white dark:bg-dashboard-card-bg p-3 rounded-lg rounded-tl-none max-w-[85%] shadow-sm animate-bubble">
              <p class="text-sm text-dashboard-text dark:text-dashboard-text">Hi there! 👋 I'm CampusConnect AI, ready to help you discover campus events and job opportunities. What are you looking for today?</p>
            </div>
          </div>
        </div>
        
        <!-- Enhanced Input Area with suggestions -->
        <div class="p-3 border-t border-dashboard-border dark:border-dashboard-border bg-white dark:bg-dashboard-card-bg">
          <!-- Quick suggestions -->
          <div class="mb-2 flex flex-wrap gap-1">
            <button class="suggestion-btn px-2 py-1 text-xs rounded-full bg-theme-primary/10 dark:bg-theme-primary/20 text-theme-primary dark:text-theme-secondary hover:bg-theme-primary/20 dark:hover:bg-theme-primary/30 transition-colors">Upcoming events</button>
            <button class="suggestion-btn px-2 py-1 text-xs rounded-full bg-theme-primary/10 dark:bg-theme-primary/20 text-theme-primary dark:text-theme-secondary hover:bg-theme-primary/20 dark:hover:bg-theme-primary/30 transition-colors">Available jobs</button>
            <button class="suggestion-btn px-2 py-1 text-xs rounded-full bg-theme-primary/10 dark:bg-theme-primary/20 text-theme-primary dark:text-theme-secondary hover:bg-theme-primary/20 dark:hover:bg-theme-primary/30 transition-colors">How to apply</button>
            <button class="suggestion-btn px-2 py-1 text-xs rounded-full bg-theme-primary/10 dark:bg-theme-primary/20 text-theme-primary dark:text-theme-secondary hover:bg-theme-primary/20 dark:hover:bg-theme-primary/30 transition-colors">This weekend</button>
          </div>
          
          <div class="relative">
            <textarea id="userInput" rows="1" 
              placeholder="Ask about events, clubs, or activities..."
              class="auto-resize w-full pl-3 pr-12 py-2 border border-dashboard-border dark:border-dashboard-border rounded-lg bg-white dark:bg-dashboard-bg text-sm text-dashboard-text dark:text-dashboard-text focus:outline-none focus:ring-2 focus:ring-theme-primary dark:focus:ring-theme-secondary transition-all"></textarea>
            
            <button id="sendButton" class="absolute right-2 bottom-2 p-1.5 rounded-full bg-theme-primary hover:bg-theme-primary-hover dark:bg-theme-secondary dark:hover:bg-theme-primary text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
              <!-- Loading spinner (hidden by default) -->
              <svg id="loadingSpinner" class="hidden h-5 w-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              
              <!-- Send icon -->
              <svg id="sendIcon" class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="22" y1="2" x2="11" y2="13"></line>
                <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
              </svg>
            </button>
          </div>
          <p class="text-xs text-dashboard-text-secondary dark:text-dashboard-text-secondary mt-1 text-right">Press Enter to send, Shift+Enter for new line</p>
        </div>
      </div>
    </div>
  `;
  
  // Add styles
  const styleElement = document.createElement('style');
  styleElement.textContent = `
    /* Subtle pattern background */
    .bg-pattern {
      background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%239C92AC' fill-opacity='0.05' fill-rule='evenodd'/%3E%3C/svg%3E");
    }

    /* Message bubble styles */
    .animate-bubble {
      animation: bubbleIn 0.3s ease-out forwards;
      transform-origin: left bottom;
    }

    /* Improved floating button */
    #assistantBtn {
      box-shadow: 0 4px 12px rgba(0, 119, 182, 0.5);
      transition: all 0.3s ease;
    }

    #assistantBtn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(0, 119, 182, 0.6);
    }

    /* Custom scrollbar for browsers that support it */
    #chatContainer::-webkit-scrollbar {
      width: 6px;
    }

    #chatContainer::-webkit-scrollbar-track {
      background: transparent;
    }

    #chatContainer::-webkit-scrollbar-thumb {
      background-color: rgba(156, 163, 175, 0.5);
      border-radius: 20px;
    }
    
    /* Hide scrollbar for Chrome, Safari and Opera */
    .no-scrollbar::-webkit-scrollbar {
      display: none;
    }
    
    /* Hide scrollbar for IE, Edge and Firefox */
    .no-scrollbar {
      -ms-overflow-style: none;  /* IE and Edge */
      scrollbar-width: none;  /* Firefox */
    }

    /* Message typing indicator */
    @keyframes blink {
      0% { opacity: 0.2; }
      20% { opacity: 1; }
      100% { opacity: 0.2; }
    }

    .typing-indicator span {
      animation: blink 1.4s infinite both;
    }
    .typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
    .typing-indicator span:nth-child(3) { animation-delay: 0.4s; }
    
    /* Auto-resize textarea */
    .auto-resize {
      min-height: 40px;
      resize: none;
      overflow: hidden;
      max-height: 150px;
    }
    
    /* Custom animation for chat bubble */
    @keyframes bubbleIn {
      0% { transform: scale(0.8); opacity: 0; }
      100% { transform: scale(1); opacity: 1; }
    }
    
    /* Animation for slide up/down */
    @keyframes slideUp {
      0% { transform: translateY(20px); opacity: 0; }
      100% { transform: translateY(0); opacity: 1; }
    }
    
    @keyframes slideDown {
      0% { transform: translateY(0); opacity: 1; }
      100% { transform: translateY(20px); opacity: 0; }
    }
    
    /* Animation for fade in/out */
    @keyframes fadeIn {
      0% { opacity: 0; }
      100% { opacity: 1; }
    }
    
    @keyframes fadeOut {
      0% { opacity: 1; }
      100% { opacity: 0; }
    }
    
    /* Animation classes */
    .animate-slide-up {
      animation: slideUp 0.3s ease-out forwards;
    }
    
    .animate-slide-down {
      animation: slideDown 0.3s ease-out forwards;
    }
    
    .animate-fade-in {
      animation: fadeIn 0.3s ease-out forwards;
    }
    
    .animate-fade-out {
      animation: fadeOut 0.3s ease-out forwards;
    }

    /* Dark mode color for theme in shadow */
    .dark #assistantBtn {
      box-shadow: 0 4px 12px rgba(52, 152, 219, 0.5);
    }
    
    .dark #assistantBtn:hover {
      box-shadow: 0 6px 16px rgba(52, 152, 219, 0.6);
    }
  `;
  
  // Add the assistant container and styles to the document
  document.body.appendChild(assistantContainer);
  document.head.appendChild(styleElement);
  
  // Initialize assistant functionality
  function initAssistant() {
    // DOM Elements
    const assistantBtn = document.getElementById('assistantBtn');
    const assistantModal = document.getElementById('assistantModal');
    const modalContainer = document.getElementById('modalContainer');
    const modalHeader = document.getElementById('modalHeader');
    const modalOverlay = document.getElementById('modalOverlay');
    const closeModal = document.getElementById('closeModal');
    const userInput = document.getElementById('userInput');
    const sendButton = document.getElementById('sendButton');
    const loadingSpinner = document.getElementById('loadingSpinner');
    const sendIcon = document.getElementById('sendIcon');
    const chatContainer = document.getElementById('chatContainer');
    const suggestionButtons = document.querySelectorAll('.suggestion-btn');
    
    // Chat history array - will be saved in sessionStorage
    let chatHistory = [];


// Scroll chat to bottom
function scrollToBottom() {
  chatContainer.scrollTop = chatContainer.scrollHeight;
}
    
    // Load chat history from sessionStorage
    function loadChatHistory() {
      const savedHistory = sessionStorage.getItem('aiAssistantChatHistory');
      if (savedHistory) {
        try {
          chatHistory = JSON.parse(savedHistory);
          
          // Clear the container except for the welcome message
          while (chatContainer.children.length > 1) {
            chatContainer.removeChild(chatContainer.lastChild);
          }
          
          // Rebuild the chat from history
          chatHistory.forEach(msg => {
            addChatMessage(msg.text, msg.type, false); // false = don't save again
          });
          
          // Scroll to bottom after loading history
          setTimeout(scrollToBottom, 100);
          
        } catch (e) {
          console.error('Error loading chat history:', e);
          chatHistory = [];
        }
      }
    }
    
    // Save chat history to sessionStorage
    function saveChatHistory(text, type) {
      // Add to history array
      chatHistory.push({ text, type });
      
      // Save to sessionStorage
      try {
        sessionStorage.setItem('aiAssistantChatHistory', JSON.stringify(chatHistory));
      } catch (e) {
        console.error('Error saving chat history:', e);
      }
    }
    
    // Clear chat history
    function clearChatHistory() {
      chatHistory = [];
      sessionStorage.removeItem('aiAssistantChatHistory');
      
      // Reset chat container to only show welcome message
      while (chatContainer.children.length > 1) {
        chatContainer.removeChild(chatContainer.lastChild);
      }
    }
    
    // Add a clear chat button to the header
    const clearChatButton = document.createElement('button');
    clearChatButton.id = 'clearChat';
    clearChatButton.className = 'p-1 rounded-full hover:bg-white/20 transition-colors';
    clearChatButton.innerHTML = `
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="3 6 5 6 21 6"></polyline>
        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
      </svg>
    `;
    clearChatButton.title = "Clear chat history";
    
    // Add the clear button to the header
    const headerButtons = modalHeader.querySelector('.flex.items-center.space-x-2');
    if (headerButtons) {
      headerButtons.insertBefore(clearChatButton, headerButtons.firstChild);
    }
    
    // Add click event listener to clear chat button
    clearChatButton.addEventListener('click', () => {
      // Ask for confirmation
      if (confirm("Are you sure you want to clear the chat history?")) {
        clearChatHistory();
      }
    });
    
    // Utility function to escape HTML
    function escapeHTML(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }
    
    // Format message text
    function formatMessageText(text) {
      // Escape HTML for content
      let formattedText = escapeHTML(text);
      
      // Convert markdown-style links [text](url) to HTML links
      formattedText = formattedText.replace(
        /\[([^\]]+)\]\(([^)]+)\)/g, 
        '<a href="$2" class="text-theme-primary dark:text-theme-secondary underline hover:text-theme-primary-hover dark:hover:text-theme-primary">$1</a>'
      );
      
      // Convert URLs to clickable links
      formattedText = formattedText.replace(
        /(https?:\/\/[^\s]+)/g, 
        '<a href="$1" target="_blank" rel="noopener noreferrer" class="text-theme-primary dark:text-theme-secondary underline hover:text-theme-primary-hover dark:hover:text-theme-primary">$1</a>'
      );
      
      // Preserve line breaks
      formattedText = formattedText.replace(/\n/g, '<br>');
      
      // Bold text between asterisks
      formattedText = formattedText.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
      
      return formattedText;
    }
    
    // Toggle modal visibility
    function toggleAssistant() {
      if (assistantModal.classList.contains('hidden')) {
        assistantModal.classList.remove('hidden');
        // On mobile, slide up from bottom; on desktop, fade in
        if (window.innerWidth < 768) {
          modalContainer.classList.add('animate-slide-up');
          modalContainer.classList.remove('animate-slide-down');
        } else {
          modalContainer.classList.add('animate-fade-in');
          modalContainer.classList.remove('animate-fade-out');
        }
        // Focus the input field
        setTimeout(() => userInput.focus(), 300);
      } else {
        closeAssistant();
      }
    }
    
    // Close assistant function
    function closeAssistant() {
      if (window.innerWidth < 768) {
        modalContainer.classList.remove('animate-slide-up');
        modalContainer.classList.add('animate-slide-down');
      } else {
        modalContainer.classList.remove('animate-fade-in');
        modalContainer.classList.add('animate-fade-out');
      }
      
      // Wait for animation to complete before hiding
      setTimeout(() => {
        assistantModal.classList.add('hidden');
      }, 280);
    }
    
    // Auto-resize textarea
    function autoResizeTextarea() {
      userInput.style.height = 'auto';
      userInput.style.height = (userInput.scrollHeight) + 'px';
    }
    
    // Show typing indicator before AI response
    function showTypingIndicator() {
      const messageDiv = document.createElement('div');
      messageDiv.className = 'flex typing-indicator';
      messageDiv.id = 'typingIndicator';
      
      const html = `
        <div class="flex-shrink-0 mr-3">
          <div class="h-9 w-9 rounded-full bg-theme-primary/20 dark:bg-theme-primary/30 flex items-center justify-center text-theme-primary dark:text-theme-secondary">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"></circle>
              <polygon points="10 8 16 12 10 16 10 8"></polygon>
            </svg>
          </div>
        </div>
        <div class="bg-white dark:bg-dashboard-card-bg p-3 rounded-lg rounded-tl-none max-w-[85%] shadow-sm">
          <p class="text-sm flex items-center">
            <span class="inline-block w-2 h-2 bg-theme-primary dark:bg-theme-secondary rounded-full mr-1"></span>
            <span class="inline-block w-2 h-2 bg-theme-primary dark:bg-theme-secondary rounded-full mr-1"></span>
            <span class="inline-block w-2 h-2 bg-theme-primary dark:bg-theme-secondary rounded-full"></span>
          </p>
        </div>
      `;
      
      messageDiv.innerHTML = html;
      chatContainer.appendChild(messageDiv);
      scrollToBottom();
      return messageDiv;
    }
    
    // Send message to AI
    function sendMessage() {
      const messageText = userInput.value.trim();
      if (!messageText) return;
      
      // Disable input and button during request
      userInput.disabled = true;
      sendButton.disabled = true;
      loadingSpinner.classList.remove('hidden');
      sendIcon.classList.add('hidden');
      
      // Add student message to chat
      addChatMessage(messageText, 'student'); // This will save to history
      
      // Clear input
      userInput.value = '';
      userInput.style.height = 'auto';
      
      // Show typing indicator
      const typingIndicator = showTypingIndicator();
      
      // Scroll to bottom
      scrollToBottom();
      
      // ALWAYS use the correct path regardless of what AI_API_PATH might be set to elsewhere
      const apiPath = '/iteam-university-website/student/ai/ai_assist.php';
      
      // Send to backend with better error handling
      fetch(apiPath, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ prompt: messageText })
      })
      .then(res => {
        // Only log status in development environment
        if (window.location.hostname === 'localhost') {
          console.log('Response status:', res.status);
        }
        
        if (!res.ok) {
          // Instead of exposing the error response text to the user,
          // we'll just throw a standardized error
          throw new Error('connection_error');
        }
        return res.json();
      })
      .then(data => {
        // Remove typing indicator
        typingIndicator.remove();
        
        // Handle possible errors in the response data
        if (!data || !data.choices || !data.choices[0] || !data.choices[0].message) {
          throw new Error('invalid_response');
        }
        
        // Get response content
        const aiResponse = data.choices[0].message.content || "Sorry, I couldn't generate a response.";
        
        // Add AI response to chat
        addChatMessage(aiResponse, 'ai'); // This will save to history
      })
      .catch(err => {
        console.error('Request failed:', err);
        
        // Remove typing indicator
        typingIndicator.remove();
        
        // Provide friendly user messages based on error type
        let userMessage = '';
        
        if (err.message === 'connection_error') {
          userMessage = "I'm having trouble connecting to the server. Please check your internet connection and try again.";
        } else if (err.message === 'invalid_response') {
          userMessage = "I couldn't process your request properly. Let's try a different question.";
        } else if (err.message.includes('NetworkError') || err.message.includes('Failed to fetch')) {
          userMessage = "There seems to be a network issue. Please check your connection and try again.";
        } else if (err.message.includes('timeout')) {
          userMessage = "The request took too long to respond. Please try again later when the server might be less busy.";
        } else {
          // Generic error message that doesn't expose implementation details
          userMessage = "I encountered an unexpected problem. Please try asking again or rephrase your question.";
        }
        
        // Don't save error messages to history
        addChatMessage(userMessage, 'error', false);
      })
      .finally(() => {
        // Re-enable input and button
        userInput.disabled = false;
        sendButton.disabled = false;
        loadingSpinner.classList.add('hidden');
        sendIcon.classList.remove('hidden');
        userInput.focus();
      });
    }
    
    // Add message to chat
    function addChatMessage(text, type, saveToHistory = true) {
      const messageDiv = document.createElement('div');
      messageDiv.className = 'flex ' + (type === 'student' ? 'justify-end' : '');
      
      let htmlContent = '';
      
      if (type === 'student') {
        // Student message (right aligned)
        htmlContent = `
          <div class="bg-theme-primary/10 dark:bg-theme-primary/20 p-3 rounded-lg rounded-tr-none max-w-[85%] shadow-sm animate-bubble">
            <p class="text-sm text-dashboard-text dark:text-dashboard-text">${escapeHTML(text)}</p>
          </div>
          <div class="flex-shrink-0 ml-3">
            <div class="h-9 w-9 rounded-full bg-theme-primary dark:bg-theme-secondary flex items-center justify-center text-white">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
              </svg>
            </div>
          </div>
        `;
      } else if (type === 'ai' || type === 'error') {
        // AI or error message (left aligned)
        const bgColor = type === 'error' ? 'bg-dashboard-danger/10 dark:bg-dashboard-danger/20' : 'bg-white dark:bg-dashboard-card-bg';
        const iconColor = type === 'error' ? 
          'bg-dashboard-danger/20 dark:bg-dashboard-danger/30 text-dashboard-danger' : 
          'bg-theme-primary/20 dark:bg-theme-primary/30 text-theme-primary dark:text-theme-secondary';
        const iconSvg = type === 'error' 
          ? '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>'
          : '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polygon points="10 8 16 12 10 16 10 8"></polygon></svg>';
          
        htmlContent = `
          <div class="flex-shrink-0 mr-3">
            <div class="h-9 w-9 rounded-full ${iconColor} flex items-center justify-center">
              ${iconSvg}
            </div>
          </div>
          <div class="${bgColor} p-3 rounded-lg rounded-tl-none max-w-[85%] shadow-sm animate-bubble">
            <p class="text-sm text-dashboard-text dark:text-dashboard-text">${formatMessageText(text)}</p>
          </div>
        `;
      }
      
      messageDiv.innerHTML = htmlContent;
      chatContainer.appendChild(messageDiv);
      
      // Save to history if requested
      if (saveToHistory && (type === 'student' || type === 'ai')) {
        saveChatHistory(text, type);
      }
      
      // Scroll to bottom
      scrollToBottom();
    }

    // Modified sendMessage function to integrate with chat history
    function sendMessage() {
      const messageText = userInput.value.trim();
      if (!messageText) return;
      
      // Disable input and button during request
      userInput.disabled = true;
      sendButton.disabled = true;
      loadingSpinner.classList.remove('hidden');
      sendIcon.classList.add('hidden');
      
      // Add student message to chat
      addChatMessage(messageText, 'student'); // This will save to history
      
      // Clear input
      userInput.value = '';
      userInput.style.height = 'auto';
      
      // Show typing indicator
      const typingIndicator = showTypingIndicator();
      
      // Scroll to bottom
      scrollToBottom();
      
      // ALWAYS use the correct path regardless of what AI_API_PATH might be set to elsewhere
      const apiPath = '/iteam-university-website/student/ai/ai_assist.php';
      
      // Send to backend with better error handling
      fetch(apiPath, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ prompt: messageText })
      })
      .then(res => {
        // Only log status in development environment
        if (window.location.hostname === 'localhost') {
          console.log('Response status:', res.status);
        }
        
        if (!res.ok) {
          // Instead of exposing the error response text to the user,
          // we'll just throw a standardized error
          throw new Error('connection_error');
        }
        return res.json();
      })
      .then(data => {
        // Remove typing indicator
        typingIndicator.remove();
        
        // Handle possible errors in the response data
        if (!data || !data.choices || !data.choices[0] || !data.choices[0].message) {
          throw new Error('invalid_response');
        }
        
        // Get response content
        const aiResponse = data.choices[0].message.content || "Sorry, I couldn't generate a response.";
        
        // Add AI response to chat
        addChatMessage(aiResponse, 'ai'); // This will save to history
      })
      .catch(err => {
        console.error('Request failed:', err);
        
        // Remove typing indicator
        typingIndicator.remove();
        
        // Provide friendly user messages based on error type
        let userMessage = '';
        
        if (err.message === 'connection_error') {
          userMessage = "I'm having trouble connecting to the server. Please check your internet connection and try again.";
        } else if (err.message === 'invalid_response') {
          userMessage = "I couldn't process your request properly. Let's try a different question.";
        } else if (err.message.includes('NetworkError') || err.message.includes('Failed to fetch')) {
          userMessage = "There seems to be a network issue. Please check your connection and try again.";
        } else if (err.message.includes('timeout')) {
          userMessage = "The request took too long to respond. Please try again later when the server might be less busy.";
        } else {
          // Generic error message that doesn't expose implementation details
          userMessage = "I encountered an unexpected problem. Please try asking again or rephrase your question.";
        }
        
        // Don't save error messages to history
        addChatMessage(userMessage, 'error', false);
      })
      .finally(() => {
        // Re-enable input and button
        userInput.disabled = false;
        sendButton.disabled = false;
        loadingSpinner.classList.add('hidden');
        sendIcon.classList.remove('hidden');
        userInput.focus();
      });
    }

    // Add click event handlers to suggestion buttons
    suggestionButtons.forEach(button => {
      button.addEventListener('click', function() {
        // Set the input value to the button text
        userInput.value = this.textContent;
        
        // Immediately send the message
        sendMessage();
      });
    });

    // Add event listeners
    assistantBtn.addEventListener('click', () => {
      toggleAssistant();
      
      // Load chat history when opening the assistant
      if (!assistantModal.classList.contains('hidden')) {
        loadChatHistory();
      }
    });
    
    closeModal.addEventListener('click', closeAssistant);
    modalOverlay?.addEventListener('click', closeAssistant);
    
    // Input handling
    userInput.addEventListener('input', autoResizeTextarea);
    userInput.addEventListener('keydown', function(e) {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
      }
    });
    sendButton.addEventListener('click', sendMessage);

    // Load chat history when initializing the assistant
    loadChatHistory();
  }
  
  // Initialize once the DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAssistant);
  } else {
    initAssistant();
  }
})();