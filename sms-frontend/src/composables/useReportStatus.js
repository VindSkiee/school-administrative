import { ref } from 'vue';
import api from '../services/api';

// Module-level cache — shared across all component instances
let cachedData = null; // { isReportPublished, publishedAt }
let fetchPromise = null;

/**
 * Composable to check if the active academic year's report is published.
 * Fetches once and caches the result for the entire app session.
 *
 * Usage:
 *   const { isReportPublished, publishedAt, isReportLoading } = useReportStatus(role);
 *
 * @param {string} role - 'teacher' or 'student'
 */
export function useReportStatus(role = 'teacher') {
  const isReportPublished = ref(false);
  const publishedAt = ref(null);
  const isReportLoading = ref(true);

  const endpoint = role === 'student'
    ? '/v1/student/reports/report-status'
    : '/v1/teacher/report-status';

  const applyCache = (data) => {
    isReportPublished.value = data.isReportPublished;
    publishedAt.value = data.publishedAt;
    isReportLoading.value = false;
  };

  const fetchStatus = async () => {
    // Return cached result if available
    if (cachedData !== null) {
      applyCache(cachedData);
      return;
    }

    // Deduplicate concurrent requests
    if (fetchPromise) {
      const data = await fetchPromise;
      applyCache(data);
      return;
    }

    fetchPromise = (async () => {
      try {
        const res = await api.get(endpoint);
        const data = {
          isReportPublished: res.data?.is_report_published ?? false,
          publishedAt: res.data?.published_at ?? null,
        };
        cachedData = data;
        return data;
      } catch {
        const fallback = { isReportPublished: false, publishedAt: null };
        cachedData = fallback;
        return fallback;
      } finally {
        fetchPromise = null;
      }
    })();

    const data = await fetchPromise;
    applyCache(data);
  };

  fetchStatus();

  return { isReportPublished, publishedAt, isReportLoading };
}
