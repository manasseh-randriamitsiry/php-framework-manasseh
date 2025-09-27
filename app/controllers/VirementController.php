<?php
namespace App\Controllers;
use App\Models\VirementModel;
use App\Models\ClientModel;

class VirementController{
    protected $virementModel;
    protected $clientModel;
    public function __construct()
    {
        $this->virementModel = new VirementModel();
        $this->clientModel = new ClientModel();
    }

    public function virement()
    {
        return view("virements/virementView");
    }

    // crud
    public function addVirement()
    {
        $n_virement = filter_input(INPUT_POST, 'n_virement', FILTER_SANITIZE_NUMBER_INT);
        $montant = filter_input(INPUT_POST, 'montant', FILTER_SANITIZE_NUMBER_INT);
        $date_virement = filter_input(INPUT_POST, 'date_virement');
        $n_compte = filter_input(INPUT_POST, 'n_compte', FILTER_SANITIZE_NUMBER_INT);

        $this->virementModel->addVirement($n_virement, $n_compte, $montant,$date_virement);
        header("Location: /virement");
    }

    public function editVirement()
    {
        $n_virement = filter_input(INPUT_POST, 'n_virement', FILTER_SANITIZE_NUMBER_INT);
        $montant = filter_input(INPUT_POST, 'montant', FILTER_SANITIZE_NUMBER_INT);
        $date_virement = filter_input(INPUT_POST, 'date_virement');
        $n_compte = filter_input(INPUT_POST, 'n_compte', FILTER_SANITIZE_NUMBER_INT);

        $this->virementModel->updateVirement($n_virement, $n_compte, $montant,$date_virement);

        header("Location: /virement");
    }
    public function deleteVirement()
    {
        $n_virement = filter_input(INPUT_POST, 'n_virement', FILTER_SANITIZE_NUMBER_INT);

        $this->virementModel->delete($n_virement);

        header("Location: /virement");
    }


}
