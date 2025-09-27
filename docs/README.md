# Framework Documentation

Welcome to the PHP MVC Framework documentation. This framework provides a simple yet powerful structure for building web applications with modern PHP practices.

## Documentation Index

### Getting Started
- [**Getting Started Guide**](GETTING_STARTED.md) - Complete setup and first steps
- [**Installation & Configuration**](GETTING_STARTED.md#installation) - Environment setup
- [**Creating Your First Module**](GETTING_STARTED.md#creating-your-first-module) - Step-by-step tutorial

### API Reference
- [**Framework API Reference**](API.md) - Complete function and class reference
- [**Routing**](API.md#routing) - URL routing and parameters
- [**Request Handling**](API.md#request-handling) - Input validation and processing
- [**Models & Database**](API.md#models--database) - Database operations and ORM
- [**Controllers**](API.md#controllers) - Request handling logic
- [**Security**](API.md#security) - CSRF, validation, encryption
- [**Helper Functions**](API.md#helper-functions) - Utility functions

### Security
- [**Security Best Practices**](SECURITY.md) - Comprehensive security guide
- [**CSRF Protection**](SECURITY.md#csrf-protection) - Cross-site request forgery
- [**Input Validation**](SECURITY.md#input-security) - Data validation and sanitization
- [**Authentication**](SECURITY.md#authentication--authorization) - User authentication
- [**File Upload Security**](SECURITY.md#file-upload-security) - Safe file handling

### Deployment
- [**Production Deployment**](DEPLOYMENT.md) - Complete deployment guide
- [**Server Configuration**](DEPLOYMENT.md#web-server-configuration) - Apache/Nginx setup
- [**SSL & Security**](DEPLOYMENT.md#ssl-certificate) - HTTPS configuration
- [**Monitoring & Backup**](DEPLOYMENT.md#monitoring--logging) - System monitoring

## Framework Overview

### Architecture

This framework follows the Model-View-Controller (MVC) pattern:

- **Models**: Handle data and business logic
- **Views**: Present data to users
- **Controllers**: Coordinate between models and views

### Key Features

✅ **MVC Architecture** - Clean separation of concerns  
✅ **Routing System** - Flexible URL routing with parameters  
✅ **Database ORM** - Simple but powerful database abstraction  
✅ **Security Features** - CSRF protection, input validation, encryption  
✅ **Environment Management** - Configuration through environment variables  
✅ **Request Handling** - Advanced request processing and validation  
✅ **Helper Functions** - Extensive utility function library  
✅ **Error Handling** - Comprehensive error management  
✅ **File Upload** - Secure file upload handling  
✅ **Session Management** - Secure session handling  

### Quick Example

#### 1. Define a Route
```php
// routes.php
$router->get('users', 'UserController@index');
$router->post('users', 'UserController@store');
```

#### 2. Create a Controller
```php
// app/controllers/UserController.php
class UserController
{
    public function index()
    {
        $users = (new UserModel())->all();
        return view('users/index', compact('users'));
    }
    
    public function store()
    {
        validate_csrf();
        $request = Request::getInstance();
        
        $errors = $request->validate([
            'name' => 'required|min:2',
            'email' => 'required|email'
        ]);
        
        if (empty($errors)) {
            (new UserModel())->create($request->only(['name', 'email']));
            return redirect('/users');
        }
        
        return view('users/create', compact('errors'));
    }
}
```

#### 3. Create a Model
```php
// app/models/UserModel.php
class UserModel extends BaseModel
{
    protected $table = 'users';
    protected $fillable = ['name', 'email'];
    
    protected function validate($data)
    {
        if (empty($data['email'])) {
            throw new \\Exception('Email is required');
        }
    }
}
```

#### 4. Create a View
```php
<!-- views/users/index.php -->
<!DOCTYPE html>
<html>
<head>
    <title>Users</title>
    <link href=\"../../public/assets/css/bootstrap.css\" rel=\"stylesheet\">
</head>
<body>
    <div class=\"container\">
        <h1>Users</h1>
        <?php foreach ($users as $user): ?>
            <div class=\"card\">
                <h5><?= htmlspecialchars($user->name) ?></h5>
                <p><?= htmlspecialchars($user->email) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
```

## Framework Components

### Core Classes

| Class | Purpose | Documentation |
|-------|---------|---------------|
| `Router` | URL routing and dispatching | [API Reference](API.md#routing) |
| `Request` | HTTP request handling | [API Reference](API.md#request-handling) |
| `BaseModel` | Database abstraction layer | [API Reference](API.md#models--database) |
| `Security` | Security utilities | [API Reference](API.md#security) |
| `Environment` | Configuration management | [API Reference](API.md#environment) |
| `Connection` | Database connection | [API Reference](API.md#models--database) |

### Helper Functions

| Function | Purpose | Example |
|----------|---------|----------|
| `view()` | Render view templates | `view('users/index', $data)` |
| `redirect()` | HTTP redirections | `redirect('/users')` |
| `csrf_field()` | CSRF protection | `echo csrf_field()` |
| `sanitize()` | Input sanitization | `$clean = sanitize($input)` |
| `env()` | Environment variables | `$debug = env('APP_DEBUG')` |
| `config()` | Configuration values | `$name = config('app.name')` |

### Directory Structure

```
project/
├── app/                 # Application logic
│   ├── controllers/     # Controllers
│   ├── models/         # Models
│   └── *.php          # Core classes
├── database/           # Database related
│   ├── migrations/    # Database migrations
│   └── Connection.php # DB connection
├── public/            # Web accessible files
│   └── assets/       # CSS, JS, images
├── views/            # View templates
├── docs/            # Documentation
├── .env            # Environment variables
├── config.php      # Configuration
├── routes.php      # Route definitions
└── index.php       # Entry point
```

## Development Workflow

### 1. Environment Setup
1. Copy `.env.example` to `.env`
2. Configure database settings
3. Generate application key
4. Run `composer install`

### 2. Create Features
1. Create migration for database schema
2. Create model for data handling
3. Create controller for business logic
4. Create views for presentation
5. Add routes for URL mapping

### 3. Security
1. Always validate user input
2. Use CSRF protection on forms
3. Sanitize output in views
4. Use prepared statements
5. Hash passwords securely

### 4. Testing
1. Test in development environment
2. Validate all user inputs
3. Check error handling
4. Verify security measures
5. Test database operations

### 5. Deployment
1. Set production environment
2. Configure web server
3. Set up SSL certificate
4. Configure monitoring
5. Set up backups

## Best Practices

### Code Organization
- Follow MVC pattern strictly
- Keep controllers thin
- Use meaningful names
- Write clear comments
- Group related functionality

### Security
- Validate all input
- Use CSRF tokens
- Sanitize output
- Use HTTPS in production
- Keep software updated

### Performance
- Use database indexes
- Implement caching
- Optimize queries
- Compress assets
- Monitor performance

### Database
- Use migrations
- Create proper indexes
- Use transactions
- Regular backups
- Monitor query performance

## Getting Help

### Documentation Structure
1. **Quick Start**: [Getting Started Guide](GETTING_STARTED.md)
2. **Reference**: [API Documentation](API.md)
3. **Security**: [Security Guide](SECURITY.md)
4. **Production**: [Deployment Guide](DEPLOYMENT.md)

### Common Issues
- **Permission errors**: Check file permissions
- **Database errors**: Verify credentials and connection
- **Routing issues**: Check route definitions
- **Security errors**: Verify CSRF tokens

### Support Resources
- Read the documentation thoroughly
- Check the example code in getting started
- Review security best practices
- Follow deployment guidelines

## Contributing

This framework is designed for educational purposes and rapid prototyping. Feel free to:

- Fork and modify the code
- Submit improvements
- Report issues
- Suggest new features
- Share your experiences

---

**Note**: This framework is intended for learning and small projects. For large-scale production applications, consider using established frameworks like Laravel, Symfony, or CodeIgniter.

**Happy Coding!** 🚀