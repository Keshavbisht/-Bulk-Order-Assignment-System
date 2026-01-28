#!/bin/bash

# PHP Installation Helper Script

echo "=========================================="
echo "PHP Installation Helper"
echo "=========================================="
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Check if PHP is already installed
if command -v php &> /dev/null; then
    PHP_VERSION=$(php -v | head -n 1)
    echo -e "${GREEN}✅ PHP is already installed!${NC}"
    echo "Version: $PHP_VERSION"
    echo ""
    echo "You can skip PHP installation and continue with setup."
    exit 0
fi

# Check if Homebrew is installed
if ! command -v brew &> /dev/null; then
    echo -e "${RED}❌ Homebrew not found${NC}"
    echo ""
    echo "Please install Homebrew first:"
    echo "/bin/bash -c \"\$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)\""
    exit 1
fi

echo -e "${GREEN}✅ Homebrew found${NC}"
echo ""

# Ask user if they want to install PHP
echo "Do you want to install PHP using Homebrew? (y/n)"
read -r INSTALL_PHP

if [[ ! "$INSTALL_PHP" =~ ^[Yy]$ ]]; then
    echo "Installation cancelled."
    exit 0
fi

echo ""
echo "Installing PHP (this may take a few minutes)..."
echo ""

# Install PHP
if brew install php; then
    echo ""
    echo -e "${GREEN}✅ PHP installed successfully!${NC}"
    echo ""
    
    # Verify installation
    if command -v php &> /dev/null; then
        PHP_VERSION=$(php -v | head -n 1)
        echo "PHP Version: $PHP_VERSION"
        echo ""
        echo -e "${GREEN}✅ PHP is ready to use!${NC}"
        echo ""
        echo "Next steps:"
        echo "1. cd \"/Volumes/2TB SSD/Development/lusong360 Assessment\""
        echo "2. Continue with database setup (see STEP_BY_STEP.md)"
    else
        echo -e "${YELLOW}⚠️  PHP installed but not in PATH${NC}"
        echo ""
        echo "Try adding to PATH:"
        echo "export PATH=\"\$(brew --prefix php)/bin:\$PATH\""
        echo "source ~/.zshrc"
    fi
else
    echo ""
    echo -e "${RED}❌ PHP installation failed${NC}"
    echo ""
    echo "Please try installing manually:"
    echo "brew install php"
    exit 1
fi
