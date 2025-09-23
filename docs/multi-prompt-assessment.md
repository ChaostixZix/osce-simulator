# Multi-Prompt Assessment System Implementation

## Overview

The multi-prompt assessment system breaks down clinical area assessments into specific aspects, allowing for more detailed and focused AI evaluation. This approach addresses token limit issues and provides deeper analysis of each performance aspect.

## Key Benefits

1. **No Token Limit Issues** - Each aspect is assessed separately
2. **Deeper Analysis** - AI can focus on specific aspects with more detail
3. **Better Debugging** - Easy to trace errors per aspect
4. **Clearer Criteria** - Defined thresholds for acceptable/good performance
5. **Flexible** - Can update prompts per aspect without affecting others
6. **Detailed Feedback** - Students receive specific feedback per aspect

## Architecture

### Clinical Areas and Aspects

The system assesses 7 clinical areas, each broken down into 3 aspects:

1. **History-Taking (20 points)**
   - Systematic Approach (7 pts)
   - Question Quality (6 pts)
   - Thoroughness (7 pts)

2. **Physical Examination (15 points)**
   - Technique (5 pts)
   - Systematic Approach (5 pts)
   - Critical Exams (5 pts)

3. **Investigations (20 points)**
   - Appropriateness (7 pts)
   - Cost Effectiveness (6 pts)
   - Sequencing (7 pts)

4. **Differential Diagnosis (15 points)**
   - Breadth (5 pts)
   - Reasoning (5 pts)
   - Prioritization (5 pts)

5. **Management (15 points)**
   - Immediate Actions (5 pts)
   - Treatment Plan (5 pts)
   - Follow-up (5 pts)

6. **Communication Skills (10 points)**
   - Clarity (3 pts)
   - Empathy (4 pts)
   - Professionalism (3 pts)

7. **Safety & Professionalism (10 points)**
   - Error Prevention (4 pts)
   - Time Management (3 pts)
   - Documentation (3 pts)

### Performance Levels

- **Good Performance**: 80-100%
  - History: Covers 80%+ of key points, efficient and structured
  - Exam: Performs 80%+ of critical exams with proper technique
  - Investigations: 80%+ appropriate and cost-effective tests

- **Acceptable Performance**: 60-79%
  - History: Covers 60% of key points with relevant questions
  - Exam: Performs 60% of critical exams with basic technique
  - Investigations: 60% appropriately indicated tests

## Implementation Components

### 1. AssessmentPromptManager
- **Location**: `app/Services/AssessmentPromptManager.php`
- **Purpose**: Manages prompt templates for each aspect
- **Features**:
  - Dedicated prompts for each aspect
  - Configurable scoring criteria
  - Performance level calculation
  - Detailed feedback generation

### 2. MultiPromptAreaAssessor
- **Location**: `app/Services/MultiPromptAreaAssessor.php`
- **Purpose**: Executes sequential assessment of aspects
- **Features**:
  - Assesses each aspect independently
  - JSON validation with repair
  - Fallback scoring for failed assessments
  - Aspect result persistence

### 3. Database Schema Updates
- **New Table**: `ai_assessment_aspect_results`
  - Stores individual aspect results
  - Links to area results
  - Performance levels and feedback

- **Enhanced Table**: `ai_assessment_area_results`
  - Added `aspect_breakdown` (JSON)
  - Added `overall_performance_level`
  - Added `detailed_feedback`
  - Added threshold tracking

### 4. Job Queue Updates
- **New Job**: `MultiPromptAssessAreaJob`
  - Handles aspect-level assessments
  - Configurable to use multi-prompt or legacy
  - Better error isolation

### 5. Configuration
- **File**: `config/multi_prompt_assessment.php`
- **Settings**:
  - Enable/disable multi-prompt assessment
  - Aspect assessment configuration
  - Performance thresholds
  - Fallback settings

## Usage

### Enable Multi-Prompt Assessment

Set in `.env` file:
```
USE_MULTI_PROMPT_ASSESSMENT=true
```

### Configuration Options

```php
// config/multi_prompt_assessment.php
return [
    'use_multi_prompt' => env('USE_MULTI_PROMPT_ASSESSMENT', true),
    
    'aspects' => [
        'enable_detailed_feedback' => true,
        'save_aspect_results' => true,
        'max_aspect_retries' => 2,
        'aspect_timeout' => 60,
    ],
    
    'thresholds' => [
        'acceptable' => 60,
        'good' => 80,
    ],
    
    'fallback' => [
        'enabled' => true,
        'max_total_retries' => 3,
        'use_rubric_on_ai_failure' => true,
    ]
];
```

### Assessment Flow

1. **Orchestration**: `AiAssessorOrchestrator` creates assessment run
2. **Fan-out**: Dispatches `MultiPromptAssessAreaJob` for each clinical area
3. **Aspect Assessment**: Each job assesses 3 aspects sequentially
4. **Result Aggregation**: `ResultReducer` combines aspect results
5. **Final Report**: Enhanced results with aspect breakdown

### Frontend Integration

The assessment results now include:
- `aspect_breakdown`: Detailed aspect scores and feedback
- `performance_level`: Overall performance (acceptable/good)
- `detailed_feedback`: Comprehensive feedback summary
- `aspect_results`: Array of individual aspect results with:
  - Aspect name and score
  - Performance level badge
  - Specific feedback
  - Evidence citations

## Migration from Legacy System

### Backward Compatibility
- Legacy assessments still work
- `ResultReducer` provides proportional aspect breakdown for legacy results
- Frontend can detect `is_multi_prompt` flag

### Data Migration
- No automatic migration needed
- New assessments use multi-prompt automatically when enabled
- Legacy assessments remain unchanged

## Monitoring and Debugging

### Logs
Each aspect assessment generates detailed logs:
- Aspect start/completion
- Success/failure with reasons
- Retry attempts
- Fallback usage

### Database Tracking
- `ai_assessment_area_results.telemetry` tracks:
  - Assessment method used
  - Number of aspects assessed
  - Performance distribution
  - Processing details

### Error Handling
- Aspect failures don't fail entire assessment
- Automatic fallback to rubric scoring
- Detailed error messages for debugging

## Performance Considerations

### Benefits
- More reliable (no token limits)
- Faster per-request processing
- Parallel aspect assessment possible
- Better error isolation

### Trade-offs
- More API calls (3 per clinical area)
- Increased database storage
- Slightly higher processing time

## Future Enhancements

1. **Parallel Aspect Processing** - Assess aspects concurrently
2. **Dynamic Prompt Selection** - Choose prompts based on case complexity
3. **Machine Learning Integration** - Improve prompts from assessment data
4. **Custom Aspect Weights** - Configure aspect importance per case
5. **Real-time Aspect Feedback** - Provide feedback during assessment