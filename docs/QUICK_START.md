# Quick Start Guide

Get up and running with the Medical Training System in 5 minutes!

## ⚡ 5-Minute Setup

### 1. Install and Configure (2 minutes)

```bash
# Clone or download the project
cd medical-training-system

# Install dependencies
npm install

# Create environment file
echo "API_URL=https://openrouter.ai/api/v1/chat/completions" > .env
echo "API_KEY=your_api_key_here" >> .env
echo "API_MODEL=anthropic/claude-3.5-sonnet" >> .env
```

**Get your API key**: Sign up at [OpenRouter.ai](https://openrouter.ai) and get your API key

### 2. Launch the Application (30 seconds)

```bash
node app.js
```

You'll see the welcome screen with available modes and commands.

### 3. Try Your First OSCE Case (2.5 minutes)

```
# In the application:
start osce          # Enter OSCE mode
stemi-001          # Select the heart attack case
```

Follow the patient interaction and complete the case!

## 🎯 Your First Case: STEMI-001

This is a 58-year-old construction worker with chest pain. Here's how to approach it:

### Step 1: History (1 minute)
```
Hello Mr. Smith, can you tell me about your chest pain?
When did it start?
Can you describe the pain?
Any other symptoms?
```

### Step 2: Examination (30 seconds)
```
I'd like to check your vital signs
Let me examine your heart and lungs
```

### Step 3: Tests (30 seconds)
```
I need an ECG immediately
Please get cardiac enzymes
```

### Step 4: Diagnosis (30 seconds)
```
Based on the ECG, this appears to be a STEMI
I recommend immediate cardiac catheterization
```

## 💡 Essential Commands

### In Chat Mode:
- `start osce` - Begin medical case training
- `help` - Show all available commands
- `stats` - View your session statistics
- `exit` - Quit with session summary

### In OSCE Mode:
- `score` - Check your current progress
- `help` - Show OSCE-specific commands
- `case info` - View case details
- `end case` - Complete and get results
- `new case` - Try another case
- `exit osce` - Return to chat mode

## 🚀 What's Next?

### Explore More Features:
- **Chat Mode**: Ask medical questions for learning
- **Multiple Cases**: Try different medical scenarios
- **Performance Tracking**: Monitor your improvement
- **System Diagnostics**: Use `system status` and `health check`

### Improve Your Skills:
1. **Practice Regularly**: Complete cases multiple times
2. **Focus on Weak Areas**: Review feedback carefully
3. **Time Management**: Aim for efficient case completion
4. **Systematic Approach**: Develop consistent routines

### Get Help:
- Type `help` anytime for context-sensitive assistance
- Check the full [User Guide](USER_GUIDE.md) for detailed instructions
- Use `stats` to track your learning progress

## 🎉 Success Tips

**For Your First Case:**
- Don't worry about perfect scores initially
- Focus on completing all sections
- Read the feedback carefully
- Try the same case again to improve

**For Ongoing Learning:**
- Set aside regular practice time
- Challenge yourself with time limits
- Review medical concepts in chat mode
- Track your progress with session stats

---

**Ready to start? Run `node app.js` and type `start osce`!**

🏥 **Happy Learning!** 📚