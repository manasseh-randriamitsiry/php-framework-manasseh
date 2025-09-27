# Deployment Guide

## Production Deployment

### 1. Server Requirements

**Minimum Requirements:**
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Composer
- SSL certificate

**Recommended:**
- PHP 8.0+
- MySQL 8.0+
- Redis (for caching)
- Monitoring tools

### 2. Environment Setup

#### Production Environment File

```bash
# .env (Production)
APP_NAME="Your App Name"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
APP_KEY=your-secure-32-character-key

# Database
DB_CONNECTION=mysql
DB_HOST=your-db-host
DB_PORT=3306
DB_DATABASE=your_production_db
DB_USERNAME=your_db_user
DB_PASSWORD=your-secure-password

# Security
SESSION_LIFETIME=120
CSRF_TOKEN_NAME=_token

# File Upload
MAX_UPLOAD_SIZE=2048
ALLOWED_FILE_TYPES=jpg,jpeg,png,gif,pdf
```

#### Generate Secure Keys

```bash
# Generate APP_KEY
php -r "echo 'APP_KEY=' . bin2hex(random_bytes(16)) . PHP_EOL;"
```

### 3. File Permissions

```bash
# Set proper file permissions
chmod 755 /path/to/your/app
chmod 644 /path/to/your/app/.env
chmod 755 /path/to/your/app/public
chmod 644 /path/to/your/app/config.php
chmod 755 /path/to/your/app/views
chmod 644 /path/to/your/app/views/*

# Make uploads directory writable
chmod 755 /path/to/your/app/uploads

# Secure sensitive files
chmod 600 /path/to/your/app/.env
```

### 4. Web Server Configuration

#### Apache Configuration

```apache
# .htaccess (in public directory)
RewriteEngine On

# Handle Angular and other frontend routes
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Security headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection \"1; mode=block\"
Header always set Strict-Transport-Security \"max-age=31536000; includeSubDomains\"

# Force HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Prevent access to sensitive files
<Files \".env\">
    Order allow,deny
    Deny from all
</Files>

<Files \"config.php\">
    Order allow,deny
    Deny from all
</Files>
```

#### Nginx Configuration

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com;
    root /path/to/your/app/public;
    index index.php;

    # SSL Configuration
    ssl_certificate /path/to/your/ssl/certificate.crt;
    ssl_certificate_key /path/to/your/ssl/private.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    # Security headers
    add_header X-Content-Type-Options nosniff;
    add_header X-Frame-Options DENY;
    add_header X-XSS-Protection \"1; mode=block\";
    add_header Strict-Transport-Security \"max-age=31536000; includeSubDomains\";

    # Handle PHP files
    location ~ \\.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Handle frontend routes
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Deny access to sensitive files
    location ~ /\\.(env|git) {
        deny all;
    }

    location ~ /config\\.php {
        deny all;
    }
}
```

### 5. Database Setup

#### MySQL Configuration

```sql
-- Create production database
CREATE DATABASE your_production_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create dedicated user
CREATE USER 'your_db_user'@'localhost' IDENTIFIED BY 'your-secure-password';
GRANT SELECT, INSERT, UPDATE, DELETE ON your_production_db.* TO 'your_db_user'@'localhost';
FLUSH PRIVILEGES;
```

#### Run Migrations

```bash
# Access your application and let it run migrations
# Or run manually if you have a migration script
php run_migrations.php
```

### 6. SSL Certificate

#### Using Let's Encrypt (Free)

```bash
# Install Certbot
sudo apt install certbot python3-certbot-apache

# Get certificate
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com

# Auto-renewal
sudo crontab -e
# Add: 0 12 * * * /usr/bin/certbot renew --quiet
```

## Optimization

### 1. PHP Optimization

```ini
; php.ini optimizations
max_execution_time = 30
memory_limit = 128M
post_max_size = 8M
upload_max_filesize = 2M
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1
date.timezone = UTC

; OpCache (if available)
opcache.enable = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 8
opcache.max_accelerated_files = 4000
opcache.revalidate_freq = 2
```

### 2. Database Optimization

```sql
-- Add indexes for frequently queried columns
CREATE INDEX idx_client_nom ON client(nom);
CREATE INDEX idx_virement_date ON virement(date_virement);

-- Optimize tables
OPTIMIZE TABLE client, virement, audit_virement;
```

### 3. Caching Headers

```apache
# Cache static assets
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css \"access plus 1 year\"
    ExpiresByType application/javascript \"access plus 1 year\"
    ExpiresByType image/png \"access plus 1 year\"
    ExpiresByType image/jpg \"access plus 1 year\"
    ExpiresByType image/jpeg \"access plus 1 year\"
    ExpiresByType image/gif \"access plus 1 year\"
</IfModule>
```

## Monitoring & Logging

### 1. Error Logging

```php
// Set up error logging in production
ini_set('log_errors', 1);
ini_set('error_log', '/var/log/your-app/error.log');
```

### 2. Application Monitoring

Create a simple health check endpoint:

```php
// Add to routes.php
$router->get('health', 'HealthController@check');

// HealthController.php
class HealthController
{
    public function check()
    {
        try {
            // Test database connection
            $pdo = connect();
            $pdo->query('SELECT 1');
            
            $status = [
                'status' => 'healthy',
                'timestamp' => date('Y-m-d H:i:s'),
                'database' => 'connected'
            ];
        } catch (Exception $e) {
            $status = [
                'status' => 'unhealthy',
                'timestamp' => date('Y-m-d H:i:s'),
                'error' => 'Database connection failed'
            ];
        }
        
        header('Content-Type: application/json');
        echo json_encode($status);
    }
}
```

### 3. Log Rotation

```bash
# /etc/logrotate.d/your-app
/var/log/your-app/*.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
}
```

## Backup Strategy

### 1. Database Backup

```bash
#!/bin/bash
# backup.sh
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR=\"/backups/database\"
DB_NAME=\"your_production_db\"
DB_USER=\"your_db_user\"
DB_PASS=\"your-secure-password\"

# Create backup
mysqldump -u$DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/backup_$DATE.sql

# Compress backup
gzip $BACKUP_DIR/backup_$DATE.sql

# Remove backups older than 30 days
find $BACKUP_DIR -name \"backup_*.sql.gz\" -mtime +30 -delete
```

### 2. File Backup

```bash
#!/bin/bash
# file_backup.sh
DATE=$(date +%Y%m%d_%H%M%S)
APP_DIR=\"/path/to/your/app\"
BACKUP_DIR=\"/backups/files\"

# Create tar archive
tar -czf $BACKUP_DIR/app_backup_$DATE.tar.gz $APP_DIR

# Remove old backups
find $BACKUP_DIR -name \"app_backup_*.tar.gz\" -mtime +7 -delete
```

### 3. Automated Backups

```bash
# Add to crontab
crontab -e

# Daily database backup at 2 AM
0 2 * * * /path/to/backup.sh

# Weekly file backup on Sundays at 3 AM
0 3 * * 0 /path/to/file_backup.sh
```

## Security Hardening

### 1. Firewall Configuration

```bash
# UFW (Ubuntu)
sudo ufw enable
sudo ufw allow 22    # SSH
sudo ufw allow 80    # HTTP
sudo ufw allow 443   # HTTPS
sudo ufw deny 3306   # MySQL (only allow local)
```

### 2. Fail2Ban

```bash
# Install fail2ban
sudo apt install fail2ban

# Configure for Apache
sudo cp /etc/fail2ban/jail.conf /etc/fail2ban/jail.local

# Edit jail.local
[apache-auth]
enabled = true
port = http,https
logpath = /var/log/apache2/error.log
```

### 3. Regular Updates

```bash
# System updates
sudo apt update && sudo apt upgrade

# PHP updates
sudo apt update php*

# Application dependencies
composer update
```

## Deployment Checklist

### Pre-deployment
- [ ] Test application in staging environment
- [ ] Backup current production database
- [ ] Backup current application files
- [ ] Review security settings
- [ ] Update dependencies

### Deployment
- [ ] Upload application files
- [ ] Set correct file permissions
- [ ] Configure environment variables
- [ ] Run database migrations
- [ ] Test application functionality
- [ ] Verify SSL certificate

### Post-deployment
- [ ] Monitor error logs
- [ ] Test all critical functionality
- [ ] Verify backup processes
- [ ] Update monitoring systems
- [ ] Document deployment

## Troubleshooting

### Common Issues

**File Permission Errors:**
```bash
sudo chown -R www-data:www-data /path/to/your/app
sudo chmod -R 755 /path/to/your/app
sudo chmod 644 /path/to/your/app/.env
```

**Database Connection Issues:**
- Check database credentials in `.env`
- Verify database server is running
- Check firewall settings

**SSL Certificate Issues:**
```bash
# Check certificate status
sudo certbot certificates

# Renew certificate
sudo certbot renew
```

**Performance Issues:**
- Enable PHP OpCache
- Optimize database queries
- Add database indexes
- Use caching headers

### Maintenance

**Regular Tasks:**
- Monitor disk space
- Review error logs
- Update software packages
- Test backup restoration
- Security audits

**Monthly Tasks:**
- Database optimization
- Log cleanup
- Performance review
- Security updates

Remember to always test changes in a staging environment before deploying to production.