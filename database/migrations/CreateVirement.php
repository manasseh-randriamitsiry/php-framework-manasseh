<?php

class CreateVirement
{
    public static function createVirementTable($pdo)
    {
        try {
            // Create virement table
            $queryCreateTable = "
                CREATE TABLE IF NOT EXISTS `virement`.`virement` (
                  `n_virement` INT(50) NOT NULL,
                  `n_compte` INT(20) NOT NULL,
                  `montant` INT(10) NOT NULL,
                  `date` DATE NOT NULL,
                  PRIMARY KEY (`n_virement`),
                  FOREIGN KEY (`n_compte`) REFERENCES `client`(`n_compte`)
                );
            ";

            $pdo->query($queryCreateTable);

        } catch (Throwable $throwable) {
            die($throwable->getMessage());
        }
    }
}
