<?php
Class CreateClient {
    public static function createClientTable($pdo)
    {
        try {
            $query= "USE `virement`;CREATE TABLE IF NOT EXISTS `virement`.`client` ( `n_compte` INT(20) NOT NULL , `nom` VARCHAR(255) NOT NULL , `solde` INT(10) NOT NULL , UNIQUE `n_compte` (`n_compte`), UNIQUE `nom` (`nom`))";
            $statement = $pdo->prepare($query);
            $statement->execute();
        } catch (Throwable $throwable){
            die($throwable->getMessage());
        }
    }
}