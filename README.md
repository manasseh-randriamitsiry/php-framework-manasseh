#By Randriamitsiry valimbavaka Manassé @2024

## Installation

```
clone this project
cd _your_project_directory
composer install
```
# Ao anaty config.php no anovana ny resaka base de donnee (user,password,dbname) ! zany oe creena aloh reo de ovaina le existant ao reo

## Requirements

* PHP 7+
* Composer

## notes if class error
after adding new classes and make it autoload
run:
```
composer dump-autoload
```

# how to add new routes and views xD
## create a table
* Edit the config.php
* create a class iside : /database/migrations
```
    public static function _YourFunction($pdo)
        {
            try {
                $createTableQuery = " _Query to create a table";
                $pdo->query($createTableQuery);
            } catch (Throwable $throwable) {
            die($throwable->getMessage());
            }
        }
```

* Execute the migration with initiate.php
```
    _YourClass::_YourFunction(connect());
```

## create the view and routing
* create a controller
* create a view
* all styles and others are inside /public, its not yet automatically so always point your link to:
```
href= '../../public/_YourFile'
```
* add your route to routes.php
```
    $router->post or get('_routePath','_YourController@_MethodeToHandleInController');
    if we want to show a view, use get(), its do not matter if it have the same route like the post
```
    we already explode it from App/Router.php so if you want to edit the routing style, just edit this file


## note that this is just a project for experience, so no guarantee if you use it. But you are free to make some editions.



