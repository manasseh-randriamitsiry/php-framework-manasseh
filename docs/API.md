# Framework API Reference

## Table of Contents

1. [Routing](#routing)
2. [Request Handling](#request-handling)
3. [Models & Database](#models--database)
4. [Controllers](#controllers)
5. [Security](#security)
6. [Environment](#environment)
7. [Helper Functions](#helper-functions)

## Routing

### Basic Routes

```php
// routes.php
$router->get('/', 'HomeController@index');
$router->post('/users', 'UserController@store');
$router->put('/users/{id}', 'UserController@update');
$router->delete('/users/{id}', 'UserController@destroy');
$router->patch('/users/{id}', 'UserController@patch');
```

### Route Parameters

```php
// Single parameter
$router->get('/users/{id}', 'UserController@show');

// Multiple parameters
$router->get('/users/{id}/posts/{postId}', 'PostController@show');

// Controller receives parameters
public function show($id, $postId = null) {
    // Use $id and $postId
}
```

### Middleware

```php
// Route-specific middleware
$router->get('/admin', 'AdminController@index')
       ->middleware('auth');

// Global middleware
$router->middleware('csrf');
```

## Request Handling

### Getting Input

```php
use App\Request;

// Get all input
$request = Request::getInstance();
$data = $request->all();

// Get specific fields
$name = Request::get('name', 'default');
$email = Request::get('email', null, 'email'); // with filter

// Get only specific fields
$userData = $request->only(['name', 'email']);

// Get except specific fields
$data = $request->except(['password']);
```

### Input Validation

```php
$errors = $request->validate([
    'name' => 'required|min:2|max:255',
    'email' => 'required|email',
    'age' => 'required|numeric'
]);

// Available rules: required, email, min:n, max:n, numeric
```

### File Uploads

```php
if ($request->hasFile('avatar')) {
    $file = $request->file('avatar');
    $errors = Security::validateFileUpload($file);
}
```

### Request Information

```php
$method = Request::method();    // GET, POST, etc.
$uri = Request::uri();          // current URI
$ip = $request->ip();           // client IP
$isAjax = $request->isAjax();   // AJAX request?
$isSecure = $request->isSecure(); // HTTPS?
```

## Models & Database

### Model Structure

```php
class UserModel extends BaseModel
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $fillable = ['name', 'email'];
    protected $casts = [
        'id' => 'int',
        'active' => 'boolean'
    ];
    
    protected function validate($data)
    {
        if (empty($data['email'])) {
            throw new \Exception('Email required');
        }
    }
}
```

### CRUD Operations

```php
$model = new UserModel();

// Create
$user = $model->create(['name' => 'John', 'email' => 'john@example.com']);

// Read
$user = $model->find(1);
$users = $model->all('name', 'ASC');

// Update
$user = $model->update(1, ['name' => 'Jane']);

// Delete
$success = $model->delete(1);
```

### Query Methods

```php
// Find by criteria
$active = $model->where('active', 1);
$search = $model->where('name', 'LIKE', '%john%');

// Count records
$total = $model->count();

// Raw queries
$results = $model->query('SELECT * FROM users WHERE age > ?', [18]);
```

### Transactions

```php
$model->beginTransaction();
try {
    $model->create($data1);
    $model->create($data2);
    $model->commit();
} catch (Exception $e) {
    $model->rollback();
}
```

## Controllers

### Basic Controller

```php
namespace App\Controllers;

use App\Models\UserModel;
use App\Request;

class UserController
{
    protected $userModel;
    
    public function __construct()
    {
        $this->userModel = new UserModel();
    }
    
    public function index()
    {
        $users = $this->userModel->all();
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
            $this->userModel->create($request->only(['name', 'email']));
            return redirect('/users');
        }
        
        return view('users/create', compact('errors'));
    }
}
```

## Security

### CSRF Protection

```php
// Generate token
$token = csrf_token();

// Form field
echo csrf_field();

// Validate
validate_csrf();
```

### Input Sanitization

```php
$clean = sanitize($input);        // Basic sanitization
$html = sanitize($input, true);   // Allow some HTML
```

### Password Handling

```php
$hash = Security::hashPassword($password);
$valid = Security::verifyPassword($password, $hash);
```

### Rate Limiting

```php
if (!Security::checkRateLimit('login_' . $ip, 5, 300)) {
    abort(429, 'Too many attempts');
}
```

### Encryption

```php
$encrypted = Security::encrypt($data);
$decrypted = Security::decrypt($encrypted);
```

## Environment

### Configuration

```php
// Load environment
Environment::load();

// Get values
$debug = env('APP_DEBUG', false);
$dbHost = Environment::get('DB_HOST', 'localhost');

// Check environment
if (Environment::isDevelopment()) {
    // Development code
}
```

### Database Config

```php
$config = Environment::getDatabaseConfig();
```

## Helper Functions

### Debug Helpers

```php
dd($data);              // Dump and die
dump($data);            // Dump without dying
```

### View Helpers

```php
view('template', $data); // Render view
redirect('/path');       // Redirect to path
```

### Session Helpers

```php
session('key');          // Get session value
flash('message');        // Get flash message
old('field');           // Get old input
```

### Security Helpers

```php
csrf_token();           // Generate CSRF token
csrf_field();           // CSRF form field
sanitize($input);       // Sanitize input
validate_csrf();        // Validate CSRF
```

### Configuration Helpers

```php
config('app.name');     // Get config value
env('APP_DEBUG');       // Get environment variable
```

### URL Helpers

```php
asset('css/app.css');   // Asset URL
url('/users');          // Generate URL
```

### Error Helpers

```php
abort(404);             // Abort with status
abort(500, 'Custom message');
```

### Database Helper

```php
connect();              // Get database connection
```

## Error Handling

### HTTP Errors

```php
abort(404, 'Not found');
abort(403, 'Forbidden');
abort(500, 'Server error');
```

### Exception Handling

```php
try {
    // risky operation
} catch (Exception $e) {
    if (env('APP_DEBUG')) {
        dd($e);
    } else {
        abort(500);
    }
}
```

## Validation Rules

| Rule | Description | Example |
|------|-------------|----------|
| `required` | Field must not be empty | `'name' => 'required'` |
| `email` | Must be valid email | `'email' => 'email'` |
| `min:n` | Minimum length | `'name' => 'min:2'` |
| `max:n` | Maximum length | `'name' => 'max:255'` |
| `numeric` | Must be numeric | `'age' => 'numeric'` |

## Response Types

### View Response

```php
return view('template', ['data' => $value]);
```

### Redirect Response

```php
return redirect('/path');
header('Location: /path');
```

### JSON Response

```php
header('Content-Type: application/json');
echo json_encode(['status' => 'success']);
```

## Configuration

### App Configuration

```php
$name = config('app.name');
$debug = config('app.debug');
```

### Database Configuration

```php
$host = config('database.host');
$name = config('database.dbname');
```

### Security Configuration

```php
$tokenName = config('security.csrf_token_name');
$lifetime = config('security.session_lifetime');
```