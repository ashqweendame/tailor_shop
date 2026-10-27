# Swift Tailoring Website

Swift Tailoring is a PHP and MySQL based web application for managing tailoring and alteration orders. It provides customer management, order creation, measurements, garment details, fabric information, order status tracking, and role based access.

## 1. Requirements

Before setting up Swift Tailoring, install the following:

- XAMPP
- A web browser such as Google Chrome, Microsoft Edge, or Firefox
- The Swift Tailoring project files
- MySQL, included with XAMPP

XAMPP provides the Apache web server and MySQL database server needed to run the website locally.

## 2. Download the Project

1. Download or obtain the Swift Tailoring project folder as `tailor_shop`.
2. Extract the ZIP file if the project was downloaded as a `.zip`.
3. Locate the extracted project folder.

Your project should contain files and folders similar to:

```text
tailor_shop/
├── admin/
├── customer/
├── database/
├── login/
├── service/
└── tailor/
```

You need to make sure to keep the file structure. It will not work otherwise because of certain include paths used in the files.

## 3. Install XAMPP

1. Download and install XAMPP.
2. Open the XAMPP Control Panel.
3. Start Apache.
4. Start MySQL.

Both services should show as running before you continue.

## 4. Move the Project to XAMPP

1. Open your XAMPP installation folder.
2. Open the `htdocs` folder.

Usually:

```text
C:\xampp\htdocs
```

3. Copy the entire Swift Tailoring project folder into `htdocs`.

For example:

```text
C:\xampp\htdocs\tailor_shop
```

Do not copy only individual PHP files. Keep the project's folder structure intact.

## 5. Create the Database

Swift Tailoring uses a MySQL database named:

```text
tailor_swift_db
```

### Using phpMyAdmin

1. Open your browser.
2. Go to:

```text
http://localhost/phpmyadmin
```

3. Click **New** on the left side.
4. Create a database named:

```text
tailor_swift_db
```

5. Open the newly created database.
6. Select the **Import** tab.
7. Choose the project's SQL database file.
8. Click **Import** or **Go**.
9. Wait until phpMyAdmin confirms that the SQL file has been imported successfully.

If the project does not include an SQL file, create the database first and then run the project's SQL schema manually.

## 6. Check the Database Connection

Find the database connection file in the project. It is commonly located inside the `database` folder.

The connection should point to your local MySQL server.

A typical local XAMPP configuration is:

```php
$host = "localhost";
$user = "root";
$password = "";
$database = "tailor_swift_db";
```

Make sure the database name matches:

```text
tailor_swift_db
```

If you configured a MySQL password during XAMPP setup, enter that password instead of leaving `$password` empty.

## 7. Start the Website

Make sure Apache and MySQL are running in XAMPP.

Then open your browser and enter:

```text
http://localhost/tailor_shop/
```

Replace `tailor_shop` with the exact name of your project folder inside `htdocs`.

For example, if your folder is named:

```text
tailor_swift
```

open:

```text
http://localhost/tailor_swift/
```

## 8. Open the Login Page

If the website does not automatically open the login page, navigate to the login folder.

For example:

```text
http://localhost/tailor_shop/login/login.php
```

Use the account credentials provided with the project.

**SERVICE ACCOUNT**

```text
username: grace_service
password: serviceGRACE
```

**ADMIN ACCOUNT**

```text
ADMIN ACCOUNT
username: patrick_admin
password: adminPATRICK
```

**TAILOR ACCOUNT**

```text
TAILOR ACCOUNT
username: cho_tailor
password: tailorCHO
```

If no account exists, create an employee account directly through the database or use the project's account creation process.

## 9. Test the Website

After logging in, check the main features.

### Service Staff

Test:

- Customer information
- Creating a new order
- Tailoring orders
- Alteration orders
- Fabric selection
- Garment components
- Customer measurements
- Assigned tailor
- Estimated pickup date
- Order price
- Order code generation
- Order tracking

### Tailor

Test:

- Viewing assigned orders
- Viewing customer information
- Viewing measurements
- Viewing garment details
- Updating order status

### Administrator

Test:

- Employee accounts
- Customer orders

## 10. Common Problems

### Apache will not start

Check whether another application is already using port 80 or another port configured for Apache.

Close the application using the port, or configure Apache to use another available port.

### MySQL will not start

Check whether another MySQL or MariaDB service is already running.

Stop the conflicting service, then start MySQL through XAMPP again.

### Database connection error

Check the database connection file and confirm:

```text
Host: localhost
Username: root
Password: your MySQL password
Database: tailor_swift_db
```

Also make sure MySQL is running in XAMPP.

### Page not found

Make sure the project folder is inside:

```text
C:\xampp\htdocs
```

Then verify the URL matches the project folder name.

Example:

```text
C:\xampp\htdocs\tailor_shop
```

should be opened through:

```text
http://localhost/tailor_shop/
```

### CSS is not loading

Check that the CSS file is inside the correct project folder and that the HTML or PHP file points to the correct stylesheet path.

For example:

```html
<link rel="stylesheet" href="service.css" />
```

If the CSS file is inside another folder, update the path accordingly.

### Images are not showing

Check that image files are included in the project and that their paths match the folder structure.

External images also require an active internet connection.

## 11. Recommended Project Location

For the easiest setup, use:

```text
C:\xampp\htdocs\tailor_shop
```

Then access the website using:

```text
http://localhost/tailor_shop/
```

## 12. Stopping the Website

When you are finished using Swift Tailoring:

1. Open XAMPP Control Panel.
2. Stop Apache.
3. Stop MySQL.

You do not need to delete the project or database. Your data will remain stored in MySQL.

## 13. Quick Setup Summary

```text
1. Install XAMPP
        ↓
2. Start Apache and MySQL
        ↓
3. Download Swift Tailoring
        ↓
4. Extract the project
        ↓
5. Move the project to C:\xampp\htdocs
        ↓
6. Open phpMyAdmin
        ↓
7. Create tailor_swift_db
        ↓
8. Import the SQL database
        ↓
9. Check database.php
        ↓
10. Open http://localhost/tailor_shop/
        ↓
11. Log in
        ↓
12. Test the system
```

## 14. Project Information

**System:** Swift Tailoring  
**Database:** `tailor_swift_db`  
**Database Server:** MySQL  
**Web Server:** Apache  
**Development Environment:** XAMPP  
**Backend:** PHP  
**Frontend:** HTML, CSS, JavaScript  
**Database Management:** phpMyAdmin
