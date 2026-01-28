# Step-by-Step Setup Guide

Follow these steps **exactly in order** to run the system.

---

## Step 1: Check Prerequisites

### 1.1 Check if PHP is installed
Open Terminal and run:
```bash
php -v
```

**Expected output:** Should show PHP version (7.4 or higher)
**If error:** Install PHP first

### 1.2 Check if MySQL is installed
```bash
mysql --version
```

**Expected output:** Should show MySQL version
**If error:** Install MySQL first

### 1.3 Check if MySQL is running
```bash
mysql -u root -p
```

**If it asks for password:** Enter your MySQL root password (or press Enter if no password)
**If it connects:** Type `EXIT;` and press Enter
**If error:** Start MySQL service

---

## Step 2: Navigate to Project Directory

Open Terminal and run:
```bash
cd "/Volumes/2TB SSD/Development/lusong360 Assessment"
```

**Verify you're in the right place:**
```bash
ls -la
```

You should see folders: `api`, `config`, `database`, `services`

---

## Step 3: Create Database

### 3.1 Open MySQL
```bash
mysql -u root -p
```
(Enter your MySQL password when prompted, or just press Enter if no password)

### 3.2 Create the database
In MySQL, type:
```sql
CREATE DATABASE order_assignment_db;
```

**Expected output:** `Query OK, 1 row affected`

### 3.3 Verify database was created
```sql
SHOW DATABASES;
```

You should see `order_assignment_db` in the list.

### 3.4 Exit MySQL
```sql
EXIT;
```

---

## Step 4: Import Database Schema

Run this command in Terminal:
```bash
mysql -u root -p order_assignment_db < database/schema.sql
```

(Enter your MySQL password when prompted)

**Expected output:** No errors, command returns to prompt

**Verify it worked:**
```bash
mysql -u root -p -e "USE order_assignment_db; SHOW TABLES;"
```

You should see tables: `orders`, `couriers`, `order_assignments`, etc.

---

## Step 5: Add Sample Data (Optional but Recommended)

```bash
mysql -u root -p order_assignment_db < database/sample_data.sql
```

**Verify data was inserted:**
```bash
mysql -u root -p -e "USE order_assignment_db; SELECT COUNT(*) FROM orders; SELECT COUNT(*) FROM couriers;"
```

You should see counts for orders and couriers.

---

## Step 6: Configure Database Connection

### 6.1 Open the config file
```bash
open config/database.php
```
Or use your text editor to open: `config/database.php`

### 6.2 Update the credentials
Find these lines (around line 8-9):
```php
private $username = 'root';
private $password = '';
```

**Change them to match your MySQL setup:**
- If your MySQL username is different, change `'root'`
- If your MySQL has a password, change `''` to your password (e.g., `'mypassword'`)

**Example:**
```php
private $username = 'root';
private $password = 'mypassword123';
```

**Save the file.**

---

## Step 7: Test Database Connection

Create a test file to verify connection works:

```bash
cat > test_connection.php << 'EOF'
<?php
require_once 'config/database.php';
try {
    $db = new Database();
    $conn = $db->getConnection();
    echo "✅ Database connection successful!\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
}
EOF
```

Run it:
```bash
php test_connection.php
```

**Expected output:** `✅ Database connection successful!`

**If you see an error:**
- Check your username/password in `config/database.php`
- Make sure MySQL is running
- Verify database `order_assignment_db` exists

---

## Step 8: Start the PHP Server

### 8.1 Make sure you're in the project directory
```bash
cd "/Volumes/2TB SSD/Development/lusong360 Assessment"
```

### 8.2 Start the server
```bash
php -S localhost:8000 router.php
```

**Expected output:**
```
PHP 7.4.x Development Server (http://localhost:8000) started
```

**⚠️ IMPORTANT:** Keep this terminal window open! The server is running.

---

## Step 9: Test the API (Open a NEW Terminal Window)

### 9.1 Open a NEW Terminal window
(Don't close the server terminal!)

### 9.2 Test API 1: Get Unassigned Orders
```bash
curl http://localhost:8000/api/orders/unassigned
```

**Expected output:** JSON with orders data

**If you see connection error:**
- Make sure server is still running in the other terminal
- Check the URL is correct: `http://localhost:8000/api/orders/unassigned`

### 9.3 Test API 2: Get Available Couriers
```bash
curl "http://localhost:8000/api/couriers/available?location=New York"
```

**Expected output:** JSON with couriers data

### 9.4 Test API 3: Bulk Assign Orders
```bash
curl -X POST http://localhost:8000/api/assignments/bulk \
  -H "Content-Type: application/json" \
  -d '{"order_ids": [1,2,3], "batch_size": 100}'
```

**Expected output:** JSON with assignment results

### 9.5 Test API 4: View Assignments
```bash
curl http://localhost:8000/api/assignments
```

**Expected output:** JSON with assignment data

---

## Step 10: Test in Browser (Optional)

Open your web browser and go to:
```
http://localhost:8000/api/orders/unassigned
```

You should see JSON data displayed.

---

## Troubleshooting Common Issues

### Issue: "php: command not found"
**Solution:** PHP is not installed or not in PATH
- Install PHP: `brew install php` (macOS) or download from php.net
- Or use full path: `/usr/bin/php -S localhost:8000 router.php`

### Issue: "mysql: command not found"
**Solution:** MySQL is not installed or not in PATH
- Install MySQL or use full path to mysql binary
- Or use MySQL Workbench to run SQL commands

### Issue: "Access denied for user 'root'@'localhost'"
**Solution:** Wrong password in `config/database.php`
- Update the password in `config/database.php`
- Or create a new MySQL user with proper permissions

### Issue: "Database connection failed"
**Solution:** 
1. Check MySQL is running: `mysql -u root -p`
2. Verify database exists: `SHOW DATABASES;`
3. Check credentials in `config/database.php`
4. Run `test_connection.php` to debug

### Issue: "404 Not Found" when accessing API
**Solution:**
1. Make sure server is running (check terminal)
2. Use correct URL: `http://localhost:8000/api/...`
3. Make sure you started server with: `php -S localhost:8000 router.php`

### Issue: Empty response or no data
**Solution:**
1. Check if sample data was inserted: `mysql -u root -p -e "USE order_assignment_db; SELECT * FROM orders;"`
2. If no data, run: `mysql -u root -p order_assignment_db < database/sample_data.sql`

### Issue: "Class not found" error
**Solution:**
1. Check file paths are correct
2. Make sure all files are in the right directories
3. Check PHP error logs

---

## Quick Verification Checklist

Before testing APIs, verify:

- [ ] PHP is installed (`php -v` works)
- [ ] MySQL is installed and running (`mysql -u root -p` works)
- [ ] Database `order_assignment_db` exists
- [ ] Tables are created (run `SHOW TABLES;` in MySQL)
- [ ] Sample data is inserted (check with `SELECT * FROM orders;`)
- [ ] `config/database.php` has correct credentials
- [ ] `test_connection.php` shows success
- [ ] Server is running (`php -S localhost:8000 router.php`)
- [ ] Server terminal shows "started" message

---

## Next Steps After Setup

Once everything is working:

1. **Read the full documentation:** `README.md`
2. **Explore the APIs:** Try different endpoints
3. **Add more test data:** Insert more orders/couriers
4. **Test edge cases:** Try assigning when no couriers available
5. **Monitor logs:** Check for any errors

---

## Getting Help

If you're still stuck:

1. **Check PHP errors:** Look at the server terminal for error messages
2. **Check MySQL errors:** Try connecting directly: `mysql -u root -p`
3. **Verify file structure:** Make sure all files are in correct locations
4. **Test each step:** Go back and verify each step completed successfully

---

## Summary of Commands

Here's a quick reference of all commands:

```bash
# 1. Navigate to project
cd "/Volumes/2TB SSD/Development/lusong360 Assessment"

# 2. Create database
mysql -u root -p -e "CREATE DATABASE order_assignment_db;"

# 3. Import schema
mysql -u root -p order_assignment_db < database/schema.sql

# 4. Import sample data
mysql -u root -p order_assignment_db < database/sample_data.sql

# 5. Test connection
php test_connection.php

# 6. Start server (keep this running!)
php -S localhost:8000 router.php

# 7. In NEW terminal, test APIs
curl http://localhost:8000/api/orders/unassigned
curl "http://localhost:8000/api/couriers/available?location=New York"
curl -X POST http://localhost:8000/api/assignments/bulk -H "Content-Type: application/json" -d '{"order_ids": [1,2,3]}'
curl http://localhost:8000/api/assignments
```

Good luck! 🚀
