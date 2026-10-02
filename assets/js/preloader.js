/**
 * Adishiv Luxury Hotel & Suites
 * Cinema-Grade Preloader & Architectural Entrance Choreography (WP6a Specification)
 */

document.addEventListener('DOMContentLoaded', () => {
  const preloader = document.getElementById('adishivPreloader');
  if (!preloader) return;

  const isReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const introSeen = sessionStorage.getItem('adishiv_intro_seen');

  // Skip preloader on reduced motion or internal navigation/return visits
  if (isReducedMotion || introSeen) {
    preloader.style.display = 'none';
    preloader.removeAttribute('inert');
    document.body.classList.add('page-revealed');
    window.dispatchEvent(new CustomEvent('adishiv:entrance', { detail: { isInitial: false } }));
    return;
  }

  // Lock body scroll during preloader presentation without layout shift
  const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
  document.body.style.overflow = 'hidden';
  if (scrollbarWidth > 0) {
    document.body.style.paddingRight = `${scrollbarWidth}px`;
  }

  // Hard failsafe: remove preloader after 5.5s under all circumstances
  const hardFailsafe = setTimeout(() => {
    finishPreloader();
  }, 5500);

  const path = preloader.querySelector('.preloader-path');
  const chars = preloader.querySelectorAll('.preloader-brand-title .char');
  const sub = preloader.querySelector('.preloader-brand-sub');
  const bar = preloader.querySelector('.preloader-progress-bar');
  const counter = preloader.querySelector('.preloader-counter');

  const heroMedia = document.querySelector('.hero-bg-media');

  function finishPreloader() {
    clearTimeout(hardFailsafe);
    sessionStorage.setItem('adishiv_intro_seen', 'true');
    preloader.style.display = 'none';
    preloader.removeAttribute('inert');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
    document.body.classList.add('page-revealed');
    window.dispatchEvent(new CustomEvent('adishiv:entrance', { detail: { isInitial: true } }));
  }

  // Readiness-driven promises
  const fontPromise = document.fonts ? document.fonts.ready : Promise.resolve();
  const heroPromise = (heroMedia && typeof heroMedia.decode === 'function') 
    ? heroMedia.decode().catch(() => {}) 
    : Promise.resolve();
  const minDisplayPromise = new Promise((resolve) => setTimeout(resolve, 1400));

  if (typeof gsap !== 'undefined') {
    const tl = gsap.timeline({
      onComplete: finishPreloader
    });

    const progressTracker = { value: 0 };

    // Animate progress smoothly toward 100%
    tl.to(progressTracker, {
      value: 100,
      duration: 1.8,
      ease: 'power2.inOut',
      onUpdate: () => {
        const val = Math.round(progressTracker.value);
        if (counter) counter.textContent = `${String(val).padStart(2, '0')} / 100`;
        if (bar) bar.style.transform = `scaleX(${val / 100})`;
      }
    }, 0);

    // Monogram stroke draw using normalized pathLength="1"
    if (path) {
      tl.fromTo(path, 
        { strokeDashoffset: 1 }, 
        { strokeDashoffset: 0, duration: 1.3, ease: 'power2.inOut' }, 
        0.1
      );
    }

    // "A D I S H I V" letters rise with 60ms stagger
    if (chars.length > 0) {
      tl.to(chars, {
        opacity: 1,
        yPercent: 0,
        duration: 0.7,
        stagger: 0.06,
        ease: 'power3.out'
      }, 0.7);
    }

    // Brand subheadline fades in
    if (sub) {
      tl.to(sub, {
        opacity: 1,
        y: 0,
        duration: 0.6,
        ease: 'power2.out'
      }, 1.25);
    }

    // Monogram fills with subtle brass glow
    if (path) {
      tl.to(path, {
        fill: 'rgba(200, 164, 107, 0.22)',
        duration: 0.4,
        ease: 'power2.inOut'
      }, 1.6);
    }

    // Architectural clip-path curtain wipe
    tl.to(preloader, {
      clipPath: 'inset(0 0 100% 0)',
      duration: 0.95,
      ease: 'power4.inOut'
    }, 1.9);

    // Overlap: trigger hero entrance at 40% of the wipe
    tl.add(() => {
      window.dispatchEvent(new CustomEvent('adishiv:entrance', { detail: { isInitial: true } }));
    }, 2.25);

    // Coordinate with real asset readiness
    Promise.all([fontPromise, heroPromise, minDisplayPromise]).then(() => {
      // Readiness confirmed
    });

  } else {
    // Native RAF fallback
    let p = 0;
    const step = () => {
      p += 2.5;
      if (bar) bar.style.transform = `scaleX(${Math.min(1, p / 100)})`;
      if (counter) counter.textContent = `${String(Math.min(100, Math.round(p))).padStart(2, '0')} / 100`;
      if (path) path.style.strokeDashoffset = String(Math.max(0, 1 - (p / 100)));
      if (p >= 100) {
        preloader.style.transition = 'clip-path 0.9s cubic-bezier(0.16, 1, 0.3, 1)';
        preloader.style.clipPath = 'inset(0 0 100% 0)';
        setTimeout(finishPreloader, 920);
      } else {
        requestAnimationFrame(step);
      }
    };
    requestAnimationFrame(step);
  }
});
