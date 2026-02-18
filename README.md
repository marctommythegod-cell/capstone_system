# PhilCST Class Card Dropping System

A professional web-based automated class card dropping system with email notification for the Guidance Office of PhilCST.

## 🎯 Features

✅ **Login System** - Secure session-based authentication for Teachers and Admin
✅ **Teacher Dashboard** - Drop student class cards with remarks
✅ **Admin Dashboard** - Monitor all dropped cards with statistics
✅ **Student Management** - Register and manage students
✅ **Teacher Management** - Register and manage teachers
✅ **Drop History** - View per-student drop history and monthly statistics
✅ **Email Notifications** - Automatic email to admin when class card is dropped
✅ **Search & Filter** - Filter by month, teacher, and search by student name
✅ **User-Friendly UI** - Clean, responsive design with modern interface
✅ **Role-Based Access** - Different dashboards for Teachers and Admin

## 🔧 System Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- XAMPP or WAMP (with Apache and MySQL)
- Web browser (Chrome, Firefox, Safari, Edge)

## 📁 Project Structure

```
SYSTEM/
├── index.php                    # Login page
├── database_setup.sql           # Database initialization script
├── config/
│   └── db.php                  # Database connection
├── includes/
│   ├── api.php                 # API endpoints
│   ├── functions.php           # Utility functions
│   ├── session_check.php       # Session validation
│   └── logout.php              # Logout handler
├── admin/
│   ├── dashboard.php           # Admin main dashboard
│   ├── dropped_cards.php       # View all dropped cards
│   ├── students.php            # Manage students
│   ├── teachers.php            # Manage teachers
│   └── drop_history.php        # Student drop history
├── teacher/
│   └── dashboard.php           # Teacher dashboard with drop form
├── email/
│   └── EmailNotifier.php       # Email notification handler
├── css/
│   └── style.css               # Main stylesheet
├── js/
│   └── functions.js            # JavaScript functionality
└── README.md                   # This file
```

## 🚀 Installation & Setup

### Step 1: Set Up Database

1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Copy all SQL from `database_setup.sql`
3. Paste into the SQL query box and execute
4. The database `philcst_class_drops` will be created with all tables and sample data

### Step 2: Configure Database Connection

Edit `config/db.php` and update if needed:
```php
$host = 'localhost';
$db = 'philcst_class_drops';
$user = 'root';
$pass = '';
```

### Step 3: Place Files in Web Server

Copy the entire `SYSTEM` folder to:
- **XAMPP**: `C:/xampp/htdocs/SYSTEM/`
- **WAMP**: `C:/wamp/www/SYSTEM/`

### Step 4: Access the System

Open your browser and go to:
```
http://localhost/SYSTEM/
```

## 👤 Default Login Credentials

### Admin (Guidance Head)
- **Email**: admin@test.com
- **Password**: 123456

### Teacher
- **Email**: teacher@test.com
- **Password**: 123456

## 📖 User Guide

### For Teachers

1. **Login** - Use your teacher credentials
2. **Dashboard** - View the main teacher dashboard
3. **Drop Class Card**:
   - Select a student from the dropdown
   - Automatically shows course and year
   - Select subject
   - Enter remarks (reason for dropping)
   - Click "Drop Class Card" button
4. **View History** - See your previous drops in the table below the form
5. **Logout** - Click Logout in sidebar

### For Admin (Guidance Head)

1. **Login** - Use admin credentials
2. **Dashboard** - View system statistics and recent drops
3. **Dropped Cards** - View all dropped cards with filters
   - Search by student name or ID
   - Filter by month
   - Filter by teacher
4. **Manage Students** - Add or delete students
5. **Manage Teachers** - Register new teachers or remove existing ones
6. **Drop History** - View complete drop history for each student
7. **Logout** - Click Logout in sidebar

## 📧 Email Notification

When a teacher drops a class card:
- An automated email is sent to the admin email address
- Email includes:
  - Student ID and Name
  - Subject details
  - Teacher name
  - Remarks
  - Date and time of drop

**Note**: Email functionality uses PHP's `mail()` function. For production, configure SMTP settings or use PHPMailer library.

## 🔒 Security Features

- ✅ Password hashing with bcrypt
- ✅ SQL injection prevention with prepared statements
- ✅ Session-based authentication
- ✅ Role-based access control
- ✅ Input validation and sanitization
- ✅ CSRF protection ready

## 🎨 Customization

### Change Colors

Edit `css/style.css` and modify CSS variables:
```css
:root {
    --primary-color: #0066cc;
    --secondary-color: #004a99;
    --success-color: #28a745;
    --error-color: #dc3545;
}
```

### Change Admin Email

Edit `email/EmailNotifier.php`:
```php
private $admin_email = 'your-email@philcst.edu.ph';
private $from_email = 'noreply@philcst.edu.ph';
```

### Add More Users

Use the admin panel to:
1. Go to "Manage Teachers" and add new teacher accounts
2. Go to "Manage Students" and add new student records

## 🐛 Troubleshooting

### Issue: Database Connection Error
- ✅ Check MySQL is running
- ✅ Verify database name in `config/db.php`
- ✅ Ensure database was created from `database_setup.sql`

### Issue: Login Not Working
- ✅ Check if users table has sample data
- ✅ Verify password hash in database
- ✅ Clear browser cookies/cache

### Issue: Email Not Sending
- ✅ Check mail server configuration in php.ini
- ✅ Verify admin email address is correct
- ✅ Check server error logs

### Issue: Page 404 Not Found
- ✅ Verify files are in correct directory
- ✅ Check file permissions
- ✅ Ensure Apache mod_rewrite is enabled

## 📝 Database Schema

### Users Table
- id, name, email, password, role, created_at, updated_at

### Students Table
- id, student_id, name, course, year, created_at, updated_at

### Subjects Table
- id, subject_no, subject_name, created_at

### Class Card Drops Table
- id, teacher_id, student_id, subject_no, subject_name, remarks, status, drop_date, drop_month, drop_year, created_at

## 🔄 Database Backup

To backup your database:
1. Go to phpMyAdmin
2. Select `philcst_class_drops` database
3. Click Export
4. Choose SQL format
5. Click Go

To restore:
1. Create new database in phpMyAdmin
2. Import the SQL file
3. Update `config/db.php` if needed

## 📞 Support

For issues or questions, check:
- Database connection settings in `config/db.php`
- File permissions on the server
- PHP error logs
- Browser console for JavaScript errors

## 📄 License

This system is developed for PhilCST Guidance Office.

## ✨ Version

**PhilCST Class Card Dropping System v1.0**
Last Updated: February 15, 2026
