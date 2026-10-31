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
│   ├── schema.sql             # Complete 3NF DDL schema for all 10 core tables + USERS
│   └── seed.sql               # Realistic initial demonstration fixtures & test accounts
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
3. Click on **Import** and select:
   - First import: [`database/schema.sql`](./database/schema.sql) (Creates `assis_db` and all 11 tables).
   - Second import: [`database/seed.sql`](./database/seed.sql) (Populates test accounts and initial data).

### Step 3: Access Application
Navigate to the application in any web browser:
```
http://localhost/after-sales-system/
```

---

## 3. Pre-Seeded Demonstration Accounts

All pre-seeded demo accounts share the password: `password123`

| Role | Username | Password | Operational Access |
|---|---|---|---|
| **Service Manager (Admin)** | `admin` | `password123` | Full administrative control, technician management, inventory, reports, and all operational modules. |
| **Front Desk / Reception** | `reception` | `password123` | Customer registration, equipment linking, logging service requests, invoice distribution, feedback capture. |
| **Technician (Audio-Visual)** | `tech.david` | `password123` | View assigned work orders, open technician workbench, record actions taken, consume spare parts. |
| **Technician (Cooling)** | `tech.sarah` | `password123` | View assigned work orders, record cooling and refrigeration repairs. |

---

## 4. Key Proposal Compliance Highlights

- **RAD Model:** Organized cleanly across modular slices corresponding to the 4 RAD phases defined in [ROADMAP.md](./ROADMAP.md).
- **Three-Tier Architecture:** Complete separation of presentation (HTML5/Bootstrap 5), application logic (PHP), and data persistence (MySQL).
- **Wireframe Fidelity:** Interfaces faithfully reproduce Figure 5.2 (Login), Figure 5.3 (Customer Registration), Figure 5.4 (Service Request Form), and Figure 5.5 (Service Request Status Report).
- **Scope Discipline:** Strictly internal after-sales workflow without external payment gateway dependencies, customer self-service portals, or mobile app distractions.
