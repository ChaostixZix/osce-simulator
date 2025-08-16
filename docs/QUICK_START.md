# quick start guide

get up and running with the medical training system in 5 minutes!

## ⚡ 5-minute setup

### 1. install and configure (2 minutes)

```bash
# clone or download the project
cd medical-training-system

# install dependencies
npm install

# create environment file
echo "API_URL=https://openrouter.ai/api/v1/chat/completions" > .env
echo "API_KEY=your_api_key_here" >> .env
echo "API_MODEL=anthropic/claude-3.5-sonnet" >> .env
```

**get your api key**: sign up at [openrouter.ai](https://openrouter.ai) and get your api key

### 2. launch the application (30 seconds)

```bash
node app.js
```

you'll see the welcome screen with available modes and commands.

### 3. try your first osce case (2.5 minutes)

```
# in the application:
start osce          # enter osce mode
stemi-001          # select the heart attack case
```

follow the patient interaction and complete the case!

## 🎯 your first case: stemi-001

this is a 58-year-old construction worker with chest pain. here's how to approach it:

### step 1: history (1 minute)
```
hello mr. smith, can you tell me about your chest pain?
when did it start?
can you describe the pain?
any other symptoms?
```

### step 2: examination (30 seconds)
```
i'd like to check your vital signs
let me examine your heart and lungs
```

### step 3: tests (30 seconds)
```
i need an ecg immediately
please get cardiac enzymes
```

### step 4: diagnosis (30 seconds)
```
based on the ecg, this appears to be a stemi
i recommend immediate cardiac catheterization
```

## 💡 essential commands

### in chat mode:
- `start osce` - begin medical case training
- `help` - show all available commands
- `stats` - view your session statistics
- `exit` - quit with session summary

### in osce mode:
- `score` - check your current progress
- `help` - show osce-specific commands
- `case info` - view case details
- `end case` - complete and get results
- `new case` - try another case
- `exit osce` - return to chat mode

## 🚀 what's next?

### explore more features:
- **chat mode**: ask medical questions for learning
- **multiple cases**: try different medical scenarios
- **performance tracking**: monitor your improvement
- **system diagnostics**: use `system status` and `health check`

### improve your skills:
1. **practice regularly**: complete cases multiple times
2. **focus on weak areas**: review feedback carefully
3. **time management**: aim for efficient case completion
4. **systematic approach**: develop consistent routines

### get help:
- type `help` anytime for context-sensitive assistance
- check the full [user guide](USER_GUIDE.md) for detailed instructions
- use `stats` to track your learning progress

## 🎉 success tips

**for your first case:**
- don't worry about perfect scores initially
- focus on completing all sections
- read the feedback carefully
- try the same case again to improve

**for ongoing learning:**
- set aside regular practice time
- challenge yourself with time limits
- review medical concepts in chat mode
- track your progress with session stats

---

**ready to start? run `node app.js` and type `start osce`!**

🏥 **happy learning!** 📚