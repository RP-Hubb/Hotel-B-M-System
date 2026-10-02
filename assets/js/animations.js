/**
 * Adishiv Luxury Hotel & Suites
 * Cinema-Grade Motion, ScrollTrigger Reveals, Magnetic Buttons & Custom Cursor (WP6)
 */

document.addEventListener('DOMContentLoaded', () => {
  const isReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const isFinePointer = window.matchMedia('(pointer: fine)').matches;

  // ----------------------------------------------------------------------------
  // 1. CUSTOM DESKTOP LERP CURSOR (WP6.8)
  // ----------------------------------------------------------------------------
  const dot = document.querySelector('.custom-cursor-dot');
  const ring = document.querySelector('.custom-cursor-ring');

  if (dot && ring && isFinePointer && !isReducedMotion) {
    let mouseX = -100, mouseY = -100;
    let ringX = -100, ringY = -100;
    let isVisible = false;
    let isRunning = false;
    let rafId = null;

    const renderLoop = () => {
      const dx = mouseX - ringX;
      const dy = mouseY - ringY;
      ringX += dx * 0.18;
      ringY += dy * 0.18;

      ring.style.transform = `translate3d(${ringX}px, ${ringY}px, 0) translate(-50%, -50%)`;

      // Stop rAF loop when settled to preserve CPU cycles
      if (Math.abs(dx) < 0.1 && Math.abs(dy) < 0.1) {
        isRunning = false;
        cancelAnimationFrame(rafId);
      } else {
        rafId = requestAnimationFrame(renderLoop);
      }
    };

    window.addEventListener('mousemove', (e) => {
      mouseX = e.clientX;
      mouseY = e.clientY;

      if (!isVisible) {
        dot.style.opacity = '1';
        ring.style.opacity = '1';
        isVisible = true;
      }

      dot.style.transform = `translate3d(${mouseX}px, ${mouseY}px, 0) translate(-50%, -50%)`;

      if (!isRunning) {
        isRunning = true;
        rafId = requestAnimationFrame(renderLoop);
      }
    }, { passive: true });

    document.addEventListener('mouseleave', () => {
      dot.style.opacity = '0';
      ring.style.opacity = '0';
      isVisible = false;
    });

    // Event delegation: robust for dynamically rendered cards & AJAX elements
    document.addEventListener('mouseover', (e) => {
      if (e.target && e.target.closest('a, button, input, select, textarea, .room-card, .btn, [data-cursor]')) {
        ring.classList.add('is-hover');
      }
    });

    document.addEventListener('mouseout', (e) => {
      if (e.target && e.target.closest('a, button, input, select, textarea, .room-card, .btn, [data-cursor]')) {
        ring.classList.remove('is-hover');
      }
    });
  }

  // ----------------------------------------------------------------------------
  // 2. MAGNETIC BUTTON PROXIMITY (Desktop Only, WP6.5 - clamped to 8px)
  // ----------------------------------------------------------------------------
  if (isFinePointer && !isReducedMotion) {
    const magneticSelector = '[data-magnetic="true"], .btn-gold, .btn-outline-gold';
    document.querySelectorAll(magneticSelector).forEach((btn) => {
      btn.addEventListener('mousemove', (e) => {
        const rect = btn.getBoundingClientRect();
        const centerX = rect.left + rect.width / 2;
        const centerY = rect.top + rect.height / 2;
        // Limit maximum magnetic deflection to 8px
        const deltaX = Math.max(-8, Math.min(8, (e.clientX - centerX) * 0.2));
        const deltaY = Math.max(-8, Math.min(8, (e.clientY - centerY) * 0.2));
        btn.style.transform = `translate3d(${deltaX}px, ${deltaY}px, 0)`;
      });

      btn.addEventListener('mouseleave', () => {
        btn.style.transform = 'translate3d(0, 0, 0)';
        btn.style.transition = 'transform 0.35s cubic-bezier(0.16, 1, 0.3, 1)';
        setTimeout(() => { btn.style.transition = ''; }, 350);
      });
    });
  }

  // ----------------------------------------------------------------------------
  // 3. HERO ENTRANCE LISTENER (WP6.3)
  // ----------------------------------------------------------------------------
  const orchestrateHeroEntrance = (detail) => {
    if (isReducedMotion) {
      document.querySelectorAll('.hero-bg-media, .hero-content > *, .booking-bar-wrapper, .site-header').forEach((el) => {
        el.style.opacity = '1';
        el.style.transform = 'none';
      });
      return;
    }

    const heroMedia = document.querySelector('.hero-bg-media');
    const heroEyebrow = document.querySelector('.hero-content .eyebrow');
    const heroHeadline = document.querySelector('.hero-headline');
    const heroSubheadline = document.querySelector('.hero-subheadline');
    const heroCtaGroup = document.querySelector('.hero-content > div:last-child');
    const bookingBar = document.querySelector('.booking-bar-wrapper');
    const header = document.querySelector('.site-header');

    if (typeof gsap !== 'undefined') {
      const heroTl = gsap.timeline();

      if (heroMedia) {
        heroTl.fromTo(heroMedia, 
          { scale: 1.14 }, 
          { scale: 1.0, duration: 1.8, ease: 'expo.out' }, 
          0
        );
      }

      if (header) {
        heroTl.fromTo(header, 
          { y: -20, opacity: 0 }, 
          { y: 0, opacity: 1, duration: 0.8, ease: 'power2.out' }, 
          0.2
        );
      }

      if (heroEyebrow) {
        heroTl.fromTo(heroEyebrow, 
          { y: 25, opacity: 0 }, 
          { y: 0, opacity: 1, duration: 0.7, ease: 'power3.out' }, 
          0.3
        );
      }

      if (heroHeadline) {
        heroTl.fromTo(heroHeadline, 
          { y: 35, opacity: 0 }, 
          { y: 0, opacity: 1, duration: 0.9, ease: 'power3.out' }, 
          0.45
        );
      }

      if (heroSubheadline) {
        heroTl.fromTo(heroSubheadline, 
          { y: 25, opacity: 0 }, 
          { y: 0, opacity: 1, duration: 0.75, ease: 'power3.out' }, 
          0.6
        );
      }

      if (heroCtaGroup) {
        heroTl.fromTo(heroCtaGroup, 
          { y: 20, opacity: 0 }, 
          { y: 0, opacity: 1, duration: 0.65, ease: 'power3.out' }, 
          0.75
        );
      }

      if (bookingBar) {
        heroTl.fromTo(bookingBar, 
          { y: 35, opacity: 0 }, 
          { y: 0, opacity: 1, duration: 0.85, ease: 'power3.out' }, 
          0.85
        );
      }
    }
  };

  window.addEventListener('adishiv:entrance', (e) => {
    orchestrateHeroEntrance(e.detail || {});
  });

  // ----------------------------------------------------------------------------
  // 4. SCROLLTRIGGER REVEAL SYSTEM (WP6.4)
  // ----------------------------------------------------------------------------
  if (typeof gsap !== 'undefined' && typeof ScrollTrigger !== 'undefined' && !isReducedMotion) {
    gsap.registerPlugin(ScrollTrigger);

    document.querySelectorAll('[data-reveal]').forEach((el) => {
      ScrollTrigger.create({
        trigger: el,
        start: 'top 85%',
        once: true,
        onEnter: () => {
          el.classList.add('is-revealed');
        }
      });
    });

    // Staggered Room & Gallery Cards
    const roomCards = document.querySelectorAll('.room-card');
    if (roomCards.length > 0) {
      ScrollTrigger.create({
        trigger: roomCards[0].parentElement,
        start: 'top 82%',
        once: true,
        onEnter: () => {
          gsap.fromTo(roomCards,
            { y: 40, opacity: 0 },
            { y: 0, opacity: 1, duration: 0.8, stagger: 0.15, ease: 'power3.out' }
          );
        }
      });
    }

    // Parallax on key editorial images (WP6.11 - restricted to <= 6% travel)
    document.querySelectorAll('.hero-bg-media, .room-card-img').forEach((img) => {
      gsap.to(img, {
        yPercent: -6,
        ease: 'none',
        scrollTrigger: {
          trigger: img,
          start: 'top bottom',
          end: 'bottom top',
          scrub: true
        }
      });
    });

  } else if (!isReducedMotion) {
    // Native IntersectionObserver Fallback
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-revealed');
          observer.unobserve(entry.target);
        }
      });
    }, { rootMargin: '0px 0px -40px 0px', threshold: 0.1 });

    document.querySelectorAll('[data-reveal], .room-card').forEach((el) => observer.observe(el));
  }
});
