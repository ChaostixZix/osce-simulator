# OSCE AI Patient Chat System

## Overview

The OSCE AI Patient Chat System is a comprehensive medical training platform that allows medical students and professionals to practice clinical skills through realistic AI-powered patient simulations. This system integrates with Google's Gemini AI to provide intelligent, context-aware patient responses during Objective Structured Clinical Examination (OSCE) training sessions.

## Features

### 🏥 AI Patient Simulation
- **Realistic Patient Responses**: AI patients respond naturally to medical questions based on their case profile
- **Context-Aware Conversations**: AI maintains conversation context and patient state throughout the session
- **Medical Case Profiles**: Each OSCE case includes detailed patient information, symptoms, and vital signs
- **Dynamic Interactions**: Patients respond differently based on the type of questions asked

### 📚 OSCE Case Management
- **Multiple Difficulty Levels**: Easy, medium, and hard cases for different skill levels
- **Structured Learning**: Each case includes objectives, stations, and checklists
- **Session Tracking**: Monitor progress and performance across different cases
- **Real-time Chat**: Interactive chat interface for patient-doctor communication

### 🔒 Security & Privacy
- **User Authentication**: Secure access to OSCE sessions
- **Session Isolation**: Users can only access their own sessions
- **Data Privacy**: All chat data is stored securely and privately

## Setup Instructions

### 1. Environment Configuration

Add the following environment variables to your `.env` file:

```bash
# AI Patient Service Configuration
GEMINI_API_KEY=your_gemini_api_key_here
GEMINI_MODEL=gemini-1.5-flash
```

### 2. Get Gemini API Key

1. Visit [Google AI Studio](https://makersuite.google.com/app/apikey)
2. Create a new API key
3. Copy the API key to your `.env` file

### 3. Database Setup

Run the migrations to create the necessary database tables:

```bash
php artisan migrate
```

### 4. Seed Sample Data

Populate the database with sample OSCE cases:

```bash
php artisan db:seed --class=OsceCaseSeeder
```

### 5. Build Frontend

Compile the Vue.js frontend:

```bash
npm run build
```

## Usage Guide

### Starting an OSCE Session

1. Navigate to `/osce` in your application
2. Browse available OSCE cases
3. Click "Start Case" on any case you want to practice
4. The system will create a new session and redirect you to the chat interface

### Chatting with AI Patients

1. **Session Overview**: View case information, patient profile, and objectives
2. **Patient Information**: Access patient demographics, symptoms, and vital signs
3. **Interactive Chat**: Ask questions and receive realistic patient responses
4. **Context Awareness**: AI patients remember previous conversations and maintain consistency

### Available OSCE Cases

The system comes with several pre-configured cases:

#### 1. Cardiopulmonary Resuscitation (CPR)
- **Difficulty**: Medium
- **Duration**: 15 minutes
- **Focus**: Basic life support, chest compressions, airway management
- **Patient**: 55-year-old male, unresponsive, cardiac arrest

#### 2. Asthma Exacerbation Management
- **Difficulty**: Hard
- **Duration**: 20 minutes
- **Focus**: Respiratory assessment, medication administration, patient education
- **Patient**: 12-year-old female, severe shortness of breath, wheezing

#### 3. Blood Pressure Measurement
- **Difficulty**: Easy
- **Duration**: 10 minutes
- **Focus**: Proper technique, patient positioning, documentation
- **Patient**: 45-year-old female, routine check-up, mild anxiety

#### 4. Chest Pain Assessment
- **Difficulty**: Hard
- **Duration**: 25 minutes
- **Focus**: Pain assessment, cardiac evaluation, emergency management
- **Patient**: 62-year-old male, acute chest pain, possible MI

## Technical Architecture

### Backend Components

#### Models
- **OsceCase**: Stores case information and AI patient data
- **OsceSession**: Manages user sessions and progress
- **OsceChatMessage**: Stores chat conversation history

#### Services
- **AiPatientService**: Handles AI patient response generation using Gemini API
- **Context Management**: Maintains conversation context and patient state
- **Fallback Responses**: Provides realistic responses when AI is unavailable

#### Controllers
- **OsceController**: Manages OSCE cases and sessions
- **OsceChatController**: Handles chat functionality and AI interactions

### Frontend Components

#### Pages
- **Osce.vue**: Main OSCE dashboard with available cases
- **OsceChat.vue**: Interactive chat interface with AI patients

#### Features
- **Real-time Chat**: Live conversation with AI patients
- **Case Information**: Display of patient profiles and medical data
- **Responsive Design**: Works on desktop and mobile devices
- **Dark Mode Support**: Consistent with application theme

## API Endpoints

### OSCE Management
- `GET /osce` - OSCE dashboard
- `GET /osce/chat/{session}` - Chat interface for specific session
- `GET /api/osce/cases` - List available cases
- `GET /api/osce/sessions` - User's OSCE sessions
- `POST /api/osce/sessions/start` - Start new OSCE session

### Chat Functionality
- `POST /api/osce/chat/start` - Initialize chat for a session
- `POST /api/osce/chat/message` - Send message to AI patient
- `GET /api/osce/chat/history/{session_id}` - Retrieve chat history

## Customization

### Adding New OSCE Cases

1. **Database Seeding**: Add new cases to `OsceCaseSeeder.php`
2. **Patient Profiles**: Define realistic patient characteristics
3. **AI Instructions**: Specify how the AI should behave
4. **Response Templates**: Create fallback responses for common scenarios

### Modifying AI Patient Behavior

1. **Prompt Engineering**: Adjust prompts in `AiPatientService.php`
2. **Context Rules**: Modify how patient context is maintained
3. **Response Patterns**: Customize fallback response logic

### Styling and UI

1. **Component Customization**: Modify Vue components in `resources/js/pages/`
2. **Theme Integration**: Update Tailwind CSS classes for consistent styling
3. **Responsive Design**: Ensure mobile-friendly interface

## Troubleshooting

### Common Issues

#### AI Service Not Responding
- Check if `GEMINI_API_KEY` is set correctly
- Verify internet connectivity
- Check Laravel logs for API errors

#### Chat Not Loading
- Ensure database migrations are run
- Check if OSCE cases are seeded
- Verify user authentication

#### Session Access Issues
- Confirm user owns the session
- Check session status (must be 'in_progress')
- Verify route permissions

### Debug Mode

Enable debug mode in `.env` to see detailed error messages:

```bash
APP_DEBUG=true
```

## Performance Considerations

### Database Optimization
- Indexes on frequently queried fields
- Efficient relationship loading
- Chat message pagination for long conversations

### AI Service Optimization
- Response caching for common questions
- Rate limiting to prevent API abuse
- Fallback responses for offline scenarios

### Frontend Performance
- Lazy loading of chat history
- Optimized bundle size
- Efficient state management

## Security Features

### Authentication
- User session validation
- Route-level access control
- CSRF protection for all forms

### Data Privacy
- User isolation for sessions
- Secure API endpoints
- Input validation and sanitization

### AI Safety
- Content filtering for inappropriate responses
- Medical accuracy validation
- Ethical AI guidelines compliance

## Future Enhancements

### Planned Features
- **Physical Exam Simulation**: Virtual patient examination
- **Lab Results Integration**: Dynamic lab values and imaging
- **Treatment Planning**: AI-assisted care plan development
- **Performance Analytics**: Detailed session analysis and feedback
- **Multi-language Support**: International medical training

### AI Improvements
- **Advanced Context Management**: Better conversation memory
- **Medical Knowledge Updates**: Regular AI model updates
- **Custom Patient Types**: Specialized case scenarios
- **Voice Integration**: Speech-to-text and text-to-speech

## Support and Contributing

### Getting Help
- Check the Laravel logs for error details
- Review the test files for usage examples
- Consult the Laravel documentation for framework-specific issues

### Contributing
1. Follow the existing code style and patterns
2. Add tests for new functionality
3. Update documentation for any changes
4. Ensure backward compatibility

## License

This project is part of the OSCE Medical Training System. Please refer to the main project license for usage terms and conditions.

---

**Note**: This system is designed for educational purposes and should not replace actual medical training or clinical practice. Always follow proper medical protocols and guidelines in real-world scenarios.
