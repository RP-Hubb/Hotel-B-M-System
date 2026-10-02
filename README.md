# Adishiv Luxury Hotel & Suites — Web Booking & Management System
**Location:** G-59, Connaught Circus, Connaught Place, New Delhi, Delhi 110001, India  
**Type:** Ultra-Luxury Boutique Heritage Hotel & Suites  
**Primary Currency:** INR (₹) | **Taxation:** Indian Luxury Hospitality GST Slabs (SAC 9963)  
**Technology Stack:** Vanilla PHP 8.0+ (8.3 tested) · Vanilla CSS3 (Custom Properties & Tokens) · Vanilla JavaScript (ES6+) · Self-Hosted GSAP 3.12.5 & ScrollTrigger · MySQL 8.0.16+ / MariaDB 10.6+ (InnoDB)

---

## 1. Project Overview

**Adishiv** is a production-hardened, ultra-luxury boutique hotel booking and property management web application. It combines contemporary architectural minimalism with Mughal stone craftsmanship, rejecting generic SaaS and UI templates.

### Core Architectural Features:
- **Zero Framework Footprint:** Pure vanilla PHP (no Laravel/Symfony, no Composer requirement) and native CSS/JS.
- **Self-Hosted Motion System:** Version-pinned GSAP 3.12.5 and ScrollTrigger stored locally in `assets/vendor/gsap/` (no CDN dependencies).
- **Cinematic Readiness Preloader:** Architectural SVG arch monogram path-drawing (`pathLength="1"`), staggered typography, and a hardware-accelerated clip-path wipe (`power4.inOut`). Returning visits in the same session bypass the preloader automatically.
- **Double-Booking Prevention:** Multi-tier inventory protection using InnoDB transactions:
  1. Deterministic physical room allocation with `SELECT ... FOR UPDATE` row-level locks.
  2. Database-level backstop ledger in `booking_nights` with a `UNIQUE(room_id, stay_date)` constraint, mathematically preventing duplicate date sales even under extreme concurrent load.
- **Paise-Precision Money & Indian GST:** Exact financial calculation avoiding floating-point drift. Per-night room value evaluates against statutory GST slabs (≤ ₹7,500 = 5%, > ₹7,500 = 18%) with balanced CGST and SGST splits.
- **Operational Decoupling:** Future inventory availability evaluates solely against confirmed/held reservations and maintenance blocks (`room_blocks`). Housekeeping status (`rooms.status`) reflects today's operational room cleaning state only.
- **Secure Confidentiality & Anti-Abuse:**
  - Online bookings generate a 128-bit cryptographically secure `access_token`. Confirmation vouchers cannot be enumerated via reference alone—they strictly require the token, resident email verification, or authenticated staff credentials.
  - Rate limiting (5 bookings/hour, 3 contact inquiries/hour per IP), contact form honeypots, and a maximum of 3 active future reservations per email.
  - Zero sensitive PII (such as Aadhaar or government IDs) collected during public checkout.
  - CLI-only administrator generator (`bin/create-admin.php`) with password strength checks (min 12 chars) and bcrypt hashing at cost 12. No seeded credentials exist in repository SQL.

---

## 2. System Requirements

- **PHP Version:** PHP 8.0, 8.1, 8.2, or 8.3 (tested on PHP 8.3 & PHP 8.0) with extensions:
  - `pdo_mysql`
  - `mbstring`
  - `json`
  - `session`
- **Database Engine:** MySQL 8.0.16+ or MariaDB 10.6+ (required for `CHECK` constraints and strict mode support; MySQL 5.7 is end-of-life and unsupported).
- **Web Server:** Apache 2.4+ (with `mod_rewrite` and `mod_headers`) OR Nginx 1.18+ OR PHP built-in CLI server (`php -S`).
- **File Path Notice:** If checking out on Windows, ensure the directory path does not cause escaping issues with command-line tools. Using a clean virtual host or path without spaces or `&` is recommended.

---

## 3. Directory Layout

```
Hotel B&M System/
├── assets/
│   ├── css/
│   │   ├── variables.css          # Design tokens: Obsidian #0C0F12, Silk #FBF9F5, Brass #C8A46B, Gold Ink #7A5C28
│   │   ├── base.css               # Typography hierarchy, skip-links, WCAG focus styles
│   │   ├── components.css         # Navigation, buttons, suite cards, booking bar, alert components
│   │   ├── motion.css             # Preloader curtain, lerp cursor, viewport reveals, reduced-motion gates
│   │   └── admin.css              # Operations dashboard, tables, occupancy chips
│   ├── js/
│   │   ├── core.js                # Motion framework, ease tokens, prefers-reduced-motion gates
│   │   ├── preloader.js           # Readiness-driven SVG draw & clip-path curtain reveal
│   │   ├── navigation.js          # Sticky header, accessible fullscreen drawer, inert & focus trap
│   │   ├── animations.js          # Lerp cursor centering, bounded magnetics, ScrollTrigger reveals
│   │   └── booking-widget.js      # Multi-step wizard, Asia/Kolkata dates, idempotency & safe DOM rendering
│   ├── vendor/
│   │   └── gsap/                  # Self-hosted GSAP 3.12.5 & ScrollTrigger (pinned local vendor files)
│   └── images/
│       ├── branding/              # Hotel facade & dusk courtyard photography
│       ├── rooms/                 # Deluxe Verandah, Imperial Suite, Presidential Residence
│       ├── dining/                # Aura fine dining restaurant
│       └── wellness/              # Heated courtyard pool & spa
├── bin/
│   ├── create-admin.php           # CLI administrator creator (interactive prompt, bcrypt cost 12)
│   ├── expire-holds.php           # Background cron task to release expired 15-minute booking holds
│   └── migrate-schema.php         # Automated column migration verification utility
├── config/
│   ├── config.php                 # Environment variables, security headers & lazy session manager
│   └── database.php               # PDO singleton with STRICT_ALL_TABLES & Asia/Kolkata timezone
├── database/
│   ├── schema.sql                 # DDL only: strict tables, foreign keys, CHECK constraints & unique keys
│   └── seed.sql                   # Idempotent demo data: room types, physical rooms, amenities & pivot records
├── includes/
│   ├── header.php                 # Global HTML head, CSRF meta, window.APP state, preloader markup & navbar
│   ├── footer.php                 # Editorial footer, copyright text, self-hosted script imports
│   ├── auth.php                   # Timing-safe auth, role permissions, login throttling & session rotation
│   ├── functions.php              # XSS escaping, rate limiter, security headers, format_inr() Indian grouping
│   └── booking-helper.php         # Date validation, GST slabs, atomic transactions, status state machine
├── api/
│   ├── check-availability.php     # Sessionless room search JSON API with private Cache-Control
│   ├── calculate-pricing.php      # Sessionless pricing calculator with whitelisted response fields
│   ├── create-booking.php         # Atomic booking transaction endpoint with CSRF & origin verification
│   └── contact-submit.php         # Rate-limited concierge inquiry transmission endpoint with honeypot
├── admin/
│   ├── index.php                  # Executive dashboard (KPIs, arrivals, departures, occupancy)
│   ├── bookings.php               # Reservation manager (status transitions, cancellations, filters)
│   ├── rooms.php                  # Room inventory tape chart, blocks & housekeeping status
│   ├── customers.php              # Resident directory with lifetime reservations & spend
│   ├── messages.php               # Concierge inquiries inbox
│   ├── settings.php               # Dynamic hotel configuration manager (contacts, GST settings)
│   ├── login.php                  # Staff portal login with brute-force throttling
│   └── logout.php                 # Staff session termination
├── tests/
│   └── run.php                    # Comprehensive CLI test runner (27 automated test cases)
├── .env.example                   # Environment configuration template (tracked in git)
├── .htaccess                      # Root Apache defense-in-depth (blocks .env, *.sql, *.md, config/)
├── nginx.conf.example             # Production Nginx server block configuration
├── PROJECT_TASK.md                # Product specifications and verified architectural decisions
└── README.md                      # This technical documentation
```

---

## 4. Installation & Local Setup

### Step 1: Configure Environment Variables
Copy `.env.example` to `.env`:
```bash
cp .env.example .env
```
Edit `.env` to match your local database credentials:
```ini
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8000
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=adishiv_hotel
DB_USER=root
DB_PASSWORD=
APP_CURRENCY_CODE=INR
APP_CURRENCY_SYMBOL=₹
```
*(Note: In production environments, set `APP_DEBUG=false` and `APP_ENV=production`. The application refuses to boot if debug mode is active in production.)*

### Step 2: Database Initialization
1. Ensure your MySQL / MariaDB service is running.
2. Create the target database:
   ```bash
   mysql -u root -e "CREATE DATABASE IF NOT EXISTS adishiv_hotel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   ```
3. Import the schema (DDL only):
   ```bash
   mysql -u root adishiv_hotel < database/schema.sql
   ```
4. Import the idempotent seed data (catalogs and physical rooms):
   ```bash
   mysql -u root adishiv_hotel < database/seed.sql
   ```

### Step 3: Create Staff Administrator
No default admin credentials exist in SQL. Run the interactive CLI utility:
```bash
php bin/create-admin.php
```
You will be prompted for an administrator email and password (minimum 12 characters). The script hashes the password with bcrypt (cost ≥ 12) and creates the administrator account.

### Step 4: Run the Application
Start PHP's built-in development server:
```bash
php -S localhost:8000
```
Open your browser and navigate to:
- **Public Sanctuary:** `http://localhost:8000`
- **Staff Portal:** `http://localhost:8000/admin/login.php`

---

## 5. Running Automated Verification Tests

A comprehensive, zero-dependency test suite is included in `tests/run.php`. It validates:
- INR currency formatting with Indian Lakh grouping (`format_inr(32000) === '₹ 32,000'`).
- Strict calendar date validation (rejecting rollover dates such as `2027-02-30`, past dates, and stays exceeding 30 nights).
- Date overlap matrix (back-to-back, enclosing, enclosed, and partial overlaps).
- Strict SQL mode (`STRICT_ALL_TABLES`) and `+05:30` Asia/Kolkata timezone compliance.
- Operational room status decoupling (ensuring room cleaning states today do not alter future availability).
- Immunity against anonymous `user_id` mass-assignment.
- Client idempotency key replays (preventing duplicate room allocation).
- DB-level double-booking backstop via `booking_nights` unique constraint violation catching.
- GST slab evaluation and integer paise precision calculations.
- Security headers execution.

Execute the test suite at any time via:
```bash
php tests/run.php
```
Expected output:
```
=======================================================
 ADISHIV LUXURY HOTEL & SUITES — SYSTEM TEST SUITE
=======================================================
1. Testing Currency Formatting (WP1.3)...
  [PASS] format_inr(32000) yields '₹ 32,000'
  [PASS] format_inr(1250000) yields '₹ 12,50,000' (Lakh grouping)
  [PASS] APP_CURRENCY_SYMBOL is defined and '₹'
...
=======================================================
 TEST SUMMARY: 27 / 27 PASSED
 ALL VERIFICATION CHECKS PASSED PERFECTLY!
=======================================================
```

---

## 6. Background Tasks & Hold Expiration Cron

When guests begin reservations, inventory is held temporarily. To purge expired holds and release inventory, configure a system cron to execute every 5 minutes:
```bash
*/5 * * * * /usr/bin/php /path/to/Hotel\ B&M\ System/bin/expire-holds.php > /dev/null 2>&1
```
*(In addition to the cron job, the booking helper runs lazy expiry checks during availability queries.)*

---

## 7. Production Hardening Checklist

Prior to production deployment:
1. **Document Root Isolation:** Configure your web server virtual host to serve from `public/` (or maintain the provided `.htaccess` / `nginx.conf.example` denying dotfiles, `*.sql`, `*.md`, and `config/`).
2. **HTTPS Enforcement:** Deploy an SSL certificate. In HTTPS mode, sessions automatically enforce `cookie_secure=1` and `HSTS` headers.
3. **Environment Flags:** Set `APP_ENV=production` and `APP_DEBUG=false` in `.env`.
4. **Database Mode:** Confirm that your production MySQL/MariaDB server enforces `STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION`.
5. **Tax & Legal Review:** Formal GST tax treatment, GSTIN assignment, SAC 9963 invoicing, and DPDP Act consent wording must be confirmed with the hotel owner's certified accountant and legal counsel before live trading.
