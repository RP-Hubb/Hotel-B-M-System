/**
 * Adishiv Luxury Hotel & Suites
 * Preloader Choreography (Left Coast Reference Inspired)
 */

document.addEventListener('DOMContentLoaded', () => {
  const preloader = document.getElementById('adishivPreloader');
  if (!preloader) return;

  // Check if reduced motion is requested
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (prefersReducedMotion) {
    preloader.style.display = 'none';
    document.body.classList.add('page-revealed');
    return;
  }

  // Check if visitor has already seen the intro during this browser session
  const introSeen = sessionStorage.getItem('adishiv_intro_seen');
  const durationMultiplier = introSeen ? 0.35 : 1.0;

  const path = document.querySelector('.preloader-path');
  const title = document.querySelector('.preloader-brand-title');
  const sub = document.querySelector('.preloader-brand-sub');
  const bar = document.querySelector('.preloader-progress-bar');
  const counter = document.querySelector('.preloader-counter');

  let progress = 0;
  const stepTime = 18 * durationMultiplier;

  // Animate progress bar & counter
  const interval = setInterval(() => {
    progress += 2;
    if (bar) bar.style.width = `${progress}%`;
    if (counter) counter.textContent = `${String(progress).padStart(2, '0')} / 100`;

    // SVG path draw
    if (path) {
      const offset = 600 - (progress / 100) * 600;
      path.style.strokeDashoffset = offset;
    }

    if (progress >= 30 && title) {
      title.style.opacity = '1';
      title.style.transform = 'translateY(0)';
      title.style.transition = 'all 0.6s cubic-bezier(0.16, 1, 0.3, 1)';
    }

    if (progress >= 50 && sub) {
      sub.style.opacity = '1';
      sub.style.transform = 'translateY(0)';
      sub.style.transition = 'all 0.6s cubic-bezier(0.16, 1, 0.3, 1)';
    }

    if (progress >= 100) {
      clearInterval(interval);
      setTimeout(finishPreloader, 250 * durationMultiplier);
    }
  }, stepTime);

  function finishPreloader() {
    sessionStorage.setItem('adishiv_intro_seen', 'true');

    // Smooth curtain lift
    preloader.style.transition = 'transform 1.1s cubic-bezier(0.85, 0, 0.15, 1), opacity 0.8s ease';
    preloader.style.transform = 'translateY(-100%)';
    preloader.style.opacity = '0.9';

    document.body.classList.add('page-revealed');

    // Trigger hero entrance
    window.dispatchEvent(new CustomEvent('adishiv:entrance'));

    setTimeout(() => {
      preloader.style.display = 'none';
    }, 1200);
  }
});
