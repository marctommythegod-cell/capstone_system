# PhilCST Class Card Dropping System — System Flow

## 📋 Overview

The **PhilCST Class Card Dropping System** is a web-based application built with PHP (XAMPP/MySQL) that allows teachers to submit class card drop requests for students and administrators to approve, manage, and track those requests. Email notifications are sent via PHPMailer (Gmail SMTP) at key stages.

---

## 🏗️ Architecture

```
┌──────────────┐      ┌────────────────┐      ┌──────────────────┐
│   Browser    │◄────►│  PHP (Apache)  │◄────►│  MySQL Database  │
│  (Frontend)  │      │   index.php    │      │ philcst_class_drops│
│  HTML/CSS/JS │      │   admin/*      │      └──────────────────┘
│              │      │   teacher/*    │
│              │      │   includes/*   │      ┌──────────────────┐
│              │      │   email/*      │─────►│  Gmail SMTP      │
│              │      │   config/*     │      │  (PHPMailer)     │
└──────────────┘      └────────────────┘      └──────────────────┘
```

### Directory Structure

| Directory       | Purpose                                      |
|-----------------|----------------------------------------------|
| `/`             | Login page (`index.php`)                     |
| `/admin/`       | Admin dashboard & management pages           |
| `/teacher/`     | Teacher dashboard & drop action pages        |
| `/includes/`    | API endpoints, helper functions, session mgmt|
| `/config/`      | Database & email configuration               |
| `/email/`       | `EmailNotifier` class (PHPMailer wrapper)    |
| `/js/`          | Client-side JavaScript (modals, filters)     |
| `/css/`         | Stylesheets                                  |
| `/vendor/`      | Composer dependencies (PHPMailer, phpdotenv)  |

---

## 🗄️ Database Schema

```
┌──────────────────┐     ┌──────────────────┐
│      users       │     │     students     │
├──────────────────┤     ├──────────────────┤
│ id (PK)          │     │ id (PK)          │
│ name             │     │ student_id (UQ)  │
│ email (UQ)       │     │ name             │
│ password (bcrypt)│     │ email (UQ)       │
│ role (enum)      │     │ course           │
│   └─ admin       │     │ year             │
│   └─ teacher     │     │ created_at       │
│ teacher_id       │     │ updated_at       │
│ address          │     └────────┬─────────┘
│ department       │              │
│ status           │              │
│ created_at       │              │
│ updated_at       │              │
└────────┬─────────┘              │
         │                        │
         │    ┌───────────────────┘
         │    │
┌────────▼────▼─────────────────────────────┐
│          class_card_drops                 │
├───────────────────────────────────────────┤
│ id (PK)                                   │
│ teacher_id (FK → users.id)                │
│ student_id (FK → students.id)             │
│ subject_no                                │
│ subject_name                              │
│ remarks                                   │
│ status (Pending / Dropped / Undropped)    │
│ drop_date                                 │
│ drop_month                                │
│ drop_year                                 │
│ retrieve_date                             │
│ undrop_remarks                            │
│ approved_by (FK → users.id, nullable)     │
│ approved_date                             │
│ created_at                                │
└───────────────────────────────────────────┘

┌──────────────────┐
│     subjects     │
├──────────────────┤
│ id (PK)          │
│ subject_no (UQ)  │
│ subject_name     │
│ created_at       │
└──────────────────┘
```

---

## 👤 User Roles

| Role      | Access                                               |
|-----------|------------------------------------------------------|
| **Admin** | Dashboard, Dropped Cards (approve/undrop), Manage Students, Manage Teachers, Drop History |
| **Teacher** | Dashboard, Drop Class Card (submit request), Drop History (cancel own pending requests) |

---

## 🔐 Authentication Flow

```
┌─────────┐      GET /index.php      ┌──────────────┐
│  User   │─────────────────────────►│  Login Page  │
└─────────┘                          └──────┬───────┘
                                            │
                                     POST (email, password)
                                            │
                                            ▼
                                   ┌────────────────┐
                                   │ Validate creds │
                                   │ (bcrypt verify)│
                                   └───────┬────────┘
                                           │
                              ┌────────────┴────────────┐
                              │                         │
                         role = admin              role = teacher
                              │                         │
                              ▼                         ▼
                   ┌──────────────────┐      ┌───────────────────┐
                   │ /admin/dashboard │      │ /teacher/dashboard│
                   └──────────────────┘      └───────────────────┘
```

1. User visits `index.php` (login page).
2. If already logged in (`$_SESSION['user_id']` is set), redirect to the appropriate dashboard.
3. On form submit, email & password are validated against the `users` table using `password_verify()`.
4. On success, session variables `user_id` and `user_role` are set and the user is redirected.
5. On failure, an error message is displayed.

### Session Protection

Every protected page includes `session_check.php`, which:
- Starts the session
- Redirects to login if `$_SESSION['user_id']` is not set
- Verifies the user still exists in the database
- Refreshes `user_role` from the database

### Logout

`/includes/logout.php` destroys the session and redirects to the login page. A JavaScript modal (`showLogoutModal()`) confirms the action before navigating.

---

## 📘 Core Workflow: Class Card Drop Lifecycle

```
  ┌──────────┐        ┌──────────┐        ┌──────────┐        ┌────────────┐
  │ Teacher  │        │ PENDING  │        │ DROPPED  │        │ UNDROPPED  │
  │ submits  │───────►│ (awaits  │───────►│ (approved│───────►│ (admin     │
  │ request  │        │ approval)│        │ by admin)│        │ retrieves) │
  └──────────┘        └────┬─────┘        └──────────┘        └────────────┘
                           │
                           │ Teacher cancels
                           ▼
                      ┌──────────┐
                      │ DELETED  │
                      │ (removed │
                      │ from DB) │
                      └──────────┘
```

### Status Values

| Status       | Meaning                                                    |
|--------------|------------------------------------------------------------|
| **Pending**  | Teacher submitted a drop request; awaiting admin approval  |
| **Dropped**  | Admin approved the request; class card is officially dropped|
| **Undropped**| Admin reversed a previously approved drop (retrieved)      |

---

## 🧑‍🏫 Teacher Flow

### 1. Dashboard (`/teacher/dashboard.php`)
- View personal statistics: total drops, this month's drops, this week's drops.
- See the 5 most recent drop records.
- Quick action buttons to "Drop Student Class Card" or "View Drop History".

### 2. Drop Class Card (`/teacher/drop_class_card.php`)
- **Select a student** from a dropdown (auto-fills course & year).
- **Search & select a subject** by code or name.
- **Enter remarks** explaining the reason.
- Submit → POST to `api.php?action=drop_class_card`.

#### API: `drop_class_card`
```
Teacher POST ──► api.php?action=drop_class_card
                      │
                      ├─ Validate session (teacher role)
                      ├─ Validate student & subject exist
                      ├─ INSERT into class_card_drops (status = 'Pending')
                      └─ Redirect back with success message
```

### 3. Drop History (`/teacher/drop_history.php`)
- View all personal drop records with live search & date range filter.
- **Cancel a pending request** → POST to `api.php?action=undo_drop`.

#### API: `undo_drop`
```
Teacher POST ──► api.php?action=undo_drop
                      │
                      ├─ Validate session (teacher role)
                      ├─ Verify drop belongs to this teacher
                      ├─ DELETE the record from class_card_drops
                      └─ Redirect back with success message
```

> **Note:** Cancelling a pending drop deletes the record entirely. No notification is sent.

---

## 🛡️ Admin Flow

### 1. Dashboard (`/admin/dashboard.php`)
- View system-wide statistics: total drops, total students, total teachers.
- See the 10 most recent drop records with status badges.

### 2. Dropped Cards (`/admin/dropped_cards.php`)
- **Pending Requests Section:** Lists all `Pending` requests with an **Approve** button.
- **Approved/Undropped Section:** Lists all `Dropped` and `Undropped` records with an **Undrop** button for active drops.

#### Approve a Drop
```
Admin clicks "Approve" ──► JavaScript showConfirmModal()
                                  │
                           Confirm clicked
                                  │
                                  ▼
                POST to api.php?action=approve_drop
                      │
                      ├─ Validate session (admin role)
                      ├─ UPDATE status → 'Dropped', set approved_by & approved_date
                      ├─ 📧 Send approval email to STUDENT (official letter)
                      ├─ 📧 Send approval email to TEACHER (confirmation)
                      └─ Redirect back with success message
```

#### Undrop a Class Card
```
Admin clicks "Undrop" ──► JavaScript showUndropModal()
                                  │
                           Enter remarks → Confirm
                                  │
                                  ▼
                POST to dropped_cards.php (action=undrop)
                      │
                      ├─ Validate admin session
                      ├─ UPDATE status → 'Undropped', set retrieve_date & undrop_remarks
                      ├─ 📧 Send undrop notification email to TEACHER
                      └─ Redirect back with success message
```

### 3. Manage Students (`/admin/students.php`)
- **Add** new students (with validation: 8-digit ID, required fields, unique email).
- **Edit** existing students (inline modal form).
- **Delete** students (with confirmation modal).
- Live table search/filter.

### 4. Manage Teachers (`/admin/teachers.php`)
- **Add** new teachers (with password policy: uppercase, lowercase, number, special char).
- **Edit** teacher details and status (active/inactive).
- **Delete** teachers (with confirmation modal).
- Live table search/filter.

### 5. Drop History (`/admin/drop_history.php`)
- View all class card drop records across all teachers and students.
- Includes drop date, retrieve date, undrop remarks, and status.
- Live search/filter by Student ID, Name, Subject, or Teacher.

---

## 📧 Email Notification Flow

The `EmailNotifier` class wraps PHPMailer with Gmail SMTP. If SMTP fails, it falls back to PHP's `mail()` function.

| Event                        | Recipient | Email Subject                                  |
|------------------------------|-----------|------------------------------------------------|
| Drop request **approved**    | Student   | "Class Card Drop Official Letter - PhilCST"   |
| Drop request **approved**    | Teacher   | "Class Card Drop APPROVED - PhilCST"           |
| Class card **undropped**     | Teacher   | "Class Card RETRIEVED (Undropped) - PhilCST"   |

> **No email is sent** when a teacher submits or cancels a pending request.

---

## 🖥️ Client-Side Features (`functions.js`)

| Feature                | Function                     | Description                                     |
|------------------------|------------------------------|-------------------------------------------------|
| Logout confirmation    | `showLogoutModal()`          | Modal dialog before logout                      |
| Custom confirm dialog  | `showConfirmModal()`         | Replaces browser `confirm()` with styled modal  |
| Undrop modal           | `showUndropModal()`          | Modal with required remarks textarea            |
| Approval modal         | `showApprovalModal()`        | Confirm before approving a drop                 |
| Live table filter      | `liveTableFilter()`          | Instant search/filter on table rows as you type |
| Student info auto-fill | `updateStudentInfo()`        | Fills course & year on student selection         |
| Delete confirmation    | `confirmDelete()`            | Modal before deleting a record                  |
| CSV export             | `exportTableToCSV()`         | Downloads visible table data as CSV             |
| Print                  | `printPage()`                | Triggers browser print dialog                   |
| Notifications          | `showNotification()`         | Dynamic alert messages                          |
| Date formatting        | `formatDate()`               | Philippines timezone, 12-hour format             |

---

## 🔄 Complete Request Lifecycle (End-to-End)

```
┌─────────────────────────────────────────────────────────────────────┐
│                        HAPPY PATH                                   │
│                                                                     │
│  1. Teacher logs in                                                 │
│  2. Navigates to "Drop Class Card"                                  │
│  3. Selects student, subject, enters remarks                        │
│  4. Submits form → record created with status = "Pending"           │
│  5. Admin logs in                                                   │
│  6. Sees pending request on "Dropped Cards" page                    │
│  7. Clicks "Approve" → confirms in modal                            │
│  8. Status updated to "Dropped", approved_by & approved_date set    │
│  9. 📧 Student receives official drop letter via email              │
│ 10. 📧 Teacher receives approval confirmation via email             │
│                                                                     │
│                   OPTIONAL: UNDROP                                   │
│                                                                     │
│ 11. Admin clicks "Undrop" on an approved drop                       │
│ 12. Enters remarks in modal → confirms                              │
│ 13. Status updated to "Undropped", retrieve_date & remarks set      │
│ 14. 📧 Teacher receives undrop notification via email               │
│                                                                     │
│                   ALTERNATIVE: CANCEL                                │
│                                                                     │
│  4a. Teacher cancels a pending request from "Drop History"          │
│  4b. Record is permanently deleted from the database                │
│  4c. No notification is sent                                        │
└─────────────────────────────────────────────────────────────────────┘
```

---

## ⚙️ Technology Stack

| Component        | Technology                              |
|------------------|-----------------------------------------|
| Backend          | PHP 8.x                                 |
| Database         | MySQL (via PDO)                         |
| Web Server       | Apache (XAMPP)                          |
| Email            | PHPMailer (Gmail SMTP, TLS on port 587) |
| Frontend         | HTML5, CSS3, Vanilla JavaScript         |
| Password Hashing | bcrypt (`password_hash` / `password_verify`) |
| Dependency Mgmt  | Composer                                |
| Timezone         | Asia/Manila (Philippines)               |

---

## 🔒 Security Measures

- **Password hashing** with bcrypt (`PASSWORD_BCRYPT`)
- **Prepared statements** (PDO) for all database queries — prevents SQL injection
- **Session validation** on every protected page (`session_check.php`)
- **Role-based access control** — admin and teacher routes are gated by `$_SESSION['user_role']`
- **Output escaping** with `htmlspecialchars()` — prevents XSS
- **CSRF-aware forms** — POST-only actions with server-side validation
- **Input validation** — length, format, and uniqueness checks on all form data
