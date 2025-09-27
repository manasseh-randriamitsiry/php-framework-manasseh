<?php
namespace App\Models;
use App\Request;

class AuditModel
{
    public function delete($id)
    {
        $id = $_POST['id'];
        $query = "DELETE FROM audit_virement WHERE id=?";
        try {
            $statement = connect()->prepare($query);
            $statement->execute([$id]);
        } catch (\PDOException $exception) {
            die($exception->getMessage());
        }
    }

}
