<?php
if (!empty($router)) {
    $router->get('','ClientController@index');

    // client
    $router->post('addClient','ClientController@addClient');
    $router->post('editClient','ClientController@editClient');
    $router->post('deleteClient','ClientController@deleteClient');

    $router->get('client','ClientController@client');

    // audit
    $router->get('audit','AuditController@audit');
    $router->post('deleteAudit','AuditController@deleteAudit');

    // virements
    $router->post('addVirement','VirementController@addVirement');
    $router->post('editVirement','VirementController@editVirement');
    $router->post('deleteVirement','VirementController@deleteVirement');

    $router->get('virement','VirementController@virement');

}