import { initLoginForm } from './auth.js';
import { initUsersCrud, initViewUser } from './users.js';
import { initReveal, renderApiBaseHint } from './ui.js';

initReveal();
renderApiBaseHint();
initViewUser();
initLoginForm();
initUsersCrud();
