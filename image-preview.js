(function() {
  function initImagePreviews() {
    document.querySelectorAll('input[type="file"][accept*="image"]').forEach((input) => {
      if (input.dataset.previewInitialized === 'true') return;
      input.dataset.previewInitialized = 'true';
      
      const previewTarget = input.getAttribute('data-preview-target');
      let previewImg = previewTarget ? document.querySelector(previewTarget) : null;
      
      if (!previewImg) {
        const container = input.closest('.card, .field, form, section');
        if (container) {
          previewImg = container.querySelector('img[data-preview]');
        }
      }
      
      if (!previewImg) return;
      
      input.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) {
          return;
        }
        
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
          alert('Please select a valid image file (JPG, PNG, or WebP).');
          e.target.value = '';
          return;
        }
        
        if (file.size > 3 * 1024 * 1024) {
          alert('Image is too large. Maximum size is 3MB.');
          e.target.value = '';
          return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
          previewImg.src = e.target.result;
          previewImg.style.display = 'block';
          previewImg.style.opacity = '0';
          setTimeout(() => {
            previewImg.style.transition = 'opacity 0.3s ease';
            previewImg.style.opacity = '1';
          }, 10);
        };
        reader.readAsDataURL(file);
      });
    });
  }
  
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initImagePreviews);
  } else {
    initImagePreviews();
  }
  
  const observer = new MutationObserver(initImagePreviews);
  observer.observe(document.body, { childList: true, subtree: true });
})();
