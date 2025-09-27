# Getting Started Guide

## Quick Start

### 1. Installation

```bash
# Clone the project
git clone <repository-url>
cd your-project-directory

# Install dependencies
composer install
```

### 2. Environment Setup

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php -r "echo 'APP_KEY=' . bin2hex(random_bytes(16)) . PHP_EOL;"
```

### 3. Database Configuration

Edit `.env` file:

```env
DB_HOST=127.0.0.1
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 4. Create Your Database

```sql
CREATE DATABASE your_database_name;
```

### 5. Run the Application

```bash
# Start development server
php -S localhost:8000
```

Visit `http://localhost:8000` in your browser.

## Project Structure

```
.
├── app/
│   ├── controllers/     # Application controllers
│   ├── models/         # Data models
│   ├── App.php        # Application container
│   ├── Request.php    # HTTP request handling
│   ├── Router.php     # URL routing
│   ├── Security.php   # Security utilities
│   ├── Environment.php # Environment management
│   └── helpers.php    # Helper functions
├── database/
│   ├── migrations/    # Database migrations
│   └── Connection.php # Database connection
├── public/
│   └── assets/       # Static assets (CSS, JS, images)
├── views/            # View templates
├── docs/            # Documentation
├── config.php       # Configuration file
├── routes.php       # Route definitions
└── index.php        # Application entry point
```

## Creating Your First Module

### 1. Create a Migration

```php
<?php
// database/migrations/CreatePostsTable.php
class CreatePostsTable
{
    public static function createPostsTable($pdo)
    {
        try {
            $createTableQuery = "
                CREATE TABLE IF NOT EXISTS posts (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    content TEXT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ";
            $pdo->query($createTableQuery);
        } catch (Throwable $throwable) {
            die($throwable->getMessage());
        }
    }
}
```

### 2. Create a Model

```php
<?php
// app/models/PostModel.php
namespace App\Models;

class PostModel extends BaseModel
{
    protected $table = 'posts';
    protected $fillable = ['title', 'content'];
    
    protected function validate($data)
    {
        if (empty($data['title'])) {
            throw new \Exception('Title is required');
        }
        
        if (empty($data['content'])) {
            throw new \Exception('Content is required');
        }
        
        return true;
    }
}
```

### 3. Create a Controller

```php
<?php
// app/controllers/PostController.php
namespace App\Controllers;

use App\Models\PostModel;
use App\Request;

class PostController
{
    protected $postModel;
    
    public function __construct()
    {
        $this->postModel = new PostModel();
    }
    
    public function index()
    {
        $posts = $this->postModel->all('created_at', 'DESC');
        return view('posts/index', compact('posts'));
    }
    
    public function show($id)
    {
        $post = $this->postModel->find($id);
        if (!$post) {
            abort(404, 'Post not found');
        }
        return view('posts/show', compact('post'));
    }
    
    public function create()
    {
        return view('posts/create');
    }
    
    public function store()
    {
        validate_csrf();
        
        $request = Request::getInstance();
        $errors = $request->validate([
            'title' => 'required|min:3|max:255',
            'content' => 'required|min:10'
        ]);
        
        if (!empty($errors)) {
            return view('posts/create', compact('errors'));
        }
        
        try {
            $postData = $request->only(['title', 'content']);
            $this->postModel->create($postData);
            return redirect('/posts');
        } catch (Exception $e) {
            $error = $e->getMessage();
            return view('posts/create', compact('error'));
        }
    }
}
```

### 4. Create Views

```php
<!-- views/posts/index.php -->
<!DOCTYPE html>
<html>
<head>
    <title>Posts</title>
    <link href="../../public/assets/css/bootstrap.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h1>Posts</h1>
        <a href="/posts/create" class="btn btn-primary">Create Post</a>
        
        <div class="row mt-3">
            <?php foreach ($posts as $post): ?>
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($post->title) ?></h5>
                            <p class="card-text"><?= substr(htmlspecialchars($post->content), 0, 100) ?>...</p>
                            <a href="/posts/<?= $post->id ?>" class="btn btn-primary">Read More</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
```

```php
<!-- views/posts/create.php -->
<!DOCTYPE html>
<html>
<head>
    <title>Create Post</title>
    <link href="../../../public/assets/css/bootstrap.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h1>Create Post</h1>
        
        <?php if (isset($errors) && !empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $field => $fieldErrors): ?>
                        <?php foreach ($fieldErrors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="/posts">
            <?= csrf_field() ?>
            
            <div class="mb-3">
                <label for="title" class="form-label">Title</label>
                <input type="text" class="form-control" id="title" name="title" value="<?= old('title') ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="content" class="form-label">Content</label>
                <textarea class="form-control" id="content" name="content" rows="5" required><?= old('content') ?></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary">Create Post</button>
            <a href="/posts" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</body>
</html>
```

### 5. Add Routes

```php
// routes.php
if (!empty($router)) {
    // ... existing routes ...
    
    // Posts routes
    $router->get('posts', 'PostController@index');
    $router->get('posts/create', 'PostController@create');
    $router->post('posts', 'PostController@store');
    $router->get('posts/{id}', 'PostController@show');
}
```

### 6. Run Migration

Add to `initiate.php`:

```php
// initiate.php
CreatePostsTable::createPostsTable(connect());
```

### 7. Update Autoloader

```bash
composer dump-autoload
```

## Common Patterns

### Form Handling with Validation

```php
public function store()
{
    validate_csrf();
    
    $request = Request::getInstance();
    $errors = $request->validate([
        'field' => 'required|min:2|max:255'
    ]);
    
    if (!empty($errors)) {
        return view('form', compact('errors'));
    }
    
    // Process valid data
}
```

### Flash Messages

```php
// In controller
session_start();
$_SESSION['success'] = 'Operation completed successfully';
return redirect('/path');

// In view
<?php if ($message = flash('success')): ?>
    <div class="alert alert-success"><?= $message ?></div>
<?php endif; ?>
```

### Error Handling

```php
try {
    // Risky operation
    $result = $model->create($data);
} catch (Exception $e) {
    if (env('APP_DEBUG')) {
        dd($e);
    } else {
        abort(500, 'Something went wrong');
    }
}
```

## Next Steps

1. Read the [API Documentation](API.md) for detailed function references
2. Check [Security Best Practices](SECURITY.md) for production deployment
3. Explore [Advanced Features](ADVANCED.md) for complex use cases
4. Review [Deployment Guide](DEPLOYMENT.md) for production setup