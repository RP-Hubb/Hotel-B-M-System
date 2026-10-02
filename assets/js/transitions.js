/**
 * Adishiv Luxury Hotel & Suites
 * Cinema-Grade Page Transition Architecture
 * 
 * Delivers seamless, imperial split-curtain transitions between site chambers,
 * elevating navigation beyond standard browser page reloads.
 */

document.addEventListener('DOMContentLoaded', () => {
  const curtain = document.getElementById('pageTransitionCurtain');
  if (!curtain) return;

  const isReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (isReducedMotion) {
    curtain.style.display = 'none';
    return;
  }

  const panelLeft = curtain.querySelector('.transition-panel--left');
  const panelRight = curtain.querySelector('.transition-panel--right');
  const badge = curtain.querySelector('.transition-brand-badge');

  function resetCurtain() {
    if (typeof gsap !== 'undefined') {
      gsap.killTweensOf([curtain, panelLeft, panelRight, badge]);
      gsap.set(panelLeft, { xPercent: -100 });
      gsap.set(panelRight, { xPercent: 100 });
      gsap.set(badge, { opacity: 0, scale: 0.92 });
    } else {
      panelLeft.style.transform = 'translateX(-100%)';
      panelRight.style.transform = 'translateX(100%)';
      badge.style.opacity = '0';
    }
    curtain.classList.remove('is-active');
    curtain.setAttribute('inert', '');
    sessionStorage.removeItem('adishiv_page_transition');
  }

  // 1. PAGE ENTRANCE (When landing on a page after an internal transition)
  const isTransitioningIn = sessionStorage.getItem('adishiv_page_transition') === 'active';
  if (isTransitioningIn) {
    sessionStorage.removeItem('adishiv_page_transition');
    curtain.removeAttribute('inert');
    curtain.classList.add('is-active');

    if (typeof gsap !== 'undefined') {
      // Set panels closed covering viewport initially
      gsap.set(panelLeft, { xPercent: 0 });
      gsap.set(panelRight, { xPercent: 0 });
      gsap.set(badge, { opacity: 1, scale: 1 });

      const enterTl = gsap.timeline({
        onComplete: () => {
          curtain.classList.remove('is-active');
          curtain.setAttribute('inert', '');
        }
      });

      // Monogram gently dissolves
      enterTl.to(badge, {
        opacity: 0,
        scale: 0.88,
        duration: 0.28,
        ease: 'power2.in'
      }, 0.05);

      // Imperial dual panels part cleanly to left and right
      enterTl.to(panelLeft, {
        xPercent: -100,
        duration: 0.58,
        ease: 'power4.inOut'
      }, 0.18);

      enterTl.to(panelRight, {
        xPercent: 100,
        duration: 0.58,
        ease: 'power4.inOut'
      }, 0.18);

      // Main content elevates with subtle majestic glide
      const main = document.getElementById('mainContent') || document.querySelector('main');
      if (main) {
        enterTl.from(main, {
          y: 22,
          opacity: 0.88,
          duration: 0.62,
          ease: 'power3.out'
        }, 0.28);
      }
    } else {
      setTimeout(resetCurtain, 300);
    }
  }

  // 2. PAGE EXIT (Intercept internal link clicks)
  let isNavigating = false;

  document.addEventListener('click', (e) => {
    if (isNavigating) return;

    // Find clicked anchor element
    const link = e.target.closest('a');
    if (!link) return;

    const href = link.getAttribute('href');
    if (!href) return;

    // Ignore links with modifier keys (new tab/window intent)
    if (e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;

    // Ignore special protocols and non-page navigations
    if (
      href.startsWith('#') ||
      href.startsWith('mailto:') ||
      href.startsWith('tel:') ||
      href.startsWith('javascript:') ||
      link.hasAttribute('download') ||
      link.getAttribute('target') === '_blank' ||
      link.classList.contains('no-transition') ||
      link.closest('[data-no-transition]')
    ) {
      return;
    }

    // Resolve full destination URL
    const destination = new URL(link.href, window.location.href);

    // Only transition within same origin
    if (destination.origin !== window.location.origin) return;

    // Ignore same-page links with only hash or exact same URL
    if (
      destination.pathname === window.location.pathname &&
      destination.search === window.location.search
    ) {
      return;
    }

    // Ignore admin logout
    if (destination.pathname.includes('logout.php')) return;

    // Close mobile/fullscreen menu if open
    const navOverlay = document.getElementById('navOverlay');
    if (navOverlay && navOverlay.classList.contains('is-active')) {
      navOverlay.classList.remove('is-active');
      document.body.classList.remove('nav-open');
    }

    // Trigger seamless exit transition
    e.preventDefault();
    isNavigating = true;

    sessionStorage.setItem('adishiv_page_transition', 'active');
    curtain.removeAttribute('inert');
    curtain.classList.add('is-active');

    // Hard failsafe in case browser navigation stalls
    const failsafe = setTimeout(() => {
      window.location.href = destination.href;
    }, 2500);

    if (typeof gsap !== 'undefined') {
      const exitTl = gsap.timeline({
        onComplete: () => {
          clearTimeout(failsafe);
          window.location.href = destination.href;
        }
      });

      // Panels slide inward from left and right
      exitTl.to(panelLeft, {
        xPercent: 0,
        duration: 0.44,
        ease: 'power4.inOut'
      }, 0);

      exitTl.to(panelRight, {
        xPercent: 0,
        duration: 0.44,
        ease: 'power4.inOut'
      }, 0);

      // Brand monogram and title emerge in gold
      exitTl.to(badge, {
        opacity: 1,
        scale: 1,
        duration: 0.36,
        ease: 'power3.out'
      }, 0.12);
    } else {
      setTimeout(() => {
        clearTimeout(failsafe);
        window.location.href = destination.href;
      }, 350);
    }
  });

  // 3. BFCache & History Navigation Recovery
  window.addEventListener('pageshow', (event) => {
    isNavigating = false;
    resetCurtain();
  });

  window.addEventListener('popstate', () => {
    isNavigating = false;
    resetCurtain();
  });
});
