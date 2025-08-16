import { cva, type VariantProps } from 'class-variance-authority'

export { default as MCQ } from './MCQ.vue'

export const mcqVariants = cva(
  'w-full',
  {
    variants: {
      variant: {
        default: 'bg-background text-foreground',
        card: 'bg-card text-card-foreground border border-border rounded-lg p-6 shadow-sm',
        outline: 'border-2 border-border rounded-lg p-6',
        ghost: 'bg-transparent',
      },
      size: {
        default: 'text-base',
        sm: 'text-sm',
        lg: 'text-lg',
        xl: 'text-xl',
      },
    },
    defaultVariants: {
      variant: 'default',
      size: 'default',
    },
  },
)

export type MCQVariants = VariantProps<typeof mcqVariants>