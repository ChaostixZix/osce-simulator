# Medical Training System

A comprehensive medical education platform that combines AI-powered chat capabilities with structured OSCE (Objective Structured Clinical Examination) training for medical students.

## 🚀 Quick Start

1. **Install Dependencies**
   ```bash
   npm install
   ```

2. **Configure Environment**
   Create a `.env` file with your OpenRouter API credentials:
   ```env
   API_URL=https://openrouter.ai/api/v1/chat/completions
   API_KEY=your_openrouter_api_key_here
   API_MODEL=anthropic/claude-3.5-sonnet
   ```

3. **Run the Application**
   ```bash
   node app.js
   ```

4. **Start Training**
   - Type `start osce` to begin medical case training
   - Type any message to chat with AI
   - Type `help` for detailed commands

## 🏥 Features

### Chat Mode
- **AI-Powered Conversations**: Natural language interaction with advanced AI models
- **Conversation Memory**: Automatic history management with intelligent summarization
- **Error Recovery**: Robust retry logic and error handling
- **Session Tracking**: Monitor your chat activity and statistics

### OSCE Mode
- **Structured Clinical Cases**: Practice with realistic medical scenarios
- **AI Patient Simulation**: Interactive patient responses based on case data
- **Performance Tracking**: Automated checklist monitoring and progress tracking
- **Intelligent Scoring**: Weighted scoring system with detailed feedback
- **Multiple Cases**: Various medical conditions and specialties

## 📚 Available Cases

### STEMI-001: Acute Coronary Syndrome
- **Scenario**: 58-year-old male with acute chest pain
- **Focus**: Emergency cardiology, ECG interpretation, time-critical management
- **Skills Practiced**: History taking, physical examination, diagnostic reasoning
- **Learning Objectives**: STEMI recognition, emergency protocols, risk stratification

*More cases available in the `cases/` directory*

## 🎯 How to Use OSCE Mode

### Starting a Case
1. Type `start osce` to enter OSCE mode
2. Select a case from the available list
3. Read the chief complaint and begin your examination

### Interacting with the Patient
- **Ask Questions**: "Can you describe your chest pain?"
- **Request Examinations**: "I'd like to check your vital signs"
- **Order Tests**: "Please get an ECG" or "I need cardiac enzymes"
- **Provide Diagnosis**: "I think this is a STEMI"

### OSCE Commands
- `score` or `progress` - Check current performance
- `case info` - View case details and objectives
- `help` - Show OSCE-specific commands
- `end case` - Complete the case and get results
- `new case` - Start a different case
- `exit osce` - Return to chat mode

### Understanding Your Score
- **History Taking (30%)**: Comprehensive patient interview
- **Physical Examination (20%)**: Appropriate clinical examinations
- **Investigations (25%)**: Ordering relevant tests and interpreting results
- **Diagnosis (15%)**: Accurate diagnostic reasoning
- **Management (10%)**: Appropriate treatment decisions

## 💬 Chat Mode Commands

### Basic Commands
- `help` - Show comprehensive help information
- `exit` - Quit the application with session summary
- `stats` - View current session statistics

### System Commands
- `system status` - Check application health and performance
- `health check` - Perform comprehensive diagnostic check

## 📊 Session Tracking

The system automatically tracks your learning progress:

- **Chat Messages**: Number of AI interactions
- **OSCE Sessions**: Completed medical cases
- **Performance Trends**: Score improvements over time
- **Time Management**: Session duration and case completion times
- **Error Monitoring**: System issues and recovery suggestions

## 🔧 System Requirements

- **Node.js**: Version 16 or higher
- **Internet Connection**: Required for AI API access
- **OpenRouter API Key**: For AI model access
- **Terminal/Command Line**: For running the application

## 📁 Project Structure

```
medical-training-system/
├── app.js                 # Main application entry point
├── lib/                   # Core application modules
│   ├── OSCEController.js  # Main OSCE logic controller
│   ├── CaseManager.js     # Case loading and management
│   ├── PatientSimulator.js # AI patient simulation
│   ├── PerformanceTracker.js # Progress tracking
│   ├── ScoringEngine.js   # Assessment and feedback
│   └── ErrorHandler.js    # Error management
├── cases/                 # Medical case files
│   ├── case-schema.json   # Case validation schema
│   ├── stemi-001.json     # Sample STEMI case
│   └── README.md          # Case documentation
├── test/                  # Test files
├── utils/                 # Utility functions
└── docs/                  # Additional documentation
```

## 🧪 Testing

Run the test suite to verify system functionality:

```bash
# Run all tests
npm test

# Run tests in watch mode
npm run test:watch

# Run specific test categories
npm test -- --grep "OSCE"
npm test -- --grep "Integration"
```

## ⚡ Real-time Slash Suggestions

- In any prompt, press `/` to see context-aware command suggestions.
- The suggestions appear inline at the cursor and disappear on the next keypress.
- In Chat mode, you’ll see chat/system commands; in OSCE mode, OSCE-specific commands.
- Works only in a TTY terminal (raw mode enabled). If your environment doesn’t behave as a TTY, run with:
  ```bash
  script -q /dev/null npm start
  ```

## 🔍 Troubleshooting

### Common Issues

**API Connection Errors**
- Verify your `.env` file contains valid API credentials
- Check your internet connection
- Ensure the API_URL is correct

**Case Loading Errors**
- Verify case files are properly formatted JSON
- Check that `cases/` directory contains valid case files
- Run case validation: `node utils/caseValidator.js`

**Performance Issues**
- Use `system status` to check system health
- Run `health check` for comprehensive diagnostics
- Monitor session stats with `stats` command

### Getting Help

1. **In-App Help**: Type `help` for context-sensitive assistance
2. **System Diagnostics**: Use `system status` and `health check`
3. **Session Information**: Check `stats` for current session data
4. **Error Recovery**: Follow suggested recovery actions for errors

## 🎓 Educational Benefits

### For Medical Students
- **Realistic Practice**: Interact with AI patients in structured scenarios
- **Immediate Feedback**: Get instant performance assessment and learning points
- **Flexible Learning**: Practice anytime, anywhere with various case types
- **Progress Tracking**: Monitor improvement over time with detailed analytics

### For Medical Educators
- **Standardized Assessment**: Consistent evaluation criteria across all students
- **Comprehensive Coverage**: Cases cover essential clinical skills and knowledge
- **Performance Analytics**: Track student progress and identify learning gaps
- **Scalable Training**: Support multiple students with automated assessment

## 🔮 Future Enhancements

- **Additional Medical Cases**: Expand case library with more specialties
- **Multiplayer Mode**: Collaborative case solving with peers
- **Advanced Analytics**: Detailed performance insights and recommendations
- **Mobile Support**: Web-based interface for mobile devices
- **Integration**: LMS integration for educational institutions

## 📄 License

This project is licensed under the ISC License. See the LICENSE file for details.

## 🤝 Contributing

We welcome contributions to improve the Medical Training System:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Submit a pull request

## 📞 Support

For technical support or questions:
- Check the troubleshooting section above
- Use in-app diagnostic commands
- Review the documentation in the `docs/` directory
- Submit issues for bugs or feature requests

---

**Happy Learning! 🏥📚**

*Improve your clinical skills with AI-powered medical training.*