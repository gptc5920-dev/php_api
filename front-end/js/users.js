import { API_BASE } from './config.js';
import { apiRequest } from './api.js';
import { qs } from './dom.js';
import { escapeHtml } from './format.js';
import { renderModuleResult, renderUserProfile } from './ui.js';

async function fetchUserById(id) {
  const data = await apiRequest(`/users.php?id=${id}`);
  return data?.data || null;
}

async function viewUserById(id) {
  const result = qs('#viewUserResult');
  const viewForm = qs('#viewUserForm');
  if (viewForm) viewForm.elements.id.value = id;

  try {
    const data = await apiRequest(`/users.php?id=${id}`);
    renderModuleResult(result, { module: 'USERS', action: 'VIEW', ok: true, payload: data });
    renderUserProfile(data?.data || null);
  } catch (error) {
    renderModuleResult(result, { module: 'USERS', action: 'VIEW', ok: false, payload: error });
    renderUserProfile(null);
  }
}

function fillUpdateFormFromUser(user) {
  const updateForm = qs('#updateUserForm');
  if (!updateForm || !user) return;

  updateForm.elements.id.value = user.id ?? '';
  updateForm.elements.name.value = user.name ?? '';
  updateForm.elements.email.value = user.email ?? '';
  updateForm.elements.role.value = user.role ?? '';
  updateForm.elements.password.value = '';
  updateForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

async function deleteUserById(id) {
  const result = qs('#deleteUserResult');
  const deleteForm = qs('#deleteUserForm');
  if (deleteForm) deleteForm.elements.id.value = id;

  try {
    const data = await apiRequest(`/users.php?id=${id}`, { method: 'DELETE' });
    renderModuleResult(result, { module: 'USERS', action: 'DELETE', ok: true, payload: data });
    renderUserProfile(null);
    await loadUsers();
  } catch (error) {
    renderModuleResult(result, { module: 'USERS', action: 'DELETE', ok: false, payload: error });
  }
}

async function loadUsers() {
  const tbody = qs('#usersTableBody');
  if (!tbody) return;

  try {
    const data = await apiRequest('/users.php');
    const users = Array.isArray(data.data) ? data.data : [];

    if (!users.length) {
      tbody.innerHTML = '<tr><td colspan="6">No users found.</td></tr>';
      return;
    }

    tbody.innerHTML = users.map((u) => `
      <tr>
        <td>${escapeHtml(u.id)}</td>
        <td>${escapeHtml(u.name)}</td>
        <td>${escapeHtml(u.email)}</td>
        <td>${escapeHtml(u.role)}</td>
        <td>${escapeHtml(u.created_at || '')}</td>
        <td class="actions-cell">
          <button type="button" class="icon-btn view" data-action="view" data-id="${escapeHtml(u.id)}" title="View Profile" aria-label="View user"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="1.8"/></svg></button>
          <button type="button" class="icon-btn edit" data-action="edit" data-id="${escapeHtml(u.id)}" title="Fill Update Form" aria-label="Update user"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20h4l10-10-4-4L4 16v4Z" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="m13 7 4 4" fill="none" stroke="currentColor" stroke-width="1.8"/></svg></button>
          <button type="button" class="icon-btn del" data-action="delete" data-id="${escapeHtml(u.id)}" title="Delete User" aria-label="Delete user"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M9 7V5h6v2" fill="none" stroke="currentColor" stroke-width="1.8"/><rect x="6" y="7" width="12" height="13" rx="1.8" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M10 11v6M14 11v6" fill="none" stroke="currentColor" stroke-width="1.8"/></svg></button>
        </td>
      </tr>
    `).join('');
  } catch (error) {
    const msg = error?.message || 'Failed to load users.';
    tbody.innerHTML = `<tr><td colspan="6">${escapeHtml(msg)}<br /><small>${escapeHtml(API_BASE)}/users.php</small></td></tr>`;
  }
}

function initListActions() {
  const tbody = qs('#usersTableBody');
  if (!tbody) return;

  tbody.addEventListener('click', async (e) => {
    const target = e.target;
    const btn = target instanceof Element ? target.closest('button[data-action][data-id]') : null;
    if (!btn) return;

    const action = btn.dataset.action;
    const id = btn.dataset.id;
    if (!id) return;

    if (action === 'view') {
      const viewForm = qs('#viewUserForm');
      if (viewForm) {
        viewForm.elements.id.value = id;
        viewForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
      renderModuleResult(qs('#viewUserResult'), {
        module: 'USERS',
        action: 'VIEW',
        ok: true,
        payload: { status: 200, message: 'ID filled. Click View Profile to fetch user.' },
      });
      return;
    }

    if (action === 'edit') {
      try {
        const user = await fetchUserById(id);
        fillUpdateFormFromUser(user);
      } catch (error) {
        renderModuleResult(qs('#updateUserResult'), { module: 'USERS', action: 'UPDATE', ok: false, payload: error });
      }
      return;
    }

    if (action === 'delete') {
      const confirmed = window.confirm(`Delete user #${id}?`);
      if (!confirmed) return;
      await deleteUserById(id);
    }
  });
}

export function initViewUser() {
  const formEl = qs('#viewUserForm');
  if (!formEl) return;

  formEl.addEventListener('submit', async (e) => {
    e.preventDefault();
    const id = new FormData(e.currentTarget).get('id');
    await viewUserById(id);
  });
}

export function initUsersCrud() {
  const createUserForm = qs('#createUserForm');
  const updateUserForm = qs('#updateUserForm');
  const deleteUserForm = qs('#deleteUserForm');
  const refreshUsersBtn = qs('#refreshUsersBtn');

  if (!createUserForm && !updateUserForm && !deleteUserForm && !refreshUsersBtn) return;

  if (createUserForm) {
    createUserForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const result = qs('#createUserResult');
      const form = new FormData(e.currentTarget);

      const payload = {
        name: form.get('name'),
        email: form.get('email'),
        role: form.get('role') || 'staff',
        password: form.get('password'),
      };

      try {
        const data = await apiRequest('/users.php', { method: 'POST', body: JSON.stringify(payload) });
        renderModuleResult(result, { module: 'USERS', action: 'CREATE', ok: true, payload: data });
        e.currentTarget.reset();
        await loadUsers();
      } catch (error) {
        renderModuleResult(result, { module: 'USERS', action: 'CREATE', ok: false, payload: error });
      }
    });
  }

  if (updateUserForm) {
    updateUserForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const result = qs('#updateUserResult');
      const form = new FormData(e.currentTarget);
      const id = form.get('id');

      const payload = {};
      if (form.get('name')) payload.name = form.get('name');
      if (form.get('email')) payload.email = form.get('email');
      if (form.get('role')) payload.role = form.get('role');
      if (form.get('password')) payload.password = form.get('password');

      try {
        const data = await apiRequest(`/users.php?id=${id}`, { method: 'PUT', body: JSON.stringify(payload) });
        renderModuleResult(result, { module: 'USERS', action: 'UPDATE', ok: true, payload: data });
        await loadUsers();
      } catch (error) {
        renderModuleResult(result, { module: 'USERS', action: 'UPDATE', ok: false, payload: error });
      }
    });
  }

  if (deleteUserForm) {
    deleteUserForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const form = new FormData(e.currentTarget);
      const id = form.get('id');

      if (!id) {
        renderModuleResult(qs('#deleteUserResult'), {
          module: 'USERS',
          action: 'DELETE',
          ok: false,
          payload: { status: 422, message: 'User ID is required for delete.' },
        });
        return;
      }

      await deleteUserById(id);
      e.currentTarget.reset();
    });
  }

  if (refreshUsersBtn) refreshUsersBtn.addEventListener('click', loadUsers);

  initListActions();
  loadUsers();
}
