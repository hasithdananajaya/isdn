(function() {
  function initPasswordToggles() {
    document.querySelectorAll('[data-toggle-password]').forEach((btn) => {
      if (btn.dataset.initialized === 'true') return;
      btn.dataset.initialized = 'true';
      
      const selector = btn.getAttribute('data-toggle-password');
      const input = selector ? document.querySelector(selector) : btn.parentElement.querySelector('input[type="password"], input[type="text"]');
      
      if (!input) return;
      
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const isPassword = input.getAttribute('type') === 'password';
        input.setAttribute('type', isPassword ? 'text' : 'password');
        
        const icon = btn.querySelector('i');
        if (icon) {
          icon.classList.toggle('fa-eye');
          icon.classList.toggle('fa-eye-slash');
          btn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
        }
      });
    });
  }
  
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPasswordToggles);
  } else {
    initPasswordToggles();
  }
  
  const observer = new MutationObserver(initPasswordToggles);
  observer.observe(document.body, { childList: true, subtree: true });
})();
