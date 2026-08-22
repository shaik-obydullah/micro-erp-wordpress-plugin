# Micro ERP – Complete Documentation

Welcome to the official documentation for **Micro ERP** — your complete small-business management solution for WordPress.

A lightweight yet complete ERP system designed specifically for WordPress. It brings contacts, double-entry accounting, human resource management, and sales management together inside your existing WordPress admin area — no external software required.

![Dashboard Overview](screenshot-1.png)

*Dashboard Overview – key business metrics and quick summaries at a glance*

## Key Benefits

- **All-in-One:** CRM, accounting, HRM, and sales in a single plugin
- **Double-Entry Accounting:** Every transaction posts balanced journal entries automatically
- **Multi-Year Books:** Manage separate fiscal years with independent books
- **Familiar Interface:** Native WordPress admin look and feel — no learning curve for your team
- **Lightweight:** No bloat; fast queries with indexed custom tables
- **Free & Open Source:** GPL v2 or later

---

## Installation

### Method 1: WordPress Admin Upload (Recommended)

1. Download the plugin ZIP file
2. Go to **Plugins → Add New → Upload Plugin**
3. Click **Choose File**, select the ZIP, then click **Install Now**
4. Click **Activate Plugin**

### Method 2: Manual (FTP)

1. Unzip the plugin folder
2. Upload the `micro-erp` folder to `/wp-content/plugins/`
3. Activate it from the **Plugins** screen in wp-admin

### Database Setup

Upon activation the plugin creates its own tables automatically (prefix `micro_erp_`):

| Table | Purpose |
|---|---|
| `fiscal_years` | Accounting periods / books |
| `accounts` | Chart of accounts |
| `journal_entries`, `journal_lines` | Double-entry ledger |
| `contacts` | Customers, suppliers, vendors |
| `quotations`, `quotation_items` | Quotes and line items |
| `sales`, `sale_items` | Sales orders and line items |
| `employees`, `departments` | HRM records |
| `attendance` | Daily attendance grid |
| `leave_types`, `leave_requests` | Leave policy and applications |
| `salary_payments` | Salary disbursements |
| `settings`, `audit_log` | Configuration and activity trail |

## System Requirements

| Component | Minimum | Recommended |
|---|---|---|
| **WordPress** | 6.0 | Latest |
| **PHP** | 7.4 | 8.0+ |
| **MySQL** | 5.6 | 8.0+ |
| **Memory Limit** | 128MB | 256MB |

## Quick Start Guide

### Step 1: Configure Settings

1. Go to **Micro ERP → Settings**
2. Enter company name, address, phone, and email
3. Set your currency and formatting preferences
4. Click **Save Settings**

![Settings Panel](screenshot-18.png)

*Settings Panel – company profile and system preferences*

### Step 2: Review Chart of Accounts

The plugin ships with sensible defaults (Bank `1002`, Accounts Receivable `1003`, Accounts Payable `2001`, Sales Income `4001`, and common expense heads). Add your own ledgers any time from **Micro ERP → Chart of Accounts**.

![Chart of Accounts](screenshot-3.png)

*Chart of Accounts – asset, liability, income, and expense ledgers*

### Step 3: Add Contacts

Add your customers and suppliers from **Micro ERP → Contacts**. Customers are required for quotations and sales; suppliers/vendors are used for purchases and payables.

![Contacts](screenshot-2.png)

*Contacts – customers and supplier directory*

### Step 4: Set Up HRM

1. Create departments under **Departments**
2. Add employees under **Employees**
3. Define leave types under **Leave → Leave Types**

### Step 5: Start Selling

Create a quotation, accept it, convert it to a sales order, and record payments — every step posts the correct accounting entries automatically.

---

## Features

## Dashboard

The dashboard summarizes your business at a glance: total receivables, payables, recent transactions, employee counts, and current fiscal year information — all computed live from your data.

![Dashboard](screenshot-1.png)

*Dashboard – business overview widgets*

## Contacts

A simple CRM for the people you do business with.

| Field | Required | Description |
|---|---|---|
| Name | Yes | Contact or company name |
| Type | Yes | Customer, Supplier, or Vendor |
| Email / Phone | No | Contact details |
| Address | No | Billing or physical address |
| Status | Yes | Active / Inactive |

Every list page includes **instant search** and **pagination**, so large directories stay fast and easy to browse.

![Contacts](screenshot-2.png)

*Contacts – customers and supplier directory*

## Chart of Accounts

The backbone of the accounting module. Accounts are grouped by type:

| Type | Examples | Used For |
|---|---|---|
| **Asset** | Cash, Bank, Accounts Receivable | Payments received, money owed to you |
| **Liability** | Accounts Payable, Loans | Money you owe |
| **Income** | Sales Revenue, Service Income | Credit side of revenue entries |
| **Expense** | Rent, Utilities, Salaries | Debit side of expense entries |

> **Tip:** Keep account codes organized by ranges (1000s assets, 2000s liabilities, 4000s income, 5000s expenses) so reports stay readable as your book grows.

![Chart of Accounts](screenshot-3.png)

*Chart of Accounts – ledgers with search and totals*

## Journal Entries

Every action in Micro ERP — a sale, an expense, a salary payment — writes a balanced journal entry. You can also record manual adjusting entries with multiple debit and credit lines. The entry list shows each transaction's debits, credits, and reference so your books always reconcile.

![Journal Entries](screenshot-4.png)

*Journal Entries – full double-entry ledger with search*

## Income & Expenses

Record direct income and operating expenses against any ledger account and payment account. Each entry automatically posts its journal lines, and both pages show grand totals across all matching rows — not just the visible page.

![Income](screenshot-5.png)

*Income – recorded revenue with totals*

![Expenses](screenshot-6.png)

*Expenses – operating costs with category totals*

## Accounts Payable

Track money you owe to suppliers. Add payable entries against the Accounts Payable ledger (`2001`), mark items paid when settled, and monitor outstanding totals via the summary bar.

![Accounts Payable](screenshot-7.png)

*Accounts Payable – outstanding obligations and totals*

## Accounts Receivable

The mirror image of payables: unpaid customer invoices appear here automatically when you create unpaid or partially-paid sales. Record receipts against invoices to settle them, with all figures flowing into the journal.

![Accounts Receivable](screenshot-8.png)

*Accounts Receivable – unpaid invoices and collections*

## Employees

Maintain your staff directory with designation, department, join date, salary, and status (active / terminated). Employee records link directly to attendance, leave requests, and salary payments.

![Employees](screenshot-9.png)

*Employees – staff directory with department and status badges*

## Departments

Group employees into departments for cleaner organization and reporting. Departments are referenced when creating employees.

![Departments](screenshot-10.png)

*Departments – organizational units*

## Attendance

A date-based attendance grid: pick a date, then mark each employee **Present**, **Absent**, or **Late** in one pass. Summary cards show today's present / absent / late / unmarked counts.

![Attendance](screenshot-11.png)

*Attendance – daily marking grid with live counters*

## Leave Management

Two parts on one page:

- **Leave Types** – define policies (e.g., Annual, Sick, Casual) with active/off toggles
- **Leave Requests** – employees apply; admins approve or reject

| Status | Meaning |
|---|---|
| Pending | Awaiting review |
| Approved | Full-day leave granted |
| Approved (Half) | Half-day granted |
| Rejected | Denied |

![Leave Types](screenshot-12.png)

*Leave Types – configurable policies*

![Leave Requests](screenshot-13.png)

*Leave Requests – approval workflow with status badges*

## Salary

Record monthly salary payments per employee. Payments post to the journal automatically (expense debit, payment-account credit), and the summary cards reflect totals across the selected month — including when filtered by name or employee ID.

![Salary](screenshot-14.png)

*Salary – monthly disbursement register*

## Quotations

Create professional quotes with unlimited line items, per-line tax rates, discounts, and a validity date. Quote statuses flow **Draft → Sent → Accepted**; accepted quotes can be converted into sales orders in one click without re-typing line items.

![Quotations](screenshot-15.png)

*Quotations – pipeline with draft/sent/accepted states*

## Sales Orders

The heart of the sales module. Each order captures the customer, line items, tax, discount, and payments:

| Payment Status | Trigger | Effect |
|---|---|---|
| **Unpaid** | No payment recorded | Full amount appears in Receivable |
| **Partial** | Part of the total received | Balance remains in Receivable |
| **Paid** | Fully settled | Cleared from Receivable |

Use the **status pills** to filter the list, the **search box** (top right) to find orders by number or customer, and the **Record Payment** action to collect balances into any bank/cash account.

![Sales Orders](screenshot-16.png)

*Sales Orders – filter pills and search in one toolbar*

## Sales Reports

Summarized performance for a chosen period: total sales value, invoice count, amount collected, and outstanding balance — with stat cards and detailed breakdowns.

![Sales Reports](screenshot-17.png)

*Sales Reports – period performance at a glance*

## Settings & Fiscal Years

**Settings** holds your company identity and currency preferences used across invoices and lists. **Fiscal Years** lets you open, close, and switch accounting periods so each year keeps its own clean set of books. All transactions are tagged to the currently active fiscal year.

> **Important:** Closing a fiscal year does not delete data — it prevents further postings to that period. Always verify the active fiscal year before bulk-importing historical data.

![Settings](screenshot-18.png)

*Settings Panel – company profile and system preferences*

---

## Frequently Asked Questions

### Setup

**Q: Is the plugin free?**
A: Yes. Micro ERP is licensed under GPL v2 or later.

**Q: Does it modify my existing WooCommerce/store data?**
A: No. Micro ERP uses its own `micro_erp_*` tables only.

**Q: Where do the default accounts come from?**
A: Activation seeds a starter chart of accounts (bank, receivable, payable, income, common expenses) which you can rename or extend.

### Accounting

**Q: Do I need accounting knowledge?**
A: No. Routine actions (sales, expenses, salaries) post correct double-entry journals behind the scenes.

**Q: Can I edit posted journal entries?**
A: Manual entries can be adjusted by creating reversing entries; system-generated entries stay consistent with their source documents.

### HRM

**Q: How is half-day leave handled?**
A: Approve with the half-day option; the request shows a distinct "Approved (Half)" badge.

**Q: Does marking attendance require saving per employee?**
A: No — the grid lets you mark everyone for the chosen date in one pass.

### Sales

**Q: What happens when I convert a quotation?**
A: Line items copy into a new sales order and the quote is marked Converted, preserving your audit trail.

**Q: Can I take partial payments?**
A: Yes — record any amount against an order; its status becomes Partial until fully settled.

## Troubleshooting

| Issue | Possible Cause | Solution |
|---|---|---|
| Tables not created | Activation interrupted | Deactivate, then re-activate the plugin |
| Menu pages return errors | PHP below 7.4 | Upgrade PHP (8.0+ recommended) |
| Totals look wrong after filtering | Filters applied to summary too | Clear filters to compare whole-book totals |
| Transactions in wrong period | Inactive/incorrect fiscal year | Switch the active fiscal year before posting |
| Styles look broken | Stale cached CSS | Hard refresh (Ctrl/Cmd+Shift+R) |

### Maintenance Tips

- **Back up first:** back up the database before major updates
- **Review receivables weekly** to chase overdue invoices early
- **Close fiscal years** once audits finish to freeze the period
- **Use the audit log** to trace who changed what

## Support & Resources

| Resource | Best For |
|---|---|
| Documentation (this file) | Self-service setup and feature guides |
| GitHub Issues | Bug reports and feature requests |
| Contact | Direct support from the developer |

> **Before Requesting Support:** Please include your WordPress version, PHP version, plugin version, and steps to reproduce the issue.

---

Micro ERP v1.0.0 · © 2024–2026 [Obydullah](https://obydullah.com) · Licensed under GPL-2.0-or-later
