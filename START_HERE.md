# 🎯 START HERE - PhilCST Class Card Dropping System

**Welcome!** This is your brand new professional class card dropping system.

## 📋 Quick Navigation

### 📚 Documentation
- **[QUICK_START.md](QUICK_START.md)** ← **START HERE** (5-minute setup)
- **[README.md](README.md)** - Full system documentation
- **[SYSTEM_COMPLETE.md](SYSTEM_COMPLETE.md)** - Implementation details

### 🗂️ System Files
- **[database_setup.sql](database_setup.sql)** - Database initialization
- **[index.php](index.php)** - Login page
- **[config/db.php](config/db.php)** - Database configuration
- **[admin/](admin/)** - Admin panel files
- **[teacher/](teacher/)** - Teacher panel files
- **[includes/](includes/)** - Core system files
- **[email/](email/)** - Email notification system
- **[css/](css/)** - Styling
- **[js/](js/)** - JavaScript

---

## 🚀 How to Get Started (3 Steps)

### **Step 1: Database Setup** ⏱️ 1 minute
1. Open **phpMyAdmin** → http://localhost/phpmyadmin
2. Click **"SQL"** tab
3. **Copy** all content from **database_setup.sql**
4. **Paste** into SQL editor
5. Click **"Go"**

✅ **Done!** Your database is ready.

### **Step 2: Copy Files** ⏱️ 1 minute
Copy the **SYSTEM** folder to:
- **XAMPP**: `C:/xampp/htdocs/SYSTEM/`
- **WAMP**: `C:/wamp/www/SYSTEM/`

✅ **Done!** Files are in place.

### **Step 3: Access System** ⏱️ 1 minute
1. Open browser
2. Go to: **http://localhost/SYSTEM/**
3. You'll see the **login page**

✅ **Done!** System is running!

---

## 👤 Test Login Credentials

### **Admin (Guidance Head)**
```
Email:    admin@test.com
Password: 123456
```

### **Teacher**
```
Email:    teacher@test.com
Password: 123456
```

---

## ✨ What's Included

| Feature | Location | Description |
|---------|----------|-------------|
| **Login System** | index.php | Secure authentication for both roles |
| **Teacher Dashboard** | teacher/dashboard.php | Drop class cards + history |
| **Admin Dashboard** | admin/dashboard.php | System statistics + overview |
| **Dropped Cards** | admin/dropped_cards.php | View/search/filter all drops |
| **Manage Students** | admin/students.php | Add/delete students |
| **Manage Teachers** | admin/teachers.php | Add/delete teachers |
| **Drop History** | admin/drop_history.php | Per-student drop tracking |
| **Email Notifications** | email/EmailNotifier.php | Auto-email to admin |
| **Responsive UI** | css/style.css | Works on all devices |

---

## 🎯 Key Capabilities

✅ **Teachers** can:
- Login securely
- Drop student class cards
- Add remarks/notes
- View drop history
- Auto-notification sent to admin

✅ **Admin** can:
- Monitor all dropped cards
- Search by student name/ID
- Filter by month and teacher
- Manage student records
- Manage teacher accounts
- View complete drop history
- Receive email notifications

---

## 🔒 Security

The system includes:
- ✅ Password hashing with bcrypt
- ✅ SQL injection prevention
- ✅ Session-based authentication
- ✅ Role-based access control
- ✅ Input validation & sanitization

---

## 📊 Database

Ready-to-use tables:
- **users** - Teachers & Admin accounts
- **students** - Student records
- **subjects** - Subject catalog
- **class_card_drops** - Drop history with tracking

Sample data included for testing!

---

## 🎨 Design

- Modern, clean interface
- Blue & white color scheme
- Fully responsive (mobile, tablet, desktop)
- Easy-to-use sidebar navigation
- Professional look and feel

---

## 💬 Quick Test Workflow

### Try as Teacher:
1. Login with teacher credentials
2. Select a student (Maria Santos, Jose Garcia, etc.)
3. Select a subject (CS101, CS102, etc.)
4. Enter a remark (e.g., "Absent due to illness")
5. Click "Drop Class Card"
6. See confirmation message

### Try as Admin:
1. Logout and login as admin
2. Go to "Dropped Cards" - see your drop!
3. Try "Manage Students" - add a new student
4. Try "Manage Teachers" - register new teacher
5. Try "Drop History" - view drops per student

---

## ⚠️ If You Have Issues

| Problem | Solution |
|---------|----------|
| **Database Error** | Check MySQL is running, verify database_setup.sql was executed |
| **Login fails** | Check credentials, clear browser cache, verify data in users table |
| **Page won't load** | Verify files in correct folder, check PHP is enabled |
| **Email not sending** | Normal on local development, works on live servers |

---

## 📝 Full Documentation

For detailed information, see:
- **[QUICK_START.md](QUICK_START.md)** - Extended setup guide
- **[README.md](README.md)** - Complete documentation
- Code comments in all PHP files

---

## 🎉 Ready to Go!

Your system is **fully functional** and ready to use!

### Next Actions:
1. ✅ Follow the 3-step setup above
2. ✅ Test login with provided credentials
3. ✅ Explore teacher dashboard
4. ✅ Try dropping a class card
5. ✅ Check admin dashboard
6. ✅ Try admin features
7. ✅ Customize as needed

---

## 📞 Support Resources

- **README.md** - Full system documentation
- **QUICK_START.md** - Setup guide
- **Code comments** - Throughout all PHP files
- **Database schema** - In database_setup.sql

---

**PhilCST Class Card Dropping System**
**Version**: 1.0
**Status**: ✅ Ready to Use
**Last Updated**: February 15, 2026

---

**[→ Read QUICK_START.md for 5-minute setup](QUICK_START.md)**
