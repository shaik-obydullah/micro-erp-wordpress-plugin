# Micro ERP

**A lightweight ERP system for small businesses — built as a WordPress plugin.**

Accounting, HRM, CRM, and Sales management, all inside your WordPress admin dashboard.

![PHP 7.4+](https://img.shields.io/badge/PHP-7.4%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)
![WordPress 6.0+](https://img.shields.io/badge/WordPress-6.0%2B-21759B?style=for-the-badge&logo=wordpress&logoColor=white)
![MySQL 8.0](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Docker Compose](https://img.shields.io/badge/Docker-Compose-2496ED?style=for-the-badge&logo=docker&logoColor=white)
![jQuery 3.x](https://img.shields.io/badge/jQuery-3.x-0769AD?style=for-the-badge&logo=jquery&logoColor=white)

![License GPL-2.0](https://img.shields.io/badge/License-GPL--2.0-blue?style=for-the-badge)
![Version 1.0.0](https://img.shields.io/badge/Version-1.0.0-brightgreen?style=for-the-badge)
![18 DB Tables](https://img.shields.io/badge/Tables-18-orange?style=for-the-badge)
![3500+ PHP Lines](https://img.shields.io/badge/PHP_Lines-3500+-6C5CE7?style=for-the-badge)
![788 CSS Lines](https://img.shields.io/badge/CSS-788_lines-0984E3?style=for-the-badge)

![Double-Entry Accounting](https://img.shields.io/badge/Accounting-Double--Entry-00B894?style=for-the-badge)
![HRM Module](https://img.shields.io/badge/HRM-Employees%20%7C%20Attendance%20%7C%20Payroll-E17055?style=for-the-badge)
![Sales Module](https://img.shields.io/badge/Sales-Quotations%20%7C%20Invoices-0984E3?style=for-the-badge)
![CRM Module](https://img.shields.io/badge/CRM-Contacts-6C5CE7?style=for-the-badge)

![19 Admin Pages](https://img.shields.io/badge/Admin_Pages-19-2D3436?style=for-the-badge)
![116 JS Lines](https://img.shields.io/badge/JS-116_lines-F39C12?style=for-the-badge)
![Security](https://img.shields.io/badge/Security-Nonces%20%7C%20Prepared%20Queries%20%7C%20Sanitization-red?style=for-the-badge)
![Auto Journal Entries](https://img.shields.io/badge/Automatic-Journal%20Entries-00cec9?style=for-the-badge)

---

## What is Micro ERP?

Micro ERP turns your existing WordPress installation into a full-featured business management system. No separate SaaS, no additional logins, no per-user fees. Just one plugin, one database, one dashboard.

### Modules

| Module         | Features                                                                             |
| -------------- | ------------------------------------------------------------------------------------ |
| **Accounting** | Chart of Accounts, double-entry journal, quick income/expense, receivables, payables |
| **HRM**        | Employees, departments, daily attendance, leave management, monthly payroll          |
| **Sales**      | Quotations, sales orders, payment recording, one-click quote-to-sale conversion      |
| **CRM**        | Customer/vendor/supplier directory with filtering and search                         |
| **Dashboard**  | KPI cards, recent transactions, pending actions, quick overview                      |

## Quick Start

```bash
# Clone and start
git clone <repo-url> && cd <repo>
docker compose up -d

# Access
# WordPress:  http://localhost:8010/wp-admin  (admin / 56942512)
# phpMyAdmin: http://localhost:8011           (root / no password)

# Seed demo data
docker compose run --rm --entrypoint /bin/sh wp-cli \
  -c "wp eval-file /var/www/html/wp-content/plugins/micro-erp/seed-demo-data.php --allow-root --path=/var/www/html"
```

## Architecture

```
micro-erp/
├── micro-erp.php                 # Plugin bootstrap
├── includes/
│   ├── helpers.php               # 30+ utility functions
│   ├── class-activator.php       # DB schema + defaults
│   ├── class-micro-erp.php       # Core: menus, routing, forms
│   └── forms/                    # 12 form handler files
├── admin/partials/               # 19 page templates
├── assets/
│   ├── css/micro-erp-admin.css   # Custom CSS framework (788 lines)
│   └── js/micro-erp-admin.js     # Dynamic calculations (116 lines)
└── seed-demo-data.php            # Test data seeder
```

## Database Schema

18 custom tables with prefix `micro_erp_`:

| Module     | Tables                                                                                       |
| ---------- | -------------------------------------------------------------------------------------------- |
| Core       | `fiscal_years`, `settings`, `audit_log`                                                      |
| CRM        | `contacts`                                                                                   |
| Accounting | `accounts`, `journal_entries`, `journal_lines`                                               |
| HRM        | `departments`, `employees`, `attendance`, `leave_types`, `leave_requests`, `salary_payments` |
| Sales      | `quotations`, `quotation_items`, `sales`, `sale_items`                                       |

## Key Features

- **Double-Entry Accounting** — Every transaction creates balanced debit/credit entries with real-time JS validation
- **Automatic Journal Entries** — Sales, payments, and salary all auto-generate correct accounting entries
- **Quote → Sale Pipeline** — One-click conversion copies line items and creates the journal entry
- **Payment Tracking** — Partial payments with automatic status transitions (Unpaid → Partial → Paid)
- **Fiscal Year Management** — Journal entries isolated by fiscal year
- **Attendance Grid** — Bulk daily attendance with check-in/out and hours calculation
- **Payroll** — Monthly salary processing with allowances, deductions, and auto-journal
- **Audit Trail** — Every create/update/delete operation is logged with user and timestamp
- **Extensible Hooks** — `do_action` on key business events for third-party integrations

## Tech Stack

| Technology     | Usage                                                                 |
| -------------- | --------------------------------------------------------------------- |
| PHP 7.4+       | Backend logic, form handling, database queries                        |
| WordPress 6.0+ | Platform, admin UI, user management, security APIs                    |
| MySQL 8.0      | All data storage via `$wpdb` with prepared statements                 |
| jQuery         | Client-side dynamic forms (journal balancing, line item calculator)   |
| CSS3           | Custom admin framework (grid, cards, badges, tables, KPI, responsive) |
| Docker         | Development environment (4-container stack)                           |

## Security

- `ABSPATH` guards on every PHP file
- Nonce verification on all form submissions
- Input sanitization (sanitize_text_field, sanitize_email, intval, floatval)
- Prepared SQL statements ($wpdb->prepare)
- Output escaping (esc_html, esc_attr, esc_url)
- Capability checks (manage_options) on all pages
- Full audit logging

## License

GPL-2.0-or-later

---

_Built with WordPress, PHP, MySQL, jQuery, and Docker — by Obydullah_
