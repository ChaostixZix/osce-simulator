/**
 * @jest-environment jsdom
 */

import { describe, test, expect, beforeEach, vi, afterEach } from 'vitest';
import { mount } from '@vue/test-utils';
import SessionTimer from '../../resources/js/components/SessionTimer.vue';

// Mock Inertia
const mockInertia = {
  visit: vi.fn(),
};

vi.mock('@inertiajs/vue3', () => ({
  router: mockInertia,
}));

// Mock fetch
global.fetch = vi.fn();

describe('SessionTimer', () => {
  let wrapper;

  const defaultProps = {
    sessionId: 1,
    initialTimeRemaining: 1500, // 25 minutes
    durationMinutes: 25,
    status: 'active'
  };

  beforeEach(() => {
    vi.clearAllMocks();
    vi.useFakeTimers();
    
    // Mock CSRF token
    const meta = document.createElement('meta');
    meta.name = 'csrf-token';
    meta.content = 'test-token';
    document.head.appendChild(meta);
  });

  afterEach(() => {
    vi.useRealTimers();
    if (wrapper) {
      wrapper.unmount();
    }
  });

  test('renders timer with initial time', () => {
    wrapper = mount(SessionTimer, {
      props: defaultProps
    });

    expect(wrapper.find('.text-sm').text()).toContain('Time Remaining: 25:00');
  });

  test('calculates progress percentage correctly', () => {
    wrapper = mount(SessionTimer, {
      props: {
        ...defaultProps,
        initialTimeRemaining: 750 // 12.5 minutes remaining
      }
    });

    // 25 minutes total, 12.5 minutes remaining = 50% progress
    expect(wrapper.find('.text-xs.text-gray-500').text()).toContain('50%');
  });

  test('shows appropriate status colors', () => {
    // Test warning state (under 10 minutes)
    wrapper = mount(SessionTimer, {
      props: {
        ...defaultProps,
        initialTimeRemaining: 300 // 5 minutes
      }
    });

    const statusElement = wrapper.find('.text-sm');
    expect(statusElement.classes()).toContain('bg-red-100');

    wrapper.unmount();

    // Test normal state (over 10 minutes)
    wrapper = mount(SessionTimer, {
      props: {
        ...defaultProps,
        initialTimeRemaining: 900 // 15 minutes
      }
    });

    const statusElement2 = wrapper.find('.text-sm');
    expect(statusElement2.classes()).toContain('bg-green-100');
  });

  test('displays expired status correctly', () => {
    wrapper = mount(SessionTimer, {
      props: {
        ...defaultProps,
        status: 'expired'
      }
    });

    expect(wrapper.find('.text-xs.text-red-600').text()).toBe('Session expired');
  });

  test('displays completed status correctly', () => {
    wrapper = mount(SessionTimer, {
      props: {
        ...defaultProps,
        status: 'completed'
      }
    });

    expect(wrapper.find('.text-xs.text-green-600').text()).toBe('Session completed');
  });

  test('syncs with server and updates timer state', async () => {
    const mockResponse = {
      remaining_seconds: 1200,
      time_status: 'active',
      is_paused: false
    };

    fetch.mockResolvedValueOnce({
      ok: true,
      json: async () => mockResponse
    });

    wrapper = mount(SessionTimer, {
      props: defaultProps
    });

    await wrapper.vm.$nextTick();

    expect(fetch).toHaveBeenCalledWith(
      '/api/osce/sessions/1/timer',
      { headers: { 'Accept': 'application/json' } }
    );
  });

  test('handles server paused state correctly', async () => {
    const mockResponse = {
      remaining_seconds: 1200,
      time_status: 'active',
      is_paused: true
    };

    fetch.mockResolvedValueOnce({
      ok: true,
      json: async () => mockResponse
    });

    wrapper = mount(SessionTimer, {
      props: defaultProps
    });

    // Simulate server response
    await wrapper.vm.syncWithServer();
    await wrapper.vm.$nextTick();

    expect(wrapper.find('.text-xs.text-orange-600').text()).toContain('Timer paused');
  });

  test('calls auto-pause on page unload', async () => {
    fetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ remaining_seconds: 1200, time_status: 'active', is_paused: false })
    });

    wrapper = mount(SessionTimer, {
      props: defaultProps
    });

    const autoPauseSpy = vi.spyOn(wrapper.vm, 'autoPauseOnLeave');
    
    // Simulate beforeunload event
    const event = new Event('beforeunload');
    window.dispatchEvent(event);
    
    expect(autoPauseSpy).toHaveBeenCalled();
  });

  test('handles visibility change events', async () => {
    fetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ remaining_seconds: 1200, time_status: 'active', is_paused: false })
    });

    wrapper = mount(SessionTimer, {
      props: defaultProps
    });

    const autoPauseSpy = vi.spyOn(wrapper.vm, 'autoPauseOnLeave');
    
    // Mock document.hidden
    Object.defineProperty(document, 'hidden', {
      writable: true,
      value: true
    });
    
    // Simulate visibility change
    const event = new Event('visibilitychange');
    document.dispatchEvent(event);
    
    expect(autoPauseSpy).toHaveBeenCalled();
  });

  test('countdown decrements correctly', async () => {
    wrapper = mount(SessionTimer, {
      props: {
        ...defaultProps,
        initialTimeRemaining: 60 // 1 minute
      }
    });

    // Initial state
    expect(wrapper.find('.text-sm').text()).toContain('01:00');

    // Advance timer by 1 second
    vi.advanceTimersByTime(1000);
    await wrapper.vm.$nextTick();

    expect(wrapper.vm.timeRemaining).toBe(59);
  });

  test('emits session-expired when timer reaches zero', async () => {
    wrapper = mount(SessionTimer, {
      props: {
        ...defaultProps,
        initialTimeRemaining: 1 // 1 second
      }
    });

    // Mock the complete session API call
    fetch.mockResolvedValueOnce({ ok: true });

    // Advance timer past zero
    vi.advanceTimersByTime(2000);
    await wrapper.vm.$nextTick();

    expect(wrapper.emitted('session-expired')).toBeTruthy();
    expect(mockInertia.visit).toHaveBeenCalledWith('/osce');
  });

  test('formats time correctly', () => {
    wrapper = mount(SessionTimer, {
      props: {
        ...defaultProps,
        initialTimeRemaining: 3661 // 61 minutes and 1 second
      }
    });

    expect(wrapper.find('.text-sm').text()).toContain('61:01');
  });
});