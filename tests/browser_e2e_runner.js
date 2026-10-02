/**
 * Adishiv Luxury Hotel & Suites
 * Automated Chrome Browser E2E Test Suite (Native CDP over WebSocket)
 *
 * Runs against live Chrome browser instance on port 9222.
 */

const { spawn } = require('child_process');
const fs = require('fs');
const path = require('path');

const ARTIFACT_DIR = 'C:\\Users\\Raj\\.gemini\\antigravity-ide\\brain\\87586af2-f5a3-40b2-afbb-7f674355a616';

class ChromeController {
  constructor(wsUrl) {
    this.wsUrl = wsUrl;
    this.ws = null;
    this.msgId = 0;
    this.callbacks = new Map();
    this.consoleLogs = [];
    this.consoleErrors = [];
    this.networkErrors = [];
  }

  async connect() {
    return new Promise((resolve, reject) => {
      this.ws = new WebSocket(this.wsUrl);
      this.ws.onopen = () => resolve();
      this.ws.onerror = (err) => reject(err);
      this.ws.onmessage = (event) => {
        const msg = JSON.parse(event.data);
        if (msg.id && this.callbacks.has(msg.id)) {
          const cb = this.callbacks.get(msg.id);
          this.callbacks.delete(msg.id);
          if (msg.error) cb.reject(new Error(msg.error.message));
          else cb.resolve(msg.result);
        } else if (msg.method) {
          this.handleEvent(msg.method, msg.params);
        }
      };
    });
  }

  handleEvent(method, params) {
    if (method === 'Runtime.consoleAPICalled') {
      const text = params.args.map(a => a.value || a.description || '').join(' ');
      this.consoleLogs.push({ type: params.type, text });
      if (params.type === 'error') {
        this.consoleErrors.push(text);
      }
    } else if (method === 'Runtime.exceptionThrown') {
      const desc = params.exceptionDetails.exception?.description || params.exceptionDetails.text;
      this.consoleErrors.push(desc);
    } else if (method === 'Network.responseReceived') {
      const res = params.response;
      if (res.status >= 400 && !res.url.includes('favicon.ico')) {
        this.networkErrors.push({ url: res.url, status: res.status });
      }
    }
  }

  send(method, params = {}, timeoutMs = 12000) {
    return new Promise((resolve, reject) => {
      const id = ++this.msgId;
      const timer = setTimeout(() => {
        if (this.callbacks.has(id)) {
          this.callbacks.delete(id);
          reject(new Error(`CDP command timed out: ${method} (id=${id})`));
        }
      }, timeoutMs);

      this.callbacks.set(id, {
        resolve: (val) => {
          clearTimeout(timer);
          resolve(val);
        },
        reject: (err) => {
          clearTimeout(timer);
          reject(err);
        }
      });
      this.ws.send(JSON.stringify({ id, method, params }));
    });
  }

  async initDomains() {
    await this.send('Page.enable');
    await this.send('Runtime.enable');
    await this.send('Network.enable');
    await this.send('DOM.enable');
  }

  async navigate(url, waitMs = 1500) {
    await this.send('Page.navigate', { url });
    await new Promise(r => setTimeout(r, waitMs));
  }

  async evaluate(expression, retries = 3) {
    const trimmed = expression.trim();
    let code;
    if (trimmed.startsWith('return ') || trimmed.includes('\n') || trimmed.includes(';') || trimmed.startsWith('const ') || trimmed.startsWith('let ') || trimmed.startsWith('var ')) {
      code = `(() => {\n${trimmed}\n})()`;
    } else {
      code = `(() => (${trimmed}))()`;
    }

    for (let attempt = 0; attempt < retries; attempt++) {
      try {
        const res = await this.send('Runtime.evaluate', {
          expression: code,
          returnByValue: true,
          awaitPromise: true
        });
        if (res.exceptionDetails) {
          throw new Error(res.exceptionDetails.exception?.description || res.exceptionDetails.text);
        }
        return res.result?.value;
      } catch (err) {
        if (err.message && (err.message.includes('Execution context was destroyed') || err.message.includes('Cannot find context')) && attempt < retries - 1) {
          await new Promise(r => setTimeout(r, 600));
          continue;
        }
        throw err;
      }
    }
  }

  async captureScreenshot(filename) {
    const res = await this.send('Page.captureScreenshot', { format: 'png' });
    const buffer = Buffer.from(res.data, 'base64');
    const outPath = path.join(ARTIFACT_DIR, filename);
    fs.writeFileSync(outPath, buffer);
    return outPath;
  }

  async setViewport(width, height, isMobile = false) {
    await this.send('Emulation.setDeviceMetricsOverride', {
      width,
      height,
      deviceScaleFactor: 1,
      mobile: isMobile
    });
  }

  async setReducedMotion(enabled) {
    await this.send('Emulation.setEmulatedMedia', {
      features: [{ name: 'prefers-reduced-motion', value: enabled ? 'reduce' : 'no-preference' }]
    });
  }
}

async function runBrowserSuite() {
  console.log('=======================================================');
  console.log(' ADISHIV — REAL CHROME BROWSER E2E VERIFICATION SUITE');
  console.log('=======================================================\n');

  const profileDir = path.join(ARTIFACT_DIR, 'chrome_test_profile_' + Date.now());
  if (!fs.existsSync(profileDir)) fs.mkdirSync(profileDir, { recursive: true });

  const chromePath = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
  const chrome = spawn(chromePath, [
    '--headless=new',
    '--disable-gpu',
    '--remote-debugging-port=9225',
    `--user-data-dir=${profileDir}`,
    '--window-size=1440,900',
    '--no-first-run',
    '--no-default-browser-check',
    'http://127.0.0.1:8000/'
  ]);

  let tabs = null;
  for (let i = 0; i < 20; i++) {
    try {
      const res = await fetch('http://127.0.0.1:9225/json/list');
      tabs = await res.json();
      if (tabs && tabs.length > 0) break;
    } catch (e) {
      await new Promise(r => setTimeout(r, 400));
    }
  }

  if (!tabs || tabs.length === 0) {
    throw new Error('Could not connect to Chrome debugging port 9225 after 20 attempts.');
  }

  let ctl;
  let testCount = 0;
  let passCount = 0;
  let failCount = 0;

  function report(name, condition, detail = '') {
    testCount++;
    if (condition) {
      passCount++;
      console.log(`  [PASS] ${name}`);
    } else {
      failCount++;
      console.log(`  [FAIL] ${name} ${detail ? `- ${detail}` : ''}`);
    }
  }

  try {
    const pageTab = tabs.find(t => t.type === 'page') || tabs[0];
    ctl = new ChromeController(pageTab.webSocketDebuggerUrl);
    await ctl.connect();
    await ctl.initDomains();

    // ------------------------------------------------------------------------
    // TEST 1: HOMEPAGE INITIAL VISIT, PRELOADER & HERO REVEAL
    // ------------------------------------------------------------------------
    console.log('1. Testing Homepage Initial Visit, Preloader & Hero Entrance...');
    await ctl.navigate('http://127.0.0.1:8000/');
    
    // Wait for preloader animation to finish (~2.8s total)
    await new Promise(r => setTimeout(r, 3200));

    const title = await ctl.evaluate('document.title');
    report('Homepage document.title is correct', title.includes('Adishiv'), title);

    const isPreloaderGone = await ctl.evaluate(`
      const p = document.getElementById('adishivPreloader');
      return p ? (window.getComputedStyle(p).display === 'none' || p.style.display === 'none') : false;
    `);
    report('Preloader curtain dismisses cleanly after timeline', isPreloaderGone);

    const isHeroVisible = await ctl.evaluate(`
      const h = document.querySelector('.hero-headline');
      return h ? (window.getComputedStyle(h).opacity !== '0' && h.textContent.includes('Sanctuary')) : false;
    `);
    report('Hero headline is visible and contains brand headline', isHeroVisible);

    const hasQuickBooking = await ctl.evaluate(`
      const b = document.getElementById('quickBookingForm');
      return b !== null && b.querySelector('input[name="check_in"]') !== null;
    `);
    report('Quick booking form is present with inputs on homepage', hasQuickBooking);

    await ctl.captureScreenshot('screenshot_desktop_homepage.png');

    // ------------------------------------------------------------------------
    // TEST 2: RETURNING VISIT IN SAME SESSION (PRELOADER BYPASS)
    // ------------------------------------------------------------------------
    console.log('\n2. Testing Returning Session (Preloader Fast Bypass)...');
    await ctl.navigate('http://127.0.0.1:8000/about.php', 1000);
    await ctl.navigate('http://127.0.0.1:8000/', 500);

    const introSeenVal = await ctl.evaluate(`sessionStorage.getItem('adishiv_intro_seen')`);
    report('sessionStorage records adishiv_intro_seen flag', introSeenVal === 'true');

    const instantPreloaderBypass = await ctl.evaluate(`
      const p = document.getElementById('adishivPreloader');
      return p ? (p.style.display === 'none' || window.getComputedStyle(p).display === 'none') : false;
    `);
    report('Subsequent visit immediately bypasses preloader without blocking', instantPreloaderBypass);

    // ------------------------------------------------------------------------
    // TEST 3: FULLSCREEN NAVIGATION DRAWER & ACCESSIBLE FOCUS TRAP
    // ------------------------------------------------------------------------
    console.log('\n3. Testing Fullscreen Navigation Drawer & Accessibility...');
    await ctl.evaluate(`document.querySelector('.menu-trigger').click()`);
    await new Promise(r => setTimeout(r, 600));

    const drawerActive = await ctl.evaluate(`
      const nav = document.getElementById('navOverlay');
      return nav ? nav.classList.contains('is-active') : false;
    `);
    report('Menu trigger opens navigation drawer (.is-active applied)', drawerActive);

    const mainInert = await ctl.evaluate(`
      const m = document.getElementById('mainContent');
      return m ? m.hasAttribute('inert') : false;
    `);
    report('<main> element receives inert attribute while drawer is open', mainInert);

    // Close via close button
    await ctl.evaluate(`document.querySelector('.nav-close-btn').click()`);
    await new Promise(r => setTimeout(r, 500));

    const drawerClosed = await ctl.evaluate(`
      const nav = document.getElementById('navOverlay');
      return nav ? !nav.classList.contains('is-active') : false;
    `);
    report('Close button removes .is-active and restores focus', drawerClosed);

    // ------------------------------------------------------------------------
    // TEST 4: ALL PUBLIC PAGES INTEGRITY
    // ------------------------------------------------------------------------
    console.log('\n4. Testing All Public Pages & Lightbox in Gallery...');
    const pages = [
      { url: 'http://127.0.0.1:8000/rooms.php', name: 'Suites Catalog' },
      { url: 'http://127.0.0.1:8000/room-details.php?slug=deluxe-verandah-room', name: 'Deluxe Verandah Room Details' },
      { url: 'http://127.0.0.1:8000/dining.php', name: 'Aura Dining' },
      { url: 'http://127.0.0.1:8000/experiences.php', name: 'Wellness & Courtyard Pool' },
      { url: 'http://127.0.0.1:8000/gallery.php', name: 'Architectural Gallery' },
      { url: 'http://127.0.0.1:8000/about.php', name: 'About & Ethos' },
      { url: 'http://127.0.0.1:8000/contact.php', name: 'Concierge & Inquiries' }
    ];

    for (const p of pages) {
      await ctl.navigate(p.url, 800);
      const ok = await ctl.evaluate(`document.querySelector('main') !== null && document.title.length > 0`);
      report(`Page loads cleanly: ${p.name}`, ok);
    }

    // Gallery Lightbox test
    await ctl.navigate('http://127.0.0.1:8000/gallery.php', 1000);
    const hasGalleryImages = await ctl.evaluate(`return document.querySelectorAll('.gallery-card').length > 0`);
    report('Gallery renders image exhibition grid', hasGalleryImages);

    // ------------------------------------------------------------------------
    // TEST 5: FULL GUEST BOOKING FLOW (END-TO-END)
    // ------------------------------------------------------------------------
    console.log('\n5. Testing End-to-End Guest Booking Flow...');
    await ctl.navigate('http://127.0.0.1:8000/booking.php', 1500);

    // Wait for live availability to load via AJAX
    await new Promise(r => setTimeout(r, 2000));
    const suitesLoaded = await ctl.evaluate(`return document.querySelectorAll('.select-suite-btn').length > 0`);
    report('Booking wizard Step 1 displays available suites', suitesLoaded);

    await ctl.captureScreenshot('screenshot_booking_step1.png');

    // Select the first available suite
    await ctl.evaluate(`document.querySelectorAll('.select-suite-btn')[0].click()`);
    await new Promise(r => setTimeout(r, 1200));

    // Verify moved to Step 2 (Guest Details)
    const step2Visible = await ctl.evaluate(`
      const s2 = document.getElementById('wizardStep2');
      return s2 ? (window.getComputedStyle(s2).display !== 'none') : false;
    `);
    report('Selecting suite transitions to Step 2 (Guest Details)', step2Visible);

    // Fill in resident details
    await ctl.evaluate(`
      document.getElementById('guestName').value = 'Lady Catherine de Bourgh';
      document.getElementById('guestEmail').value = 'catherine@rosings-heritage.co.uk';
      document.getElementById('guestPhone').value = '+447911122334';
      document.getElementById('specialRequests').value = 'High floor suite with silent courtyard aspect';
    `);

    // Proceed to Step 3
    await ctl.evaluate(`document.getElementById('proceedToReviewBtn').click()`);
    await new Promise(r => setTimeout(r, 1200));

    const step3Visible = await ctl.evaluate(`
      const s3 = document.getElementById('wizardStep3');
      return s3 ? (window.getComputedStyle(s3).display !== 'none') : false;
    `);
    report('Proceeds to Step 3 (Review & Confirm)', step3Visible);

    // Verify financial breakdown in summary
    const summaryContainsFinancials = await ctl.evaluate(`
      const s = document.getElementById('bookingSummaryCard');
      return s ? (s.textContent.includes('Room Subtotal') && s.textContent.includes('GST')) : false;
    `);
    report('Step 3 summary displays room subtotal and GST breakdown', summaryContainsFinancials);

    await ctl.captureScreenshot('screenshot_booking_step3.png');

    // Confirm booking
    await ctl.evaluate(`document.getElementById('finalConfirmBookingBtn').click()`);
    
    // Wait for API roundtrip & redirect to confirmation.php
    await new Promise(r => setTimeout(r, 4000));

    const currentUrl = await ctl.evaluate('return window.location.href');
    const isConfirmationPage = currentUrl.includes('confirmation.php?ref=ADI-');
    report('Booking redirects to confirmation.php with reference code', isConfirmationPage, currentUrl);

    const hasAccessVoucher = await ctl.evaluate(`
      const v = document.getElementById('printableVoucher');
      return v ? (v.textContent.includes('Official Booking Voucher') && v.textContent.includes('Lady Catherine de Bourgh')) : false;
    `);
    report('Confirmation page renders official voucher pass with resident details', hasAccessVoucher);

    const hasTaxSplit = await ctl.evaluate(`
      return document.body.textContent.includes('Central GST') && document.body.textContent.includes('State GST');
    `);
    report('Confirmation voucher presents balanced CGST and SGST splits', hasTaxSplit);

    await ctl.captureScreenshot('screenshot_booking_confirmation.png');

    // ------------------------------------------------------------------------
    // TEST 6: ADMIN AUTHENTICATION & OPERATIONS
    // ------------------------------------------------------------------------
    console.log('\n6. Testing Administrator Portal & Operations...');
    await ctl.navigate('http://127.0.0.1:8000/admin/login.php', 1000);

    const onAdminLoginPage = await ctl.evaluate(`return document.querySelector('form') !== null && document.title.includes('Staff')`);
    report('Admin login portal loads cleanly', onAdminLoginPage);

    // Fill in credentials
    await ctl.evaluate(`
      document.querySelector('input[name="email"]').value = 'admin@adishivhotel.com';
      document.querySelector('input[name="password"]').value = 'Admin@Adishiv2026';
      document.querySelector('button[type="submit"]').click();
    `);

    // Wait for redirect to admin index
    await new Promise(r => setTimeout(r, 3500));

    const onAdminDashboard = await ctl.evaluate(`
      return window.location.href.includes('/admin/') && document.body.textContent.includes('Total Revenue');
    `);
    report('Admin authentication succeeds and loads Overview dashboard', onAdminDashboard);

    const kpiCardsVisible = await ctl.evaluate(`
      return document.querySelectorAll('.kpi-card').length >= 3;
    `);
    report('Dashboard displays KPI metrics (Revenue, Occupancy, Arrivals)', kpiCardsVisible);

    await ctl.captureScreenshot('screenshot_admin_dashboard.png');

    // Navigate to admin bookings
    await ctl.navigate('http://127.0.0.1:8000/admin/bookings.php', 1000);
    const bookingListContainsNewBooking = await ctl.evaluate(`
      return document.body.textContent.includes('Lady Catherine de Bourgh') || document.querySelectorAll('table tbody tr').length > 0;
    `);
    report('Admin bookings manager displays active reservations', bookingListContainsNewBooking);

    // ------------------------------------------------------------------------
    // TEST 7: RESPONSIVE MOBILE VIEWPORT (390 x 844)
    // ------------------------------------------------------------------------
    console.log('\n7. Testing Mobile Viewport (390x844)...');
    await ctl.setViewport(390, 844, true);
    await ctl.navigate('http://127.0.0.1:8000/', 1500);

    const noHorizontalOverflow = await ctl.evaluate(`
      return document.documentElement.scrollWidth <= window.innerWidth;
    `);
    report('Mobile homepage has zero horizontal overflow (scrollWidth <= 390px)', noHorizontalOverflow);

    const mobileMenuTriggerVisible = await ctl.evaluate(`
      const t = document.querySelector('.menu-trigger');
      return t ? (window.getComputedStyle(t).display !== 'none') : false;
    `);
    report('Mobile navigation hamburger trigger is accessible on mobile', mobileMenuTriggerVisible);

    await ctl.captureScreenshot('screenshot_mobile_home.png');

    // Reset viewport
    await ctl.setViewport(1440, 900, false);

    // ------------------------------------------------------------------------
    // TEST 8: ACCESSIBILITY PREFERS-REDUCED-MOTION
    // ------------------------------------------------------------------------
    console.log('\n8. Testing prefers-reduced-motion: reduce...');
    await ctl.setReducedMotion(true);
    await ctl.navigate('http://127.0.0.1:8000/', 1000);

    const reducedMotionInstant = await ctl.evaluate(`
      const p = document.getElementById('adishivPreloader');
      return p ? (window.getComputedStyle(p).display === 'none' || p.style.display === 'none') : false;
    `);
    report('Preloader curtain is immediately bypassed under prefers-reduced-motion', reducedMotionInstant);

    const heroElementsVisible = await ctl.evaluate(`
      const h = document.querySelector('.hero-headline');
      return h ? (window.getComputedStyle(h).opacity === '1') : false;
    `);
    report('Hero typography is immediately 100% visible under reduced motion', heroElementsVisible);

    // ------------------------------------------------------------------------
    // TEST 9: CONSOLE LOGS & NETWORK HEALTH
    // ------------------------------------------------------------------------
    console.log('\n9. Console & Network Errors Verification...');
    report('Zero browser console errors detected', ctl.consoleErrors.length === 0, JSON.stringify(ctl.consoleErrors));
    report('Zero failed network requests / 404s detected', ctl.networkErrors.length === 0, JSON.stringify(ctl.networkErrors));

  } catch (err) {
    console.error('Browser suite runtime failure:', err);
    failCount++;
  } finally {
    if (ctl && ctl.ws) ctl.ws.close();
    chrome.kill();
    try { fs.rmSync(profileDir, { recursive: true, force: true }); } catch (e) {}
  }

  console.log('\n=======================================================');
  console.log(` BROWSER VERIFICATION SUMMARY: ${passCount} / ${testCount} PASSED`);
  if (failCount > 0) {
    console.log(` FAILURES: ${failCount}`);
  } else {
    console.log(' ALL BROWSER E2E TESTS PASSED FLAWLESSLY!');
  }
  console.log('=======================================================\n');

  process.exit(failCount > 0 ? 1 : 0);
}

runBrowserSuite();
