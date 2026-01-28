#!/bin/bash

# Automated Setup Script
# This script helps set up the database

echo "=========================================="
echo "Bulk Order Assignment System - Setup"
echo "=========================================="
echo ""

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if MySQL is available
echo "Checking MySQL..."
if ! command -v mysql &> /dev/null; then
    echo -e "${RED}❌ MySQL not found. Please install MySQL first.${NC}"
    exit 1
fi
echo -e "${GREEN}✅ MySQL found${NC}"
echo ""

# Get MySQL credentials
echo "Enter MySQL username (default: root):"
read -r MYSQL_USER
MYSQL_USER=${MYSQL_USER:-root}

echo "Enter MySQL password (press Enter if no password):"
read -s MYSQL_PASS
echo ""

# Test MySQL connection
echo "Testing MySQL connection..."
if mysql -u "$MYSQL_USER" -p"$MYSQL_PASS" -e "SELECT 1" &> /dev/null; then
    echo -e "${GREEN}✅ MySQL connection successful${NC}"
else
    echo -e "${RED}❌ MySQL connection failed. Please check your credentials.${NC}"
    exit 1
fi
echo ""

# Create database
echo "Creating database..."
if mysql -u "$MYSQL_USER" -p"$MYSQL_PASS" -e "CREATE DATABASE IF NOT EXISTS order_assignment_db;" 2>/dev/null; then
    echo -e "${GREEN}✅ Database created${NC}"
else
    echo -e "${YELLOW}⚠️  Database might already exist${NC}"
fi
echo ""

# Import schema
echo "Importing database schema..."
if mysql -u "$MYSQL_USER" -p"$MYSQL_PASS" order_assignment_db < database/schema.sql 2>/dev/null; then
    echo -e "${GREEN}✅ Schema imported${NC}"
else
    echo -e "${RED}❌ Failed to import schema${NC}"
    exit 1
fi
echo ""

# Ask about sample data
echo "Do you want to import sample data? (y/n)"
read -r IMPORT_SAMPLE
if [[ "$IMPORT_SAMPLE" =~ ^[Yy]$ ]]; then
    echo "Importing sample data..."
    if mysql -u "$MYSQL_USER" -p"$MYSQL_PASS" order_assignment_db < database/sample_data.sql 2>/dev/null; then
        echo -e "${GREEN}✅ Sample data imported${NC}"
    else
        echo -e "${YELLOW}⚠️  Failed to import sample data (might already exist)${NC}"
    fi
fi
echo ""

# Update config file
echo "Updating database configuration..."
sed -i.bak "s/private \$username = '.*';/private \$username = '$MYSQL_USER';/" config/database.php
if [ -n "$MYSQL_PASS" ]; then
    sed -i.bak "s/private \$password = '.*';/private \$password = '$MYSQL_PASS';/" config/database.php
fi
echo -e "${GREEN}✅ Configuration updated${NC}"
echo ""

# Test connection
echo "Testing PHP database connection..."
if php test_connection.php &> /dev/null; then
    echo -e "${GREEN}✅ PHP connection test passed${NC}"
else
    echo -e "${YELLOW}⚠️  PHP connection test failed. Please check config/database.php${NC}"
fi
echo ""

echo "=========================================="
echo -e "${GREEN}Setup Complete!${NC}"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Start the server:"
echo "   php -S localhost:8000 router.php"
echo ""
echo "2. In a new terminal, test the API:"
echo "   curl http://localhost:8000/api/orders/unassigned"
echo ""
echo "See STEP_BY_STEP.md for detailed instructions."
