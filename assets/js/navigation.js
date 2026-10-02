/**
 * Adishiv Luxury Hotel & Suites
 * Header Navigation & Fullscreen Drawer Management (Accessible Focus Trap, Inert & BFCache safe)
 */

document.addEventListener('DOMContentLoaded', () => {
  const header = document.querySelector('.site-header');
  const menuTrigger = document.querySelector('.menu-trigger');
  const navOverlay = document.getElementById('navOverlay');
  const navCloseBtn = document.querySelector('.nav-close-btn');
  const mainContent = document.getElementById('mainContent') || document.querySelector('main');

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

  // 2. Fullscreen Drawer Navigation with Staggered Links (WP6.5)
  if (menuTrigger && navOverlay) {
    const menuItems = navOverlay.querySelectorAll('.nav-menu-item');
    const metaCols = navOverlay.querySelectorAll('.nav-overlay-meta > div');

    const openMenu = () => {
      const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
      document.body.style.overflow = 'hidden';
      if (scrollbarWidth > 0) {
        document.body.style.paddingRight = `${scrollbarWidth}px`;
      }
      if (mainContent) {
        mainContent.setAttribute('inert', '');
      }

      navOverlay.classList.add('is-active');
      navOverlay.setAttribute('aria-hidden', 'false');
      menuTrigger.setAttribute('aria-expanded', 'true');

      // Animate staggered menu links if GSAP is available
      if (typeof gsap !== 'undefined' && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        gsap.fromTo(menuItems, 
          { y: 35, opacity: 0 },
          { y: 0, opacity: 1, duration: 0.55, stagger: 0.06, ease: 'power3.out', delay: 0.15 }
        );
        gsap.fromTo(metaCols,
          { y: 20, opacity: 0 },
          { y: 0, opacity: 1, duration: 0.45, stagger: 0.08, ease: 'power2.out', delay: 0.3 }
        );
      }

      // Accessible Focus Trap: focus close button
      if (navCloseBtn) navCloseBtn.focus();
    };

    const closeMenu = () => {
      navOverlay.classList.remove('is-active');
      navOverlay.setAttribute('aria-hidden', 'true');
      menuTrigger.setAttribute('aria-expanded', 'false');

      if (mainContent) {
        mainContent.removeAttribute('inert');
      }
      document.body.style.overflow = '';
      document.body.style.paddingRight = '';
      menuTrigger.focus();
    };

    menuTrigger.addEventListener('click', openMenu);
    if (navCloseBtn) navCloseBtn.addEventListener('click', closeMenu);

    // Close on same-page anchor click
    navOverlay.querySelectorAll('a').forEach((link) => {
      link.addEventListener('click', () => {
        closeMenu();
      });
    });

    // Keyboard Trap & Escape Listener
    navOverlay.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        closeMenu();
        return;
      }

      // Trap Tab key inside overlay
      if (e.key === 'Tab') {
        const focusable = navOverlay.querySelectorAll('a, button, input, select, textarea');
        if (focusable.length === 0) return;
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

    // Reset state on BFCache restore (WP6.5)
    window.addEventListener('pageshow', (event) => {
      if (event.persisted) {
        closeMenu();
      }
    });
  }
});
