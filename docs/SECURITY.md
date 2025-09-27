# Security Best Practices

## Overview

This framework includes several security features to protect your application. Follow these guidelines to ensure your application remains secure.

## Environment Security

### 1. Environment Variables

```bash
# Production .env file
APP_ENV=production
APP_DEBUG=false
APP_KEY=your-secure-32-character-key-here
```

**Important:**
- Never commit `.env` files to version control
- Generate a unique `APP_KEY` for each environment
- Set `APP_DEBUG=false` in production
- Use strong database passwords

### 2. File Permissions

```bash
# Set proper permissions
chmod 644 .env
chmod 755 public/
chmod 644 config.php
```

## Input Security

### 1. CSRF Protection

Always use CSRF protection for forms:

```php
<!-- In forms -->
<?= csrf_field() ?>

<!-- In controllers -->
validate_csrf();
```

### 2. Input Validation

Validate all user input:

```php
$errors = $request->validate([
    'email' => 'required|email',
    'name' => 'required|min:2|max:255',
    'age' => 'required|numeric'
]);
```

### 3. Input Sanitization

Sanitize input to prevent XSS:

```php
// Automatic sanitization
$clean = sanitize($_POST['content']);

// Allow specific HTML tags
$clean = sanitize($_POST['content'], true);

// Manual sanitization
$clean = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
```

### 4. SQL Injection Prevention

Always use prepared statements:

```php
// Good - Uses prepared statements
$statement = $pdo->prepare('SELECT * FROM users WHERE email = ?');
$statement->execute([$email]);

// Bad - Direct concatenation
$query = "SELECT * FROM users WHERE email = '$email'";
```

## Authentication & Authorization

### 1. Password Security

```php
// Hash passwords
$hashedPassword = Security::hashPassword($password);

// Verify passwords
if (Security::verifyPassword($password, $hashedPassword)) {
    // Password is correct
}
```

### 2. Rate Limiting

Implement rate limiting for sensitive operations:

```php
// Check rate limit
$key = 'login_' . $_SERVER['REMOTE_ADDR'];
if (!Security::checkRateLimit($key, 5, 300)) {
    abort(429, 'Too many login attempts');
}
```

### 3. Session Security

Configure secure sessions:

```php
session_start([
    'cookie_lifetime' => 0,
    'cookie_secure' => true,     // HTTPS only
    'cookie_httponly' => true,   // No JavaScript access
    'cookie_samesite' => 'Strict'
]);
```

## File Upload Security

### 1. File Validation

```php
if ($request->hasFile('upload')) {
    $file = $request->file('upload');
    
    // Validate file
    $errors = Security::validateFileUpload($file);
    if (!empty($errors)) {
        // Handle errors
        return view('upload', compact('errors'));
    }
    
    // Process valid file
}
```

### 2. Allowed File Types

Configure in `.env`:

```env
ALLOWED_FILE_TYPES=jpg,jpeg,png,gif,pdf,doc,docx
MAX_UPLOAD_SIZE=2048
```

### 3. Safe File Storage

```php
// Generate safe filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid() . '.' . $extension;
$uploadPath = 'uploads/' . $filename;

// Move file to safe location
move_uploaded_file($file['tmp_name'], $uploadPath);
```

## Database Security

### 1. Connection Security

```php
// Use SSL connections in production
$options = [
    PDO::MYSQL_ATTR_SSL_CA => '/path/to/ca-cert.pem',
    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true
];
```

### 2. Least Privilege

- Create database users with minimal required permissions
- Use separate users for different operations
- Never use root/admin accounts in application code

### 3. Backup Security

- Encrypt database backups
- Store backups in secure locations
- Regularly test backup restoration

## HTTPS Configuration

### 1. Force HTTPS

```php
// In .htaccess (Apache)
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

// In code
if (!$request->isSecure() && env('APP_ENV') === 'production') {
    $redirectUrl = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    redirect($redirectUrl, 301);
}
```

### 2. Security Headers

```php
// Add security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
header('Content-Security-Policy: default-src \'self\'');
```

## Error Handling

### 1. Production Error Handling

```php
// Never show detailed errors in production
if (env('APP_ENV') === 'production') {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', '/path/to/error.log');
}
```

### 2. Custom Error Pages

Create custom error pages:

```php
// views/errors/404.php
<!DOCTYPE html>
<html>
<head>
    <title>Page Not Found</title>
</head>
<body>
    <h1>404 - Page Not Found</h1>
    <p>The requested page could not be found.</p>
</body>
</html>
```

## Logging & Monitoring

### 1. Security Event Logging

```php
// Log security events
Security::logSecurityEvent('failed_login', [
    'email' => $email,
    'ip' => $_SERVER['REMOTE_ADDR']
]);
```

### 2. Monitor Failed Attempts

Track and alert on:
- Failed login attempts
- Invalid CSRF tokens
- File upload violations
- Rate limit violations

## Data Protection

### 1. Encryption

```php
// Encrypt sensitive data
$encrypted = Security::encrypt($sensitiveData);

// Store encrypted data
$user->encrypted_field = $encrypted;

// Decrypt when needed
$decrypted = Security::decrypt($user->encrypted_field);
```

### 2. Data Masking

```php
// Mask sensitive data in logs
function maskEmail($email) {
    $parts = explode('@', $email);
    return substr($parts[0], 0, 2) . '***@' . $parts[1];
}
```

## Security Checklist

### Development
- [ ] Use CSRF protection on all forms
- [ ] Validate and sanitize all user input
- [ ] Use prepared statements for database queries
- [ ] Hash passwords with secure algorithms
- [ ] Implement proper error handling
- [ ] Use HTTPS in development environment

### Production
- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Generate unique `APP_KEY`
- [ ] Configure secure session settings
- [ ] Set up proper file permissions
- [ ] Enable security headers
- [ ] Configure SSL/TLS
- [ ] Set up monitoring and logging
- [ ] Regular security updates
- [ ] Database connection over SSL

### Ongoing
- [ ] Regular security audits
- [ ] Monitor security logs
- [ ] Update dependencies
- [ ] Backup verification
- [ ] Penetration testing
- [ ] Security training for team

## Common Vulnerabilities

### 1. XSS Prevention

```php
// Always escape output
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');

// Use framework helpers
echo sanitize($userInput);
```

### 2. SQL Injection Prevention

```php
// Good
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$userId]);

// Bad
$query = "SELECT * FROM users WHERE id = $userId";
```

### 3. CSRF Prevention

```php
// Always verify CSRF tokens
validate_csrf();

// Include in forms
echo csrf_field();
```

### 4. Directory Traversal Prevention

```php
// Validate file paths
function isValidPath($path) {
    $realPath = realpath($path);
    $allowedPath = realpath('/allowed/directory/');
    return strpos($realPath, $allowedPath) === 0;
}
```

## Emergency Response

### 1. Security Incident Response

1. **Immediate Actions:**
   - Change all passwords
   - Revoke API keys
   - Review access logs
   - Block suspicious IPs

2. **Investigation:**
   - Analyze logs
   - Identify breach scope
   - Document findings

3. **Recovery:**
   - Apply security patches
   - Restore from clean backups
   - Update security measures

### 2. Contact Information

Maintain emergency contact list:
- System administrators
- Security team
- Hosting provider
- Database administrator

Remember: Security is an ongoing process, not a one-time setup. Regularly review and update your security measures.