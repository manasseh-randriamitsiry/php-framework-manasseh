<?php
use App\App;
use Database\Connection;

if (!function_exists('dd')){
    function dd($data){
        echo '<pre>';
        die(var_dump($data));
        echo '</pre>';
    }
}
if (!function_exists('connect')){
    function connect(){
        App::bind('config',require "config.php");
        return Connection::makeConnection(
            App::get('config')['database']
        );
    }
}
if (!function_exists('view')){
    function view($view,$data=NULL){
        require "views/{$view}.php";
    }

}