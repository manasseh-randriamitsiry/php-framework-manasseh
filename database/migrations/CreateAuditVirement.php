<?php

class CreateAuditVirement
{
    public static function createAuditVirementTable($pdo)
    {
        try {
            // Create audit_virement table
            $createTableQuery = "
                USE `virement`;
                CREATE TABLE IF NOT EXISTS `virement`.`audit_virement` (
                  `id` INT(50) NOT NULL AUTO_INCREMENT,
                  `type_action` VARCHAR(50) NOT NULL,
                  `date_operation` DATE NOT NULL,
                  `n_virement` INT(255) NOT NULL,
                  `n_compte` INT(255) NOT NULL,
                  `nom_client` VARCHAR(255) NOT NULL,
                  `date_virement` DATE NOT NULL,
                  `montant_ancien` INT(50) NOT NULL,
                  `montant_nouv` INT(50) NOT NULL,
                  `utilisateur` VARCHAR(255) NOT NULL,
                  PRIMARY KEY (`id`)
                );
            ";

            $pdo->query($createTableQuery);

            // Create after_virement_update trigger
            $afterUpdateTrigger = "
                CREATE TRIGGER IF NOT EXISTS after_virement_update
                AFTER UPDATE ON virement
                FOR EACH ROW
                BEGIN
                  DECLARE montant_diff INT;
                  SET montant_diff = NEW.montant - OLD.montant;

                  UPDATE client
                  SET solde = solde + montant_diff
                  WHERE n_compte = NEW.n_compte;

                  INSERT INTO audit_virement (type_action, date_operation, n_virement, n_compte, nom_client, date_virement, montant_ancien, montant_nouv, utilisateur)
                  VALUES (
                    'UPDATE',
                    CURRENT_TIMESTAMP,
                    NEW.n_virement,
                    NEW.n_compte,
                    (SELECT nom FROM client WHERE n_compte = NEW.n_compte),
                    NEW.date,
                    OLD.montant,
                    NEW.montant,
                    'manasse'
                  );
                END;
            ";

            $pdo->query($afterUpdateTrigger);

            // Create after_virement_delete trigger
            $afterDeleteTrigger = "
            CREATE TRIGGER IF NOT EXISTS after_virement_delete
                AFTER DELETE ON virement
                FOR EACH ROW
                BEGIN
                  DECLARE montant_diff INT;
                  SET montant_diff = OLD.montant;

                  UPDATE client
                  SET solde = solde - montant_diff
                  WHERE n_compte = OLD.n_compte;

                  INSERT INTO audit_virement (type_action, date_operation, n_virement, n_compte, nom_client, date_virement, montant_ancien, montant_nouv, utilisateur)
                  VALUES (
                    'DELETE',
                    CURRENT_TIMESTAMP,
                    OLD.n_virement,
                    OLD.n_compte,
                    (SELECT nom FROM client WHERE n_compte = OLD.n_compte),
                    OLD.date,
                    OLD.montant,
                    OLD.montant,
                    'manasse'
                  );
                END;
            ";

            $pdo->query($afterDeleteTrigger);

            // Create after_virement_insert trigger
            $afterInsertTrigger = "
                CREATE TRIGGER IF NOT EXISTS after_virement_insert
                AFTER INSERT ON virement
                FOR EACH ROW
                BEGIN
                  DECLARE montant_ancien INT;
                  SELECT solde INTO montant_ancien FROM client WHERE n_compte = NEW.n_compte;

                  UPDATE client
                  SET solde = montant_ancien + NEW.montant
                  WHERE n_compte = NEW.n_compte;

                  INSERT INTO audit_virement (type_action, date_operation, n_virement, n_compte, nom_client, date_virement, montant_ancien, montant_nouv, utilisateur)
                  VALUES (
                    'INSERT',
                    CURRENT_TIMESTAMP,
                    NEW.n_virement,
                    NEW.n_compte,
                    (SELECT nom FROM client WHERE n_compte = NEW.n_compte),
                    NEW.date,
                    montant_ancien,
                    montant_ancien + NEW.montant,
                    'manasse'
                  );
                END;
            ";

            $pdo->query($afterInsertTrigger);
        } catch (Throwable $throwable) {
            die($throwable->getMessage());
        }
    }
}
