# MCQ Component

A reusable Multiple Choice Question component built with Vue 3, TypeScript, and Tailwind CSS.

## Features

- ✅ **Reusable & Customizable**: Easy to use with different data structures
- ✅ **Multiple Variants**: Different visual styles (default, card, outline, ghost)
- ✅ **Responsive Sizes**: Small, default, large, and extra-large text sizes
- ✅ **Interactive**: Click to select answers with visual feedback
- ✅ **Accessible**: Proper ARIA labels and keyboard navigation
- ✅ **Event Handling**: Emits events when answers are selected
- ✅ **Explanation Support**: Optional explanations for answers
- ✅ **Reset Functionality**: Built-in reset button for multiple attempts

## Basic Usage

```vue
<template>
  <MCQ :data="mcqData" />
</template>

<script setup>
import { MCQ } from '@/components/ui/mcq'

const mcqData = {
  question: "What is the capital of France?",
  options: [
    { id: 'a', text: 'London', value: 'a' },
    { id: 'b', text: 'Berlin', value: 'b' },
    { id: 'c', text: 'Paris', value: 'c' },
    { id: 'd', text: 'Madrid', value: 'd' }
  ],
  correctAnswer: 'c',
  explanation: 'Paris is the capital and largest city of France.'
}
</script>
```

## Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `data` | `MCQData` | **Required** | The MCQ data object |
| `variant` | `'default' \| 'card' \| 'outline' \| 'ghost'` | `'default'` | Visual style variant |
| `size` | `'sm' \| 'default' \| 'lg' \| 'xl'` | `'default'` | Text size variant |
| `showExplanation` | `boolean` | `false` | Whether to show explanation after answering |
| `showCorrectAnswer` | `boolean` | `false` | Whether to show correct answer after answering |
| `class` | `string` | `undefined` | Additional CSS classes |
| `onAnswerSelect` | `function` | `undefined` | Callback function when answer is selected |

## Data Structure

```typescript
interface MCQOption {
  id: string      // Unique identifier for the option
  text: string    // Display text for the option
  value: string   // Value to identify the option
}

interface MCQData {
  question: string           // The question text
  options: MCQOption[]       // Array of answer options
  correctAnswer?: string     // The correct answer value (optional)
  explanation?: string       // Explanation text (optional)
}
```

## Events

### `@answer-select`

Emitted when a user selects an answer:

```vue
<MCQ 
  :data="mcqData"
  @answer-select="handleAnswer"
/>

<script setup>
const handleAnswer = (selectedAnswer: string, isCorrect: boolean) => {
  console.log(`Selected: ${selectedAnswer}, Correct: ${isCorrect}`)
}
</script>
```

## Variants

### Default
```vue
<MCQ :data="mcqData" variant="default" />
```

### Card
```vue
<MCQ :data="mcqData" variant="card" />
```

### Outline
```vue
<MCQ :data="mcqData" variant="outline" />
```

### Ghost
```vue
<MCQ :data="mcqData" variant="ghost" />
```

## Sizes

### Small
```vue
<MCQ :data="mcqData" size="sm" />
```

### Default
```vue
<MCQ :data="mcqData" size="default" />
```

### Large
```vue
<MCQ :data="mcqData" size="lg" />
```

### Extra Large
```vue
<MCQ :data="mcqData" size="xl" />
```

## Advanced Usage

### With Explanation and Correct Answer Display
```vue
<MCQ 
  :data="mcqData"
  :show-explanation="true"
  :show-correct-answer="true"
  variant="card"
  size="lg"
/>
```

### Custom Styling
```vue
<MCQ 
  :data="mcqData"
  class="max-w-lg mx-auto bg-blue-50 p-6 rounded-xl"
/>
```

### Multiple MCQs with Navigation
```vue
<template>
  <div>
    <MCQ 
      :data="mcqs[currentIndex]"
      @answer-select="handleAnswer"
    />
    
    <div class="flex justify-between mt-4">
      <button @click="previous">Previous</button>
      <button @click="next">Next</button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { MCQ } from '@/components/ui/mcq'

const currentIndex = ref(0)
const mcqs = ref([/* your MCQ data array */])

const next = () => {
  if (currentIndex.value < mcqs.value.length - 1) {
    currentIndex.value++
  }
}

const previous = () => {
  if (currentIndex.value > 0) {
    currentIndex.value--
  }
}
</script>
```

## Styling

The component uses Tailwind CSS classes and follows the design system. You can customize the appearance by:

1. **Using variants**: Different visual styles
2. **Adding custom classes**: Pass additional CSS classes
3. **Modifying the component**: Edit the component file directly

## Accessibility

- Proper ARIA labels for screen readers
- Keyboard navigation support
- High contrast visual feedback
- Semantic HTML structure

## Browser Support

- Vue 3+
- Modern browsers with ES6+ support
- Tailwind CSS 4.0+

## Examples

See the demo pages for more examples:
- `MCQDemo.vue` - Full interactive demo
- `SimpleMCQExample.vue` - Basic usage example