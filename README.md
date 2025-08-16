# medical training system

a comprehensive medical education platform that combines ai-powered chat capabilities with structured osce (objective structured clinical examination) training for medical students.

## 🚀 quick start

1. **install dependencies**
   ```bash
   npm install
   ```

2. **configure environment**
   create a `.env` file with your openrouter api credentials:
   ```env
   API_URL=https://openrouter.ai/api/v1/chat/completions
   API_KEY=your_openrouter_api_key_here
   API_MODEL=anthropic/claude-3.5-sonnet
   ```

3. **run the application**
   ```bash
   node app.js
   ```

4. **start training**
   - type `start osce` to begin medical case training
   - type any message to chat with ai
   - type `help` for detailed commands

## 🏥 features

### chat mode
- **ai-powered conversations**: natural language interaction with advanced ai models
- **conversation memory**: automatic history management with intelligent summarization
- **error recovery**: robust retry logic and error handling
- **session tracking**: monitor your chat activity and statistics

### osce mode
- **structured clinical cases**: practice with realistic medical scenarios
- **ai patient simulation**: interactive patient responses based on case data
- **performance tracking**: automated checklist monitoring and progress tracking
- **intelligent scoring**: weighted scoring system with detailed feedback
- **multiple cases**: various medical conditions and specialties

## 📚 available cases

### stemi-001: acute coronary syndrome
- **scenario**: 58-year-old male with acute chest pain
- **focus**: emergency cardiology, ecg interpretation, time-critical management
- **skills practiced**: history taking, physical examination, diagnostic reasoning
- **learning objectives**: stemi recognition, emergency protocols, risk stratification

*more cases available in the `cases/` directory*

## 🎯 how to use osce mode

### starting a case
1. type `start osce` to enter osce mode
2. select a case from the available list
3. read the chief complaint and begin your examination

### interacting with the patient
- **ask questions**: "can you describe your chest pain?"
- **request examinations**: "i'd like to check your vital signs"
- **order tests**: "please get an ecg" or "i need cardiac enzymes"
- **provide diagnosis**: "i think this is a stemi"

### osce commands
- `score` or `progress` - check current performance
- `case info` - view case details and objectives
- `help` - show osce-specific commands
- `end case` - complete the case and get results
- `new case` - start a different case
- `exit osce` - return to chat mode

### understanding your score
- **history taking (30%)**: comprehensive patient interview
- **physical examination (20%)**: appropriate clinical examinations
- **investigations (25%)**: ordering relevant tests and interpreting results
- **diagnosis (15%)**: accurate diagnostic reasoning
- **management (10%)**: appropriate treatment decisions

## 💬 chat mode commands

### basic commands
- `help` - show comprehensive help information
- `exit` - quit the application with session summary
- `stats` - view current session statistics

### system commands
- `system status` - check application health and performance
- `health check` - perform comprehensive diagnostic check

## 📊 session tracking

the system automatically tracks your learning progress:

- **chat messages**: number of ai interactions
- **osce sessions**: completed medical cases
- **performance trends**: score improvements over time
- **time management**: session duration and case completion times
- **error monitoring**: system issues and recovery suggestions

## 🔧 system requirements

- **node.js**: version 16 or higher
- **internet connection**: required for ai api access
- **openrouter api key**: for ai model access
- **terminal/command line**: for running the application

## 📁 project structure

```
medical-training-system/
├── app.js                 # main application entry point
├── lib/                   # core application modules
│   ├── OSCEController.js  # main osce logic controller
│   ├── CaseManager.js     # case loading and management
│   ├── PatientSimulator.js # ai patient simulation
│   ├── PerformanceTracker.js # progress tracking
│   ├── ScoringEngine.js   # assessment and feedback
│   └── ErrorHandler.js    # error management
├── cases/                 # medical case files
│   ├── case-schema.json   # case validation schema
│   ├── stemi-001.json     # sample stemi case
│   └── README.md          # case documentation
├── test/                  # test files
├── utils/                 # utility functions
└── docs/                  # additional documentation
```

## 🧪 testing

run the test suite to verify system functionality:

```bash
# run all tests
npm test

# run tests in watch mode
npm run test:watch

# run specific test categories
npm test -- --grep "osce"
npm test -- --grep "integration"
```

## ⚡ real-time slash suggestions

- in any prompt, press `/` to see context-aware command suggestions.
- the suggestions appear inline at the cursor and disappear on the next keypress.
- in chat mode, you'll see chat/system commands; in osce mode, osce-specific commands.
- works only in a tty terminal (raw mode enabled). if your environment doesn't behave as a tty, run with:
  ```bash
  script -q /dev/null npm start
  ```

## 🔍 troubleshooting

### common issues

**api connection errors**
- verify your `.env` file contains valid api credentials
- check your internet connection
- ensure the api_url is correct

**case loading errors**
- verify case files are properly formatted json
- check that `cases/` directory contains valid case files
- run case validation: `node utils/caseValidator.js`

**performance issues**
- use `system status` to check system health
- run `health check` for comprehensive diagnostics
- monitor session stats with `stats` command

### getting help

1. **in-app help**: type `help` for context-sensitive assistance
2. **system diagnostics**: use `system status` and `health check`
3. **session information**: check `stats` for current session data
4. **error recovery**: follow suggested recovery actions for errors

## 🎓 educational benefits

### for medical students
- **realistic practice**: interact with ai patients in structured scenarios
- **immediate feedback**: get instant performance assessment and learning points
- **flexible learning**: practice anytime, anywhere with various case types
- **progress tracking**: monitor improvement over time with detailed analytics

### for medical educators
- **standardized assessment**: consistent evaluation criteria across all students
- **comprehensive coverage**: cases cover essential clinical skills and knowledge
- **performance analytics**: track student progress and identify learning gaps
- **scalable training**: support multiple students with automated assessment

## 🔮 future enhancements

- **additional medical cases**: expand case library with more specialties
- **multiplayer mode**: collaborative case solving with peers
- **advanced analytics**: detailed performance insights and recommendations
- **mobile support**: web-based interface for mobile devices
- **integration**: lms integration for educational institutions

## 📄 license

this project is licensed under the isc license. see the license file for details.

## 🤝 contributing

we welcome contributions to improve the medical training system:

1. fork the repository
2. create a feature branch
3. make your changes
4. add tests for new functionality
5. submit a pull request

## 📞 support

for technical support or questions:
- check the troubleshooting section above
- use in-app diagnostic commands
- review the documentation in the `docs/` directory
- submit issues for bugs or feature requests

---

**happy learning! 🏥📚**

*improve your clinical skills with ai-powered medical training.*