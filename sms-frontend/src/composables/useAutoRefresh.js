import { ref, onMounted, onUnmounted } from 'vue';

/**
 * Auto-refresh data when browser tab becomes visible.
 *
 * @param {Function} fetchFn - The async function to call for refresh
 * @param {Object} options
 * @param {boolean} options.enabled - Enable/disable auto-refresh (default: true)
 * @returns {{ refresh: Function, lastRefreshed: import('vue').Ref<Date|null>, isRefreshing: import('vue').Ref<boolean> }}
 */
export function useAutoRefresh(fetchFn, options = {}) {
  const { enabled = true } = options;

  const lastRefreshed = ref(null);
  const isRefreshing = ref(false);

  const refresh = async () => {
    if (isRefreshing.value) return;
    isRefreshing.value = true;
    try {
      await fetchFn();
      lastRefreshed.value = new Date();
    } finally {
      isRefreshing.value = false;
    }
  };

  const handleVisibilityChange = () => {
    if (enabled && document.visibilityState === 'visible') {
      refresh();
    }
  };

  onMounted(() => {
    document.addEventListener('visibilitychange', handleVisibilityChange);
    lastRefreshed.value = new Date();
  });

  onUnmounted(() => {
    document.removeEventListener('visibilitychange', handleVisibilityChange);
  });

  return { refresh, lastRefreshed, isRefreshing };
}
