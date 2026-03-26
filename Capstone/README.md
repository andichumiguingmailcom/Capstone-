# CoopIMS – Cooperative Information Management System

## Setup Instructions

### Requirements
- PHP 7.4+ or 8.x
- MySQL 5.7+ or MariaDB
- Apache/Nginx web server (XAMPP/WAMP for local dev)

### Installation Steps
1. Copy the entire `coop_ims/` folder into your web server root (e.g. `htdocs/coop_ims/`)
2. Import the database schema:
   ```
   mysql -u root -p < database.sql
   ```
3. Edit `includes/config.php` — update DB credentials if needed:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');       // ← update this
   define('DB_NAME', 'coop_ims');
   ```
4. Create upload directory and set permissions:
   ```
   mkdir -p uploads/docs
   chmod 755 uploads/
   ```
5. Visit: `http://localhost/coop_ims/`

### Default Login
| Username | Password   | Role  |
|----------|-----------|-------|
| admin    | password  | Admin |
| staff1   | password  | Staff |

---

## File Structure

```
coop_ims/
│
├── index.php                    ← Login page
├── logout.php
├── database.sql                 ← Full DB schema + seed data
├── README.md
│
├── css/
│   └── style.css               ← Global styles
│
├── js/
│   └── app.js                  ← Shared JS utilities
│
├── includes/
│   ├── config.php              ← DB config + auth helpers
│   ├── admin_sidebar.php       ← Admin navigation
│   └── member_sidebar.php      ← Member navigation
│
├── uploads/
│   └── docs/                   ← Uploaded member documents
│
│── ADMIN PAGES ─────────────────────────────────────────
├── admin_dashboard.php         ← Overview stats & alerts
├── admin_members.php           ← Member list management
├── admin_pre_applications.php  ← Review & approve pre-registrations
├── admin_documents.php         ← Upload/manage member documents
├── admin_loan_applications.php ← Approve/reject loan applications
├── admin_inventory.php         ← Stock-in, stock-out, product management
├── admin_sales.php             ← Record & view sales transactions
├── admin_reports.php           ← Consolidated reports (loans + store)
├── admin_users.php             ← Staff/admin account management
└── admin_audit.php             ← System audit log
│
│── MEMBER PAGES ────────────────────────────────────────
├── member_dashboard.php        ← Member overview
├── member_loan_apply.php       ← Submit loan application
├── member_loans.php            ← View loan balances, due dates
├── member_loan_payment.php     ← Pay via GCash (QR code)
├── member_purchases.php        ← View grocery/rice purchase history
├── member_transactions.php     ← Full transaction history
└── member_pre_application.php  ← New member pre-registration
```

---

## Features Implemented

### Member Portal
- ✅ Online loan application (with loan type selection)
- ✅ Application status tracking
- ✅ Loan balance & payment history (calendar view)
- ✅ GCash loan payment with QR code
- ✅ Auto-update balance after payment
- ✅ Purchase history (grocery & rice) with search & filter
- ✅ Transaction monitoring for year-end benefits
- ✅ Member pre-application with verification message

### Admin Portal
- ✅ Dashboard with key statistics & alerts
- ✅ User management (staff/admin roles)
- ✅ Document management (upload by member ID/type)
- ✅ Loan application approval/rejection
- ✅ Pre-registration approval
- ✅ Consolidated reports (loans + sales)
- ✅ System audit logs
- ✅ Inventory control (search, category, stock-in/out)
- ✅ Sales recording (cash & credit, member-linked)
- ✅ Purchase linking to member profiles
