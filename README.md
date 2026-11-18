# PosMyAdmin - PostgreSQL Web Management Dashboard

A modern, high-performance web-based administration tool for **PostgreSQL**, built with **PHP 8.1+**, **Tailwind CSS**, and **Lucide Icons**.

---

## 🌟 Key Features

### 1. Robust Authentication & Session Security
- **PostgreSQL Native Credential Verification:** Direct connection testing against your PostgreSQL server (`Host`, `Port`, `Database`, `Username`, `Password`, `SSL Mode`).
- **AES-256-GCM Session Encryption:** Active credentials are encrypted using OpenSSL before being stored in PHP sessions.
- **CSRF Protection:** Cryptographic anti-CSRF token verification on all POST/PUT/DELETE forms and actions.
- **Brute-Force Rate Limiter:** Automatic IP-based lockout after 5 consecutive failed login attempts (15-minute cooldown).
- **Session Fingerprinting:** Binds user session to client `User-Agent` and IP subnet to prevent session hijacking.
- **Inactivity Timeout:** Automatic session expiration upon 60 minutes of inactivity.

### 2. Comprehensive Server Dashboard
The homepage provides a real-time health overview of your PostgreSQL server:
- **Server Core & Environment:** PostgreSQL version, architecture, server encoding, client encoding, port, and timezone.
- **Server Uptime:** Formatted uptime (e.g. `2d 4h 12m`) and postmaster start timestamp.
- **Connection Pool Gauge:** Live active vs. idle connections with percentage meter against `max_connections`.
- **Buffer Cache Hit Ratio:** Real-time buffer cache hit percentage (`blks_hit / (blks_hit + blks_read)`).
- **Transaction Health:** Total commits vs. rollbacks ratio, conflict counts, and deadlocks.
- **Storage & Databases:** Cluster storage size and full table of all databases with disk sizes (`pg_size_pretty`) and active connections.
- **Server Configuration:** Quick inspection of `shared_buffers`, `work_mem`, `maintenance_work_mem`, `effective_cache_size`, `wal_level`, and `fsync`.

### 3. Database & Schema Management
- **Databases:** Create and drop databases with custom encodings (`UTF8`, `LATIN1`, etc.).
- **Schemas:** PostgreSQL schema support (list, create, and drop cascade schemas).
- **Quick Database Switcher:** Easily switch active session database from the top bar dropdown.

### 4. Interactive Data Grid (Browse & CRUD)
- **Smart Pagination:** Configurable page size (25, 50, 100 rows per page).
- **Dynamic Sorting:** Click any column header to sort ascending or descending.
- **Filter Builder:** Filter rows by column using operators (`=`, `ILIKE`, `!=`, `>`, `<`, `IS NULL`, `IS NOT NULL`).
- **Inline / Modal Editing:** Edit any record directly in a modal without page reloads.
- **Row Insert:** Form tailored to PostgreSQL data types with automatic sequence detection.
- **Row Deletion:** Safe deletion using primary keys with confirmation.
- **Truncate & Drop:** Rapid table cleanup operations.

### 5. Table Structure & Constraints
- Inspect column names, data types, nullability, default values, and comments.
- Primary keys and Unique constraints.
- Foreign keys with referential actions (`ON UPDATE`, `ON DELETE`).
- Table indexes definitions.
- **Relational Foreign Key Quick-Jump:** Foreign key values in the data grid render as interactive badges. Clicking any linked value immediately redirects to that referenced record in the foreign table!

### 6. Full Database Backup & Import Hub (`/database/backup`)
- **Full Database Export (SQL Dump):**
  - Download complete PostgreSQL `.sql` script containing all schemas, tables, indexes, constraints, and data in 1 click.
  - Options to include/exclude DDL structure, data inserts, `DROP TABLE` statements, and transaction wrapper (`BEGIN; ... COMMIT;`).
- **Full Database Import:**
  - Upload `.sql` file or paste raw SQL scripts.
  - Transaction safety: automatic rollback on syntax or execution failure.
  - Instant progress and duration reporting.

### 6. SQL Query Console
- Live query editor with syntax-ready dark theme.
- **Keyboard Shortcut:** `Ctrl + Enter` (or `Cmd + Enter`) to run queries immediately.
- **EXPLAIN (ANALYZE, BUFFERS):** One-click query execution plan and performance profiling.
- **Execution Timing:** Query run time measured in milliseconds (`ms`).
- **Quick Snippets:** Common pre-built queries for quick inspection.
- **Export Results:** Export query output directly to **CSV** or **JSON**.

### 7. Server Activity Monitor (`pg_stat_activity`)
- Live process list with PID, connected user, client IP, query runtime duration, state, and query text.
- **Cancel Query:** `pg_cancel_backend(pid)` to safely stop long-running queries.
- **Kill Process:** `pg_terminate_backend(pid)` to terminate hanging connections.

### 8. Export Utilities
- Export any table to **SQL** (schema inserts), **CSV**, or **JSON**.

### 9. Centralized Error & Security Logging
- **Application Error Log (`storage/logs/error.log`):**
  - Captures all PHP errors, warnings, uncaught exceptions, and fatal crashes with full stack traces.
- **Dedicated Authentication & Security Log (`storage/logs/auth.log`):**
  - Records all security and authentication events separately:
    - Failed login attempts (`LOGIN_FAILED`) with username, host, and attempt counter (e.g. `2/5`).
    - Brute-force rate limit lockouts (`RATE_LIMIT_LOCKOUT` and `RATE_LIMIT_BLOCKED`) with remaining duration.
    - Successful authentications (`LOGIN_SUCCESS`) for security audit trails.
- **In-App Dual Log Viewer (`/logs`):** Switch between `error.log` and `auth.log` tabs, view live file sizes, and download or clear each log independently.
- **Auto-Rotation:** Automatically rotates log files when reaching 10 MB to prevent disk bloat.

### 10. Reverse Proxy & Cloudflare Native Support
- **Automatic Real IP Resolution:** Accurately extracts the real visitor IP address when behind **Cloudflare** (`CF-Connecting-IP`, `True-Client-IP`), standard load balancers (`X-Forwarded-For`), and reverse proxies (`X-Real-IP`).
- **Anti-Spoofing IP Validation:** Strictly validates all proxy headers against valid IPv4/IPv6 syntax before acceptance to prevent header injection attacks.
- **Cloudflare Telemetry in Audit Logs:** Automatically logs visitor country code (`HTTP_CF_IPCOUNTRY`) and Cloudflare Ray ID (`HTTP_CF_RAY`) in `storage/logs/auth.log` and `error.log`.
- **Accurate Rate Limiting & Fingerprinting:** Prevents Cloudflare edge server IPs from being mistakenly locked out or causing false session invalidation.

---

## 🚀 Quick Start

### Prerequisites
- PHP 8.1+ with `pdo_pgsql`, `openssl`, and `json` extensions.
- Running PostgreSQL server (local or remote).

### Running the Application
1. Start the server using the batch file:
   ```cmd
   serve.bat
   ```
   Or via command line:
   ```cmd
   php -S 127.0.0.1:8080 -t public
   ```
2. Open your browser and navigate to:
   ```
   http://127.0.0.1:8080
   ```
3. Enter your PostgreSQL credentials:
   - **Host:** `127.0.0.1`
   - **Port:** `5432`
   - **Database:** `postgres`
   - **Username:** `postgres`
   - **Password:** *your postgres password*
