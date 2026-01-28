<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/OrderService.php';
require_once __DIR__ . '/CourierService.php';

/**
 * Assignment Service - Handles bulk order assignment logic
 */
class AssignmentService {
    private $conn;
    private $orderService;
    private $courierService;
    private $maxRetries = 3;
    private $lockTimeout = 30; // seconds

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
        $this->orderService = new OrderService();
        $this->courierService = new CourierService();
    }

    /**
     * Bulk assign orders to couriers
     * Algorithm:
     * 1. Group orders by delivery location
     * 2. For each location, find available couriers
     * 3. Assign orders to couriers based on capacity (greedy algorithm)
     * 4. Use database transactions and row-level locks to prevent race conditions
     * 5. Handle failures gracefully with retry logic
     */
    public function bulkAssignOrders($orderIds = null, $batchSize = 100) {
        $results = [
            'total_processed' => 0,
            'successful' => 0,
            'failed' => 0,
            'assignments' => [],
            'errors' => []
        ];

        try {
            $this->conn->beginTransaction();

            // Get unassigned orders
            if ($orderIds === null) {
                $orders = $this->orderService->getUnassignedOrders(1, $batchSize);
            } else {
                $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
                $sql = "SELECT * FROM orders WHERE order_id IN ($placeholders) AND status = 'UNASSIGNED'";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute($orderIds);
                $orders = $stmt->fetchAll();
            }

            if (empty($orders)) {
                $this->conn->rollBack();
                return $results;
            }

            // Group orders by location
            $ordersByLocation = [];
            foreach ($orders as $order) {
                $location = $order['delivery_location'];
                if (!isset($ordersByLocation[$location])) {
                    $ordersByLocation[$location] = [];
                }
                $ordersByLocation[$location][] = $order;
            }

            // Process each location
            foreach ($ordersByLocation as $location => $locationOrders) {
                $locationResults = $this->assignOrdersForLocation($location, $locationOrders);
                
                $results['total_processed'] += count($locationOrders);
                $results['successful'] += $locationResults['successful'];
                $results['failed'] += $locationResults['failed'];
                $results['assignments'] = array_merge($results['assignments'], $locationResults['assignments']);
                $results['errors'] = array_merge($results['errors'], $locationResults['errors']);
            }

            $this->conn->commit();
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            $results['errors'][] = [
                'type' => 'transaction_error',
                'message' => $e->getMessage()
            ];
            error_log("Bulk assignment error: " . $e->getMessage());
        }

        return $results;
    }

    /**
     * Assign orders for a specific location
     */
    private function assignOrdersForLocation($location, $orders) {
        $results = [
            'successful' => 0,
            'failed' => 0,
            'assignments' => [],
            'errors' => []
        ];

        // Get available couriers for this location
        $couriers = $this->courierService->getAvailableCouriers($location);
        
        if (empty($couriers)) {
            foreach ($orders as $order) {
                $results['failed']++;
                $results['errors'][] = [
                    'order_id' => $order['order_id'],
                    'error' => 'No available couriers for location: ' . $location
                ];
            }
            return $results;
        }

        // Assign orders using greedy algorithm (assign to courier with most available capacity)
        foreach ($orders as $order) {
            $assigned = false;
            
            foreach ($couriers as $courier) {
                // Check if courier has capacity (with lock)
                $courierCapacity = $this->courierService->getCourierCapacityWithLock($courier['id']);
                
                if ($courierCapacity && $courierCapacity['available_capacity'] > 0) {
                    $assignment = $this->createAssignment($order['order_id'], $courier['id']);
                    
                    if ($assignment['success']) {
                        // Update courier capacity
                        $this->courierService->updateCourierCapacity($courier['id'], 1);
                        
                        // Update order status
                        $this->orderService->updateOrderStatus($order['order_id'], 'ASSIGNED');
                        
                        $results['successful']++;
                        $results['assignments'][] = $assignment['data'];
                        $assigned = true;
                        
                        // Update local courier capacity for next iteration
                        $courier['current_assigned_count']++;
                        break;
                    } else {
                        $results['errors'][] = [
                            'order_id' => $order['order_id'],
                            'courier_id' => $courier['id'],
                            'error' => $assignment['error']
                        ];
                    }
                }
            }
            
            if (!$assigned) {
                $results['failed']++;
                $results['errors'][] = [
                    'order_id' => $order['order_id'],
                    'error' => 'No courier with available capacity'
                ];
            }
        }

        return $results;
    }

    /**
     * Create assignment with duplicate prevention
     */
    private function createAssignment($orderId, $courierId) {
        try {
            // Check for existing assignment
            $checkSql = "SELECT assignment_id FROM order_assignments 
                        WHERE order_id = :order_id AND status = 'CONFIRMED'";
            $checkStmt = $this->conn->prepare($checkSql);
            $checkStmt->execute([':order_id' => $orderId]);
            
            if ($checkStmt->fetch()) {
                return [
                    'success' => false,
                    'error' => 'Order already assigned'
                ];
            }

            // Create new assignment
            $sql = "INSERT INTO order_assignments 
                    (order_id, courier_id, assignment_date, status) 
                    VALUES (:order_id, :courier_id, NOW(), 'CONFIRMED')";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':order_id' => $orderId,
                ':courier_id' => $courierId
            ]);

            $assignmentId = $this->conn->lastInsertId();

            // Log assignment
            $this->logAssignment($assignmentId, 'ASSIGNED', [
                'order_id' => $orderId,
                'courier_id' => $courierId
            ]);

            return [
                'success' => true,
                'data' => [
                    'assignment_id' => $assignmentId,
                    'order_id' => $orderId,
                    'courier_id' => $courierId
                ]
            ];

        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate entry
                return [
                    'success' => false,
                    'error' => 'Duplicate assignment prevented'
                ];
            }
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get assignment results
     */
    public function getAssignmentResults($assignmentIds = null, $page = 1, $limit = 100) {
        $offset = ($page - 1) * $limit;
        
        if ($assignmentIds) {
            $placeholders = implode(',', array_fill(0, count($assignmentIds), '?'));
            $sql = "SELECT oa.*, o.order_date, o.delivery_location, o.order_value,
                           c.name as courier_name
                    FROM order_assignments oa
                    JOIN orders o ON oa.order_id = o.order_id
                    JOIN couriers c ON oa.courier_id = c.id
                    WHERE oa.assignment_id IN ($placeholders)
                    ORDER BY oa.assignment_date DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($assignmentIds);
        } else {
            $sql = "SELECT oa.*, o.order_date, o.delivery_location, o.order_value,
                           c.name as courier_name
                    FROM order_assignments oa
                    JOIN orders o ON oa.order_id = o.order_id
                    JOIN couriers c ON oa.courier_id = c.id
                    ORDER BY oa.assignment_date DESC
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
        }
        
        return $stmt->fetchAll();
    }

    /**
     * Retry failed assignments
     */
    public function retryFailedAssignments($maxRetries = null) {
        if ($maxRetries === null) {
            $maxRetries = $this->maxRetries;
        }

        $sql = "SELECT oa.*, o.delivery_location
                FROM order_assignments oa
                JOIN orders o ON oa.order_id = o.order_id
                WHERE oa.status = 'FAILED' 
                AND oa.retry_count < :max_retries";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':max_retries' => $maxRetries]);
        $failedAssignments = $stmt->fetchAll();

        $results = ['retried' => 0, 'still_failed' => 0];

        foreach ($failedAssignments as $assignment) {
            try {
                $this->conn->beginTransaction();

                // Check if courier still available
                $courier = $this->courierService->getCourierById($assignment['courier_id']);
                if (!$courier) {
                    throw new Exception('Courier not found');
                }

                // Check capacity
                $capacity = $this->courierService->getCourierCapacityWithLock($courier['id']);
                if ($capacity['available_capacity'] <= 0) {
                    throw new Exception('Courier capacity full');
                }

                // Retry assignment
                $updateSql = "UPDATE order_assignments 
                             SET status = 'CONFIRMED', 
                                 retry_count = retry_count + 1,
                                 error_message = NULL
                             WHERE assignment_id = :assignment_id";
                
                $updateStmt = $this->conn->prepare($updateSql);
                $updateStmt->execute([':assignment_id' => $assignment['assignment_id']]);

                $this->courierService->updateCourierCapacity($courier['id'], 1);
                $this->orderService->updateOrderStatus($assignment['order_id'], 'ASSIGNED');

                $this->conn->commit();
                $results['retried']++;

            } catch (Exception $e) {
                $this->conn->rollBack();
                
                // Update retry count
                $updateSql = "UPDATE order_assignments 
                             SET retry_count = retry_count + 1,
                                 error_message = :error
                             WHERE assignment_id = :assignment_id";
                $updateStmt = $this->conn->prepare($updateSql);
                $updateStmt->execute([
                    ':assignment_id' => $assignment['assignment_id'],
                    ':error' => $e->getMessage()
                ]);
                
                $results['still_failed']++;
                error_log("Retry failed for assignment {$assignment['assignment_id']}: " . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Log assignment action
     */
    private function logAssignment($assignmentId, $action, $details) {
        $sql = "INSERT INTO assignment_logs (assignment_id, action, details) 
                VALUES (:assignment_id, :action, :details)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':assignment_id' => $assignmentId,
            ':action' => $action,
            ':details' => json_encode($details)
        ]);
    }

    /**
     * Clean up expired locks
     */
    public function cleanupExpiredLocks() {
        $sql = "DELETE FROM assignment_locks WHERE expires_at < NOW()";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute();
    }
}


