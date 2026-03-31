import { API_BASE } from './config.js';
import { qs } from './dom.js';
import { toJsonText } from './format.js';

export function setResultState(el, ok) {
  if (!el) return;
  el.classList.remove('success', 'error');
  el.classList.add(ok ? 'success' : 'error');
}

export function renderModuleResult(el, { module, action, ok, payload }) {
  if (!el) return;

  setResultState(el, ok);

  const status = payload?.status ?? (ok ? 200 : 500);
  const message = payload?.message ?? (ok ? 'Success' : 'Request failed');
  const data = payload?.data ?? null;

  const lines = [
    `[${module}] ${action}`,
    `Status: ${status}`,
    `Message: ${message}`,
  ];

  if (data !== null) {
    lines.push('Data:');
    lines.push(toJsonText(data));
  } else {
    lines.push('Payload:');
    lines.push(toJsonText(payload));
  }

  el.textContent = lines.join('\n');
}

export function initReveal() {
  const revealItems = document.querySelectorAll('.reveal');
  if (!revealItems.length) return;

  const io = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        io.unobserve(entry.target);
      }
    });
  }, { threshold: 0.18 });

  revealItems.forEach((el, i) => setTimeout(() => io.observe(el), i * 80));
}

export function renderApiBaseHint() {
  const el = qs('#apiBaseHint');
  if (!el) return;
  el.textContent = `API Base: ${API_BASE}`;
}

export function renderUserProfile(user) {
  const profileId = qs('#profileId');
  const profileName = qs('#profileName');
  const profileEmail = qs('#profileEmail');
  const profileRole = qs('#profileRole');
  const profileCreated = qs('#profileCreated');

  if (!profileId || !profileName || !profileEmail || !profileRole || !profileCreated) return;

  profileId.textContent = user?.id ?? '-';
  profileName.textContent = user?.name ?? '-';
  profileEmail.textContent = user?.email ?? '-';
  profileRole.textContent = user?.role ?? '-';
  profileCreated.textContent = user?.created_at ?? '-';
}
