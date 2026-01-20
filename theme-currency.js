(() => {
  const RATE = 320;

  function formatNumber(num) {
    try {
      return new Intl.NumberFormat(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num);
    } catch (_) {
      return (Math.round(num * 100) / 100).toFixed(2);
    }
  }

  function getCurrency() {
    const sessionCurrency = document.documentElement.getAttribute('data-currency');
    return sessionCurrency || localStorage.getItem('isdn_currency') || 'USD';
  }

  function setCurrency(cur) {
    localStorage.setItem('isdn_currency', cur);
    document.documentElement.setAttribute('data-currency', cur);
    convertAllPrices(cur);
    updateCurrencyUI(cur);
    const url = new URL(window.location);
    url.searchParams.set('set_currency', cur);
    window.history.replaceState({}, '', url);
  }

  function getTheme() {
    return localStorage.getItem('isdn_theme') || 'light';
  }

  function setTheme(theme) {
    localStorage.setItem('isdn_theme', theme);
    document.documentElement.setAttribute('data-theme', theme);
    updateThemeUI(theme);
  }

  function convertAllPrices(currency) {
    const nodes = document.querySelectorAll('[data-price]');
    nodes.forEach((el) => {
      const baseUsd = parseFloat(el.getAttribute('data-price') || '0') || 0;
      if (currency === 'LKR') {
        el.textContent = 'LKR ' + formatNumber(baseUsd * RATE);
      } else {
        el.textContent = '$' + formatNumber(baseUsd);
      }
    });
  }

  function updateCurrencyUI(currency) {
    const selects = document.querySelectorAll('[data-currency-select]');
    selects.forEach((sel) => {
      try { sel.value = currency; } catch (_) {}
    });
    const labels = document.querySelectorAll('[data-currency-label]');
    labels.forEach((el) => el.textContent = currency);
  }

  function updateThemeUI(theme) {
    const toggles = document.querySelectorAll('[data-theme-toggle]');
    toggles.forEach((btn) => {
      const icon = btn.querySelector('i');
      if (!icon) return;
      icon.classList.remove('fa-moon', 'fa-sun');
      icon.classList.add(theme === 'dark' ? 'fa-sun' : 'fa-moon');
      btn.setAttribute('aria-label', theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode');
    });
  }

  function initializeThemeAndCurrency() {
    const sessionCurrency = document.documentElement.getAttribute('data-currency');
    const theme = getTheme();
    let currency = sessionCurrency || getCurrency();
    
    if (!sessionCurrency && localStorage.getItem('isdn_currency')) {
      currency = localStorage.getItem('isdn_currency');
    }
    
    document.documentElement.setAttribute('data-theme', theme);
    document.documentElement.setAttribute('data-currency', currency);
    
    if (sessionCurrency) {
      localStorage.setItem('isdn_currency', sessionCurrency);
    } else if (currency) {
      localStorage.setItem('isdn_currency', currency);
    }
    
    convertAllPrices(currency);
    updateCurrencyUI(currency);
    updateThemeUI(theme);
  }

  function attachHandlers() {
    document.addEventListener('click', (e) => {
      const t = e.target.closest('[data-theme-toggle]');
      if (t) {
        const next = (getTheme() === 'dark') ? 'light' : 'dark';
        setTheme(next);
      }
    });

    document.addEventListener('change', (e) => {
      const sel = e.target.closest('[data-currency-select]');
      if (sel) {
        const newCurrency = sel.value;
        const currentCurrency = getCurrency();
        
        if (newCurrency !== currentCurrency && (newCurrency === 'USD' || newCurrency === 'LKR')) {
          setCurrency(newCurrency);
          
          const url = new URL(window.location.href);
          url.searchParams.set('set_currency', newCurrency);
          window.location.href = url.toString();
        }
      }
    });
  }

  window.ISDN = window.ISDN || {};
  window.ISDN.initializeThemeAndCurrency = initializeThemeAndCurrency;
  window.ISDN.toggleTheme = () => setTheme(getTheme() === 'dark' ? 'light' : 'dark');
  window.ISDN.changeCurrency = setCurrency;
  window.ISDN.convertAllPrices = convertAllPrices;

  document.addEventListener('DOMContentLoaded', () => {
    initializeThemeAndCurrency();
    attachHandlers();
  });
})();

