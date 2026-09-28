/**
 * Adishiv Luxury Hotel & Suites
 * Header Navigation & Fullscreen Drawer Management
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

  // 2. Fullscreen Drawer Navigation
  if (menuTrigger && navOverlay) {
    const openMenu = () => {
      navOverlay.classList.add('is-active');
      navOverlay.setAttribute('aria-hidden', 'false');
      menuTrigger.setAttribute('aria-expanded', 'true');
      document.body.style.overflow = 'hidden';

      // Focus first link for accessibility
      const firstLink = navOverlay.querySelector('a, button');
      if (firstLink) firstLink.focus();
    };

    const closeMenu = () => {
      navOverlay.classList.remove('is-active');
      navOverlay.setAttribute('aria-hidden', 'true');
      menuTrigger.setAttribute('aria-expanded', 'false');
      document.body.style.overflow = '';
      menuTrigger.focus();
    };

    menuTrigger.addEventListener('click', openMenu);
    if (navCloseBtn) navCloseBtn.addEventListener('click', closeMenu);

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && navOverlay.classList.contains('is-active')) {
        closeMenu();
      }
    });
  }
});
