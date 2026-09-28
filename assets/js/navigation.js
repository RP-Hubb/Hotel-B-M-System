/**
 * Adishiv Luxury Hotel & Suites
 * Header Navigation & Fullscreen Drawer Management (GSAP Choreography & Accessible Focus Trap)
 */

document.addEventListener('DOMContentLoaded', () => {
  const header = document.querySelector('.site-header');
  const menuTrigger = document.querySelector('.menu-trigger');
  const navOverlay = document.getElementById('navOverlay');
  const navCloseBtn = document.querySelector('.nav-close-btn');

  // 1. Sticky Header Scroll Effect
  if (header) {
    const handleScroll = () => {
      if (window.scrollY > 40) {
        header.classList.add('scrolled');
      } else {
        header.classList.remove('scrolled');
      }
    };
    window.addEventListener('scroll', handleScroll, { passive: true });
    handleScroll();
  }

  // 2. Fullscreen Drawer Navigation with Staggered Links
  if (menuTrigger && navOverlay) {
    const menuItems = navOverlay.querySelectorAll('.nav-menu-item');
    const metaCols = navOverlay.querySelectorAll('.nav-overlay-meta > div');

    const openMenu = () => {
      navOverlay.classList.add('is-active');
      navOverlay.setAttribute('aria-hidden', 'false');
      menuTrigger.setAttribute('aria-expanded', 'true');
      document.body.style.overflow = 'hidden';

      // Animate staggered menu links if GSAP is available
      if (typeof gsap !== 'undefined') {
        gsap.fromTo(menuItems, 
          { y: 35, opacity: 0 },
          { y: 0, opacity: 1, duration: 0.6, stagger: 0.08, ease: 'power3.out', delay: 0.1 }
        );
        gsap.fromTo(metaCols,
          { y: 25, opacity: 0 },
          { y: 0, opacity: 1, duration: 0.5, stagger: 0.1, ease: 'power2.out', delay: 0.3 }
        );
      }

      // Accessible Focus Trap: focus first element
      if (navCloseBtn) navCloseBtn.focus();
    };

    const closeMenu = () => {
      if (typeof gsap !== 'undefined') {
        gsap.to(navOverlay, {
          opacity: 0,
          duration: 0.35,
          ease: 'power2.inOut',
          onComplete: () => {
            navOverlay.classList.remove('is-active');
            navOverlay.setAttribute('aria-hidden', 'true');
            menuTrigger.setAttribute('aria-expanded', 'false');
            document.body.style.overflow = '';
            gsap.set(navOverlay, { opacity: '' });
            menuTrigger.focus();
          }
        });
      } else {
        navOverlay.classList.remove('is-active');
        navOverlay.setAttribute('aria-hidden', 'true');
        menuTrigger.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
        menuTrigger.focus();
      }
    };

    menuTrigger.addEventListener('click', openMenu);
    if (navCloseBtn) navCloseBtn.addEventListener('click', closeMenu);

    // Keyboard Trap & Escape Listener
    navOverlay.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        closeMenu();
        return;
      }

      // Trap Tab key inside overlay
      if (e.key === 'Tab') {
        const focusable = navOverlay.querySelectorAll('a, button, input');
        const first = focusable[0];
        const last = focusable[focusable.length - 1];

        if (e.shiftKey && document.activeElement === first) {
          last.focus();
          e.preventDefault();
        } else if (!e.shiftKey && document.activeElement === last) {
          first.focus();
          e.preventDefault();
        }
      }
    });
  }
});
