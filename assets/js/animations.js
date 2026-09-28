/**
 * Adishiv Luxury Hotel & Suites
 * Cinema-Grade Motion, ScrollTrigger Reveals, Magnetic Buttons & Custom Cursor
 */

document.addEventListener('DOMContentLoaded', () => {
  const isReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const isFinePointer = window.matchMedia('(pointer: fine)').matches;

  // ----------------------------------------------------------------------------
  // 1. CUSTOM DESKTOP LERP CURSOR
  // ----------------------------------------------------------------------------
  const dot = document.querySelector('.custom-cursor-dot');
  const ring = document.querySelector('.custom-cursor-ring');

  if (dot && ring && isFinePointer && !isReducedMotion) {
    let mouseX = -100, mouseY = -100;
    let ringX = -100, ringY = -100;
    let isVisible = false;

    window.addEventListener('mousemove', (e) => {
      mouseX = e.clientX;
      mouseY = e.clientY;
      if (!isVisible) {
        dot.style.opacity = '1';
        ring.style.opacity = '1';
        isVisible = true;
      }
      dot.style.transform = `translate(${mouseX}px, ${mouseY}px)`;
    }, { passive: true });

    document.addEventListener('mouseleave', () => {
      dot.style.opacity = '0';
      ring.style.opacity = '0';
      isVisible = false;
    });

    const updateCursor = () => {
      ringX += (mouseX - ringX) * 0.16;
      ringY += (mouseY - ringY) * 0.16;
      ring.style.transform = `translate(${ringX}px, ${ringY}px)`;
      requestAnimationFrame(updateCursor);
    };
    requestAnimationFrame(updateCursor);

    // Expand cursor on interactive targets
    const interactives = 'a, button, input, select, textarea, .room-card, .gallery-card, .btn';
    document.querySelectorAll(interactives).forEach((el) => {
      el.addEventListener('mouseenter', () => ring.classList.add('is-hover'));
      el.addEventListener('mouseleave', () => ring.classList.remove('is-hover'));
    });
  }

  // ----------------------------------------------------------------------------
  // 2. MAGNETIC BUTTON PROXIMITY (Desktop Only)
  // ----------------------------------------------------------------------------
  if (isFinePointer && !isReducedMotion) {
    const magneticBtns = document.querySelectorAll('.btn-gold, .btn-outline-gold, .menu-trigger');
    magneticBtns.forEach((btn) => {
      btn.addEventListener('mousemove', (e) => {
        const rect = btn.getBoundingClientRect();
        const centerX = rect.left + rect.width / 2;
        const centerY = rect.top + rect.height / 2;
        const deltaX = (e.clientX - centerX) * 0.25;
        const deltaY = (e.clientY - centerY) * 0.25;
        btn.style.transform = `translate(${deltaX}px, ${deltaY}px)`;
      });

      btn.addEventListener('mouseleave', () => {
        btn.style.transform = 'translate(0px, 0px)';
        btn.style.transition = 'transform 0.4s cubic-bezier(0.25, 1, 0.5, 1)';
        setTimeout(() => { btn.style.transition = ''; }, 400);
      });
    });
  }

  // ----------------------------------------------------------------------------
  // 3. GSAP SCROLLTRIGGER ORCHESTRATION
  // ----------------------------------------------------------------------------
  if (typeof gsap !== 'undefined' && typeof ScrollTrigger !== 'undefined' && !isReducedMotion) {
    gsap.registerPlugin(ScrollTrigger);

    // Section Headings & Eyebrows Reveal
    document.querySelectorAll('[data-reveal]').forEach((sec) => {
      gsap.fromTo(sec, 
        { y: 40, opacity: 0 },
        {
          y: 0,
          opacity: 1,
          duration: 0.9,
          ease: 'power3.out',
          scrollTrigger: {
            trigger: sec,
            start: 'top 85%',
            toggleActions: 'play none none none'
          }
        }
      );
    });

    // Room Cards Stagger Reveal
    const roomCards = document.querySelectorAll('.room-card');
    if (roomCards.length > 0) {
      gsap.fromTo(roomCards,
        { y: 50, opacity: 0 },
        {
          y: 0,
          opacity: 1,
          duration: 0.85,
          stagger: 0.18,
          ease: 'power3.out',
          scrollTrigger: {
            trigger: roomCards[0].parentElement,
            start: 'top 82%'
          }
        }
      );
    }

    // Editorial Photos Parallax Movement
    document.querySelectorAll('.lg-grid-2 img, .room-card-img').forEach((img) => {
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
    }, { rootMargin: '0px 0px -50px 0px', threshold: 0.1 });

    document.querySelectorAll('[data-reveal], .room-card').forEach((el) => observer.observe(el));
  }
});
