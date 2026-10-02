/**
 * Adishiv Luxury Hotel & Suites
 * Core Motion Framework & Reduced-Motion Gating (WP6.1)
 */

window.AdishivMotion = (function () {
  'use strict';

  // Check user preference for reduced motion
  const reducedMotionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
  let isReducedMotion = reducedMotionQuery.matches;

  reducedMotionQuery.addEventListener('change', (e) => {
    isReducedMotion = e.matches;
    if (isReducedMotion) {
      document.documentElement.classList.add('reduced-motion');
    } else {
      document.documentElement.classList.remove('reduced-motion');
    }
  });

  if (isReducedMotion) {
    document.documentElement.classList.add('reduced-motion');
  }

  // Motion duration tokens (in seconds for GSAP)
  const tokens = {
    micro: 0.18,
    ui: 0.32,
    reveal: 0.8,
    scene: 1.1,
    wipe: 0.45
  };

  // Eases
  const eases = {
    entrance: 'cubic-bezier(0.16, 1, 0.3, 1)',
    wipe: 'power4.inOut',
    smooth: 'power2.out',
    spring: 'elastic.out(1, 0.75)'
  };

  // Safe registration of GSAP plugins
  if (typeof gsap !== 'undefined' && typeof ScrollTrigger !== 'undefined') {
    gsap.registerPlugin(ScrollTrigger);
  }

  return {
    isReducedMotion: () => isReducedMotion,
    tokens,
    eases,
    gsap: typeof gsap !== 'undefined' ? gsap : null,
    ScrollTrigger: typeof ScrollTrigger !== 'undefined' ? ScrollTrigger : null
  };
})();
