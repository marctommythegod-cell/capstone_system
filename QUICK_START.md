# 🚀 QUICK START GUIDE

## 5-Minute Setup

### 1. Create Database (1 minute)

1. Open **phpMyAdmin**: http://localhost/phpmyadmin
2. Click on "SQL" tab at top
3. Copy all content from `database_setup.sql` file
4. Paste into phpMyAdmin SQL editor
5. Click **Go** button

✅ Database is ready!

---

### 2. Copy Files (1 minute)

- Copy the entire `SYSTEM` folder to:
  - **XAMPP**: `C:/xampp/htdocs/SYSTEM/`
  - **WAMP**: `C:/wamp/www/SYSTEM/`

✅ Files are ready!

---

### 3. Access System (1 minute)

1. Open browser
2. Go to: **http://localhost/SYSTEM/**
3. You should see login page

✅ System is running!

---

## Login & Test

### Admin Login
- Email: `admin@test.com`
- Password: `123456`

### Teacher Login
- Email: `teacher@test.com`
- Password: `123456`

---

## Quick Test Workflow

### As Teacher:
1. Login with teacher credentials
2. Go to "Teacher Dashboard"
3. Select a student (e.g., "2021-0001 - Maria Santos")
4. Select a subject (e.g., "CS101")
5. Enter remarks (e.g., "Absent due to illness")
6. Click "Drop Class Card"
7. Check success message

### As Admin:
1. Logout (click Logout in sidebar)
2. Login with admin credentials
3. Go to "Admin Dashboard"
4. View the drop you just created in "Recent Class Card Drops"
5. Try other features:
   - Click "Dropped Cards" to see all drops with filters
   - Click "Manage Students" to add/remove students
   - Click "Manage Teachers" to add/remove teachers
   - Click "Drop History" to see per-student history

---

## Troubleshooting

### Page shows blank or error:
```
Check if:
1. MySQL is running
2. Files are in correct folder
3. PHP is enabled
4. No file permission issues
```

### Can't login:
```
1. Check credentials are correct
2. Try: admin@test.com / 123456
3. Clear browser cache
4. Check database has users
```

### Email not sending:
```
This is normal on local development.
Email function works on live servers with mail configured.
Check admin dashboard still shows the drop was recorded.
```

---

## File Locations

```
📁 SYSTEM/
├── 📄 index.php                          (Login Page)
├── 📄 database_setup.sql                 (Database Setup)
├── 📁 config/db.php                      (Database Connection)
├── 📁 admin/
│   ├── dashboard.php                     (Admin Main Page)
│   ├── dropped_cards.php                 (View All Drops)
│   ├── students.php                      (Manage Students)
│   ├── teachers.php                      (Manage Teachers)
│   └── drop_history.php                  (Student History)
├── 📁 teacher/
│   └── dashboard.php                     (Teacher Main Page)
├── 📁 includes/
│   ├── api.php                           (Drop Handler)
│   ├── functions.php                     (Utilities)
│   ├── session_check.php                 (Auth Check)
│   └── logout.php                        (Logout)
├── 📁 email/
│   └── EmailNotifier.php                 (Email Handler)
├── 📁 css/
│   └── style.css                         (Styling)
└── 📁 js/
    └── functions.js                      (JavaScript)
```

---

## Key Features

| Feature | Where | How |
|---------|-------|-----|
| Drop Class Card | Teacher Dashboard | Select student + subject + remarks → Click Drop |
| View Drops | Admin Dashboard | All drops shown in table |
| Filter Drops | Dropped Cards Page | Use search, month, teacher filters |
| Manage Students | Admin → Manage Students | Add/Delete students |
| Manage Teachers | Admin → Manage Teachers | Add/Delete teachers |
| View History | Admin → Drop History | Select student → See all drops |

---

## Next Steps

1. ✅ Run database setup from SQL file
2. ✅ Copy SYSTEM folder to web server
3. ✅ Test login with provided credentials
4. ✅ Test teacher drop flow
5. ✅ Test admin features
6. ✅ Add your own teachers and students
7. ✅ Customize colors/settings as needed

---

## Support

- Check README.md for detailed documentation
- Check browser console for JavaScript errors (F12)
- Check PHP error logs for backend errors
- Verify database connection in config/db.php

**System Ready! 🎉**
