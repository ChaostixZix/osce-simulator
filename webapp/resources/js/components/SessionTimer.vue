<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import { router } from '@inertiajs/vue3';

interface Props {
  sessionId: number;
  initialTimeRemaining: number;
  durationMinutes: number;
  status: 'active' | 'expired' | 'completed';
}

const props = defineProps<Props>();

const timeRemaining = ref<number>(props.initialTimeRemaining || 0);
const status = ref<Props['status']>(props.status);
const isPaused = ref<boolean>(false);
const serverPaused = ref<boolean>(false);
const pollingIntervalMs = ref<number>(10000);
let tickTimer: number | undefined;
let pollTimer: number | undefined;
let lastSyncAt = 0;
let lastKnownServerRemaining = props.initialTimeRemaining || 0;
let pageUnloadListenerAdded = false;

const progressPercentage = computed(() => {
  const total = props.durationMinutes * 60;
  const elapsed = Math.max(0, total - timeRemaining.value);
  if (total <= 0) return 0;
  return Math.min(100, Math.round((elapsed / total) * 1000) / 10);
});

const formattedTime = computed(() => {
  const seconds = Math.max(0, Math.floor(timeRemaining.value));
  const mm = Math.floor(seconds / 60).toString().padStart(2, '0');
  const ss = (seconds % 60).toString().padStart(2, '0');
  return `${mm}:${ss}`;
});

const stateColor = computed(() => {
  if (status.value === 'completed') return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
  if (status.value === 'expired') return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
  const minutesLeft = timeRemaining.value / 60;
  if (minutesLeft <= 5) return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
  if (minutesLeft <= 10) return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
  return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
});

const emit = defineEmits<{
  (e: 'session-expired'): void;
  (e: 'session-completed'): void;
}>();

function clearTimers() {
  if (tickTimer) window.clearInterval(tickTimer);
  if (pollTimer) window.clearInterval(pollTimer);
}

function scheduleTicking() {
  if (tickTimer) window.clearInterval(tickTimer);
  tickTimer = window.setInterval(() => {
    if (isPaused.value) return;
    if (status.value !== 'active') return;
    timeRemaining.value = Math.max(0, timeRemaining.value - 1);
    if (timeRemaining.value === 0) {
      status.value = 'expired';
      emit('session-expired');
      // Attempt to notify server to complete session
      fetch(`/api/osce/sessions/${props.sessionId}/complete`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
        }
      }).catch(() => {});
      router.visit(`/osce`);
    }
  }, 1000);
}

function schedulePolling() {
  if (pollTimer) window.clearInterval(pollTimer);
  pollTimer = window.setInterval(() => {
    if (isPaused.value) return;
    if (status.value === 'completed') return;
    syncWithServer();
  }, pollingIntervalMs.value);
}

async function syncWithServer() {
  // Debounce rapid calls
  const nowTs = Date.now();
  if (nowTs - lastSyncAt < 500) return;
  lastSyncAt = nowTs;

  try {
    const res = await fetch(`/api/osce/sessions/${props.sessionId}/timer`, { headers: { 'Accept': 'application/json' }});
    if (!res.ok) return;
    const data = await res.json();
    
    lastKnownServerRemaining = data.remaining_seconds ?? lastKnownServerRemaining;
    timeRemaining.value = lastKnownServerRemaining;
    status.value = data.time_status || status.value;
    serverPaused.value = data.is_paused || false;
    
    // Sync local pause state with server
    if (serverPaused.value && !isPaused.value) {
      isPaused.value = true;
    } else if (!serverPaused.value && isPaused.value && status.value === 'active') {
      isPaused.value = false;
    }
    
    // Increase poll frequency when under 2 minutes
    const nextInterval = (lastKnownServerRemaining <= 120) ? 1000 : 10000;
    if (nextInterval !== pollingIntervalMs.value) {
      pollingIntervalMs.value = nextInterval;
      schedulePolling();
    }
    
    if (status.value === 'expired') {
      emit('session-expired');
      router.visit('/osce');
    }
    if (status.value === 'completed') {
      emit('session-completed');
    }
  } catch (e) {
    // Network issues: continue local countdown; UI can optionally show offline indicator
  }
}

function togglePause() {
  isPaused.value = !isPaused.value;
}

async function autoPauseOnLeave() {
  if (status.value === 'active' && !serverPaused.value) {
    try {
      await fetch(`/api/osce/sessions/${props.sessionId}/auto-pause`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
        }
      });
    } catch (e) {
      // Ignore errors on page unload
    }
  }
}

function setupPageUnloadListener() {
  if (!pageUnloadListenerAdded) {
    // Handle page unload/refresh
    window.addEventListener('beforeunload', autoPauseOnLeave);
    
    // Handle page visibility changes (tab switching, minimize)
    document.addEventListener('visibilitychange', () => {
      if (document.hidden && status.value === 'active' && !serverPaused.value) {
        autoPauseOnLeave();
      }
    });
    
    pageUnloadListenerAdded = true;
  }
}

function removePageUnloadListener() {
  if (pageUnloadListenerAdded) {
    window.removeEventListener('beforeunload', autoPauseOnLeave);
    pageUnloadListenerAdded = false;
  }
}

onMounted(() => {
  // Initial sync to be sure
  syncWithServer();
  scheduleTicking();
  schedulePolling();
  setupPageUnloadListener();
});

onBeforeUnmount(() => {
  clearTimers();
  removePageUnloadListener();
});

watch(() => props.status, (newVal) => {
  status.value = newVal;
});

</script>

<template>
  <div class="w-full">
    <div class="flex items-center justify-between mb-2">
      <div class="text-sm font-medium" :class="stateColor">
        <span class="px-2 py-1 rounded">Time Remaining: {{ formattedTime }}</span>
      </div>
      <div class="text-xs text-gray-500">{{ progressPercentage }}%</div>
    </div>
    <div class="w-full h-2 bg-gray-200 dark:bg-gray-800 rounded overflow-hidden">
      <div class="h-2 bg-blue-500 transition-all" :style="{ width: `${progressPercentage}%` }"></div>
    </div>
    <div class="mt-2 flex items-center justify-between">
      <div class="text-xs text-gray-500" v-if="status === 'active' && !serverPaused">
        Session is active. {{ timeRemaining <= 300 ? 'Wrap up soon.' : '' }}
      </div>
      <div class="text-xs text-orange-600" v-else-if="status === 'active' && serverPaused">
        Timer paused - will resume automatically when you return
      </div>
      <div class="text-xs text-red-600" v-else-if="status === 'expired'">Session expired</div>
      <div class="text-xs text-green-600" v-else-if="status === 'completed'">Session completed</div>
      <button class="text-xs underline" @click="togglePause" type="button" v-if="status === 'active'">
        {{ isPaused ? 'Resume' : 'Pause' }}
      </button>
    </div>
  </div>
</template>

<style scoped>
.transition-all { transition: width 0.6s ease; }
</style>


