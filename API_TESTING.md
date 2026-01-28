# API Testing Guide

Your server is running! Here's how to test all the endpoints.

## 🌐 Browser Testing (GET requests only)

Open these URLs in your browser:

1. **Home/API Documentation:**
   ```
   http://localhost:8000/
   ```

2. **Unassigned Orders:**
   ```
   http://localhost:8000/api/orders/unassigned
   ```

3. **Available Couriers:**
   ```
   http://localhost:8000/api/couriers/available?location=New York
   ```

4. **Assignment Results:**
   ```
   http://localhost:8000/api/assignments
   ```

## 💻 Terminal Testing (All endpoints)

### Test 1: Get Unassigned Orders
```bash
curl http://localhost:8000/api/orders/unassigned
```

With pagination:
```bash
curl "http://localhost:8000/api/orders/unassigned?page=1&limit=5"
```

With location filter:
```bash
curl "http://localhost:8000/api/orders/unassigned?location=New York"
```

### Test 2: Get Available Couriers
```bash
curl "http://localhost:8000/api/couriers/available?location=New York"
```

With limit:
```bash
curl "http://localhost:8000/api/couriers/available?location=New York&limit=10"
```

### Test 3: Bulk Assign Orders
```bash
curl -X POST http://localhost:8000/api/assignments/bulk \
  -H "Content-Type: application/json" \
  -d '{"order_ids": [1,2,3], "batch_size": 100}'
```

Assign all unassigned orders:
```bash
curl -X POST http://localhost:8000/api/assignments/bulk \
  -H "Content-Type: application/json" \
  -d '{"batch_size": 100}'
```

### Test 4: View Assignment Results
```bash
curl http://localhost:8000/api/assignments
```

With pagination:
```bash
curl "http://localhost:8000/api/assignments?page=1&limit=10"
```

With specific IDs:
```bash
curl "http://localhost:8000/api/assignments?assignment_ids=1,2,3"
```

### Test 5: Retry Failed Assignments
```bash
curl -X POST http://localhost:8000/api/assignments/retry
```

## 📋 Complete Test Sequence

Run these commands in order to test the full flow:

```bash
# 1. Check unassigned orders
curl http://localhost:8000/api/orders/unassigned

# 2. Check available couriers
curl "http://localhost:8000/api/couriers/available?location=New York"

# 3. Assign orders
curl -X POST http://localhost:8000/api/assignments/bulk \
  -H "Content-Type: application/json" \
  -d '{"order_ids": [1,2,3], "batch_size": 100}'

# 4. View assignment results
curl http://localhost:8000/api/assignments

# 5. Check unassigned orders again (should be fewer)
curl http://localhost:8000/api/orders/unassigned
```

## 🎯 Expected Responses

### Successful Response Format:
```json
{
  "success": true,
  "data": [...],
  "pagination": {...}
}
```

### Error Response Format:
```json
{
  "error": "Error message",
  "message": "Detailed error description"
}
```

## 🔍 Pretty Print JSON (Optional)

To see formatted JSON in terminal, pipe to `python`:

```bash
curl http://localhost:8000/api/orders/unassigned | python3 -m json.tool
```

Or use `jq` if installed:
```bash
curl http://localhost:8000/api/orders/unassigned | jq
```

## 🐛 Troubleshooting

### "Connection refused"
- Make sure server is running: `php -S localhost:8000 router.php`
- Check the port (should be 8000)

### "404 Not Found"
- Make sure you're using `/api/` prefix
- Check URL spelling

### Empty response
- Check if database has data
- Verify database connection works: `php test_connection.php`

### JSON parse errors
- Check server terminal for PHP errors
- Verify database tables exist

## 📱 Using Postman

1. Import these endpoints:
   - GET: `http://localhost:8000/api/orders/unassigned`
   - GET: `http://localhost:8000/api/couriers/available?location=New York`
   - POST: `http://localhost:8000/api/assignments/bulk`
   - GET: `http://localhost:8000/api/assignments`
   - POST: `http://localhost:8000/api/assignments/retry`

2. For POST requests, set:
   - Method: POST
   - Headers: `Content-Type: application/json`
   - Body: Raw JSON

## ✅ Quick Health Check

Run this to verify everything works:

```bash
echo "Testing API endpoints..."
echo ""
echo "1. Unassigned Orders:"
curl -s http://localhost:8000/api/orders/unassigned | head -c 100
echo "..."
echo ""
echo "2. Available Couriers:"
curl -s "http://localhost:8000/api/couriers/available?location=New York" | head -c 100
echo "..."
echo ""
echo "✅ If you see JSON output, APIs are working!"
```

Happy testing! 🚀
