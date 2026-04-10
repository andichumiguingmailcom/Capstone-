// ── TOAST NOTIFICATION ──
function showToast(msg, type = 'success') {
  let toast = document.getElementById('toast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'toast';
    toast.className = 'toast';
    document.body.appendChild(toast);
  }
  toast.textContent = msg;
  toast.style.borderLeftColor = type === 'error' ? '#e63946' : type === 'warning' ? '#f0a500' : '#2e9e58';
  toast.classList.add('show');
  setTimeout(() => toast.classList.remove('show'), 3000);
}

// ── CONFIRMATION MODAL ──
function confirmAction(message, callback) {
  // Remove existing if any
  const existing = document.getElementById('modal-confirm-action');
  if (existing) existing.remove();

  const modalHtml = `
    <div class="modal-overlay" id="modal-confirm-action" style="z-index: 9999;">
      <div class="modal" style="max-width:400px; text-align:center; padding: 24px;">
        <div class="modal-title" style="margin-bottom: 12px;">Confirm Action</div>
        <div style="margin-bottom:24px; color: var(--text-muted); line-height: 1.5;">${message}</div>
        <div class="modal-footer" style="justify-content:center; gap:12px; border:none; padding:0;">
          <button class="btn btn-ghost" id="confirm-cancel">Cancel</button>
          <button class="btn btn-primary" id="confirm-yes">Yes, Proceed</button>
        </div>
      </div>
    </div>`;
  document.body.insertAdjacentHTML('beforeend', modalHtml);
  
  const modal = document.getElementById('modal-confirm-action');
  setTimeout(() => modal.classList.add('open'), 10);

  const close = () => { modal.classList.remove('open'); setTimeout(() => modal.remove(), 300); };
  document.getElementById('confirm-yes').onclick = () => { close(); callback(); };
  document.getElementById('confirm-cancel').onclick = close;
}

// ── MODAL ──
function openModal(id) {
  document.getElementById(id).classList.add('open');
}
function closeModal(id) {
  document.getElementById(id).classList.remove('open');
}
document.addEventListener('click', e => {
  if (e.target.classList.contains('modal-overlay')) {
    e.target.classList.remove('open');
  }
});

// ── TABS ──
function initTabs() {
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const group = btn.closest('.tabs-wrapper') || btn.parentElement.parentElement;
      group.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      group.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
      btn.classList.add('active');
      const target = group.querySelector(`#${btn.dataset.tab}`);
      if (target) {
        target.classList.add('active');
        target.querySelectorAll('.reveal-on-scroll:not(.is-visible)').forEach((el) => {
          el.classList.add('is-visible');
        });
      }
    });
  });
}

// ── TABLE SEARCH ──
function filterTable(inputId, tableId) {
  const q = document.getElementById(inputId).value.toLowerCase();
  document.querySelectorAll(`#${tableId} tbody tr`).forEach(row => {
    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}

// ── NAV ACTIVE STATE ──
function setActiveNav() {
  const page = window.location.pathname.split('/').pop();
  document.querySelectorAll('.nav-item').forEach(item => {
    item.classList.remove('active');
    if (item.getAttribute('href') === page) item.classList.add('active');
  });
}

// ── SIDEBAR TOGGLE (mobile) ──
function toggleSidebar() {
  const sidebar = document.querySelector('.sidebar');
  if (!sidebar) return;
  const isOpen = sidebar.classList.toggle('open');
  document.body.classList.toggle('sidebar-open', isOpen);
  const backdrop = document.querySelector('.sidebar-backdrop');
  if (backdrop) backdrop.classList.toggle('open', isOpen);
}

function closeSidebar() {
  const sidebar = document.querySelector('.sidebar');
  if (!sidebar) return;
  sidebar.classList.remove('open');
  document.body.classList.remove('sidebar-open');
  const backdrop = document.querySelector('.sidebar-backdrop');
  if (backdrop) backdrop.classList.remove('open');
}

function initSidebarToggle() {
  const sidebar = document.querySelector('.sidebar');
  const topbar = document.querySelector('.topbar');
  if (!sidebar || !topbar) return;

  if (!document.querySelector('.sidebar-backdrop')) {
    const backdrop = document.createElement('div');
    backdrop.className = 'sidebar-backdrop';
    backdrop.addEventListener('click', closeSidebar);
    document.body.appendChild(backdrop);
  }

  if (!document.querySelector('.sidebar-toggle')) {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'sidebar-toggle';
    btn.setAttribute('aria-label', 'Toggle sidebar');
    btn.innerHTML =
      "<span class='sidebar-toggle__bar'></span><span class='sidebar-toggle__bar'></span><span class='sidebar-toggle__bar'></span>";
    btn.addEventListener('click', toggleSidebar);
    topbar.insertBefore(btn, topbar.firstChild);
  }

  // Close sidebar after navigating (mobile)
  document.querySelectorAll('.sidebar .nav-item').forEach((a) => {
    a.addEventListener('click', () => {
      if (window.matchMedia('(max-width: 900px)').matches) closeSidebar();
    });
  });

  // Close on Escape
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeSidebar();
  });
}

// ── CONFIRM DELETE ──
function confirmDelete(msg) {
  return confirm(msg || 'Are you sure you want to delete this record?');
}

// ── FORMAT CURRENCY ──
function formatPeso(n) {
  return '₱' + parseFloat(n).toLocaleString('en-PH', { minimumFractionDigits: 2 });
}

// ── DATE HELPERS ──
function formatDate(d) {
  return new Date(d).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
}

// ── PRINT ──
function printSection(id) {
  const el = document.getElementById(id);
  const w = window.open('', '', 'width=900,height=600');
  w.document.write('<html><head><title>Print</title></head><body>' + el.innerHTML + '</body></html>');
  w.document.close();
  w.print();
}

function initThemeToggle() {
  // Corporate redesign: keep a consistent light UI (disable runtime theme toggle).
  return;
  if (document.querySelector('.theme-toggle')) return;
  const btn = document.createElement('button');
  btn.type = 'button';
  btn.className = 'theme-toggle';
  btn.setAttribute('aria-label', 'Toggle color theme');
  btn.addEventListener('click', () => {
    const next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    try {
      localStorage.setItem('coopims-theme', next);
    } catch (e) {}
  });

  const loginPage = document.querySelector('.login-page');
  if (loginPage) {
    btn.classList.add('theme-toggle--floating');
    loginPage.appendChild(btn);
    return;
  }

  const actions = document.querySelector('.topbar-actions');
  if (actions) {
    actions.insertBefore(btn, actions.firstChild);
    return;
  }
  const topbar = document.querySelector('.topbar');
  if (topbar) {
    const wrap = document.createElement('div');
    wrap.className = 'topbar-actions';
    wrap.appendChild(btn);
    topbar.appendChild(wrap);
  }
}

function initScrollReveal() {
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  const els = document.querySelectorAll('.card, .stat-card, .welcome-hero, .stats-grid');
  if (!els.length) return;
  els.forEach((el) => el.classList.add('reveal-on-scroll'));
  const io = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
        } else {
          entry.target.classList.remove('is-visible');
        }
      });
    },
    { threshold: 0.1 }
  );
  els.forEach((el) => io.observe(el));
}

// ── GLOBAL ACTION INTERCEPTOR ──
function initActionHandlers() {
  // Intercept all POST forms for confirmation
  document.addEventListener('submit', function(e) {
    const form = e.target;
    if (form.method.toLowerCase() === 'post' && !form.dataset.confirmed) {
      e.preventDefault();
      const submitBtn = form.querySelector('button[type="submit"]');
      const actionName = submitBtn ? submitBtn.innerText.replace(/[✅📥📤🗑️+]/g, '').trim() : "Submit";
      
      confirmAction(`Are you sure you want to ${actionName.toLowerCase()}?`, () => {
        form.dataset.confirmed = "true";
        form.submit();
      });
    }
  });

  // Check for msg in URL to show toast
  const params = new URLSearchParams(window.location.search);
  if (params.has('msg')) {
    const msg = params.get('msg');
    showToast(msg, msg.toLowerCase().includes('error') ? 'error' : 'success');
    // Clean URL
    window.history.replaceState({}, document.title, window.location.pathname);
  }
}

function preserveContextInNavigation() {
  const params = new URLSearchParams(window.location.search);
  const adminCtx = params.get('admin_ctx');
  const memberCtx = params.get('member_ctx');
  const contextParam = adminCtx ? ['admin_ctx', adminCtx] : memberCtx ? ['member_ctx', memberCtx] : null;
  if (!contextParam) return;

  const [key, value] = contextParam;
  const shouldPreserve = (href) => {
    if (!href || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) return false;
    if (href.includes(key + '=')) return false;
    return true;
  };

  document.querySelectorAll('a[href]').forEach((link) => {
    const href = link.getAttribute('href');
    if (!shouldPreserve(href)) return;
    try {
      const url = new URL(href, window.location.origin + window.location.pathname);
      url.searchParams.set(key, value);
      link.setAttribute('href', url.pathname + url.search + (url.hash || ''));
    } catch (err) {
      // Ignore malformed hrefs such as mailto: or javascript:
    }
  });

  document.querySelectorAll('form').forEach((form) => {
    if (form.querySelector(`[name="${key}"]`)) return;
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = key;
    input.value = value;
    form.appendChild(input);
  });
}

function appendCurrentContextToUrl(url) {
  const params = new URLSearchParams(window.location.search);
  const adminCtx = params.get('admin_ctx');
  const memberCtx = params.get('member_ctx');
  const key = adminCtx ? 'admin_ctx' : memberCtx ? 'member_ctx' : null;
  const value = adminCtx || memberCtx;
  if (!key || !value) {
    return url;
  }
  return url + (url.includes('?') ? '&' : '?') + encodeURIComponent(key) + '=' + encodeURIComponent(value);
}

function togglePasswordVisibility(inputId, btn) {
  const input = document.getElementById(inputId);
  if (input.type === 'password') {
    input.type = 'text';
    btn.textContent = '🙈';
  } else {
    input.type = 'password';
    btn.textContent = '👁️';
  }
}

document.addEventListener('DOMContentLoaded', () => {
  initSidebarToggle();
  initThemeToggle();
  initScrollReveal();
  initTabs();
  setActiveNav();
  preserveContextInNavigation();
  initActionHandlers();
});
