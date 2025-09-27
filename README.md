# PHP MVC Framework

*By Randriamitsiry Valimbavaka Manassé - 2024*

A lightweight PHP MVC framework designed for learning and rapid prototyping. This framework provides a simple yet powerful structure for building web applications with MySQL database integration.

## Features

- **MVC Architecture**: Clean separation of concerns with Models, Views, and Controllers
- **Simple Routing**: Easy-to-define routes with parameter support
- **Database Integration**: MySQL database connection with PDO
- **Autoloading**: PSR-4 compatible autoloading with Composer
- **Request Handling**: Built-in request processing and validation
- **Migration System**: Database schema management
- **Helper Functions**: Useful utility functions for development

## Requirements

- PHP 7.4 or higher
- Composer
- MySQL 5.7 or higher
- Web server (Apache/Nginx)

## Installation

1. Clone the repository:
```bash
git clone <repository-url>
cd your-project-directory
```

2. Install dependencies:
```bash
composer install
```

3. Configure database:
   - Copy `config.php` and update database credentials
   - Create your MySQL database
   - Run the application to execute migrations

4. Set up web server:
   - Point your web server document root to the project directory
   - Ensure URL rewriting is enabled

## Configuration

### Database Configuration

Edit `config.php` to configure your database connection:

```php
return [
    'database' => [
        'host' => '127.0.0.1',
        'dbname' => 'your_database_name',
        'user' => 'your_username',
        'password' => 'your_password'
    ]
];
```

## Quick Start

### Creating a New Module

#### 1. Create a Migration

Create a new migration class in `/database/migrations/`:

```php
<?php
class CreateYourTable
{
    public static function createYourTableTable($pdo)
    {
        try {
            $createTableQuery = "
                CREATE TABLE IF NOT EXISTS your_table (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ";
            $pdo->query($createTableQuery);
        } catch (Throwable $throwable) {
            die($throwable->getMessage());
        }
    }
}
```

#### 2. Create a Model

Create a model in `/app/models/`:

```php
<?php
namespace App\Models;

class YourModel
{
    public function getAll()
    {
        $query = "SELECT * FROM your_table";
        $statement = connect()->prepare($query);
        $statement->execute();
        return $statement->fetchAll(\PDO::FETCH_OBJ);
    }
    
    public function create($data)
    {
        $query = "INSERT INTO your_table (name) VALUES (?)";
        $statement = connect()->prepare($query);
        return $statement->execute([$data['name']]);
    }
}
```

#### 3. Create a Controller

Create a controller in `/app/controllers/`:

```php
<?php
namespace App\Controllers;

use App\Models\YourModel;

class YourController
{
    protected $yourModel;
    
    public function __construct()
    {
        $this->yourModel = new YourModel();
    }
    
    public function index()
    {
        $data = $this->yourModel->getAll();
        return view('your/index', ['data' => $data]);
    }
    
    public function store()
    {
        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $this->yourModel->create(['name' => $name]);
        header('Location: /your-route');
    }
}
```

#### 4. Create a View

Create a view in `/views/your/index.php`:

```php
<!DOCTYPE html>
<html>
<head>
    <title>Your Page</title>
    <link href="../../public/assets/css/bootstrap.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h1>Your Data</h1>
        <ul>
            <?php foreach ($data as $item): ?>
                <li><?= htmlspecialchars($item->name) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</body>
</html>
```

#### 5. Add Routes

Add routes to `routes.php`:

```php
$router->get('your-route', 'YourController@index');
$router->post('your-route', 'YourController@store');
```

#### 6. Execute Migration

Add your migration to `initiate.php`:

```php
CreateYourTable::createYourTableTable(connect());
```

### Autoloading New Classes

After adding new classes, regenerate the autoload files:

```bash
composer dump-autoload
```

## Framework Structure

```
.
├── app/
│   ├── controllers/     # Application controllers
│   ├── models/         # Data models
│   ├── factory/        # Model factories
│   ├── App.php        # Application container
│   ├── Request.php    # HTTP request handling
│   ├── Router.php     # URL routing
│   └── helpers.php    # Helper functions
├── database/
│   ├── migrations/    # Database migrations
│   └── Connection.php # Database connection
├── public/
│   └── assets/       # Static assets (CSS, JS, images)
├── views/            # View templates
├── config.php        # Configuration file
├── routes.php        # Route definitions
├── initiate.php      # Application initialization
└── index.php         # Application entry point
```

## Available Helper Functions

- `dd($data)` - Debug and die (dump variable and stop execution)
- `connect()` - Get database connection
- `view($view, $data)` - Render a view template

## Security Notes

- Always use prepared statements for database queries
- Sanitize user input using `filter_input()` functions
- Use HTTPS in production
- Keep database credentials secure
- Validate and escape output in views

## Contributing

This framework is designed for educational purposes and experimentation. Feel free to:

- Fork and modify the code
- Submit improvements
- Report issues
- Suggest new features

## License

This project is open source and available for educational use. No warranty is provided for production use.

## Support

For questions or support, please refer to the source code documentation or create an issue in the repository.



