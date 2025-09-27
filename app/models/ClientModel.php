<?php

namespace App\Models;

use App\Request;
use PDOException;

class ClientModel extends BaseModel
{
    protected $table = 'client';
    protected $primaryKey = 'n_compte';
    protected $fillable = ['n_compte', 'nom', 'solde'];
    protected $casts = [
        'n_compte' => 'int',
        'solde' => 'float'
    ];
    protected $timestamps = false; // This table doesn't have timestamps
    
    /**
     * Validation rules for client data
     */
    protected function validate($data)
    {
        $errors = [];
        
        // Validate account number
        if (empty($data['n_compte'])) {
            $errors[] = "Account number is required";
        } elseif (!is_numeric($data['n_compte'])) {
            $errors[] = "Account number must be numeric";
        } elseif ($data['n_compte'] <= 0) {
            $errors[] = "Account number must be positive";
        }
        
        // Validate client name
        if (empty($data['nom'])) {
            $errors[] = "Client name is required";
        } elseif (strlen($data['nom']) < 2) {
            $errors[] = "Client name must be at least 2 characters";
        } elseif (strlen($data['nom']) > 255) {
            $errors[] = "Client name must not exceed 255 characters";
        }
        
        // Validate balance
        if (!isset($data['solde'])) {
            $errors[] = "Balance is required";
        } elseif (!is_numeric($data['solde'])) {
            $errors[] = "Balance must be numeric";
        } elseif ($data['solde'] < 0) {
            $errors[] = "Balance cannot be negative";
        }
        
        if (!empty($errors)) {
            throw new \Exception("Validation failed: " . implode(', ', $errors));
        }
        
        return true;
    }
    
    /**
     * Insert a new client with enhanced validation and error handling
     */
    public function insertClient($n_compte, $nom_client, $solde)
    {
        try {
            // Sanitize input data
            $data = [
                'n_compte' => filter_var($n_compte, FILTER_SANITIZE_NUMBER_INT),
                'nom' => filter_var($nom_client, FILTER_SANITIZE_STRING),
                'solde' => filter_var($solde, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION)
            ];
            
            // Check if account number already exists
            if ($this->accountExists($data['n_compte'])) {
                throw new \Exception("Account number {$data['n_compte']} already exists");
            }
            
            // Use the base model's create method
            $result = $this->create($data);
            
            if ($result) {
                $this->setSuccessMessage("Client added successfully");
                return $result;
            }
            
            throw new \Exception("Failed to create client");
            
        } catch (\Exception $e) {
            $this->setErrorMessage($e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Update client with enhanced validation
     */
    public function updateClient($n_compte, $nom_client, $solde)
    {
        try {
            // Sanitize input data
            $data = [
                'nom' => filter_var($nom_client, FILTER_SANITIZE_STRING),
                'solde' => filter_var($solde, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION)
            ];
            
            // Check if client exists
            if (!$this->accountExists($n_compte)) {
                throw new \Exception("Client with account number {$n_compte} not found");
            }
            
            // Use the base model's update method
            $result = $this->update($n_compte, $data);
            
            if ($result) {
                $this->setSuccessMessage("Client updated successfully");
                return $result;
            }
            
            throw new \Exception("Failed to update client");
            
        } catch (\Exception $e) {
            $this->setErrorMessage($e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Delete client with enhanced error handling
     */
    public function deleteClient($n_compte)
    {
        try {
            // Sanitize input
            $n_compte = filter_var($n_compte, FILTER_SANITIZE_NUMBER_INT);
            
            // Check if client exists
            if (!$this->accountExists($n_compte)) {
                throw new \Exception("Client with account number {$n_compte} not found");
            }
            
            // Check if client has any virements (foreign key constraint)
            if ($this->hasVirements($n_compte)) {
                throw new \Exception("Cannot delete client with existing transactions");
            }
            
            // Use the base model's delete method
            $result = $this->delete($n_compte);
            
            if ($result) {
                $this->setSuccessMessage("Client deleted successfully");
                return true;
            }
            
            throw new \Exception("Failed to delete client");
            
        } catch (\Exception $e) {
            $this->setErrorMessage($e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get all clients with optional filtering and sorting
     */
    public function getAllClients($orderBy = 'nom', $direction = 'ASC')
    {
        try {
            return $this->all($orderBy, $direction);
        } catch (\Exception $e) {
            $this->setErrorMessage("Failed to retrieve clients: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get client by account number
     */
    public function getClientByAccount($n_compte)
    {
        try {
            $n_compte = filter_var($n_compte, FILTER_SANITIZE_NUMBER_INT);
            return $this->find($n_compte);
        } catch (\Exception $e) {
            $this->setErrorMessage("Failed to retrieve client: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Search clients by name
     */
    public function searchByName($name)
    {
        try {
            $name = filter_var($name, FILTER_SANITIZE_STRING);
            return $this->where('nom', 'LIKE', "%{$name}%");
        } catch (\Exception $e) {
            $this->setErrorMessage("Failed to search clients: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get clients with balance above threshold
     */
    public function getClientsByMinBalance($minBalance)
    {
        try {
            return $this->where('solde', '>=', $minBalance);
        } catch (\Exception $e) {
            $this->setErrorMessage("Failed to retrieve clients: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update client balance
     */
    public function updateBalance($n_compte, $newBalance)
    {
        try {
            $n_compte = filter_var($n_compte, FILTER_SANITIZE_NUMBER_INT);
            $newBalance = filter_var($newBalance, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            
            if ($newBalance < 0) {
                throw new \Exception("Balance cannot be negative");
            }
            
            return $this->update($n_compte, ['solde' => $newBalance]);
        } catch (\Exception $e) {
            $this->setErrorMessage($e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Check if account number exists
     */
    protected function accountExists($n_compte)
    {
        try {
            $result = $this->find($n_compte);
            return $result !== null;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Check if client has any virements
     */
    protected function hasVirements($n_compte)
    {
        try {
            $query = "SELECT COUNT(*) as count FROM virement WHERE n_compte_emetteur = ? OR n_compte_recepteur = ?";
            $statement = $this->connection->prepare($query);
            $statement->execute([$n_compte, $n_compte]);
            
            $result = $statement->fetch(\PDO::FETCH_OBJ);
            return $result->count > 0;
        } catch (\Exception $e) {
            return false; // Assume no virements if query fails
        }
    }
    
    /**
     * Get client statistics
     */
    public function getClientStats()
    {
        try {
            $stats = [];
            
            // Total clients
            $stats['total_clients'] = $this->count();
            
            // Total balance
            $query = "SELECT SUM(solde) as total_balance, AVG(solde) as avg_balance, MAX(solde) as max_balance, MIN(solde) as min_balance FROM {$this->table}";
            $result = $this->query($query);
            
            if (!empty($result)) {
                $stats = array_merge($stats, (array) $result[0]);
            }
            
            return $stats;
        } catch (\Exception $e) {
            $this->setErrorMessage("Failed to get statistics: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Set success message in session
     */
    protected function setSuccessMessage($message)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['success_message'] = $message;
    }
    
    /**
     * Set error message in session
     */
    protected function setErrorMessage($message)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['error_message'] = $message;
    }
    
    /**
     * Legacy method for backward compatibility
     */
    public function delete($id)
    {
        return $this->deleteClient($id);
    }
}