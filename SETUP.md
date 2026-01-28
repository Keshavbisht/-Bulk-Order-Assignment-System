# Setup and Running Guide

## Quick Start

### Step 1: Database Setup

1. **Create the database:**
   ```bash
   mysql -u root -p
   ```
   Then in MySQL:
   ```sql
   CREATE DATABASE order_assignment_db;
   EXIT;
   ```

2. **Import the schema:**
   ```bash
   mysql -u root -p order_assignment_db < database/schema.sql
   ```

3. **Update database credentials:**
   Edit `config/database.php` and update:
   ```php
   private $host = 'localhost';
   private $db_name = 'order_assignment_db';
   private $username = 'root';  // Change if needed
   private $password = '';      // Change if needed
   ```

### Step 2: Web Server Setup

#### Option A: Using PHP Built-in Server (Quick Testing)

```bash
cd "/Volumes/2TB SSD/Development/lusong360 Assessment"
php -S localhost:8000
```

Then access APIs at: `http://localhost:8000/api/...`

#### Option B: Using Apache

1. **Enable mod_rewrite:**
   ```bash
   sudo a2enmod rewrite
   ```

2. **Configure Virtual Host** (or use existing):
   ```apache
   <VirtualHost *:80>
       ServerName order-assignment.local
       DocumentRoot "/Volumes/2TB SSD/Development/lusong360 Assessment"
       
       <Directory "/Volumes/2TB SSD/Development/lusong360 Assessment">
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```

3. **Restart Apache:**
   ```bash
   sudo systemctl restart apache2  # Linux
   # or
   sudo apachectl restart           # macOS
   ```

#### Option C: Using Nginx

Add to your Nginx config:
```nginx
server {
    listen 80;
    server_name order-assignment.local;
    root "/Volumes/2TB SSD/Development/lusong360 Assessment";
    index index.php;

    location / {
        try_files $uri $uri/ /api/index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

### Step 3: Insert Sample Data (Optional)

Run this SQL to create test data:

```sql
USE order_assignment_db;

-- Insert sample couriers
INSERT INTO couriers (name, serviceable_locations, daily_capacity, is_active) VALUES
('John Doe', '["New York", "Boston"]', 50, 1),
('Jane Smith', '["New York", "Philadelphia"]', 75, 1),
('Bob Johnson', '["Boston", "Cambridge"]', 60, 1),
('Alice Williams', '["New York", "Boston", "Philadelphia"]', 100, 1);

-- Insert sample orders
INSERT INTO orders (order_date, delivery_location, order_value, status) VALUES
(NOW(), 'New York', 150.00, 'UNASSIGNED'),
(NOW(), 'Boston', 200.00, 'UNASSIGNED'),
(NOW(), 'New York', 175.00, 'UNASSIGNED'),
(NOW(), 'Philadelphia', 120.00, 'UNASSIGNED'),
(NOW(), 'New York', 300.00, 'UNASSIGNED'),
(NOW(), 'Boston', 250.00, 'UNASSIGNED'),
(NOW(), 'Cambridge', 180.00, 'UNASSIGNED'),
(NOW(), 'New York', 220.00, 'UNASSIGNED');
```

Or run from command line:
```bash
mysql -u root -p order_assignment_db < database/sample_data.sql
```

### Step 4: Test the APIs

#### Test 1: Get Unassigned Orders
```bash
curl http://localhost:8000/api/orders/unassigned?page=1&limit=10
```

#### Test 2: Get Available Couriers
```bash
curl "http://localhost:8000/api/couriers/available?location=New York"
```

#### Test 3: Bulk Assign Orders
```bash
curl -X POST http://localhost:8000/api/assignments/bulk \
  -H "Content-Type: application/json" \
  -d '{"order_ids": [1,2,3,4,5], "batch_size": 100}'
```

#### Test 4: View Assignment Results
```bash
curl http://localhost:8000/api/assignments?page=1&limit=10
```

#### Test 5: Retry Failed Assignments
```bash
curl -X POST http://localhost:8000/api/assignments/retry
```

## Testing with Browser

If using PHP built-in server, you can also test in browser:

1. **Unassigned Orders:**
   ```
   http://localhost:8000/api/orders/unassigned
   ```

2. **Available Couriers:**
   ```
   http://localhost:8000/api/couriers/available?location=New York
   ```

3. **Assignments:**
   ```
   http://localhost:8000/api/assignments
   ```

## Common Issues

### Issue: "Database connection failed"
**Solution:** Check database credentials in `config/database.php` and ensure MySQL is running.

### Issue: "404 Not Found" on API calls
**Solution:** 
- For PHP built-in server: Make sure you're using `/api/` prefix
- For Apache: Check `.htaccess` is enabled and mod_rewrite is active
- For Nginx: Check URL rewriting configuration

### Issue: "Class not found" errors
**Solution:** Check file paths in `require_once` statements match your directory structure.

### Issue: JSON errors in response
**Solution:** Check PHP error logs. Common causes:
- Database connection issues
- SQL syntax errors
- Missing tables (run schema.sql)

## Production Deployment

For production:

1. **Update database credentials** in `config/database.php`
2. **Enable error logging** (disable error display):
   ```php
   ini_set('display_errors', 0);
   error_reporting(E_ALL);
   ini_set('log_errors', 1);
   ```
3. **Use HTTPS** with proper SSL certificates
4. **Implement authentication** (JWT/OAuth2)
5. **Set up monitoring** and logging
6. **Use connection pooling** for database
7. **Enable caching** (Redis/Memcached)
8. **Set up queue system** for async processing

## Performance Testing

Test with larger datasets:

```bash
# Insert 1000 test orders
mysql -u root -p order_assignment_db -e "
INSERT INTO orders (order_date, delivery_location, order_value, status)
SELECT NOW(), 
       CASE (FLOOR(RAND() * 3))
           WHEN 0 THEN 'New York'
           WHEN 1 THEN 'Boston'
           ELSE 'Philadelphia'
       END,
       ROUND(100 + RAND() * 400, 2),
       'UNASSIGNED'
FROM (SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) t1
CROSS JOIN (SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) t2
CROSS JOIN (SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) t3
CROSS JOIN (SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) t4;
"

# Then test bulk assignment
curl -X POST http://localhost:8000/api/assignments/bulk \
  -H "Content-Type: application/json" \
  -d '{"batch_size": 500}'
```
