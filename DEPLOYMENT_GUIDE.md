# Production Deployment & Operations Guide

## Alamgir Welfare Trust Int'l (AWT) - Intern Management System (AWT-IMS)

The **AWT Intern Management System (AWT-IMS)** is **live and fully deployed in production** on your Plesk hosting account under:
👉 **[https://app.alamgirwelfaretrust.com.pk/intern/](https://app.alamgirwelfaretrust.com.pk/intern/)**

---

## 1. Live Environment & Infrastructure Details

| Component | Production Configuration |
| :--- | :--- |
| **Live URL** | `https://app.alamgirwelfaretrust.com.pk/intern/` |
| **FTP Host / Server** | `65.21.160.27` / `s92.itserver.biz` |
| **Control Panel** | Plesk Windows Edition (PleskWin) |
| **Web Server** | Microsoft IIS 10.0 |
| **Backend Runtime** | PHP 8.1+ (FastCGI) |
| **Database Server** | MariaDB v10.11.3 (`localhost:3306`) |
| **Database Name** | `intern` |
| **Database User** | `intern` |
| **Historical Data** | **764 Authentic Intern Records** migrated from Access (`New Internship_be.accdb` + `Certificate Intership.accdb`) |

---

## 2. Production Access Portals & Credentials

All modules and portals are fully active:

### 👑 Administrator Portal
- **URL**: [https://app.alamgirwelfaretrust.com.pk/intern/login.php?role=admin](https://app.alamgirwelfaretrust.com.pk/intern/login.php?role=admin)
- **Email / Username**: `admin@awt.org`
- **Password**: `admin123`
- *Features*: Manage 764+ interns, review online applications, generate printable certificates & offer letters, mark daily team attendance, evaluate 10-point appraisals, supervisor assignments, university reports & 1-click CSV export.

### 🧑‍🏫 Supervisor / Mentor Portal
- **URL**: [https://app.alamgirwelfaretrust.com.pk/intern/login.php?role=supervisor](https://app.alamgirwelfaretrust.com.pk/intern/login.php?role=supervisor)
- **Email**: `supervisor@awt.org`
- **Password**: `supervisor123`
- *Features*: Assigned intern roster, daily attendance marking, task assignment & grading with feedback, 10-point performance appraisal evaluation.

### 🎓 Intern Self-Service Portal
- **URL**: [https://app.alamgirwelfaretrust.com.pk/intern/login.php?role=intern](https://app.alamgirwelfaretrust.com.pk/intern/login.php?role=intern)
- **Login Identifier**: Any legacy Intern ID (e.g. `917`, `866`, `56`, `101`) or registered student email
- **Password**: `intern123`
- *Features*: Personal progress dashboard, 6-point document checklist status, task deliverables upload, attendance log, view/print completion certificate & offer letter.
- *Note*: Any of the 764 historical interns can log in immediately; the system auto-provisions their secure user account upon first login.

### 📝 Public Online Application
- **URL**: [https://app.alamgirwelfaretrust.com.pk/intern/apply.php](https://app.alamgirwelfaretrust.com.pk/intern/apply.php)
- Allows prospective students to submit internship requests directly into the system for administrative review.

### 🔍 Certificate Verification Portal
- **URL**: [https://app.alamgirwelfaretrust.com.pk/intern/verify.php](https://app.alamgirwelfaretrust.com.pk/intern/verify.php)
- Allows universities and prospective employers to verify completion certificates by entering the Certificate Number or scanning the QR code on the certificate.

---

## 3. Database Architecture & Tables in Production

The MariaDB database `intern` is active with 9 optimized tables:

1. `users`: Authentication & RBAC (Admin, Supervisor, Intern).
2. `interns`: 764 authentic legacy records with full profiles, document booleans, certificate periods, and 10 appraisal scores.
3. `supervisors`: Department mentors and supervisors.
4. `tasks`: Project and work assignments with priorities and deadlines.
5. `task_submissions`: Work deliverables uploaded by interns with supervisor grades and feedback.
6. `attendance`: Daily attendance ledger (Present, Absent, Leave, Late).
7. `certificates`: 32 pre-populated completion certificate records with verification hashes, plus dynamic generation for all certified interns.
8. `settings`: Organization parameters, coordinator signatory name, active academic year.
9. `activity_logs`: Audit trail of logins, updates, and status changes.

---

## 4. Maintenance & Data Imports

- **Local Source Files**: Both Access databases (`New Internship_be.accdb` and `Certificate Intership.accdb`) remain intact and safely preserved in your workspace `d:\Abubakar\AWT Internship Managment`.
- **Master Database SQL**: A complete all-in-one SQL file containing schema, seed data, and all 764 records is stored at:
  👉 [database/awt_internship_full.sql](file:///d:/Abubakar/AWT%20Internship%20Managment/database/awt_internship_full.sql) (1.36 MB)
- **Incremental Import from Certificate Intership.accdb**:
  To add the 64 new interns, update existing certificate periods, and populate the certificate ledger in your live MariaDB database without re-creating existing tables:
  1. Open **phpMyAdmin** in Plesk &rarr; select database `intern`.
  2. Click the **Import** tab.
  3. Choose the incremental file: 👉 [database/migrate_certificate_data.sql](file:///d:/Abubakar/AWT%20Internship%20Managment/database/migrate_certificate_data.sql) (91 KB).
  4. Click **Go**.
  5. All 64 new interns and 32 certificate records will be instantly added and active!
- **Re-running Data Importers Locally**:
  ```powershell
  # 1. To re-export base 700 records from New Internship_be.accdb:
  powershell -ExecutionPolicy Bypass -File database\export_from_access.ps1

  # 2. To re-export remaining records and certificate ledger from Certificate Intership.accdb:
  powershell -ExecutionPolicy Bypass -File database\import_certificate_data.ps1
  ```
