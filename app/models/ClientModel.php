<?php


namespace App\Models;
use App\Request;

class ClientModel
{
    public function insertClient($n_compte,$nom_client,$solde){
        $query = "INSERT INTO client (n_compte, nom, solde) VALUES (?,?,?)";
        try {
            $statement = connect()->prepare($query);
            $statement->execute([$n_compte,$nom_client,$solde]);
            session_start();
            $_SESSION['message'] = "Ajout client avec succès";
        } catch (\PDOException $exception){
            die("le numero de compte est unique, mais il semble qu'il ya dejà un dans le BD");
        }
    }


    public function updateClient($n_compte,$nom_client,$solde)
    {
        $query = "UPDATE client SET nom=?, solde=? WHERE n_compte=?";
        try {
            $statement = connect()->prepare($query);
            $statement->execute([$nom_client, $solde, $n_compte]);
            session_start();
            $_SESSION['message'] = "Update client avec succès";
        } catch (\PDOException $exception) {
            die($exception->getMessage());
        }
    }
    public function delete($id)
    {
        $id = $_POST['n_compte'];
        $query = "DELETE FROM client WHERE n_compte=?";

        try {
            $statement = connect()->prepare($query);
            $statement->execute([$id]);
            session_start();
            $_SESSION['message'] = "Suppression client avec succès";
            header("Location: /client");
        } catch (\PDOException $exception) {
            die($exception->getMessage());
        }
    }

    public function getAllClients()
    {
        $query = "SELECT * FROM client";
        try {
            $statement = connect()->prepare($query);
            $statement->execute();
            return $statement->fetchAll(\PDO::FETCH_OBJ);
        } catch (\SQLiteException $exception) {
            die($exception->getMessage());
        }
    }
}