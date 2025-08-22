/**
 * SessionTimer Component Tests
 * Tests for frontend timer functionality and synchronization
 */

import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import SessionTimer from '@/components/SessionTimer.vue';

// Mock fetch API
global.fetch = jest.fn();

// Mock CSRF token
Object.defineProperty(document, 'querySelector', {
  value: jest.fn().mockReturnValue({ getAttribute: () => 'mock-csrf-token' }),
  writable: true,
});

describe('SessionTimer Component', () => {
  let wrapper;
  
  beforeEach(() => {
    // Clear all mocks
    fetch.mockClear();
    jest.clearAllTimers();
    jest.useFakeTimers();
  });

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount();
    }
    jest.runOnlyPendingTimers();
    jest.useRealTimers();
  });

  test('initializes with correct time remaining', () => {
    wrapper = mount(SessionTimer, {
      props: {
        sessionId: 1,
        initialTimeRemaining: 900, // 15 minutes
        durationMinutes: 15,
        status: 'active'
      }
    });

    expect(wrapper.vm.timeRemaining).toBe(900);
    expect(wrapper.text()).toContain('15:00');
  });

  test('counts down correctly every second', async () => {
    wrapper = mount(SessionTimer, {
      props: {
        sessionId: 1,
        initialTimeRemaining: 120, // 2 minutes
        durationMinutes: 15,
        status: 'active'
      }
    });

    expect(wrapper.vm.timeRemaining).toBe(120);
    expect(wrapper.text()).toContain('02:00');

    // Advance 1 second
    jest.advanceTimersByTime(1000);
    await nextTick();

    expect(wrapper.vm.timeRemaining).toBe(119);
    expect(wrapper.text()).toContain('01:59');

    // Advance 59 more seconds (total 60 seconds)
    jest.advanceTimersByTime(59000);
    await nextTick();

    expect(wrapper.vm.timeRemaining).toBe(60);
    expect(wrapper.text()).toContain('01:00');
  });

  test('pauses countdown when isPaused is true', async () => {
    wrapper = mount(SessionTimer, {
      props: {
        sessionId: 1,
        initialTimeRemaining: 120,
        durationMinutes: 15,
        status: 'active'
      }
    });

    expect(wrapper.vm.timeRemaining).toBe(120);

    // Pause the timer
    wrapper.vm.togglePause();
    await nextTick();

    expect(wrapper.vm.isPaused).toBe(true);

    // Advance time - should not countdown when paused
    jest.advanceTimersByTime(5000);
    await nextTick();

    expect(wrapper.vm.timeRemaining).toBe(120); // Should remain unchanged
  });

  test('syncs with server correctly', async () => {
    // Mock successful server response
    fetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve({
        remaining_seconds: 600,
        time_status: 'active'
      })
    });

    wrapper = mount(SessionTimer, {
      props: {
        sessionId: 1,
        initialTimeRemaining: 900,
        durationMinutes: 15,
        status: 'active'
      }
    });

    // Trigger server sync
    await wrapper.vm.syncWithServer();
    await nextTick();

    // Should update timeRemaining with server data
    expect(wrapper.vm.timeRemaining).toBe(600);
    expect(fetch).toHaveBeenCalledWith('/api/osce/sessions/1/timer', {
      headers: { 'Accept': 'application/json' }
    });
  });

  test('handles server sync errors gracefully', async () => {
    // Mock failed server response
    fetch.mockRejectedValueOnce(new Error('Network error'));

    wrapper = mount(SessionTimer, {
      props: {
        sessionId: 1,
        initialTimeRemaining: 900,
        durationMinutes: 15,
        status: 'active'
      }
    });

    const originalTime = wrapper.vm.timeRemaining;

    // Trigger server sync
    await wrapper.vm.syncWithServer();
    await nextTick();

    // Should continue with local countdown on network error
    expect(wrapper.vm.timeRemaining).toBe(originalTime);
  });

  test('emits session-expired when time reaches zero', async () => {
    // Mock successful session completion request
    fetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve({ success: true })
    });

    wrapper = mount(SessionTimer, {
      props: {
        sessionId: 1,
        initialTimeRemaining: 2, // 2 seconds
        durationMinutes: 15,
        status: 'active'
      }
    });

    // Advance to zero
    jest.advanceTimersByTime(2000);
    await nextTick();

    expect(wrapper.emitted('session-expired')).toBeTruthy();
    expect(wrapper.vm.timeRemaining).toBe(0);
    expect(wrapper.vm.status).toBe('expired');
  });

  test('progress percentage calculates correctly', () => {
    wrapper = mount(SessionTimer, {
      props: {
        sessionId: 1,
        initialTimeRemaining: 450, // 7.5 minutes remaining
        durationMinutes: 15, // 15 minute total duration
        status: 'active'
      }
    });

    // 7.5 minutes elapsed out of 15 = 50% progress
    expect(wrapper.vm.progressPercentage).toBe(50.0);
  });

  test('adjusts polling frequency when time is low', async () => {
    // Mock server responses
    fetch
      .mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({
          remaining_seconds: 150, // 2.5 minutes
          time_status: 'active'
        })
      })
      .mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({
          remaining_seconds: 90, // 1.5 minutes
          time_status: 'active'
        })
      });

    wrapper = mount(SessionTimer, {
      props: {
        sessionId: 1,
        initialTimeRemaining: 180, // 3 minutes
        durationMinutes: 15,
        status: 'active'
      }
    });

    // Initial polling interval should be 10 seconds
    expect(wrapper.vm.pollingIntervalMs).toBe(10000);

    // Sync with server (remaining time > 2 minutes)
    await wrapper.vm.syncWithServer();
    await nextTick();
    
    expect(wrapper.vm.pollingIntervalMs).toBe(10000); // Should remain 10 seconds

    // Sync again with low remaining time (< 2 minutes)
    await wrapper.vm.syncWithServer();
    await nextTick();
    
    expect(wrapper.vm.pollingIntervalMs).toBe(1000); // Should change to 1 second
  });

  test('detects when server time is inconsistent with local time', async () => {
    // Mock server response with inconsistent time
    fetch.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve({
        remaining_seconds: 1200, // Server says 20 minutes remaining
        time_status: 'active'
      })
    });

    wrapper = mount(SessionTimer, {
      props: {
        sessionId: 1,
        initialTimeRemaining: 600, // Local says 10 minutes remaining
        durationMinutes: 15,
        status: 'active'
      }
    });

    const localTimeBefore = wrapper.vm.timeRemaining;

    // Sync with server
    await wrapper.vm.syncWithServer();
    await nextTick();

    // Should update to server time
    expect(wrapper.vm.timeRemaining).toBe(1200);
    expect(wrapper.vm.timeRemaining).not.toBe(localTimeBefore);
  });

  test('maintains countdown consistency after component remount', () => {
    // First mount
    const wrapper1 = mount(SessionTimer, {
      props: {
        sessionId: 1,
        initialTimeRemaining: 600,
        durationMinutes: 15,
        status: 'active'
      }
    });

    expect(wrapper1.vm.timeRemaining).toBe(600);
    wrapper1.unmount();

    // Simulate page refresh/remount with updated time from server
    const wrapper2 = mount(SessionTimer, {
      props: {
        sessionId: 1,
        initialTimeRemaining: 570, // 30 seconds less
        durationMinutes: 15,
        status: 'active'
      }
    });

    expect(wrapper2.vm.timeRemaining).toBe(570);
    
    wrapper2.unmount();
  });

  test('handles zero and negative time remaining gracefully', () => {
    wrapper = mount(SessionTimer, {
      props: {
        sessionId: 1,
        initialTimeRemaining: -10, // Negative time
        durationMinutes: 15,
        status: 'expired'
      }
    });

    expect(wrapper.vm.timeRemaining).toBe(0); // Should be clamped to 0
    expect(wrapper.text()).toContain('00:00');
  });
});