# After-Sales Service Information System (ASSIS)

**Candidate:** Najma Hassan (ADM: BIT/2024/48669)  
**Institution:** Mount Kenya University – School of Computing and Informatics  
**Architecture:** Three-Tier Architecture (Presentation: HTML5/Bootstrap 5; Application: PHP; Data: MySQL)  
**Methodological Model:** Rapid Application Development (RAD) (Martin, 1991)  

---

## 1. Project Directory Structure

```
after-sales-system/
├── config/
│   └── database.php           # PDO connection, base URL helper, session & CSRF security
├── database/
│   └── schema.sql             # Complete 3NF DDL schema for all 10 core tables + USERS
├── DATABASE_DOCUMENTATION.md  # As-built ERD, data dictionary, table and relationship reference
├── includes/
│   ├── auth.php               # Role-Based Access Control (RBAC) & session guards
│   ├── header.php             # Responsive Bootstrap 5 header & dynamic RBAC navbar
│   └── footer.php             # Common footer, copyright, and script links
├── assets/
│   ├── css/
│   │   └── style.css          # Cohesive UI styling, stat cards, badges & print styles
│   └── js/
│       └── main.js            # Auto-dismissing alerts, confirm guards, print triggers
├── auth/
│   ├── login.php              # Secure login interface (Figure 5.2 in proposal)
│   └── logout.php             # Session termination script
├── customers/
│   ├── index.php              # Customer directory with search & product counter
│   ├── create.php             # Customer registration form (Figure 5.3 in proposal)
│   └── view.php               # Customer profile with linked products & service history
├── products/
│   ├── index.php              # Equipment registry with warranty status indicators
│   └── create.php             # Product registration linked to customer & warranty terms
├── requests/
│   ├── index.php              # Service request list with status & priority filters
│   ├── create.php             # Service request logging form (Figure 5.4 in proposal)
│   └── view.php               # Complete lifecycle ticket view & technician assignment
├── technicians/
│   ├── index.php              # Technician specialist directory & workload counters
│   └── workbench.php          # Technician repair workbench & atomic parts deduction
├── inventory/
│   └── index.php              # Spare parts inventory, reorder alerts, and stock-in
├── billing/
│   ├── index.php              # Invoices directory with payment status filters
│   └── view.php               # Printable official service invoice (Labour + Parts total)
├── feedback/
│   ├── index.php              # Customer feedback list & average CSAT rating
│   └── create.php             # Customer rating (1-5 stars) and comments capture
├── reports/
│   ├── index.php              # Management reporting dashboard
│   ├── status.php             # Service Request Status Report (Figure 5.5 wireframe)
│   ├── workload.php           # Technician workload & throughput report
│   └── inventory.php          # Spare parts inventory valuation & reorder report
├── dashboard.php              # Role-tailored operational KPI dashboard
├── index.php                  # Entry router (redirects to dashboard or login)
├── ROADMAP.md                 # 21-Week RAD project implementation roadmap
└── TECHNICAL_SPECIFICATION.md # Complete technical specification document
```

---

## 2. Local Setup & Execution Guide (XAMPP Environment)

### Step 1: Copy to Web Root
Copy or link the `after-sales-system` directory into your XAMPP web server directory:
```
C:\xampp\htdocs\after-sales-system
```

### Step 2: Database Initialization
1. Start **Apache** and **MySQL** in your XAMPP Control Panel.
2. Open **phpMyAdmin** in your browser (`http://localhost/phpmyadmin`).
3. Click on **Import** and select [`database/schema.sql`](./database/schema.sql) (Creates `assis_db` and all relational tables).

   *(Note: Alternatively, ASSIS automatically creates the database and executes `schema.sql` on first launch).*

### Step 3: First-Run Administrator Registration
1. Configure a random `INITIAL_ADMIN_SETUP_KEY` of at least 32 characters for PHP/Apache. For XAMPP, add `SetEnv INITIAL_ADMIN_SETUP_KEY "your-long-random-key"` to the Apache configuration and restart Apache. Do not commit the key.
2. Navigate to the application in any web browser:
   ```
   http://localhost/after-sales-system/
   ```
3. The system detects a fresh installation (0 users) and presents the **Administrator Setup** screen (`/auth/register.php`).
4. Enter the configured setup key, **Full Name**, **Username**, and **Password** to register the primary Administrator account. Setup is disabled if the key is not configured and closes after an account is created.
5. Sign in to access the system. You can then register Front Desk and Technician staff via **Settings > Staff User Management** (`/users/index.php`).

---

## 3. User Roles & Access Control

ASSIS strictly enforces Role-Based Access Control (RBAC) across three operational roles:

| Role | Operational Scope | Account Creation |
|---|---|---|
| **Service Manager (Admin)** | Full administrative control, technician management, inventory, management reports, and staff user management. | Created during initial system setup (`/auth/register.php`). |
| **Front Desk / Reception** | Customer registration, equipment linking, service request logging, invoice distribution, feedback collection. | Registered by Administrator under **Settings > Staff Users**. |
| **Technician** | Access assigned work orders on the Technician Workbench (`/technicians/workbench.php`), record actions taken, consume spare parts. | Registered by Administrator under **Settings > Staff Users** and linked to a Technician specialist record. |

---

## 4. Key Proposal Compliance Highlights

- **RAD Model:** Organized cleanly across modular slices corresponding to the 4 RAD phases defined in [ROADMAP.md](./ROADMAP.md).
- **Three-Tier Architecture:** Complete separation of presentation (HTML5/Bootstrap 5), application logic (PHP), and data persistence (MySQL).
- **Wireframe Fidelity:** Interfaces faithfully reproduce Figure 5.2 (Login), Figure 5.3 (Customer Registration), Figure 5.4 (Service Request Form), and Figure 5.5 (Service Request Status Report).
- **Scope Discipline:** Strictly internal after-sales workflow without external payment gateway dependencies, customer self-service portals, or mobile app distractions.
