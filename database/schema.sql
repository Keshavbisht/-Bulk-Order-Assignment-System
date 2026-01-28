-- ============================================
-- Bulk Order Assignment System - Database Schema
-- ============================================

-- Orders Table
CREATE TABLE IF NOT EXISTS orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    order_date DATETIME NOT NULL,
    delivery_location VARCHAR(255) NOT NULL,
    order_value DECIMAL(10, 2) NOT NULL,
    status ENUM('NEW', 'ASSIGNED', 'UNASSIGNED') DEFAULT 'NEW',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_delivery_location (delivery_location),
    INDEX idx_order_date (order_date),
    INDEX idx_status_location (status, delivery_location)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Couriers Table
CREATE TABLE IF NOT EXISTS couriers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    serviceable_locations JSON NOT NULL,
    daily_capacity INT NOT NULL DEFAULT 0,
    current_assigned_count INT NOT NULL DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active (is_active),
    INDEX idx_capacity (daily_capacity, current_assigned_count)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order Assignments Table
CREATE TABLE IF NOT EXISTS order_assignments (
    assignment_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    courier_id INT NOT NULL,
    assignment_date DATETIME NOT NULL,
    status ENUM('PENDING', 'CONFIRMED', 'FAILED', 'CANCELLED') DEFAULT 'PENDING',
    retry_count INT DEFAULT 0,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (courier_id) REFERENCES couriers(id) ON DELETE RESTRICT,
    UNIQUE KEY unique_order_assignment (order_id, courier_id),
    INDEX idx_courier_id (courier_id),
    INDEX idx_assignment_date (assignment_date),
    INDEX idx_status (status),
    INDEX idx_order_courier (order_id, courier_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Assignment Logs Table (for monitoring and audit)
CREATE TABLE IF NOT EXISTS assignment_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT,
    action VARCHAR(50) NOT NULL,
    details JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_assignment_id (assignment_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Assignment Locks Table (for preventing race conditions)
CREATE TABLE IF NOT EXISTS assignment_locks (
    lock_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    courier_id INT,
    lock_type ENUM('ASSIGNMENT', 'CAPACITY_UPDATE') NOT NULL,
    locked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    UNIQUE KEY unique_order_lock (order_id, lock_type),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Index Explanation:
-- ============================================
-- orders table:
--   - idx_status: Fast lookup of orders by status (NEW, UNASSIGNED)
--   - idx_delivery_location: Quick filtering by location
--   - idx_order_date: Efficient date range queries
--   - idx_status_location: Composite index for common query pattern (status + location)
--
-- couriers table:
--   - idx_active: Filter active couriers quickly
--   - idx_capacity: Optimize queries checking available capacity
--
-- order_assignments table:
--   - idx_courier_id: Fast lookup of assignments by courier
--   - idx_assignment_date: Date-based queries
--   - idx_status: Filter by assignment status
--   - idx_order_courier: Composite for checking existing assignments
--   - unique_order_assignment: Prevents duplicate assignments
--
-- assignment_locks table:
--   - idx_expires_at: Cleanup expired locks efficiently

