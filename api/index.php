<?php
/**
 * REST API Router for Bulk Order Assignment System
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../services/OrderService.php';
require_once __DIR__ . '/../services/CourierService.php';
require_once __DIR__ . '/../services/AssignmentService.php';

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/api', '', $path);
$pathParts = array_filter(explode('/', $path));
$pathParts = array_values($pathParts);

// Get request body
$input = json_decode(file_get_contents('php://input'), true);

// Route handling
try {
    switch ($method) {
        case 'GET':
            handleGetRequest($pathParts);
            break;
        case 'POST':
            handlePostRequest($pathParts, $input);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
    error_log("API Error: " . $e->getMessage());
}

/**
 * Handle GET requests
 */
function handleGetRequest($pathParts) {
    if (empty($pathParts)) {
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
        return;
    }

    $endpoint = $pathParts[0];

    switch ($endpoint) {
        case 'orders':
            if (isset($pathParts[1]) && $pathParts[1] === 'unassigned') {
                getUnassignedOrders();
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Endpoint not found']);
            }
            break;

        case 'couriers':
            if (isset($pathParts[1]) && $pathParts[1] === 'available') {
                getAvailableCouriers();
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Endpoint not found']);
            }
            break;

        case 'assignments':
            getAssignments();
            break;

        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
            break;
    }
}

/**
 * Handle POST requests
 */
function handlePostRequest($pathParts, $input) {
    if (empty($pathParts)) {
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
        return;
    }

    $endpoint = $pathParts[0];

    switch ($endpoint) {
        case 'assignments':
            if (isset($pathParts[1])) {
                if ($pathParts[1] === 'bulk') {
                    bulkAssignOrders($input);
                } elseif ($pathParts[1] === 'retry') {
                    retryFailedAssignments();
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Endpoint not found']);
                }
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Endpoint not found']);
            }
            break;

        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
            break;
    }
}

/**
 * API 1: Fetch unassigned orders
 * GET /api/orders/unassigned?page=1&limit=100&location=CityName
 */
function getUnassignedOrders() {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    $location = isset($_GET['location']) ? $_GET['location'] : null;

    $orderService = new OrderService();
    $orders = $orderService->getUnassignedOrders($page, $limit, $location);
    $total = $orderService->getUnassignedOrdersCount($location);

    echo json_encode([
        'success' => true,
        'data' => $orders,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'total_pages' => ceil($total / $limit)
        ]
    ]);
}

/**
 * API 2: Fetch available couriers
 * GET /api/couriers/available?location=CityName&limit=50
 */
function getAvailableCouriers() {
    $location = isset($_GET['location']) ? $_GET['location'] : null;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;

    if (!$location) {
        http_response_code(400);
        echo json_encode(['error' => 'Location parameter is required']);
        return;
    }

    $courierService = new CourierService();
    $couriers = $courierService->getAvailableCouriers($location, $limit);

    echo json_encode([
        'success' => true,
        'data' => $couriers,
        'count' => count($couriers)
    ]);
}

/**
 * API 3: Bulk assign orders
 * POST /api/assignments/bulk
 * Body: { "order_ids": [1, 2, 3], "batch_size": 100 }
 */
function bulkAssignOrders($input) {
    $orderIds = isset($input['order_ids']) ? $input['order_ids'] : null;
    $batchSize = isset($input['batch_size']) ? (int)$input['batch_size'] : 100;

    $assignmentService = new AssignmentService();
    $results = $assignmentService->bulkAssignOrders($orderIds, $batchSize);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'results' => $results
    ]);
}

/**
 * API 4: View assignment results
 * GET /api/assignments?page=1&limit=100&assignment_ids=1,2,3
 */
function getAssignments() {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    $assignmentIds = null;

    if (isset($_GET['assignment_ids'])) {
        $assignmentIds = array_map('intval', explode(',', $_GET['assignment_ids']));
    }

    $assignmentService = new AssignmentService();
    $assignments = $assignmentService->getAssignmentResults($assignmentIds, $page, $limit);

    echo json_encode([
        'success' => true,
        'data' => $assignments,
        'count' => count($assignments)
    ]);
}

/**
 * Retry failed assignments
 * POST /api/assignments/retry
 */
function retryFailedAssignments() {
    $assignmentService = new AssignmentService();
    $results = $assignmentService->retryFailedAssignments();

    echo json_encode([
        'success' => true,
        'results' => $results
    ]);
}


