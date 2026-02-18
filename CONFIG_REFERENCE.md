# System Configuration Reference

## Database Configuration
- **Host**: localhost
- **Database Name**: philcst_class_drops
- **Username**: root
- **Password**: (blank)
- **Port**: 3306

## Email Configuration
- **Admin Email**: admin@test.com
- **From Email**: noreply@philcst.edu.ph
- **From Name**: PhilCST Class Card System
- **Method**: PHP mail() function

## Default Users
### Admin Account
- **ID**: 1
- **Name**: Guidance Head
- **Email**: admin@test.com
- **Password Hash**: $2y$10$slYQmyNdGzin7olVnyi1OPST9/PgBkb33Jsrmjkr8UbUVV4t8qK9m
- **Plain Password**: 123456
- **Role**: admin

### Teacher Account
- **ID**: 2
- **Name**: Juan Dela Cruz
- **Email**: teacher@test.com
- **Password Hash**: $2y$10$slYQmyNdGzin7olVnyi1OPST9/PgBkb33Jsrmjkr8UbUVV4t8qK9m
- **Plain Password**: 123456
- **Role**: teacher

## Sample Students
1. **2021-0001** - Maria Santos (BS Computer Science, Year 1)
2. **2021-0002** - Jose Garcia (BS Computer Science, Year 1)
3. **2021-0003** - Ana Lopez (BS Computer Science, Year 2)
4. **2021-0004** - Carlos Mendoza (BS Information Technology, Year 1)
5. **2021-0005** - Rosa Fernandez (BS Information Technology, Year 2)

## Sample Subjects
1. **CS101** - Introduction to Programming
2. **CS102** - Data Structures
3. **CS201** - Web Development
4. **IT101** - Network Basics
5. **IT102** - Database Design

## Directory Structure
```
SYSTEM/
├── index.php                          # Login page
├── database_setup.sql                 # Database script
├── config/
│   └── db.php                        # Database connection
├── includes/
│   ├── api.php                       # Drop handler
│   ├── functions.php                 # Utilities
│   ├── session_check.php             # Auth check
│   └── logout.php                    # Logout
├── admin/
│   ├── dashboard.php                 # Main
│   ├── dropped_cards.php             # View drops
│   ├── students.php                  # Manage students
│   ├── teachers.php                  # Manage teachers
│   └── drop_history.php              # History
├── teacher/
│   └── dashboard.php                 # Teacher main
├── email/
│   └── EmailNotifier.php             # Email handler
├── css/
│   └── style.css                     # Styling
├── js/
│   └── functions.js                  # JavaScript
├── START_HERE.md                     # Start guide
├── QUICK_START.md                    # Quick setup
├── README.md                         # Documentation
└── SYSTEM_COMPLETE.md                # Implementation

```

## URLs

### Login
- http://localhost/SYSTEM/

### Admin Pages
- http://localhost/SYSTEM/admin/dashboard.php
- http://localhost/SYSTEM/admin/dropped_cards.php
- http://localhost/SYSTEM/admin/students.php
- http://localhost/SYSTEM/admin/teachers.php
- http://localhost/SYSTEM/admin/drop_history.php

### Teacher Pages
- http://localhost/SYSTEM/teacher/dashboard.php

### API
- http://localhost/SYSTEM/includes/api.php?action=drop_class_card

### Logout
- http://localhost/SYSTEM/includes/logout.php

## Session Variables
- `$_SESSION['user_id']` - User ID
- `$_SESSION['user_role']` - 'teacher' or 'admin'

## Color Scheme
- **Primary**: #0066cc (Blue)
- **Secondary**: #004a99 (Dark Blue)
- **Success**: #28a745 (Green)
- **Error**: #dc3545 (Red)
- **Light Gray**: #f5f5f5
- **Border**: #ddd

## Database Tables

### users
- id (PK)
- name (VARCHAR 100)
- email (VARCHAR 100, UNIQUE)
- password (VARCHAR 255)
- role (ENUM: teacher, admin)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)

### students
- id (PK)
- student_id (VARCHAR 20, UNIQUE)
- name (VARCHAR 100)
- course (VARCHAR 100)
- year (INT)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)

### subjects
- id (PK)
- subject_no (VARCHAR 20, UNIQUE)
- subject_name (VARCHAR 150)
- created_at (TIMESTAMP)

### class_card_drops
- id (PK)
- teacher_id (FK → users.id)
- student_id (FK → students.id)
- subject_no (VARCHAR 20)
- subject_name (VARCHAR 150)
- remarks (TEXT)
- status (VARCHAR 50, default: 'Dropped')
- drop_date (DATETIME)
- drop_month (VARCHAR 10)
- drop_year (INT)
- created_at (TIMESTAMP)

## PHP Settings Required
- **MySQL support**: Yes
- **Session support**: Yes
- **Standard PHP functions**: Yes
- **File upload**: No
- **Mail function**: Yes (for email)

## Browser Support
- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers

## File Permissions
- All PHP files: 644
- All directories: 755
- Writable folders: None required (no file uploads)

## Security Headers
- SQL injection prevention: Prepared statements used throughout
- XSS prevention: Input sanitization with htmlspecialchars()
- CSRF: Session-based tokens ready for implementation
- Password security: bcrypt hashing with PASSWORD_BCRYPT

## Performance Optimization
- Database indexes on: teacher_id, student_id, drop_month, student_id (students)
- Efficient queries with JOINs
- Minimal database calls

## Backup Recommendations
- Backup database weekly
- Backup files monthly
- Keep database_setup.sql as restoration point

## Maintenance Tasks
- Monitor error logs regularly
- Clean up old sessions (if custom session storage added)
- Archive old drop records annually
- Update teacher/student lists as needed

---

**Configuration Last Updated**: February 15, 2026
**System Version**: 1.0
**Status**: Production Ready
