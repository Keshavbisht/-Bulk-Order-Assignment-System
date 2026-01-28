<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Order Assignment System - API</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2.5em;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 1.1em;
        }
        .status {
            background: #e8f5e9;
            border-left: 4px solid #4caf50;
            padding: 15px;
            margin-bottom: 30px;
            border-radius: 4px;
        }
        .status.success {
            background: #e8f5e9;
            border-color: #4caf50;
        }
        .api-endpoint {
            background: #f5f5f5;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }
        .api-endpoint h3 {
            color: #333;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .method {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 0.85em;
        }
        .method.get {
            background: #4caf50;
            color: white;
        }
        .method.post {
            background: #2196f3;
            color: white;
        }
        .endpoint-url {
            font-family: 'Courier New', monospace;
            background: #fff;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
            border: 1px solid #ddd;
            word-break: break-all;
        }
        .test-link {
            display: inline-block;
            margin-top: 10px;
            padding: 8px 16px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.9em;
            transition: background 0.3s;
        }
        .test-link:hover {
            background: #5568d3;
        }
        .description {
            color: #666;
            margin-top: 10px;
            line-height: 1.6;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: center;
            color: #999;
        }
        code {
            background: #f0f0f0;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Bulk Order Assignment System</h1>
        <p class="subtitle">REST API Endpoints</p>
        
        <div class="status success">
            ✅ <strong>Server is running!</strong> API endpoints are available below.
        </div>

        <div class="api-endpoint">
            <h3>
                <span class="method get">GET</span>
                Fetch Unassigned Orders
            </h3>
            <div class="endpoint-url">
                <a href="/api/orders/unassigned" target="_blank">/api/orders/unassigned</a>
            </div>
            <div class="description">
                <strong>Query Parameters:</strong><br>
                • <code>page</code> (optional): Page number (default: 1)<br>
                • <code>limit</code> (optional): Items per page (default: 100)<br>
                • <code>location</code> (optional): Filter by delivery location
            </div>
            <a href="/api/orders/unassigned" class="test-link" target="_blank">Test This Endpoint →</a>
        </div>

        <div class="api-endpoint">
            <h3>
                <span class="method get">GET</span>
                Fetch Available Couriers
            </h3>
            <div class="endpoint-url">
                <a href="/api/couriers/available?location=New York" target="_blank">/api/couriers/available?location=New York</a>
            </div>
            <div class="description">
                <strong>Query Parameters:</strong><br>
                • <code>location</code> (required): Delivery location<br>
                • <code>limit</code> (optional): Maximum number of couriers
            </div>
            <a href="/api/couriers/available?location=New York" class="test-link" target="_blank">Test This Endpoint →</a>
        </div>

        <div class="api-endpoint">
            <h3>
                <span class="method post">POST</span>
                Bulk Assign Orders
            </h3>
            <div class="endpoint-url">/api/assignments/bulk</div>
            <div class="description">
                <strong>Request Body (JSON):</strong><br>
                <code>{"order_ids": [1, 2, 3], "batch_size": 100}</code><br><br>
                <strong>Note:</strong> Use curl or Postman to test POST endpoints. See examples below.
            </div>
        </div>

        <div class="api-endpoint">
            <h3>
                <span class="method get">GET</span>
                View Assignment Results
            </h3>
            <div class="endpoint-url">
                <a href="/api/assignments" target="_blank">/api/assignments</a>
            </div>
            <div class="description">
                <strong>Query Parameters:</strong><br>
                • <code>page</code> (optional): Page number<br>
                • <code>limit</code> (optional): Items per page<br>
                • <code>assignment_ids</code> (optional): Comma-separated IDs
            </div>
            <a href="/api/assignments" class="test-link" target="_blank">Test This Endpoint →</a>
        </div>

        <div class="api-endpoint">
            <h3>
                <span class="method post">POST</span>
                Retry Failed Assignments
            </h3>
            <div class="endpoint-url">/api/assignments/retry</div>
            <div class="description">
                Retries failed assignments that haven't exceeded max retry count.
            </div>
        </div>

        <div class="footer">
            <p><strong>Quick Test Commands:</strong></p>
            <p style="margin-top: 10px; font-size: 0.9em;">
                <code>curl http://localhost:8000/api/orders/unassigned</code><br>
                <code>curl "http://localhost:8000/api/couriers/available?location=New York"</code><br>
                <code>curl -X POST http://localhost:8000/api/assignments/bulk -H "Content-Type: application/json" -d '{"order_ids": [1,2,3]}'</code>
            </p>
            <p style="margin-top: 20px;">
                📖 See <code>README.md</code> or <code>STEP_BY_STEP.md</code> for full documentation
            </p>
        </div>
    </div>
</body>
</html>
