export function resolveApiBase() {
  const queryBase = new URLSearchParams(window.location.search).get('api_base');
  if (queryBase) return queryBase.replace(/\/$/, '');

  const savedBase = window.localStorage.getItem('api_base');
  if (savedBase) return savedBase.replace(/\/$/, '');

  if (window.location.pathname.includes('/api/front-end/')) {
    return `${window.location.origin}/api`;
  }

  return 'http://localhost/api';
}

export const API_BASE = resolveApiBase();
