# Installing PHP on macOS

PHP is not currently installed on your system. Here's how to install it.

---

## Method 1: Install PHP using Homebrew (Recommended)

Since you have Homebrew installed, this is the easiest method.

### Step 1: Install PHP

Open Terminal and run:

```bash
brew install php
```

This will install the latest version of PHP (PHP 8.x).

**Note:** This may take a few minutes. You'll see progress as it downloads and installs.

### Step 2: Verify Installation

After installation completes, verify it worked:

```bash
php -v
```

**Expected output:**
```
PHP 8.x.x (cli) (built: ...)
Copyright (c) The PHP Group
...
```

### Step 3: Check PHP Location

```bash
which php
```

This will show where PHP is installed (usually `/opt/homebrew/bin/php` or `/usr/local/bin/php`)

---

## Method 2: Install PHP using XAMPP/MAMP (Alternative)

If you prefer a complete web server package:

### Option A: XAMPP
1. Download from: https://www.apachefriends.org/
2. Install the package
3. PHP will be at: `/Applications/XAMPP/xamppfiles/bin/php`

### Option B: MAMP
1. Download from: https://www.mamp.info/
2. Install the package
3. PHP will be at: `/Applications/MAMP/bin/php/php8.x.x/bin/php`

**Note:** With XAMPP/MAMP, you'll need to use the full path to PHP or add it to your PATH.

---

## After Installing PHP

### 1. Navigate to Project

```bash
cd "/Volumes/2TB SSD/Development/lusong360 Assessment"
```

### 2. Test PHP

```bash
php -v
```

### 3. Continue with Setup

Now you can continue with the setup steps:

1. **Create database:**
   ```bash
   mysql -u root -p -e "CREATE DATABASE order_assignment_db;"
   ```

2. **Import schema:**
   ```bash
   mysql -u root -p order_assignment_db < database/schema.sql
   ```

3. **Import sample data:**
   ```bash
   mysql -u root -p order_assignment_db < database/sample_data.sql
   ```

4. **Test connection:**
   ```bash
   php test_connection.php
   ```

5. **Start server:**
   ```bash
   php -S localhost:8000 router.php
   ```

---

## Troubleshooting

### Issue: "brew: command not found" after installing PHP

**Solution:** Add Homebrew to your PATH. Add this to your `~/.zshrc` file:

```bash
echo 'eval "$(/opt/homebrew/bin/brew shellenv)"' >> ~/.zshrc
source ~/.zshrc
```

Then try `php -v` again.

### Issue: PHP installed but "command not found"

**Solution:** Add PHP to your PATH. Find where PHP is installed:

```bash
brew --prefix php
```

Then add it to PATH in `~/.zshrc`:

```bash
export PATH="$(brew --prefix php)/bin:$PATH"
```

Reload: `source ~/.zshrc`

### Issue: Multiple PHP versions

**Solution:** Use the full path or switch versions:

```bash
# Check which PHP
which php

# Use specific version
/opt/homebrew/bin/php -v
```

---

## Quick Install Command

Run this single command to install PHP:

```bash
brew install php && php -v
```

If successful, you'll see the PHP version. Then continue with the setup!

---

## Verify Everything Works

After installing PHP, run this test:

```bash
cd "/Volumes/2TB SSD/Development/lusong360 Assessment"
php -r "echo 'PHP is working! Version: ' . PHP_VERSION . PHP_EOL;"
```

You should see: `PHP is working! Version: 8.x.x`

---

## Next Steps

Once PHP is installed:

1. ✅ PHP installed → Continue to database setup
2. ✅ Database setup → Test connection
3. ✅ Test connection → Start server
4. ✅ Start server → Test APIs

See `STEP_BY_STEP.md` for complete instructions.
