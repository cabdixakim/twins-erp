import Cropper from 'cropperjs';
import 'cropperjs/dist/cropper.min.css';

window.Cropper = Cropper;
/**
 * Twins ERP — sleep / session restore fix
 * Prevents frozen overlays after monitor sleep or session timeout
 */

// If page is restored from browser cache (very common after sleep)
window.addEventListener('pageshow', function (e) {
  if (e.persisted) {
    window.location.reload();
  }
});

function twinsCleanupOverlays() {
  const selectors = [
    '.modal-backdrop',
    '[data-backdrop]',
    '[data-overlay]',
    '.fixed.inset-0',
    '.fixed.inset-0.z-40',
    '.fixed.inset-0.z-50'
  ];

  document.querySelectorAll(selectors.join(',')).forEach(el => {
    const s = getComputedStyle(el);
    if (s.position === 'fixed' && (s.top === '0px' || s.inset !== 'auto')) {
      el.remove();
    }
  });

  document.documentElement.classList.remove('overflow-hidden');
  document.body.classList.remove('overflow-hidden');
}

// When tab regains focus (after sleep / lock screen)
window.addEventListener('focus', twinsCleanupOverlays);
document.addEventListener('visibilitychange', function () {
  if (!document.hidden) twinsCleanupOverlays();
});

// Backdrop blur fix on visibility change
document.addEventListener('visibilitychange', () => {
  if (document.visibilityState === 'visible') {
    document.body.classList.remove('backdrop-blur');
    document.body.style.backdropFilter = 'none';
  }
});