<?php
use App\Router;
use App\Request;

CreateClient::createClientTable(connect());
CreateVirement::createVirementTable(connect());
CreateAuditVirement::createAuditVirementTable(connect());

Router::load("routes.php")
    ->show(Request::uri(),Request::method());
