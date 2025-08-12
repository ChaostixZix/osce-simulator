import dotenv from 'dotenv';
import axios from 'axios';
import readline from 'readline';
import OSCEControllerWrapper from './lib/OSCEControllerWrapper.js';

dotenv.config();

// Global error handlers
process.on('uncaughtException', (error) => {
    console.error('Uncaught Exception:', error.message);
    console.error('Stack:', error.stack);
    console.error('The application will continue running, but please report this error.');
});

process.on('unhandledRejection', (reason, promise) => {
    console.error('Unhandled Rejection at:', promise, 'reason:', reason);
    console.error('The application will continue running, but please report this error.');
});

const rl = readline.createInterface({
    input: process.stdin,
    output: process.stdout
});

// Real-time keypress handling for slash command suggestions
if (process.stdin.isTTY) {
    readline.emitKeypressEvents(process.stdin);
    try {
        process.stdin.setRawMode(true);
    } catch (_) {
        // Ignore if raw mode cannot be set (e.g., non-interactive environments)
    }
}

// OSCE Controller wrapper instance
const osceController = new OSCEControllerWrapper({
    apiUrl: process.env.API_URL,
    apiKey: process.env.API_KEY,
    model: process.env.API_MODEL
});

let chatHistory = []; // Array untuk menyimpan riwayat chat
let summarizedHistory = ""; // String untuk menyimpan ringkasan percakapan sebelumnya
const MAX_HISTORY_LENGTH = 10; // Maksimal 10 pesan dalam sliding window
let osceMode = false; // Track if we're in OSCE mode

// Session management
let sessionStats = {
    startTime: new Date(),
    chatMessages: 0,
    osceSessionsCompleted: 0,
    totalOsceTime: 0,
    casesAttempted: [],
    errors: []
};

// Session tracking functions
function trackChatMessage() {
    sessionStats.chatMessages++;
}

function trackOsceSession(caseId, duration, score) {
    sessionStats.osceSessionsCompleted++;
    sessionStats.totalOsceTime += duration;
    sessionStats.casesAttempted.push({
        caseId,
        duration,
        score,
        timestamp: new Date()
    });
}

function trackError(error, context) {
    sessionStats.errors.push({
        error: error.message,
        context,
        timestamp: new Date()
    });
}

function getSessionSummary() {
    const totalTime = Math.round((new Date() - sessionStats.startTime) / 1000 / 60);
    const avgOsceTime = sessionStats.osceSessionsCompleted > 0 ?
        Math.round(sessionStats.totalOsceTime / sessionStats.osceSessionsCompleted / 1000 / 60) : 0;

    return {
        totalSessionTime: totalTime,
        chatMessages: sessionStats.chatMessages,
        osceSessionsCompleted: sessionStats.osceSessionsCompleted,
        averageOsceTime: avgOsceTime,
        casesAttempted: sessionStats.casesAttempted.length,
        uniqueCases: [...new Set(sessionStats.casesAttempted.map(c => c.caseId))].length,
        errorCount: sessionStats.errors.length
    };
}

// Fungsi untuk meringkas pesan-pesan lama
async function summarizeMessages(messages) {
    const conversationText = messages.map(msg =>
        `${msg.role}: ${msg.content}`
    ).join('\n');

    const summaryPrompt = [{
        role: "system",
        content: "Ringkas percakapan berikut dalam 2-3 kalimat, fokus pada poin-poin penting dan konteks yang relevan:"
    }, {
        role: "user",
        content: conversationText
    }];

    try {
        const response = await axios.post(process.env.API_URL, {
            "model": process.env.API_MODEL,
            "messages": summaryPrompt
        }, {
            headers: {
                "Authorization": `Bearer ${process.env.API_KEY}`,
                "HTTP-Referer": "http://localhost:3000",
                "X-Title": "My OpenRouter App",
                "Content-Type": "application/json"
            }
        });

        return response.data.choices[0].message.content;
    } catch (error) {
        console.error('Error summarizing messages:', error.message);
        return "Percakapan sebelumnya membahas berbagai topik.";
    }
}

// Fungsi untuk mengelola riwayat chat dengan sliding window + summarization
async function manageHistory() {
    if (chatHistory.length > MAX_HISTORY_LENGTH) {
        // Ambil 6 pesan tertua untuk diringkas (sisakan 4 pesan terbaru)
        const messagesToSummarize = chatHistory.slice(0, 6);

        console.log('Meringkas percakapan lama...');
        const summary = await summarizeMessages(messagesToSummarize);

        // Update summarized history
        if (summarizedHistory) {
            summarizedHistory += " " + summary;
        } else {
            summarizedHistory = summary;
        }

        // Hapus pesan yang sudah diringkas, sisakan 4 pesan terbaru
        chatHistory = chatHistory.slice(6);
    }
}

// Fungsi untuk mendapatkan context lengkap (ringkasan + recent messages)
function getFullContext() {
    const messages = [];

    // Tambahkan ringkasan jika ada
    if (summarizedHistory) {
        messages.push({
            role: "system",
            content: `Konteks percakapan sebelumnya: ${summarizedHistory}`
        });
    }

    // Tambahkan pesan-pesan terbaru
    messages.push(...chatHistory);

    return messages;
}

async function callOpenRouter(messages) {
    const maxRetries = 3;
    let retryCount = 0;

    while (retryCount < maxRetries) {
        try {
            const response = await axios.post(process.env.API_URL, {
                "model": process.env.API_MODEL,
                "messages": messages
            }, {
                headers: {
                    "Authorization": `Bearer ${process.env.API_KEY}`,
                    "HTTP-Referer": "http://localhost:3000",
                    "X-Title": "My OpenRouter App",
                    "Content-Type": "application/json"
                },
                timeout: 30000 // 30 second timeout
            });

            return response.data.choices[0].message.content;

        } catch (error) {
            retryCount++;

            // Log the error with more detail
            if (error.response) {
                console.error(`API Error (attempt ${retryCount}/${maxRetries}):`, {
                    status: error.response.status,
                    statusText: error.response.statusText,
                    data: error.response.data
                });
            } else if (error.request) {
                console.error(`Network Error (attempt ${retryCount}/${maxRetries}):`, error.message);
            } else {
                console.error(`Request Error (attempt ${retryCount}/${maxRetries}):`, error.message);
            }

            // Don't retry on client errors (4xx) except rate limiting
            if (error.response && error.response.status >= 400 && error.response.status < 500 && error.response.status !== 429) {
                console.error('Client error - not retrying');
                break;
            }

            // If this was the last retry, break
            if (retryCount >= maxRetries) {
                break;
            }

            // Wait before retrying (exponential backoff)
            const delay = Math.min(1000 * Math.pow(2, retryCount - 1), 10000);
            console.log(`Retrying in ${delay}ms...`);
            await new Promise(resolve => setTimeout(resolve, delay));
        }
    }

    return null;
}

// Display startup message with enhanced formatting
function displayStartupMessage() {
    console.clear();
    console.log('╔══════════════════════════════════════════════════════════════╗');
    console.log('║                    Medical Training System                   ║');
    console.log('║                         v1.0.0                              ║');
    console.log('╠══════════════════════════════════════════════════════════════╣');
    console.log('║                                                              ║');
    console.log('║  🗣️  Chat Mode: General conversation with AI                 ║');
    console.log('║  🏥 OSCE Mode: Structured clinical examination training      ║');
    console.log('║                                                              ║');
    console.log('║  Quick Start:                                                ║');
    console.log('║  • Type "start osce" to begin medical case training         ║');
    console.log('║  • Type any message to chat with AI                         ║');
    console.log('║  • Type "help" for detailed command reference               ║');
    console.log('║                                                              ║');
    console.log('║  System Commands:                                            ║');
    console.log('║  • "system status" - Check system health                    ║');
    console.log('║  • "health check" - Perform diagnostic check                ║');
    console.log('║  • "exit" - Quit application                                ║');
    console.log('╚══════════════════════════════════════════════════════════════╝');
    console.log(`\n🤖 AI Model: ${process.env.API_MODEL || 'Not configured'}`);
    console.log(`📍 Current Mode: Chat`);
    console.log(`📚 Available Cases: Loading...`);

    // Asynchronously load and display case count
    setTimeout(async () => {
        try {
            const controller = new OSCEControllerWrapper({
                apiUrl: process.env.API_URL,
                apiKey: process.env.API_KEY,
                model: process.env.API_MODEL
            });
            const cases = await controller.listCases();
            const caseCount = cases.split('\n').filter(line => line.includes('ID:')).length;
            process.stdout.write(`\r📚 Available Cases: ${caseCount} loaded\n`);
        } catch (error) {
            process.stdout.write(`\r📚 Available Cases: Error loading cases\n`);
        }
        console.log('═'.repeat(64));
        console.log('💡 Tip: Type "start osce" to begin your first medical case!');
        displayModeIndicator('chat');
    }, 100);
}

// Enhanced command parsing for OSCE-specific actions
function parseOSCECommand(input) {
    const inputLower = input.toLowerCase().trim();

    // Direct command mappings
    const commandMap = {
        'score': 'score',
        'progress': 'score',
        'status': 'score',
        'help': 'help',
        '?': 'help',
        'case info': 'case info',
        'info': 'case info',
        'details': 'case info',
        'end case': 'end case',
        'finish': 'end case',
        'complete': 'end case',
        'exit osce': 'exit osce',
        'quit osce': 'exit osce',
        'leave osce': 'exit osce',
        'new case': 'new case',
        'restart': 'restart',
        'list': 'list',
        'cases': 'list'
    };

    return commandMap[inputLower] || null;
}

// Format OSCE responses with enhanced display
function formatOSCEResponse(response, state) {
    let formatted = '';

    // Add session status header for active cases
    if (state && state.currentCase && !state.awaitingCaseSelection && !state.showingResults) {
        const duration = Math.round(state.sessionDuration / (1000 * 60));
        formatted += `┌─ Session Status ─────────────────────────────────────────────┐\n`;
        formatted += `│ Case: ${state.currentCase.padEnd(45)} │\n`;
        formatted += `│ Duration: ${duration} minutes${' '.repeat(37 - duration.toString().length)} │\n`;
        formatted += `└──────────────────────────────────────────────────────────────┘\n\n`;
    }

    // Format the main response
    formatted += response;

    // Add command hints based on current state
    if (state) {
        formatted += '\n\n';
        if (state.awaitingCaseSelection) {
            formatted += '💡 Tip: Type a case ID to select, "list" to see cases, "help" for commands';
        } else if (state.showingResults) {
            formatted += '💡 Tip: Type "new case" to try another case, "help" for options';
        } else if (state.currentCase) {
            formatted += '💡 Tip: Ask questions, request exams/tests, "score" for progress, "help" for commands';
        }
    }

    return formatted;
}

// Display mode indicator
function displayModeIndicator(mode, caseId = null) {
    const modeDisplay = mode === 'osce' ?
        (caseId ? `OSCE - ${caseId}` : 'OSCE') : 'Chat';

    process.stdout.write(`\r\x1b[K[${modeDisplay}] `);
}

// Ephemeral slash suggestions state and helpers
let suggestionsVisible = false;
let suggestionLinesPrinted = 0;
let suggestionClearTimer = null;

function clearSlashSuggestions() {
    if (!suggestionsVisible || suggestionLinesPrinted <= 0) return;
    // Save cursor, clear the previously printed suggestion block line-by-line, then restore
    process.stdout.write('\x1b7'); // save cursor position
    for (let i = 0; i < suggestionLinesPrinted; i++) {
        process.stdout.write(`\x1b[1A\x1b[2K`); // up 1 line, clear line
    }
    process.stdout.write('\x1b8'); // restore cursor position
    suggestionsVisible = false;
    suggestionLinesPrinted = 0;
}

// Command catalogs for suggestions
const chatCommands = [
    'help',
    'exit',
    'system status',
    'health check',
    'tutorial',
    'start osce',
    'session stats',
    'stats'
];

const osceCommands = [
    'score', 'progress', 'status', 'help', '?',
    'case info', 'end case', 'exit osce', 'new case', 'restart', 'list', 'cases'
];

function renderSlashSuggestions(isOsceMode) {
    if (suggestionsVisible) return; // Do not spam on key repeats
    const list = isOsceMode ? osceCommands : chatCommands;
    const header = isOsceMode ? 'OSCE Commands' : 'Chat/System Commands';
    const lines = list.map(cmd => `   • ${cmd}`).join('\n');
    process.stdout.write(`\n╔════════ ${header} ════════╗\n${lines}\n╚══════════════════════════════╝\n`);
    // Track how many lines we printed so we can clear them later (blank + header + items + footer)
    suggestionLinesPrinted = 1 + 1 + list.length + 1;
    suggestionsVisible = true;
    // Auto-hide after a short delay (acts like autocomplete popover)
    if (suggestionClearTimer) clearTimeout(suggestionClearTimer);
    suggestionClearTimer = setTimeout(() => {
        clearSlashSuggestions();
        // Ensure the mode indicator is still visible
        displayModeIndicator(isOsceMode ? 'osce' : 'chat');
    }, 1500);
    // Repaint mode indicator so user can keep typing
    displayModeIndicator(isOsceMode ? 'osce' : 'chat');
}

// Enhanced progress indicator
function showProgressIndicator(message = 'Processing') {
    const frames = ['⠋', '⠙', '⠹', '⠸', '⠼', '⠴', '⠦', '⠧', '⠇', '⠏'];
    let i = 0;

    const interval = setInterval(() => {
        process.stdout.write(`\r${frames[i]} ${message}...`);
        i = (i + 1) % frames.length;
    }, 100);

    return interval;
}

// Check if this is first run and offer tutorial
function checkFirstRun() {
    try {
        const fs = require('fs');
        const firstRunFile = '.first_run_complete';

        if (!fs.existsSync(firstRunFile)) {
            // First run - offer tutorial
            setTimeout(() => {
                console.log('\n🎓 Welcome to your first session!');
                console.log('💡 Would you like a quick tutorial? Type "tutorial" to start');
                console.log('   Or type "start osce" to jump right into medical case training');

                // Create the first run marker
                fs.writeFileSync(firstRunFile, new Date().toISOString());
            }, 2000);
        }
    } catch (error) {
        // Ignore errors - this is just a nice-to-have feature
    }
}

// Tutorial function
function showTutorial() {
    console.log(`
╔══════════════════════════════════════════════════════════════╗
║                    Quick Tutorial (2 minutes)               ║
╚══════════════════════════════════════════════════════════════╝

🎯 **What is this system?**
This is an AI-powered medical training platform with two modes:
• 💬 Chat Mode: Ask medical questions and get AI responses
• 🏥 OSCE Mode: Practice structured clinical cases with AI patients

🚀 **Let's try OSCE mode:**
1. Type "start osce" to enter medical training mode
2. Select "stemi-001" (a heart attack case)
3. Talk to the AI patient like a real patient
4. Get scored on your clinical performance

💡 **Example interaction:**
   You: "Hello, can you tell me about your chest pain?"
   AI Patient: "It started 2 hours ago, feels like crushing..."
   You: "I'd like to check your vital signs"
   AI Patient: "Blood pressure 160/95, heart rate 110..."

🎓 **Tips for success:**
• Be systematic: History → Examination → Tests → Diagnosis
• Ask specific questions: "When did it start?" not just "Tell me more"
• Order appropriate tests: ECG for chest pain, X-ray for breathing issues
• Provide clear diagnoses: "STEMI" not just "heart problem"

⏱️ **Time commitment:**
• Tutorial: 2 minutes (this message)
• First OSCE case: 15-20 minutes
• Chat mode: As long as you want

🎯 **Ready to start?**
Type "start osce" now to begin your first medical case!
Or type "help" anytime for detailed assistance.

═══════════════════════════════════════════════════════════════`);
}

// Initialize with enhanced startup
displayStartupMessage();
checkFirstRun();

rl.on('line', async (input) => {
    const inputTrimmed = input.trim();

    if (inputTrimmed.toLowerCase() === 'exit') {
        // Display session summary before exit
        const summary = getSessionSummary();
        console.log('\n╔══════════════════════════════════════════════════════════════╗');
        console.log('║                        Session Summary                       ║');
        console.log('╚══════════════════════════════════════════════════════════════╝');
        console.log(`📊 Total Session Time: ${summary.totalSessionTime} minutes`);
        console.log(`💬 Chat Messages: ${summary.chatMessages}`);
        console.log(`🏥 OSCE Cases Completed: ${summary.osceSessionsCompleted}`);
        if (summary.osceSessionsCompleted > 0) {
            console.log(`📚 Unique Cases Attempted: ${summary.uniqueCases}`);
            console.log(`⏱️  Average Case Time: ${summary.averageOsceTime} minutes`);
        }
        if (summary.errorCount > 0) {
            console.log(`⚠️  Errors Encountered: ${summary.errorCount}`);
        }

        // Show recent case performance if any
        if (sessionStats.casesAttempted.length > 0) {
            console.log('\n🎯 Recent Case Performance:');
            sessionStats.casesAttempted.slice(-3).forEach(attempt => {
                const duration = Math.round(attempt.duration / 1000 / 60);
                console.log(`   • ${attempt.caseId}: ${attempt.score}% (${duration}min)`);
            });
        }

        console.log('\n👋 Thank you for using the Medical Training System!');
        console.log('💡 Keep practicing to improve your clinical skills!');
        rl.close();
        return;
    }

    if (inputTrimmed.toLowerCase() === 'help' && !osceMode) {
        console.log(`
╔══════════════════════════════════════════════════════════════╗
║                        Help - Chat Mode                     ║
╠══════════════════════════════════════════════════════════════╣
║  🚀 Getting Started:                                         ║
║  • Type "start osce" to enter medical training mode         ║
║  • Type any message to have a conversation with AI          ║
║                                                              ║
║  💬 Chat Commands:                                           ║
║  • "help" - Show this help message                          ║
║  • "exit" - Quit the application                            ║
║                                                              ║
║  🔧 System Commands:                                         ║
║  • "system status" - Check system health and statistics     ║
║  • "health check" - Perform comprehensive diagnostic check  ║
║                                                              ║
║  📚 About Chat Mode:                                         ║
║  • Maintains conversation history automatically             ║
║  • Uses sliding window with summarization for long chats    ║
║  • Supports retry logic for failed requests                 ║
║                                                              ║
║  🏥 About OSCE Mode:                                         ║
║  • Structured clinical examination training                 ║
║  • AI-powered patient simulation                            ║
║  • Automated performance tracking and scoring               ║
║  • Multiple medical cases available                         ║
║                                                              ║
║  💡 Tips:                                                    ║
║  • Use clear, specific questions for better AI responses    ║
║  • Try "start osce" to experience medical case training     ║
║  • Check system status if you encounter any issues          ║
╚══════════════════════════════════════════════════════════════╝`);
        console.log('═'.repeat(64));
        return;
    }

    if (inputTrimmed.toLowerCase() === 'tutorial') {
        showTutorial();
        console.log('═'.repeat(64));
        displayModeIndicator('chat');
        return;
    }

    if (inputTrimmed.toLowerCase() === 'start osce') {
        // Switch to OSCE mode with enhanced display
        osceMode = true;
        console.log('\n🏥 Initializing OSCE Medical Training System...');

        const progressIndicator = showProgressIndicator('Loading cases');
        const response = await osceController.startOSCE();
        clearInterval(progressIndicator);
        process.stdout.write('\r\x1b[K'); // Clear progress line

        const state = await osceController.getState();
        console.log(formatOSCEResponse(response, state));
        console.log('═'.repeat(64));
        displayModeIndicator('osce');
        return;
    }

    // Session statistics command
    if (inputTrimmed.toLowerCase() === 'session stats' || inputTrimmed.toLowerCase() === 'stats') {
        const summary = getSessionSummary();
        console.log('\n╔══════════════════════════════════════════════════════════════╗');
        console.log('║                      Session Statistics                     ║');
        console.log('╚══════════════════════════════════════════════════════════════╝');
        console.log(`⏱️  Session Duration: ${summary.totalSessionTime} minutes`);
        console.log(`💬 Chat Messages Sent: ${summary.chatMessages}`);
        console.log(`🏥 OSCE Cases Completed: ${summary.osceSessionsCompleted}`);

        if (summary.osceSessionsCompleted > 0) {
            console.log(`📚 Unique Cases Attempted: ${summary.uniqueCases} of ${summary.casesAttempted}`);
            console.log(`⏱️  Average Case Duration: ${summary.averageOsceTime} minutes`);
            console.log(`📈 Total OSCE Time: ${Math.round(sessionStats.totalOsceTime / 1000 / 60)} minutes`);

            // Show performance trend
            if (sessionStats.casesAttempted.length >= 2) {
                const recent = sessionStats.casesAttempted.slice(-2);
                const trend = recent[1].score - recent[0].score;
                const trendIcon = trend > 0 ? '📈' : trend < 0 ? '📉' : '➡️';
                console.log(`${trendIcon} Performance Trend: ${trend > 0 ? '+' : ''}${trend.toFixed(1)}%`);
            }
        }

        if (summary.errorCount > 0) {
            console.log(`⚠️  Errors Encountered: ${summary.errorCount}`);
            const recentErrors = sessionStats.errors.slice(-3);
            console.log('   Recent errors:');
            recentErrors.forEach(err => {
                console.log(`   • ${err.context}: ${err.error.substring(0, 50)}...`);
            });
        }

        console.log('═'.repeat(64));
        displayModeIndicator(osceMode ? 'osce' : 'chat');
        return;
    }

    // System status and health check commands
    if (inputTrimmed.toLowerCase() === 'system status') {
        console.log('\n🔍 Checking system status...');
        try {
            const status = await osceController.getSystemStatus();
            console.log('\n╔══════════════════════════════════════════════════════════════╗');
            console.log('║                      System Status                          ║');
            console.log('╚══════════════════════════════════════════════════════════════╝');
            console.log(`\n📊 Overall Status: ${status.isActive ? '🟢 Active' : '🔴 Inactive'}`);
            console.log(`📁 Available Cases: ${status.availableCases}`);
            console.log(`⚠️  Error Count: ${status.errorCount}`);
            if (status.lastError) {
                console.log(`🚨 Last Error: ${status.lastError}`);
            }
            console.log(`⏱️  Session Duration: ${Math.round(status.sessionDuration / 1000)}s`);

            // Component status
            console.log('\n📋 Component Status:');
            console.log(`   • Case Manager: ${status.caseManagerStats.successRate.toFixed(1)}% success rate`);
            console.log(`   • Patient Simulator: ${status.patientSimulatorStats.successRate.toFixed(1)}% success rate`);
            console.log(`   • Average Response Time: ${status.patientSimulatorStats.averageResponseTime.toFixed(0)}ms`);
        } catch (error) {
            console.log(`❌ Error checking system status: ${error.message}`);
        }
        console.log('═'.repeat(64));
        displayModeIndicator(osceMode ? 'osce' : 'chat');
        return;
    }

    if (inputTrimmed.toLowerCase() === 'health check') {
        console.log('\n🏥 Performing health check...');
        try {
            const health = await osceController.performHealthCheck();
            const statusIcon = health.overall === 'healthy' ? '🟢' :
                health.overall === 'degraded' ? '🟡' : '🔴';

            console.log(`\n${statusIcon} Overall Health: ${health.overall.toUpperCase()}`);

            if (health.issues.length > 0) {
                console.log('\n⚠️  Issues Found:');
                health.issues.forEach(issue => console.log(`   • ${issue}`));

                console.log('\n💡 Recovery Suggestions:');
                const suggestions = await osceController.getErrorRecoverySuggestions();
                suggestions.forEach(suggestion => console.log(`   • ${suggestion}`));
            } else {
                console.log('\n✅ No issues detected - system is running normally');
            }
        } catch (error) {
            console.log(`❌ Error performing health check: ${error.message}`);
        }
        console.log('═'.repeat(64));
        displayModeIndicator(osceMode ? 'osce' : 'chat');
        return;
    }

    if (osceMode) {
        // Parse OSCE-specific commands
        const command = parseOSCECommand(inputTrimmed);

        if (command === 'exit osce') {
            // Exit OSCE mode with confirmation
            osceMode = false;
            osceController.reset();
            console.log('\n✅ Exited OSCE mode. Returning to chat mode.');
            console.log('═'.repeat(64));
            displayModeIndicator('chat');
            return;
        }

        // Show enhanced progress indicator
        const state = await osceController.getState();
        const progressMessage = state.awaitingCaseSelection ? 'Processing selection' :
            state.showingResults ? 'Generating results' : 'Consulting patient';

        const progressIndicator = showProgressIndicator(progressMessage);

        try {
            const response = await osceController.processUserInput(inputTrimmed);
            clearInterval(progressIndicator);
            process.stdout.write('\r\x1b[K'); // Clear progress line

            const updatedState = await osceController.getState();
            console.log(formatOSCEResponse(response, updatedState));

            // Check if case was just completed and track it
            if (updatedState.showingResults && updatedState.currentCase && updatedState.sessionDuration) {
                const score = updatedState.lastScore || 0;
                trackOsceSession(updatedState.currentCase, updatedState.sessionDuration, score);

                // Show completion celebration
                console.log('\n🎉 Case completed! Great work on your clinical skills practice.');
                console.log(`📊 Your score: ${score}% | ⏱️ Time: ${Math.round(updatedState.sessionDuration / 1000 / 60)} minutes`);

                // Suggest next actions
                console.log('\n💡 What\'s next?');
                console.log('   • Type "new case" to try another case');
                console.log('   • Type "stats" to see your session statistics');
                console.log('   • Type "exit osce" to return to chat mode');
            }

        } catch (error) {
            clearInterval(progressIndicator);
            process.stdout.write('\r\x1b[K');

            // Track error for session stats
            trackError(error, 'OSCE Mode');

            // Enhanced error handling for OSCE mode
            console.log(`❌ Error: ${error.message}`);

            // Provide recovery suggestions for common errors
            if (error.message.includes('API') || error.message.includes('network')) {
                console.log('💡 Suggestion: Check your internet connection and API credentials');
            } else if (error.message.includes('case') || error.message.includes('file')) {
                console.log('💡 Suggestion: Verify that case files are properly installed and formatted');
            }

            // Offer to check system status
            console.log('💡 Type "system status" to check system health, or "help" for assistance');
        }

        console.log('═'.repeat(64));
        const finalState = await osceController.getState();
        displayModeIndicator('osce', finalState.currentCase);

    } else {
        // Regular chat mode with enhanced display
        if (!inputTrimmed) {
            displayModeIndicator('chat');
            return;
        }

        // Track chat message for session stats
        trackChatMessage();

        // Add user message to history
        chatHistory.push({
            role: "user",
            content: inputTrimmed
        });

        // Manage chat history
        await manageHistory();

        const progressIndicator = showProgressIndicator('Thinking');

        try {
            // Get full context and call API
            const fullContext = getFullContext();
            const response = await callOpenRouter(fullContext);

            clearInterval(progressIndicator);
            process.stdout.write('\r\x1b[K');

            if (response) {
                // Add bot response to history
                chatHistory.push({
                    role: "assistant",
                    content: response
                });

                console.log(`🤖 ${response}`);
                console.log(`\n📊 History: ${chatHistory.length} messages${summarizedHistory ? ' (with summary)' : ''}`);
            } else {
                console.log('❌ Sorry, I couldn\'t process your request. Please try again.');
            }
        } catch (error) {
            clearInterval(progressIndicator);
            process.stdout.write('\r\x1b[K');

            // Track error for session stats
            trackError(error, 'Chat Mode');

            // Enhanced error handling for chat mode
            console.log(`❌ Error: ${error.message}`);

            // Provide helpful suggestions
            if (error.code === 'ETIMEDOUT') {
                console.log('💡 The request timed out. Please try again.');
            } else if (error.response?.status === 429) {
                console.log('💡 Rate limit exceeded. Please wait a moment before trying again.');
            } else if (error.response?.status >= 500) {
                console.log('💡 The AI service is temporarily unavailable. Please try again later.');
            } else {
                console.log('💡 Please try rephrasing your message or check your internet connection.');
            }
        }

        console.log('═'.repeat(64));
        displayModeIndicator('chat');
    }
});

// Listen for real-time keypress to trigger slash suggestions without Enter
if (process.stdin.isTTY) {
    process.stdin.on('keypress', (str, key) => {
        if (!key) return;
        // Trigger when actual '/' character is typed
        if (str === '/') {
            renderSlashSuggestions(osceMode);
            return; // prevent repeated processing for the same keypress
        }

        // Allow exiting raw mode gracefully on Ctrl+C
        if (key.sequence === '\u0003') { // Ctrl+C
            rl.close();
            return;
        }

        // On any other keypress, hide suggestions if visible (behaves like autocomplete)
        if (suggestionsVisible) {
            clearSlashSuggestions();
            displayModeIndicator(osceMode ? 'osce' : 'chat');
        }
    });
}