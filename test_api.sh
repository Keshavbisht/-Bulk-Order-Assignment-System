#!/bin/bash

# API Testing Script
# Make sure the server is running first!

BASE_URL="http://localhost:8000/api"

echo "=========================================="
echo "Testing Bulk Order Assignment System APIs"
echo "=========================================="
echo ""

echo "1. Testing: Get Unassigned Orders"
echo "-----------------------------------"
curl -s "$BASE_URL/orders/unassigned?page=1&limit=5" | python3 -m json.tool
echo ""
echo ""

echo "2. Testing: Get Available Couriers for New York"
echo "-----------------------------------"
curl -s "$BASE_URL/couriers/available?location=New York" | python3 -m json.tool
echo ""
echo ""

echo "3. Testing: Bulk Assign Orders"
echo "-----------------------------------"
curl -s -X POST "$BASE_URL/assignments/bulk" \
  -H "Content-Type: application/json" \
  -d '{"order_ids": [1,2,3,4,5], "batch_size": 100}' | python3 -m json.tool
echo ""
echo ""

echo "4. Testing: View Assignment Results"
echo "-----------------------------------"
curl -s "$BASE_URL/assignments?page=1&limit=10" | python3 -m json.tool
echo ""
echo ""

echo "5. Testing: Retry Failed Assignments"
echo "-----------------------------------"
curl -s -X POST "$BASE_URL/assignments/retry" | python3 -m json.tool
echo ""
echo ""

echo "=========================================="
echo "Testing Complete!"
echo "=========================================="
