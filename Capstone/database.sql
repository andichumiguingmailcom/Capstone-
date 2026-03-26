-- ════════════════════════════════════════
--  COOPERATIVE INFORMATION MANAGEMENT SYSTEM
--  Database Schema
-- ════════════════════════════════════════

CREATE DATABASE IF NOT EXISTS coop_ims;
USE coop_ims;

-- ── USERS (Staff / Admin) ──
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(60) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    first_name  VARCHAR(60) NOT NULL,
    middle_name VARCHAR(60),
    last_name   VARCHAR(60) NOT NULL,
    email       VARCHAR(120),
    role        ENUM('loan_officer','cashier','book_keeper','collector','general_manager','staff') DEFAULT 'staff',
    is_active   TINYINT(1) DEFAULT 1,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ── MEMBERS ──
CREATE TABLE IF NOT EXISTS members (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    member_id       VARCHAR(20) NOT NULL UNIQUE,
    first_name      VARCHAR(60) NOT NULL,
    middle_name     VARCHAR(60),
    last_name       VARCHAR(60) NOT NULL,
    email           VARCHAR(120),
    phone           VARCHAR(20),
    street          VARCHAR(150),
    barangay        VARCHAR(100),
    city            VARCHAR(100),
    province        VARCHAR(100),
    date_joined     DATE,
    status          ENUM('active','inactive','suspended') DEFAULT 'active',
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ── MEMBER PRE-APPLICATIONS ──
CREATE TABLE IF NOT EXISTS pre_applications (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    first_name      VARCHAR(60) NOT NULL,
    middle_name     VARCHAR(60),
    last_name       VARCHAR(60) NOT NULL,
    email           VARCHAR(120),
    phone           VARCHAR(20),
    street          VARCHAR(150),
    barangay        VARCHAR(100),
    city            VARCHAR(100),
    province        VARCHAR(100),
    submitted_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    status          ENUM('pending','approved','rejected') DEFAULT 'pending',
    verified_at     DATETIME,
    admin_notes     TEXT
);

-- ── LOAN TYPES ──
CREATE TABLE IF NOT EXISTS loan_types (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    type_name   VARCHAR(80) NOT NULL,
    max_amount  DECIMAL(12,2),
    interest    DECIMAL(5,2), -- Monthly interest rate
    penalty_rate DECIMAL(5,2) DEFAULT 2.00, -- Monthly penalty rate for overdue
    max_months  INT
);

-- ── LOAN APPLICATIONS ──
CREATE TABLE IF NOT EXISTS loan_applications (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    member_id       INT NOT NULL,
    loan_type_id    INT NOT NULL,
    amount          DECIMAL(12,2) NOT NULL,
    term_months     INT NOT NULL,
    purpose         TEXT,
    status          ENUM('pending','approved','rejected','disbursed') DEFAULT 'pending',
    applied_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    approved_at     DATETIME,
    approved_by     INT,
    remarks         TEXT,
    FOREIGN KEY (member_id) REFERENCES members(id),
    FOREIGN KEY (loan_type_id) REFERENCES loan_types(id),
    FOREIGN KEY (approved_by) REFERENCES users(id)
);

-- ── LOANS (Active) ──
CREATE TABLE IF NOT EXISTS loans (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    application_id  INT NOT NULL,
    member_id       INT NOT NULL,
    principal       DECIMAL(12,2),
    balance         DECIMAL(12,2),
    accrued_penalty DECIMAL(12,2) DEFAULT 0.00,
    monthly_due     DECIMAL(12,2),
    due_date        DATE,
    disbursed_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    status          ENUM('active','settled','defaulted') DEFAULT 'active',
    FOREIGN KEY (application_id) REFERENCES loan_applications(id),
    FOREIGN KEY (member_id) REFERENCES members(id)
);

-- ── LOAN PAYMENTS ──
CREATE TABLE IF NOT EXISTS loan_payments (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    loan_id         INT NOT NULL,
    amount          DECIMAL(12,2) NOT NULL,
    paid_at         DATETIME DEFAULT CURRENT_TIMESTAMP,
    payment_method  ENUM('gcash','cash','bank') DEFAULT 'gcash',
    reference_no    VARCHAR(60),
    recorded_by     INT,
    FOREIGN KEY (loan_id) REFERENCES loans(id),
    FOREIGN KEY (recorded_by) REFERENCES users(id)
);

-- ── PRE-APPLICATION DOCUMENTS ──
CREATE TABLE IF NOT EXISTS pre_application_documents (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    pre_application_id  INT NOT NULL,
    doc_type            VARCHAR(80) NOT NULL,
    filename            VARCHAR(255) NOT NULL,
    filepath            VARCHAR(255) NOT NULL,
    uploaded_at         DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pre_application_id) REFERENCES pre_applications(id)
);

-- ── PRODUCTS (Grocery & Rice) ──
CREATE TABLE IF NOT EXISTS products (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    sku         VARCHAR(40) UNIQUE,
    name        VARCHAR(150) NOT NULL,
    category    ENUM('grocery','rice','other') DEFAULT 'grocery',
    unit        VARCHAR(20),
    price       DECIMAL(10,2) DEFAULT 0,
    stock       INT DEFAULT 0,
    reorder_pt  INT DEFAULT 5,
    is_active   TINYINT(1) DEFAULT 1,
    updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ── STOCK MOVEMENTS ──
CREATE TABLE IF NOT EXISTS stock_movements (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    product_id  INT NOT NULL,
    type        ENUM('stock_in','stock_out','adjustment') NOT NULL,
    qty         INT NOT NULL,
    notes       TEXT,
    moved_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    moved_by    INT,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (moved_by) REFERENCES users(id)
);

-- ── SALES / PURCHASES ──
CREATE TABLE IF NOT EXISTS sales (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    member_id       INT,
    sale_date       DATE NOT NULL,
    total           DECIMAL(12,2) DEFAULT 0,
    payment_type    ENUM('cash','credit') DEFAULT 'cash',
    status          ENUM('completed','voided') DEFAULT 'completed',
    recorded_by     INT,
    FOREIGN KEY (member_id) REFERENCES members(id),
    FOREIGN KEY (recorded_by) REFERENCES users(id)
);

-- ── SALE ITEMS ──
CREATE TABLE IF NOT EXISTS sale_items (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    sale_id     INT NOT NULL,
    product_id  INT NOT NULL,
    qty         INT NOT NULL,
    unit_price  DECIMAL(10,2),
    subtotal    DECIMAL(12,2),
    FOREIGN KEY (sale_id) REFERENCES sales(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- ── DOCUMENTS ──
CREATE TABLE IF NOT EXISTS documents (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    member_id   INT NOT NULL,
    doc_type    VARCHAR(80),
    filename    VARCHAR(200),
    filepath    VARCHAR(255),
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    uploaded_by INT,
    FOREIGN KEY (member_id) REFERENCES members(id),
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);

-- ── AUDIT LOG ──
CREATE TABLE IF NOT EXISTS audit_logs (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT,
    action      VARCHAR(100),
    table_name  VARCHAR(60),
    record_id   INT,
    details     TEXT,
    ip_address  VARCHAR(45),
    logged_at   DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ── SEED DATA ──
INSERT IGNORE INTO users (username, password, first_name, last_name, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Administrator', 'admin'),
('staff1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Maria', 'Santos', 'staff');

INSERT IGNORE INTO loan_types (type_name, max_amount, interest, penalty_rate, max_months) VALUES
('Emergency Loan', 10000.00, 2.5, 5.00, 6),
('Regular Loan', 50000.00, 1.5, 2.00, 24),
('Educational Loan', 30000.00, 1.0, 2.00, 12),
('Business Loan', 100000.00, 2.0, 3.00, 36);

INSERT IGNORE INTO members (member_id, first_name, last_name, email, phone, date_joined, status) VALUES
('MEM-001', 'Juan', 'dela Cruz', 'juan@email.com', '09171234567', '2022-01-15', 'active'),
('MEM-002', 'Maria', 'Santos', 'maria@email.com', '09281234567', '2022-03-10', 'active'),
('MEM-003', 'Pedro', 'Reyes', 'pedro@email.com', '09391234567', '2023-06-01', 'active');

INSERT IGNORE INTO products (sku, name, category, unit, price, stock, reorder_pt) VALUES
('RIC-001', 'Premium White Rice', 'rice', 'kg', 55.00, 500, 50),
('RIC-002', 'Brown Rice', 'rice', 'kg', 65.00, 200, 30),
('GRC-001', 'Cooking Oil 1L', 'grocery', 'bottle', 85.00, 120, 20),
('GRC-002', 'Sugar 1kg', 'grocery', 'pack', 70.00, 80, 15),
('GRC-003', 'Salt 500g', 'grocery', 'pack', 18.00, 150, 25);
