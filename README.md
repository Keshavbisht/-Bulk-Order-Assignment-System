# Bulk Order Assignment System

A comprehensive backend service for managing bulk order assignments to couriers in an Order Management System.

## Table of Contents

1. [Database Design](#database-design)
2. [Bulk Assignment Logic](#bulk-assignment-logic)
3. [API Design](#api-design)
4. [Performance & Scalability](#performance--scalability)
5. [Edge Cases](#edge-cases)
6. [Error Handling](#error-handling)
7. [Installation & Setup](#installation--setup)

---

## Database Design

### Table Structure

#### 1. **orders** Table
Stores order information with status tracking.

- `order_id` (INT, PRIMARY KEY, AUTO_INCREMENT)
- `order_date` (DATETIME)
- `delivery_location` (VARCHAR(255))
- `order_value` (DECIMAL(10,2))
- `status` (ENUM: 'NEW', 'ASSIGNED', 'UNASSIGNED')
- `created_at`, `updated_at` (TIMESTAMP)

**Indexes:**
- `idx_status`: Fast lookup of orders by status
- `idx_delivery_location`: Quick filtering by location
- `idx_order_date`: Efficient date range queries
- `idx_status_location`: Composite index for common query pattern (status + location)

#### 2. **couriers** Table
Stores courier information and capacity.

- `id` (INT, PRIMARY KEY, AUTO_INCREMENT)
- `name` (VARCHAR(255))
- `serviceable_locations` (JSON): Array of locations the courier can service
- `daily_capacity` (INT): Maximum orders per day
- `current_assigned_count` (INT): Currently assigned orders
- `is_active` (BOOLEAN)
- `created_at`, `updated_at` (TIMESTAMP)

**Indexes:**
- `idx_active`: Filter active couriers quickly
- `idx_capacity`: Optimize queries checking available capacity

#### 3. **order_assignments** Table
Tracks assignments between orders and couriers.

- `assignment_id` (INT, PRIMARY KEY, AUTO_INCREMENT)
- `order_id` (INT, FOREIGN KEY → orders.order_id)
- `courier_id` (INT, FOREIGN KEY → couriers.id)
- `assignment_date` (DATETIME)
- `status` (ENUM: 'PENDING', 'CONFIRMED', 'FAILED', 'CANCELLED')
- `retry_count` (INT): Number of retry attempts
- `error_message` (TEXT)
- `created_at`, `updated_at` (TIMESTAMP)

**Indexes:**
- `idx_courier_id`: Fast lookup of assignments by courier
- `idx_assignment_date`: Date-based queries
- `idx_status`: Filter by assignment status
- `idx_order_courier`: Composite for checking existing assignments
- `unique_order_assignment`: Prevents duplicate assignments (UNIQUE constraint)

#### 4. **assignment_logs** Table
Audit trail for assignment actions.

- `log_id` (INT, PRIMARY KEY, AUTO_INCREMENT)
- `assignment_id` (INT, FOREIGN KEY → order_assignments.assignment_id)
- `action` (VARCHAR(50))
- `details` (JSON)
- `created_at` (TIMESTAMP)

#### 5. **assignment_locks** Table
Prevents race conditions during concurrent assignments.

- `lock_id` (INT, PRIMARY KEY, AUTO_INCREMENT)
- `order_id` (INT, UNIQUE)
- `courier_id` (INT, nullable)
- `lock_type` (ENUM: 'ASSIGNMENT', 'CAPACITY_UPDATE')
- `locked_at`, `expires_at` (TIMESTAMP)

### Primary Keys and Foreign Keys

**Primary Keys:**
- All tables have auto-incrementing primary keys for unique identification

**Foreign Keys:**
- `order_assignments.order_id` → `orders.order_id` (CASCADE on delete)
- `order_assignments.courier_id` → `couriers.id` (RESTRICT on delete)
- `assignment_logs.assignment_id` → `order_assignments.assignment_id`

### Index Strategy

**Why these indexes?**

1. **Status-based queries**: Most queries filter by order status (NEW, UNASSIGNED), so `idx_status` is critical
2. **Location-based filtering**: Orders are grouped by location for assignment, requiring `idx_delivery_location`
3. **Composite indexes**: `idx_status_location` optimizes the common pattern of filtering by both status and location
4. **Capacity checks**: `idx_capacity` speeds up queries checking if couriers have available capacity
5. **Assignment lookups**: Multiple indexes on `order_assignments` support various query patterns
6. **Unique constraints**: Prevent duplicate assignments and ensure data integrity

---

## Bulk Assignment Logic

### Algorithm

The bulk assignment uses a **greedy algorithm** with the following steps:

1. **Group orders by location**: Orders are grouped by `delivery_location` to optimize courier matching
2. **Find available couriers**: For each location, query couriers who:
   - Are active
   - Service that location (JSON contains check)
   - Have available capacity (`daily_capacity - current_assigned_count > 0`)
3. **Assign using greedy approach**: For each order:
   - Sort couriers by available capacity (descending)
   - Assign to the courier with the most available capacity
   - Update courier capacity atomically
4. **Transaction management**: Entire batch runs in a single transaction for atomicity
5. **Row-level locking**: Uses `FOR UPDATE` locks to prevent race conditions

### Assignment Failure Handling

**3rd Party Failures:**
- Assignments are marked as 'FAILED' with error messages
- Retry mechanism with exponential backoff
- Failed assignments can be retried via API endpoint
- Error details logged in `assignment_logs` table

**Internal Failures:**
- Database transaction rollback on critical errors
- Partial failures logged but don't block entire batch
- Capacity checks prevent over-assignment
- Duplicate prevention via unique constraints

---

## API Design

### 1. Fetch Unassigned Orders

**Endpoint:** `GET /api/orders/unassigned`

**Query Parameters:**
- `page` (optional, default: 1): Page number
- `limit` (optional, default: 100): Items per page
- `location` (optional): Filter by delivery location

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "order_id": 1,
      "order_date": "2024-01-15 10:30:00",
      "delivery_location": "New York",
      "order_value": 150.00,
      "status": "UNASSIGNED",
      "assignment_count": 0
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 100,
    "total": 500,
    "total_pages": 5
  }
}
```

### 2. Fetch Available Couriers

**Endpoint:** `GET /api/couriers/available`

**Query Parameters:**
- `location` (required): Delivery location
- `limit` (optional): Maximum number of couriers to return

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "John Doe",
      "serviceable_locations": ["New York", "Boston"],
      "daily_capacity": 50,
      "current_assigned_count": 20,
      "available_capacity": 30
    }
  ],
  "count": 1
}
```

### 3. Bulk Assign Orders

**Endpoint:** `POST /api/assignments/bulk`

**Request Body:**
```json
{
  "order_ids": [1, 2, 3, 4, 5],
  "batch_size": 100
}
```

**Note:** If `order_ids` is null or not provided, system assigns all unassigned orders (up to batch_size).

**Response:**
```json
{
  "success": true,
  "results": {
    "total_processed": 5,
    "successful": 4,
    "failed": 1,
    "assignments": [
      {
        "assignment_id": 101,
        "order_id": 1,
        "courier_id": 5
      }
    ],
    "errors": [
      {
        "order_id": 5,
        "error": "No courier with available capacity"
      }
    ]
  }
}
```

### 4. View Assignment Results

**Endpoint:** `GET /api/assignments`

**Query Parameters:**
- `page` (optional, default: 1)
- `limit` (optional, default: 100)
- `assignment_ids` (optional, comma-separated): Filter by specific assignment IDs

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "assignment_id": 101,
      "order_id": 1,
      "courier_id": 5,
      "assignment_date": "2024-01-15 11:00:00",
      "status": "CONFIRMED",
      "order_date": "2024-01-15 10:30:00",
      "delivery_location": "New York",
      "order_value": 150.00,
      "courier_name": "John Doe"
    }
  ],
  "count": 1
}
```

### 5. Retry Failed Assignments

**Endpoint:** `POST /api/assignments/retry`

**Response:**
```json
{
  "success": true,
  "results": {
    "retried": 3,
    "still_failed": 1
  }
}
```

---

## Performance & Scalability

### Handling 1000-10000 Orders/Day

**Optimization Strategies:**

1. **Batch Processing**
   - Process orders in batches (default: 100 per batch)
   - Prevents memory exhaustion and long-running transactions
   - Allows progress tracking and partial success handling

2. **Database Indexing**
   - All critical query paths are indexed
   - Composite indexes for multi-column filters
   - Regular index maintenance and optimization

3. **Pagination**
   - All list endpoints support pagination
   - Prevents loading large datasets into memory
   - Enables efficient processing of large order volumes

4. **Connection Pooling**
   - Use connection pooling in production
   - Reuse database connections
   - Configure appropriate pool size based on load

### Race Condition Prevention

**Mechanisms:**

1. **Database Transactions**
   - Entire assignment batch runs in a single transaction
   - Atomic commit or rollback prevents partial states

2. **Row-Level Locks**
   - `SELECT ... FOR UPDATE` on courier capacity checks
   - Prevents concurrent capacity updates
   - Ensures accurate capacity tracking

3. **Unique Constraints**
   - `unique_order_assignment` prevents duplicate assignments
   - Database-level enforcement is atomic

4. **Lock Table**
   - `assignment_locks` table for distributed locking
   - Expires automatically to prevent deadlocks
   - Cleanup job removes expired locks

### Database Transactions

**Transaction Strategy:**
- **Bulk assignment**: Single transaction per batch
- **Individual assignments**: Transaction per order (within batch)
- **Retry logic**: Each retry in its own transaction
- **Rollback on errors**: Ensures data consistency

### Pagination and Batching

**Pagination:**
- All list endpoints support `page` and `limit` parameters
- Prevents memory issues with large datasets
- Enables efficient API consumption

**Batching:**
- Bulk assignment processes in configurable batch sizes
- Default: 100 orders per batch
- Can be adjusted based on system capacity

### Additional Scenarios

1. **High Concurrency**
   - Use database connection pooling
   - Implement queue system (Redis/RabbitMQ) for async processing
   - Consider read replicas for reporting queries

2. **Peak Load Handling**
   - Implement rate limiting on API endpoints
   - Queue bulk assignments during peak hours
   - Scale horizontally with load balancer

3. **Data Archival**
   - Archive old assignments to separate tables
   - Maintain indexes on active data only
   - Regular cleanup of expired locks and logs

---

## Edge Cases

### 1. No Available Couriers

**Handling:**
- Orders remain in 'UNASSIGNED' status
- Error logged in assignment results
- System can retry when couriers become available
- Alert/notification system can notify administrators

**Implementation:**
```php
if (empty($couriers)) {
    // Mark all orders as failed with specific error
    // Log error for monitoring
    // Return detailed error in API response
}
```

### 2. Partial Assignment

**Handling:**
- System processes all orders in batch
- Successful assignments are committed
- Failed assignments are logged with reasons
- API response includes detailed success/failure breakdown
- Failed orders remain 'UNASSIGNED' for retry

**Response Structure:**
- `successful`: Count of successful assignments
- `failed`: Count of failed assignments
- `assignments`: Array of successful assignment details
- `errors`: Array of error details for failures

### 3. Courier Capacity Updates During Assignment

**Handling:**
- Row-level locks (`FOR UPDATE`) prevent concurrent capacity changes
- Capacity checked atomically before assignment
- If capacity becomes unavailable, assignment fails gracefully
- Error logged for monitoring

**Implementation:**
```php
// Lock courier row during capacity check
$courierCapacity = $this->courierService->getCourierCapacityWithLock($courierId);
// Update capacity atomically
$this->courierService->updateCourierCapacity($courierId, 1);
```

### 4. Duplicate Assignment Prevention

**Handling:**
- Unique constraint on `(order_id, courier_id)` in `order_assignments`
- Pre-assignment check for existing confirmed assignments
- Database-level enforcement prevents race conditions
- Error handling catches duplicate entry exceptions

**Implementation:**
- Check before insert: `SELECT ... WHERE order_id = X AND status = 'CONFIRMED'`
- Unique constraint: `UNIQUE KEY unique_order_assignment (order_id, courier_id)`
- Exception handling for duplicate entry errors

---

## Error Handling

### Retry Logic

**Implementation:**
- Failed assignments stored with `status = 'FAILED'`
- `retry_count` tracks number of attempts
- Maximum retries configurable (default: 3)
- Retry endpoint: `POST /api/assignments/retry`

**Retry Strategy:**
1. Check if assignment still eligible (courier exists, has capacity)
2. Update assignment status to 'CONFIRMED' if successful
3. Increment retry_count on each attempt
4. Stop retrying after max_retries reached

**Exponential Backoff:**
- Can be implemented in cron job or queue worker
- Delay increases with each retry: 1s, 2s, 4s, etc.

### Logging and Monitoring

**Logging:**
- All assignments logged in `assignment_logs` table
- Error messages stored in `order_assignments.error_message`
- PHP error_log for critical errors
- Structured logging with JSON details

**Monitoring Points:**
1. **Assignment Success Rate**: `successful / total_processed`
2. **Failed Assignment Count**: Query `status = 'FAILED'`
3. **Retry Success Rate**: Monitor retry endpoint results
4. **Capacity Utilization**: Track courier capacity usage
5. **Processing Time**: Monitor API response times

**Recommended Monitoring:**
- Set up alerts for high failure rates
- Monitor courier capacity utilization
- Track assignment processing time
- Alert on database connection issues

### Real-Time System Improvements

**For Real-Time Requirements:**

1. **WebSocket Integration**
   - Push assignment updates to clients in real-time
   - Notify when assignments complete
   - Live dashboard updates

2. **Message Queue**
   - Use Redis/RabbitMQ for async processing
   - Decouple assignment logic from API
   - Enable horizontal scaling

3. **Event-Driven Architecture**
   - Emit events on assignment completion
   - Allow other services to react to assignments
   - Enable microservices communication

4. **Caching**
   - Cache available couriers by location
   - Reduce database queries
   - Use Redis for fast lookups

5. **Database Read Replicas**
   - Separate read/write operations
   - Scale read queries horizontally
   - Reduce load on primary database

---

## Installation & Setup

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher (or MariaDB 10.2+)
- Apache/Nginx with mod_rewrite enabled
- PDO MySQL extension

### Setup Steps

1. **Database Setup**
   ```bash
   # Create database
   mysql -u root -p
   CREATE DATABASE order_assignment_db;
   
   # Import schema
   mysql -u root -p order_assignment_db < database/schema.sql
   ```

2. **Configuration**
   - Update `config/database.php` with your database credentials:
     ```php
     private $host = 'localhost';
     private $db_name = 'order_assignment_db';
     private $username = 'your_username';
     private $password = 'your_password';
     ```

3. **Web Server Configuration**
   - For Apache: Ensure `.htaccess` is enabled
   - For Nginx: Configure URL rewriting
   - Point document root to project directory

4. **Test API**
   ```bash
   # Test unassigned orders endpoint
   curl http://localhost/api/orders/unassigned?page=1&limit=10
   
   # Test bulk assignment
   curl -X POST http://localhost/api/assignments/bulk \
     -H "Content-Type: application/json" \
     -d '{"order_ids": [1,2,3], "batch_size": 100}'
   ```

### Sample Data

You can insert sample data for testing:

```sql
-- Insert sample couriers
INSERT INTO couriers (name, serviceable_locations, daily_capacity, is_active) VALUES
('John Doe', '["New York", "Boston"]', 50, 1),
('Jane Smith', '["New York", "Philadelphia"]', 75, 1),
('Bob Johnson', '["Boston", "Cambridge"]', 60, 1);

-- Insert sample orders
INSERT INTO orders (order_date, delivery_location, order_value, status) VALUES
(NOW(), 'New York', 150.00, 'UNASSIGNED'),
(NOW(), 'Boston', 200.00, 'UNASSIGNED'),
(NOW(), 'New York', 175.00, 'UNASSIGNED');
```

---

## File Structure

```
lusong360 Assessment/
├── api/
│   └── index.php              # REST API router
├── config/
│   └── database.php           # Database configuration
├── database/
│   └── schema.sql             # Database schema
├── services/
│   ├── AssignmentService.php  # Bulk assignment logic
│   ├── CourierService.php     # Courier operations
│   └── OrderService.php       # Order operations
├── .htaccess                  # URL rewriting rules
└── README.md                  # This documentation
```

---

## Future Enhancements

1. **Queue System**: Implement Redis/RabbitMQ for async processing
2. **Caching Layer**: Add Redis for frequently accessed data
3. **API Authentication**: Implement JWT or OAuth2
4. **Rate Limiting**: Protect APIs from abuse
5. **Unit Tests**: Add PHPUnit test suite
6. **API Documentation**: Generate Swagger/OpenAPI docs
7. **Monitoring Dashboard**: Real-time assignment metrics
8. **Automated Retries**: Cron job for automatic retry of failed assignments

---

## License

This project is created for assessment purposes.


