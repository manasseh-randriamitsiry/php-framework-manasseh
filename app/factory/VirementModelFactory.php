<?php


use App\Models\VirementModel;

class VirementModelFactory
{
    public static function create($n_virement, $n_compte, $montant, $date)
    {
        return new VirementModel($n_virement, $n_compte, $montant, $date);
    }
}
