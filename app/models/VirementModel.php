<?php

namespace App\Models;

class VirementModel
{


    public function addVirement($n_virement,$n_compte,$montant,$date){
        $query = "INSERT INTO virement (n_virement,n_compte, montant, date) VALUES (?,?,?,?)";
        try {
            $statement = connect()->prepare($query);
            $statement->execute([$n_virement,$n_compte,$montant,$date]);
            session_start();
            $_SESSION['message'] = "Ajout virement avec succès";
        } catch (\SQLiteException $exception){
            $_SESSION['message'] = "Erreur ajout virement, verifier les informations";
        }
    }

    public function updateVirement($n_virement,$n_compte,$montant,$date)
    {
        $query = "UPDATE virement SET n_compte=?, montant=? , date=? WHERE n_virement=?";
        try {
            $statement = connect()->prepare($query);
            $statement->execute([$n_compte,$montant,$date,$n_virement]);
            session_start();
            $_SESSION['message'] = "Update virement avec succès";
        } catch (\PDOException $exception) {
            die($exception->getMessage());
        }
    }

    public function delete($id)
    {
        $id = $_POST['n_virement'];
        $query = "DELETE FROM virement WHERE n_virement=?";

        try {
            $statement = connect()->prepare($query);
            $statement->execute([$id]);
            session_start();
            $_SESSION['message'] = "Suppression virement avec succès";
            header("Location: /virement");
        } catch (\PDOException $exception) {
            die($exception->getMessage());
        }
    }

    public function getAllVirements()
    {
        $query = "SELECT * FROM virement";
        try {
            $statement = connect()->prepare($query);
            $statement->execute();
            return $statement->fetchAll(\PDO::FETCH_OBJ);
        } catch (\SQLiteException $exception) {
            die($exception->getMessage());
        }
    }
}