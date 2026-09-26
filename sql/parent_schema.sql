-- =============================================================================
-- Quinos Customer Balance — PARENT DATABASE (customer_balance_db)
-- =============================================================================
-- Holds what spans all clients: super admins, the client registry (name, slug,
-- logo, which POS database is theirs) and which of a client's employees may
-- sign in, with what role.
--
-- Client data itself never lives here. Every client has its own POS database
-- on the same MySQL server (same credentials) and this app only reads
-- tbl_customers / tbl_point_transactions and manages tbl_employees there.
--
-- Run once:
--     mysql -u USER -p customer_balance_db < sql/parent_schema.sql
-- (cPanel: phpMyAdmin > customer_balance_db > Import, or paste into SQL.)
-- Then open  <base-url>/admin/setup  to create the first super admin.
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS sa_admins (
    id            INT          NOT NULL AUTO_INCREMENT,
    username      VARCHAR(64)  NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at    DATETIME     NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_sa_admins_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS clients (
    id         INT          NOT NULL AUTO_INCREMENT,
    name       VARCHAR(120) NOT NULL,
    -- URL segment: <base-url>/<slug>. Lowercase letters, digits and dashes.
    slug       VARCHAR(64)  NOT NULL,
    -- Path relative to the app root, e.g. uploads/logos/demo-1a2b3c.png
    logo_path  VARCHAR(255)     NULL,
    -- The client's own POS database on this server.
    db_name    VARCHAR(64)  NOT NULL,
    active     TINYINT(1)   NOT NULL DEFAULT 1,
    created_at DATETIME     NOT NULL,
    updated_at DATETIME     NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_clients_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Only employees listed here can sign in at their client's URL. employee_id is
-- tbl_employees.id in that client's own database (no FK — different DB).
CREATE TABLE IF NOT EXISTS client_users (
    id          INT                       NOT NULL AUTO_INCREMENT,
    client_id   INT                       NOT NULL,
    employee_id BIGINT                    NOT NULL,
    role        ENUM('admin', 'cashier')  NOT NULL DEFAULT 'cashier',
    created_at  DATETIME                  NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_client_users (client_id, employee_id),
    CONSTRAINT fk_client_users_client FOREIGN KEY (client_id)
        REFERENCES clients (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
