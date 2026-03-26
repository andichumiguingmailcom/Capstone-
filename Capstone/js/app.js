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
  document.querySelector('.sidebar').classList.toggle('open');
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
  const els = document.querySelectorAll('.card, .stat-card, .welcome-hero');
  if (!els.length) return;
  els.forEach((el) => el.classList.add('reveal-on-scroll'));
  const io = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          io.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.06, rootMargin: '0px 0px -32px 0px' }
  );
  els.forEach((el) => io.observe(el));
}

document.addEventListener('DOMContentLoaded', () => {
  initThemeToggle();
  initScrollReveal();
  initTabs();
  setActiveNav();
});
