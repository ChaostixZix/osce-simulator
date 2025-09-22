<script setup lang="ts">
import { computed } from 'vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';

const props = defineProps<{
  name?: string
  size?: 'sm' | 'md' | 'lg'
  bgClass?: string
}>();

const initial = computed(() => {
  const n = (props.name || '').trim();
  return n && n[0] ? n[0].toUpperCase() : '?';
});

const sizeClass = computed(() => {
  switch (props.size) {
    case 'lg':
      return 'h-12 w-12 text-lg';
    case 'md':
      return 'h-8 w-8';
    case 'sm':
    default:
      return 'h-6 w-6 text-xs';
  }
});

const bg = computed(() => props.bgClass || 'bg-gray-200 text-gray-700');
</script>

<template>
  <Avatar :class="`${sizeClass}`">
    <AvatarFallback :class="`font-semibold ${bg}`">{{ initial }}</AvatarFallback>
  </Avatar>
  
</template>

