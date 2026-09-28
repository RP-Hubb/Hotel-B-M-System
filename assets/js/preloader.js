/**
 * Adishiv Luxury Hotel & Suites
 * Cinema-Grade Preloader & Hero Entrance Choreography (GSAP Powered)
 * Inspired by Left Coast (https://leftcoast.refractweb.com/)
 */

document.addEventListener('DOMContentLoaded', () => {
  const preloader = document.getElementById('adishivPreloader');
  if (!preloader) return;

  // 1. Accessibility: Strictly honor prefers-reduced-motion
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (prefersReducedMotion) {
    preloader.style.display = 'none';
    document.body.classList.add('page-revealed');
    // Ensure hero elements are immediately visible
    const heroElements = document.querySelectorAll('.hero-bg-media, .hero-headline, .hero-subheadline, .hero-content .eyebrow, .booking-bar-wrapper, .site-header');
    heroElements.forEach(el => {
      el.style.opacity = '1';
      el.style.transform = 'none';
    });
    return;
  }

  // Check if visitor has already seen the full intro during this session
  const introSeen = sessionStorage.getItem('adishiv_intro_seen');
  const isQuickIntro = Boolean(introSeen);

  const path = document.querySelector('.preloader-path');
  const title = document.querySelector('.preloader-brand-title');
  const sub = document.querySelector('.preloader-brand-sub');
  const bar = document.querySelector('.preloader-progress-bar');
  const counter = document.querySelector('.preloader-counter');

  // Hero elements to orchestrate
  const heroMedia = document.querySelector('.hero-bg-media');
  const heroEyebrow = document.querySelector('.hero-content .eyebrow');
  const heroHeadline = document.querySelector('.hero-headline');
  const heroSubheadline = document.querySelector('.hero-subheadline');
  const heroCtaGroup = document.querySelector('.hero-content > div:last-child');
  const bookingBar = document.querySelector('.booking-bar-wrapper');
  const header = document.querySelector('.site-header');

  // Check if GSAP is available
  if (typeof gsap !== 'undefined') {
    // Master GSAP Timeline
    const tl = gsap.timeline({
      onComplete: () => {
        sessionStorage.setItem('adishiv_intro_seen', 'true');
        preloader.style.display = 'none';
        document.body.classList.add('page-revealed');
      }
    });

    if (isQuickIntro) {
      // Streamlined 0.4s micro-transition for subsequent navigations
      tl.to(preloader, {
        yPercent: -100,
        duration: 0.55,
        ease: 'power3.inOut'
      });
      if (heroMedia) {
        tl.fromTo(heroMedia, { scale: 1.05 }, { scale: 1.0, duration: 0.8, ease: 'power2.out' }, '-=0.3');
      }
    } else {
      // Full cinematic 2.2s Left-Coast inspired introduction
      const countObj = { value: 0 };

      // Phase 1: SVG Monogram Arch Draw & Counter
      tl.to(countObj, {
        value: 100,
        duration: 1.4,
        ease: 'power2.inOut',
        onUpdate: () => {
          const val = Math.round(countObj.value);
          if (counter) counter.textContent = `${String(val).padStart(2, '0')} / 100`;
          if (bar) bar.style.width = `${val}%`;
          if (path) {
            path.style.strokeDashoffset = 600 - (val / 100) * 600;
          }
        }
      }, 0);

      // Phase 2: Brand typography emergence
      tl.to(title, {
        opacity: 1,
        y: 0,
        duration: 0.7,
        ease: 'power3.out'
      }, 0.35);

      tl.to(sub, {
        opacity: 1,
        y: 0,
        duration: 0.7,
        ease: 'power3.out'
      }, 0.6);

      // Phase 3: Monogram fill glow
      if (path) {
        tl.to(path, {
          fill: 'rgba(200, 164, 107, 0.15)',
          duration: 0.4
        }, 1.1);
      }

      // Phase 4: Curtain wipe revealing the sanctuary
      tl.to(preloader, {
        yPercent: -100,
        duration: 0.95,
        ease: 'power4.inOut'
      }, 1.55);

      // Phase 5: Hero orchestrated entrance
      if (heroMedia) {
        tl.fromTo(heroMedia, 
          { scale: 1.12, opacity: 0.4 }, 
          { scale: 1.0, opacity: 0.65, duration: 1.4, ease: 'power2.out' }, 
          1.7
        );
      }

      if (heroEyebrow) {
        tl.fromTo(heroEyebrow, 
          { y: 20, opacity: 0 }, 
          { y: 0, opacity: 1, duration: 0.7, ease: 'power3.out' }, 
          1.85
        );
      }

      if (heroHeadline) {
        tl.fromTo(heroHeadline, 
          { y: 35, opacity: 0 }, 
          { y: 0, opacity: 1, duration: 0.85, ease: 'power3.out' }, 
          1.95
        );
      }

      if (heroSubheadline) {
        tl.fromTo(heroSubheadline, 
          { y: 20, opacity: 0 }, 
          { y: 0, opacity: 1, duration: 0.75, ease: 'power3.out' }, 
          2.05
        );
      }

      if (heroCtaGroup) {
        tl.fromTo(heroCtaGroup, 
          { y: 15, opacity: 0 }, 
          { y: 0, opacity: 1, duration: 0.65, ease: 'power3.out' }, 
          2.15
        );
      }

      if (bookingBar) {
        tl.fromTo(bookingBar, 
          { y: 40, opacity: 0 }, 
          { y: 0, opacity: 1, duration: 0.85, ease: 'power3.out' }, 
          2.1
        );
      }

      if (header) {
        tl.fromTo(header, 
          { y: -20, opacity: 0 }, 
          { y: 0, opacity: 1, duration: 0.65, ease: 'power2.out' }, 
          2.0
        );
      }
    }
  } else {
    // Fallback if GSAP is unavailable (native RAF timer)
    let p = 0;
    const step = () => {
      p += 4;
      if (bar) bar.style.width = `${p}%`;
      if (counter) counter.textContent = `${String(Math.min(100, p)).padStart(2, '0')} / 100`;
      if (path) path.style.strokeDashoffset = 600 - (p / 100) * 600;
      if (p >= 100) {
        preloader.style.transition = 'transform 0.9s cubic-bezier(0.85, 0, 0.15, 1)';
        preloader.style.transform = 'translateY(-100%)';
        setTimeout(() => { preloader.style.display = 'none'; }, 950);
      } else {
        requestAnimationFrame(step);
      }
    };
    requestAnimationFrame(step);
  }
});
