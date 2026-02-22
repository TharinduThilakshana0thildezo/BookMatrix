# 📚 BookMatrix — Library Management System

BookMatrix is a full‑stack web‑based Library Management System designed to manage books, users, issuing/returning workflows, and administrative operations efficiently. It provides separate interfaces for administrators and users, enabling seamless digital library operations.

---

## 🚀 Features

### 👤 User Module

* User registration and authentication
* Secure login/logout system
* Browse available books
* Search books by title, author, or category
* Issue book requests
* View issued books and status
* User profile management

### 🛠️ Admin Module

* Admin authentication dashboard
* Add / update / delete books
* Manage users
* Approve or reject book issue requests
* Track issued and returned books
* Library statistics dashboard

### 📖 Book Management

* Book catalog system
* Category organization
* Book availability tracking
* Upload book images

### 🔐 Security

* Session‑based authentication
* Form validation
* Protected admin routes

---

## 🧰 Tech Stack

**Frontend**

* HTML5
* CSS3
* JavaScript

**Backend**

* PHP

**Database**

* MySQL

**Server**

* Apache (XAMPP / WAMP / LAMP recommended)

---

## 📦 Project Structure

```
BookMatrix/
│
├── admin/                 # Admin panel files
├── api/                   # Backend API endpoints
├── assets/                # CSS, JS, images
├── upload/                # Uploaded book images
├── vendor/                # Composer dependencies
├── partials/              # Reusable UI components
│
├── index.php              # Homepage
├── dashboard.html         # User dashboard
├── books.html             # Books listing page
├── issue.html             # Book issue page
├── profile.php            # User profile
│
├── admin-login.html       # Admin login
├── admin-dashboard.html   # Admin dashboard
│
├── database_connection.php# DB connection file
├── function.php           # Core helper functions
└── composer.json          # PHP dependencies
```

---

## ⚙️ Installation Guide

### 1️⃣ Clone the Repository

```bash
git clone https://github.com/TharinduThilakshana0thildezo/BookMatrix.git
cd BookMatrix
```

### 2️⃣ Setup Local Server

Install one of the following:

* XAMPP
* WAMP
* LAMP

Move the project folder to:

```
XAMPP: htdocs/
WAMP: www/
```

---

### 3️⃣ Configure Database

1. Open **phpMyAdmin**
2. Create a new database:

```
bookmatrix_db
```

3. Import the SQL file (if provided) or create required tables manually.

---

### 4️⃣ Update Database Connection

Edit:

```
database_connection.php
```

Set your credentials:

```php
$host = "localhost";
$user = "root";
$password = "";
$database = "library";
```

---

### 5️⃣ Install Dependencies (Optional)

If Composer is used:

```bash
composer install
```

---

## ▶️ Running the Project

Start Apache and MySQL from XAMPP/WAMP.

Open browser:

```
http://localhost/BookMatrix/index.html
```

---

## 🔑 Default Access

### Admin Login

```
/admin-login.html
```

### User Login

```
/login.html
```

---

## 🧪 Testing Checklist

* User registration/login
* Admin login
* Add book
* Issue book
* Return book
* Search functionality

---

## 🛡️ Security Notes

* Use strong admin credentials
* Restrict direct access to admin routes
* Sanitize user inputs

---


## 🤝 Contributing

Contributions are welcome.

1. Fork the repository
2. Create a new branch
3. Commit changes
4. Submit a pull request

---

## 👨‍💻 Author

**Tharindu Thilakshana**
Software Engineer

---

## ⭐ Support

If you like this project, give it a ⭐ on GitHub.
