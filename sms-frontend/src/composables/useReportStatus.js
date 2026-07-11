import { ref } from 'vue';
import api from '../services/api';

/**
 * Composable to check if the active academic year's report is published (per-class).
 * Fetches from backend and returns per-class publish status.
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

  const fetchStatus = async () => {
    try {
      const res = await api.get(endpoint);
      isReportPublished.value = res.data?.is_report_published ?? false;
      publishedAt.value = res.data?.published_at ?? null;
    } catch {
      isReportPublished.value = false;
      publishedAt.value = null;
    } finally {
      isReportLoading.value = false;
    }
  };

  fetchStatus();

  return { isReportPublished, publishedAt, isReportLoading };
}
