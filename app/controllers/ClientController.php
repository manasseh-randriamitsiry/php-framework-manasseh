<?php
namespace App\Controllers;

use App\Models\ClientModel;

class ClientController
{
    protected $clientModel;

    public function __construct()
    {
        $this->clientModel = new ClientModel();
    }

    public function index()
    {
        return view("indexView");
    }

    public function client()
    {
        $clients = $this->getAllClients();
        return view("client/clientView", ['clients' => $clients]);
    }

    public function addClient()
    {
        $n_compte = filter_input(INPUT_POST, 'n_compte', FILTER_SANITIZE_NUMBER_INT);
        $nom_client = filter_input(INPUT_POST, 'nom_client', FILTER_SANITIZE_STRING);
        $solde = filter_input(INPUT_POST, 'solde', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

        $this->clientModel->insertClient($n_compte, $nom_client, $solde);
        header("Location: /client");
    }

    public function editClient()
    {
        $n_compte = filter_input(INPUT_POST, 'n_compte', FILTER_SANITIZE_NUMBER_INT);
        $nom_client = filter_input(INPUT_POST, 'nom_client', FILTER_SANITIZE_STRING);
        $solde = filter_input(INPUT_POST, 'solde', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

        $this->clientModel->updateClient($n_compte, $nom_client, $solde);
        header("Location: /client");
    }

    public function deleteClient()
    {
        $n_compte = $_POST['n_compte'];
        $this->clientModel->delete($n_compte);
        header("Location: /client");
    }

    protected function getAllClients()
    {
        return $this->clientModel->getAllClients();
    }
}
