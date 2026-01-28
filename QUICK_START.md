# Quick Start Guide

## 🚀 Fastest Way to Run (PHP Built-in Server)

### 1. Setup Database (One-time)

```bash
# Create database
mysql -u root -p -e "CREATE DATABASE order_assignment_db;"

# Import schema
mysql -u root -p order_assignment_db < database/schema.sql

# Add sample data (optional)
mysql -u root -p order_assignment_db < database/sample_data.sql
```

### 2. Update Database Config

Edit `config/database.php`:
```php
private $username = 'root';  // Your MySQL username
private $password = '';       // Your MySQL password
```

### 3. Start Server

```bash
cd "/Volumes/2TB SSD/Development/lusong360 Assessment"
php -S localhost:8000 router.php
```

### 4. Test APIs

Open a new terminal and run:

```bash
# Test 1: Get unassigned orders
curl http://localhost:8000/api/orders/unassigned

# Test 2: Get available couriers
curl "http://localhost:8000/api/couriers/available?location=New York"

# Test 3: Bulk assign orders
curl -X POST http://localhost:8000/api/assignments/bulk \
  -H "Content-Type: application/json" \
  -d '{"order_ids": [1,2,3], "batch_size": 100}'

# Test 4: View assignments
curl http://localhost:8000/api/assignments
```

Or use the test script:
```bash
chmod +x test_api.sh
./test_api.sh
```

## 📋 Step-by-Step Example

### Step 1: Check Unassigned Orders
```bash
curl http://localhost:8000/api/orders/unassigned?page=1&limit=5
```

**Expected Response:**
```json
{
  "success": true,
  "data": [
    {
      "order_id": 1,
      "order_date": "2024-01-15 10:30:00",
      "delivery_location": "New York",
      "order_value": "150.00",
      "status": "UNASSIGNED"
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 5,
    "total": 10,
    "total_pages": 2
  }
}
```

### Step 2: Check Available Couriers
```bash
curl "http://localhost:8000/api/couriers/available?location=New York"
```

**Expected Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "John Doe",
      "daily_capacity": 50,
      "current_assigned_count": 0,
      "available_capacity": 50
    }
  ],
  "count": 1
}
```

### Step 3: Assign Orders
```bash
curl -X POST http://localhost:8000/api/assignments/bulk \
  -H "Content-Type: application/json" \
  -d '{"order_ids": [1,2,3], "batch_size": 100}'
```

**Expected Response:**
```json
{
  "success": true,
  "results": {
    "total_processed": 3,
    "successful": 3,
    "failed": 0,
    "assignments": [
      {
        "assignment_id": 1,
        "order_id": 1,
        "courier_id": 1
      }
    ],
    "errors": []
  }
}
```

### Step 4: View Results
```bash
curl http://localhost:8000/api/assignments
```

## 🔧 Troubleshooting

### "Database connection failed"
- Check MySQL is running: `mysql -u root -p`
- Verify credentials in `config/database.php`
- Ensure database exists: `SHOW DATABASES;`

### "404 Not Found"
- Make sure server is running: Check terminal for "Development Server started"
- Use correct URL format: `http://localhost:8000/api/...`
- Check you're using `router.php` when starting server

### "Class not found"
- Verify all files are in correct directories
- Check `require_once` paths in PHP files

### Empty responses
- Check if sample data was inserted
- Verify orders have status = 'UNASSIGNED'
- Check PHP error logs

## 📁 File Structure

```
lusong360 Assessment/
├── api/
│   └── index.php              # API endpoints
├── config/
│   └── database.php           # Database config
├── database/
│   ├── schema.sql             # Database schema
│   └── sample_data.sql        # Sample data
├── services/
│   ├── AssignmentService.php  # Assignment logic
│   ├── CourierService.php     # Courier operations
│   └── OrderService.php       # Order operations
├── router.php                 # PHP built-in server router
├── test_api.sh               # Test script
└── README.md                  # Full documentation
```

## 🎯 Next Steps

1. **Read Full Documentation**: See `README.md` for detailed explanations
2. **Setup Guide**: See `SETUP.md` for production deployment
3. **Test APIs**: Use `test_api.sh` or curl commands
4. **Customize**: Modify services for your specific needs

## 💡 Tips

- Keep the server running in one terminal
- Use another terminal for API testing
- Check server terminal for PHP errors
- Use browser for GET requests: `http://localhost:8000/api/orders/unassigned`
- For POST requests, use curl or Postman
