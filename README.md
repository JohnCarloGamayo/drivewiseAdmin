# DriveWise Admin

This is the admin dashboard for the DriveWise system.

---

## 🔧 Installation Guide

### 1. Download the Project
- Click the green `Code` button on this page and select **Download ZIP**.
- Extract the ZIP file.

### 2. Move to htdocs
- Copy the extracted `drivewiseAdmin` folder into your `htdocs` directory.
- Example path: `C:\xampp\htdocs\drivewiseAdmin`

### 3. Set Up the Database
- Open [phpMyAdmin](http://localhost/phpmyadmin).
- Create a new database named: `drivewise_admin`
- Locate and open the `drivewise_admin.sql` file inside the project folder.
- Copy all the SQL code and paste it into the **SQL tab** of your newly created database, then click **Go** to import all tables and default data.

### 4. Run the System
- Open your browser and navigate to:



---

## 🔐 Admin Login

- **Email**: You must change this to your own email address (see below)
- **Default Password**: `@Admin123`

### 📧 How to Change the Admin Email
1. After importing the database, go to phpMyAdmin.
2. Open the `drivewise_admin` database.
3. Click the `admin` table.
4. Click **Edit** on the existing admin account.
5. Replace the `email` field with your own valid email address.
6. Save the changes.

> ⚠️ A valid email is required to receive OTP verification codes when changing sensitive settings.

---

## 📌 Additional Notes
- Make sure **XAMPP** is running (`Apache` and `MySQL` must be started).
- Tested with **PHP 8.x**
- Email/OTP features require a properly configured mail setup (like PHPMailer + SMTP).
- Customize the system based on your school's or organization's needs.

---

## 📫 Developer

Developed by **John Carlo Gamayo**  
GitHub: [@JohnCarloGamayo](https://github.com/JohnCarloGamayo)
