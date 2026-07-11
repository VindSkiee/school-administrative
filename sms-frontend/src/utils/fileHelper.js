/**
 * Build a public storage URL for a given relative path.
 *
 * `VITE_API_BASE_URL` typically ends with "/api" (e.g. http://school-api.test/api).
 * Laravel's public disk serves files at "/storage/..." (root, not under /api).
 * This helper strips the trailing "/api" so the final URL is correct.
 */
export const getStorageUrl = (path) => {
  if (!path) return '#';
  const base = (import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000/api').replace(/\/api\/?$/, '');
  return `${base}/storage/${path}`;
};
