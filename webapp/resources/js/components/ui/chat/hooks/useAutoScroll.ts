import { ref, watch, onMounted, onUnmounted, nextTick } from 'vue';

interface ScrollState {
  isAtBottom: boolean;
  autoScrollEnabled: boolean;
}

interface UseAutoScrollOptions {
  offset?: number;
  smooth?: boolean;
  content?: any; // Equivalent to React.ReactNode in this context
}

export function useAutoScroll(options: UseAutoScrollOptions = {}) {
  const { offset = 20, smooth = false, content } = options;
  const scrollRef = ref<HTMLElement | null>(null);
  const lastContentHeight = ref(0);

  const isAtBottom = ref(true);
  const autoScrollEnabled = ref(true);

  const checkIsAtBottom = (element: HTMLElement) => {
    const { scrollTop, scrollHeight, clientHeight } = element;
    const distanceToBottom = Math.abs(
      scrollHeight - scrollTop - clientHeight,
    );
    return distanceToBottom <= offset;
  };

  const scrollToBottom = (instant?: boolean) => {
    if (!scrollRef.value) return;

    const targetScrollTop =
      scrollRef.value.scrollHeight - scrollRef.value.clientHeight;

    if (instant) {
      scrollRef.value.scrollTop = targetScrollTop;
    } else {
      scrollRef.value.scrollTo({
        top: targetScrollTop,
        behavior: smooth ? "smooth" : "auto",
      });
    }

    isAtBottom.value = true;
    autoScrollEnabled.value = true;
  };

  const handleScroll = () => {
    if (!scrollRef.value) return;

    const atBottom = checkIsAtBottom(scrollRef.value);
    isAtBottom.value = atBottom;
    if (atBottom) {
      autoScrollEnabled.value = true;
    }
  };

  onMounted(() => {
    const element = scrollRef.value;
    if (element) {
      element.addEventListener("scroll", handleScroll, { passive: true });
    }
  });

  onUnmounted(() => {
    const element = scrollRef.value;
    if (element) {
      element.removeEventListener("scroll", handleScroll);
    }
  });

  watch(content, () => {
    nextTick(() => {
      if (autoScrollEnabled.value) {
        scrollToBottom(lastContentHeight.value === 0);
      }
      if (scrollRef.value) {
        lastContentHeight.value = scrollRef.value.scrollHeight;
      }
    });
  }, { deep: true });

  onMounted(() => {
    const element = scrollRef.value;
    if (element) {
      const resizeObserver = new ResizeObserver(() => {
        if (autoScrollEnabled.value) {
          scrollToBottom(true);
        }
      });

      resizeObserver.observe(element);
      onUnmounted(() => resizeObserver.disconnect());
    }
  });

  const disableAutoScroll = () => {
    if (!scrollRef.value) return;
    const atBottom = checkIsAtBottom(scrollRef.value);

    if (!atBottom) {
      autoScrollEnabled.value = false;
    }
  };

  return {
    scrollRef,
    isAtBottom,
    autoScrollEnabled,
    scrollToBottom: () => scrollToBottom(false),
    disableAutoScroll,
  };
}
