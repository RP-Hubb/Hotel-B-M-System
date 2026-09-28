# Adishiv Luxury Hotel & Suites — Web Booking & Management System
**Location:** Diplomatic Enclave, Chanakyapuri, New Delhi, India  
**Type:** Ultra-Luxury Heritage Modern Boutique Hotel  
**Primary Currency:** INR (₹) | **Default Tax:** 18% GST  
**Technology Stack:** HTML5 · Vanilla CSS3 · Vanilla JavaScript (ES6+) · GSAP 3.12+ · PHP 8.0+ · MySQL / MariaDB (InnoDB)

---

## 1. Project Overview

**Adishiv** is a production-quality, bespoke hotel booking and management web application built for an ultra-luxury hospitality destination in New Delhi. Combining contemporary architectural minimalism with imperial Indian heritage, the application rejects generic dashboard/SaaS patterns in favor of:
- **Cinematic Entry & Preloader:** An original vector SVG path-drawing and curtain reveal inspired by *Left Coast*.
- **Editorial Hospitality Layouts:** Generous typography hierarchies and photographic rhythms inspired by *Haven Annecy* and *Qissa*.
- **Real-Time Booking Engine:** Live room availability search, dynamic 18% GST tax calculation, guest details collection, and unique voucher generation (`ADI-2026-XXXX`).
- **Concurrency & Double-Booking Prevention:** Atomic InnoDB transactions with `FOR UPDATE` row-level locks on physical inventory.
- **Administrative Control Panel:** Operational dashboard featuring revenue summaries, occupancy rate tracking, room status management, and reservation check-in/check-out workflows.
- **Dynamic Configuration:** Hotel branding, phone, address, and seasonal pricing can be updated from the database without touching source code.

---

## 2. System Requirements

- **PHP Version:** PHP 8.0 or higher (with `pdo_mysql`, `session`, `json`, and `mbstring` extensions enabled).
- **Database:** MySQL 5.7+, MySQL 8.0+, or MariaDB 10.4+.
- **Web Server:** Apache (via XAMPP / WampServer / Laragon) OR PHP's built-in CLI server (`php -S`).
- **Browser Compatibility:** Chrome, Safari, Firefox, Edge (Modern desktop, tablet, and mobile).

---

## 3. Directory Structure

```
Hotel B&M System/
├── assets/
│   ├── css/
│   │   ├── variables.css          # Design system tokens, imperial gold & obsidian palette
│   │   ├── base.css               # Typography hierarchy, reset, accessibility skip-link
│   │   ├── components.css         # Navigation, buttons, room cards, booking strip, forms
│   │   ├── motion.css             # Preloader animation, custom cursor, curtain wipe
│   │   └── admin.css              # Administrative tables, KPI metrics, status chips
│   ├── js/
│   │   ├── core.js                # Core bootstrap
│   │   ├── preloader.js           # SVG path draw & curtain reveal sequence
│   │   ├── navigation.js          # Sticky header & accessible fullscreen drawer
│   │   ├── animations.js          # Custom trailing cursor & scroll reveals
│   │   └── booking-widget.js      # Multi-step booking wizard & AJAX price calculator
│   └── images/
│       ├── branding/              # Hotel facade & dusk courtyard imagery
│       ├── rooms/                 # Deluxe Verandah, Imperial Suite, Presidential Residence
│       ├── dining/                # Aura fine dining restaurant
│       └── wellness/              # Heated marble courtyard pool
├── config/
│   ├── config.php                 # Environment loader & security constants
│   └── database.php               # PDO singleton database connection
├── includes/
│   ├── header.php                 # Global HTML head, meta tags, luxury navbar & preloader
│   ├── footer.php                 # Editorial footer & script imports
│   ├── auth.php                   # Authentication guards, role verification, session security
│   ├── functions.php              # XSS escaping, CSRF tokens, INR currency formatting
│   └── booking-helper.php         # Date validation, GST calculations, atomic booking locks
├── api/
│   ├── check-availability.php     # Real-time room availability JSON API
│   ├── calculate-pricing.php      # Live night count & 18% GST tax calculation API
│   ├── create-booking.php         # Atomic booking transaction endpoint
│   └── contact-submit.php         # Concierge inquiry transmission API
├── admin/
│   ├── index.php                  # Overview dashboard (KPIs, revenue, occupancy)
│   ├── bookings.php               # Reservation manager (Check-in, Check-out, Cancel)
│   ├── rooms.php                  # Room inventory status & suite pricing updater
│   ├── customers.php              # Resident directory with lifetime spend & VIP badges
│   ├── messages.php               # Inquiries inbox
│   ├── settings.php               # Dynamic hotel configuration manager
│   ├── login.php                  # Dedicated staff login portal
│   └── logout.php                 # Staff session termination
├── database/
│   └── schema.sql                 # Complete MySQL schema, foreign keys, indexes, seeds
├── .env.example                   # Environment configuration template
├── .env                           # Active environment settings
├── PROJECT_TASK.md                # Master project specification (Single Source of Truth)
└── README.md                      # This documentation file
```

---

## 4. Local Installation & Setup

### Step 1: Database Setup
1. Ensure MySQL / MariaDB is running (e.g. via XAMPP Control Panel).
2. Create and seed the database using `database/schema.sql`:
   ```bash
   mysql -u root < database/schema.sql
   ```
   *This automatically creates the `adishiv_hotel` database with 11 relational tables and initial luxury suite categories, amenities, physical rooms, and admin credentials.*

### Step 2: Environment Configuration
Verify that `.env` in the root folder matches your local MySQL credentials:
```env
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8000
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=adishiv_hotel
DB_USER=root
DB_PASSWORD=
CURRENCY_CODE=INR
CURRENCY_SYMBOL=₹
```

### Step 3: Run the Application
Open a terminal in the project directory and start PHP's built-in development server:
```bash
php -S localhost:8000
```
Open your browser and navigate to:
```
http://localhost:8000
```

---

## 5. Administrative Access & Credentials

The database seeds a default administrator account:
- **Admin Portal URL:** `http://localhost:8000/admin/login.php`
- **Username / Email:** `admin@adishivhotel.com`
- **Password:** `Admin@Adishiv2026`

### Key Admin Features:
1. **Overview Dashboard (`admin/index.php`):** Live revenue figures in INR (₹), current occupancy percentage, today's arrivals, and pending inquiries.
2. **Bookings Manager (`admin/bookings.php`):** Search by guest name or voucher code, filter by status, and click **Check In** or **Check Out** to update room status automatically.
3. **Room Inventory & Rates (`admin/rooms.php`):** Add physical rooms (e.g. 101, 201), toggle status between `Available`, `Occupied`, `Housekeeping`, or `Maintenance`, and adjust suite nightly rates.
4. **Dynamic Settings (`admin/settings.php`):** Edit concierge contact telephone, email, address, and GST rate dynamically.

---

## 6. Security Features

- **SQL Injection Defense:** All queries utilize PDO prepared statements with strict parameter binding.
- **Cross-Site Scripting (XSS):** Global sanitization via `htmlspecialchars(..., ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`.
- **CSRF Protection:** Cryptographic tokens generated per session and validated on all POST actions.
- **Session Hardening:** Cookies flagged with `HttpOnly` and `SameSite=Lax`, with `session_regenerate_id(true)` applied on authentication.
- **Concurrency Protection:** Double booking is mathematically prevented through InnoDB `FOR UPDATE` row-level locks during reservation transactions.

---

## 7. Customization & Brand Asset Swapping

To customize the hotel for a different property:
1. **Name, Phone, Address:** Update in `admin/settings.php` or `site_settings` table.
2. **Photography:** Replace files in `assets/images/branding/` and `assets/images/rooms/`.
3. **Colors & Fonts:** Modify CSS variables in `assets/css/variables.css`.

---

## 8. Definition of Done & Quality Audit

- Cohesive luxury visual identity inspired by editorial references (*Left Coast*, *Haven Annecy*, *Qissa*).
- Left Coast inspired SVG path preloader and curtain reveal.
- Responsive layout across desktop, laptop, tablet, and mobile.
- MySQL relational database storing rooms, bookings, guests, and payments.
- Real-time availability calculation and double-booking collision prevention.
- Administrative control dashboard for reservations, rooms, and inquiries.
- Zero console errors and zero PHP warnings.
- WCAG 2.2 AA accessibility and `prefers-reduced-motion` compliance.
