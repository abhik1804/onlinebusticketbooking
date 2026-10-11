/**
 * Shreenathji Travels - Main Frontend JavaScript
 */

document.addEventListener('DOMContentLoaded', function () {
  // Sticky Navbar Blur Effect
  const navbar = document.querySelector('.st-navbar');
  if (navbar) {
    window.addEventListener('scroll', function () {
      if (window.scrollY > 30) {
        navbar.classList.add('scrolled');
      } else {
        navbar.classList.remove('scrolled');
      }
    });
  }

  // Initialize Bootstrap Tooltips & Popovers if available
  if (typeof bootstrap !== 'undefined') {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl);
    });
  }
});

/**
 * Toast Notification Utility
 * @param {string} message
 * @param {'success'|'danger'|'warning'|'info'} type
 */
function showToast(message, type = 'info') {
  let toastContainer = document.getElementById('st-toast-container');
  if (!toastContainer) {
    toastContainer = document.createElement('div');
    toastContainer.id = 'st-toast-container';
    toastContainer.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; display: flex; flex-direction: column; gap: 10px; pointer-events: none;';
    document.body.appendChild(toastContainer);
  }

  const toast = document.createElement('div');
  const bgColors = {
    success: '#10b981',
    danger: '#ef4444',
    warning: '#f59e0b',
    info: '#2563eb'
  };
  const icons = {
    success: '✓',
    danger: '✕',
    warning: '⚠',
    info: 'ℹ'
  };

  toast.style.cssText = `
    background: #ffffff;
    color: #0f172a;
    border-left: 4px solid ${bgColors[type] || '#2563eb'};
    border-radius: 8px;
    padding: 12px 18px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 280px;
    max-width: 420px;
    font-size: 0.9rem;
    font-weight: 600;
    pointer-events: auto;
    opacity: 0;
    transform: translateY(-10px);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  `;

  toast.innerHTML = `
    <span style="background: ${bgColors[type] || '#2563eb'}; color: #fff; width: 22px; height: 22px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; flex-shrink: 0;">${icons[type] || 'ℹ'}</span>
    <span style="flex-grow: 1;">${message}</span>
    <button type="button" style="border: none; background: transparent; color: #94a3b8; cursor: pointer; font-size: 1rem;" onclick="this.parentElement.remove();">&times;</button>
  `;

  toastContainer.appendChild(toast);
  // Animate in
  setTimeout(() => {
    toast.style.opacity = '1';
    toast.style.transform = 'translateY(0)';
  }, 10);

  // Auto remove after 4.5s
  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(-10px)';
    setTimeout(() => toast.remove(), 300);
  }, 4500);
}
