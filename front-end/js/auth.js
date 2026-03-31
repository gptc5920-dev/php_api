import { apiRequest } from './api.js';
import { qs } from './dom.js';
import { renderModuleResult } from './ui.js';

export function initLoginForm() {
  const formEl = qs('#loginForm');
  if (!formEl) return;

  formEl.addEventListener('submit', async (e) => {
    e.preventDefault();
    const result = qs('#loginResult');
    const form = new FormData(e.currentTarget);

    const payload = {
      email: form.get('email'),
      password: form.get('password'),
    };

    try {
      const data = await apiRequest('/login.php', { method: 'POST', body: JSON.stringify(payload) });
      renderModuleResult(result, { module: 'AUTH', action: 'LOGIN', ok: true, payload: data });
    } catch (error) {
      renderModuleResult(result, { module: 'AUTH', action: 'LOGIN', ok: false, payload: error });
    }
  });
}
