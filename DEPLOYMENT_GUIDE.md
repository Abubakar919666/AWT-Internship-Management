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
| **FTP Username** | `iternawt` |
| **FTP Password** | `e^18bD4q3` |
| **Control Panel** | Plesk Windows Edition (PleskWin) |
| **Web Server** | Microsoft IIS 10.0 |
| **Backend Runtime** | PHP 8.1+ (FastCGI) |
| **Database Server** | MariaDB v10.11.3 (`localhost:3306`) |
| **Database Name** | `intern` |
| **Database User** | `intern` / `iternawt` |
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
- **Universal Password**: `supervisor123`
- **Active Supervisor Accounts**:
  1. **Muhammad Wali Saleem** &bull; `supervisor@awt.org` / `wali.saleem` (Coordination & Media)
  2. **Abdul Latif** &bull; `latif@awt.org` / `abdul.latif` (Operations & Social Work)
  3. **Muhammad Amir** &bull; `amir@awt.org` / `muhammad.amir` (Health & OPD Unit)
  4. **Sohail Ahmed Khan** &bull; `sohail@awt.org` / `sohail.khan` (Administration)
  5. **Umer Qureshi** &bull; `umer@awt.org` / `umer.qureshi` (Marketing & Public Relations)
  6. **Niaz Khan** &bull; `niaz@awt.org` / `niaz.khan` (Field Operations & Logistics)
  7. **Nisar Ahmed** &bull; `nisar@awt.org` / `nisar.ahmed` (HR & Internship Coordination)
- *Supervisor Switcher*: Once logged into the Supervisor Dashboard, supervisors and administrators can switch between any supervisor's assigned interns or view **All Supervisors (Consolidated View)** directly using the top-right header menu or the prominent dashboard selector ribbon.
- *Features*: Department intern roster, daily attendance marking, task assignment & grading with feedback, 10-point performance appraisal evaluation.

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

---

## 5. Intern Photo Upload & Identity Card System

The system now supports intern photograph management with live camera scanning and printable official identity cards:

### 📸 Features Implemented:
1. **Interactive Photo Avatar with Camera Button**:
   - Located on the circular profile avatar in [admin/intern_view.php](file:///d:/Abubakar/AWT%20Internship%20Managment/admin/intern_view.php).
   - Allows instant photo upload or camera scan right from the profile header.
2. **Dual Upload Modal (`#uploadPhotoModal`)**:
   - **Upload File / Scanned Picture Tab**: Drag & drop or browse image files (JPG, PNG, WebP up to 8MB) with instant client-side preview.
   - **Live Camera Scanner Tab**: Connects to the computer webcam / scanner camera, provides a live viewfinder with circular face alignment guidelines, and captures high-resolution snapshots with 1-click retake or save.
3. **Official Intern Identity Card (`admin/id_card.php`)**:
   - Official 2-sided identity card with AWT branding.
   - Front side displays the intern's uploaded photo, student name, intern code, university, department, valid period, and emergency contact.
   - Back side features official terms, supervisor signature line, contact info, and dynamic QR Code + Code128 Barcode.
   - Dedicated print-optimized CSS for standard 85.6mm x 54mm ID card stock.
4. **Documents Submitted Checklist Integration**:
   - "Photograph" checklist item automatically reflects `Received` with green badge and a `View Photo` modal once uploaded.
5. **Database Auto-Migration**:
   - The `photo` column in the `interns` table self-provisions automatically via `ensure_intern_photo_column()` in `config/helpers.php`.
