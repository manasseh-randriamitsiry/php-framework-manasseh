<?php

namespace App;

class Validator
{
    protected $data;
    protected $rules;
    protected $errors = [];
    protected $customMessages = [];
    
    public function __construct($data, $rules, $customMessages = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->customMessages = $customMessages;
    }
    
    /**
     * Validate data against rules
     */
    public function validate()
    {
        foreach ($this->rules as $field => $rules) {
            $this->validateField($field, $rules);
        }
        
        return empty($this->errors);
    }
    
    /**
     * Validate a single field
     */
    protected function validateField($field, $rules)
    {
        $value = $this->data[$field] ?? null;
        $ruleList = is_string($rules) ? explode('|', $rules) : $rules;
        
        foreach ($ruleList as $rule) {
            $this->applyRule($field, $value, $rule);
        }
    }
    
    /**
     * Apply a validation rule
     */
    protected function applyRule($field, $value, $rule)
    {
        // Parse rule and parameters
        $ruleParts = explode(':', $rule, 2);
        $ruleName = $ruleParts[0];
        $parameters = isset($ruleParts[1]) ? explode(',', $ruleParts[1]) : [];
        
        // Get validation method
        $method = 'validate' . ucfirst($ruleName);
        
        if (method_exists($this, $method)) {
            $result = $this->$method($field, $value, $parameters);
            
            if ($result !== true) {
                $this->addError($field, $result ?: $this->getDefaultMessage($ruleName, $field, $parameters));
            }
        } else {
            throw new \Exception("Validation rule '{$ruleName}' does not exist");
        }
    }
    
    /**
     * Add validation error
     */
    protected function addError($field, $message)
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        
        $this->errors[$field][] = $message;
    }
    
    /**
     * Get validation errors
     */
    public function getErrors()
    {
        return $this->errors;
    }
    
    /**
     * Check if validation failed
     */
    public function fails()
    {
        return !empty($this->errors);
    }
    
    /**
     * Check if validation passed
     */
    public function passes()
    {
        return empty($this->errors);
    }
    
    /**
     * Get first error for a field
     */
    public function getFirstError($field)
    {
        return $this->errors[$field][0] ?? null;
    }
    
    // Validation Rules
    
    /**
     * Required rule
     */
    protected function validateRequired($field, $value, $parameters)
    {
        if (is_null($value) || $value === '' || (is_array($value) && empty($value))) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Email rule
     */
    protected function validateEmail($field, $value, $parameters)
    {
        if (empty($value)) {
            return true; // Let required rule handle empty values
        }
        
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Minimum length rule
     */
    protected function validateMin($field, $value, $parameters)
    {
        if (empty($value)) {
            return true;
        }
        
        $min = (int) $parameters[0];
        return strlen($value) >= $min;
    }
    
    /**
     * Maximum length rule
     */
    protected function validateMax($field, $value, $parameters)
    {
        if (empty($value)) {
            return true;
        }
        
        $max = (int) $parameters[0];
        return strlen($value) <= $max;
    }
    
    /**
     * Numeric rule
     */
    protected function validateNumeric($field, $value, $parameters)
    {
        if (empty($value)) {
            return true;
        }
        
        return is_numeric($value);
    }
    
    /**
     * Integer rule
     */
    protected function validateInteger($field, $value, $parameters)
    {
        if (empty($value)) {
            return true;
        }
        
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }
    
    /**
     * URL rule
     */
    protected function validateUrl($field, $value, $parameters)
    {
        if (empty($value)) {
            return true;
        }
        
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * Alpha rule (letters only)
     */
    protected function validateAlpha($field, $value, $parameters)
    {
        if (empty($value)) {
            return true;
        }
        
        return ctype_alpha($value);
    }
    
    /**
     * In array rule
     */
    protected function validateIn($field, $value, $parameters)
    {
        if (empty($value)) {
            return true;
        }
        
        return in_array($value, $parameters);
    }
    
    /**
     * Confirmation rule (password confirmation)
     */
    protected function validateConfirmed($field, $value, $parameters)
    {
        $confirmField = $field . '_confirmation';
        $confirmValue = $this->data[$confirmField] ?? null;
        
        return $value === $confirmValue;
    }
    
    /**
     * Get default error message
     */
    protected function getDefaultMessage($rule, $field, $parameters = [])
    {
        $messages = [
            'required' => "The {$field} field is required.",
            'email' => "The {$field} must be a valid email address.",
            'min' => "The {$field} must be at least {$parameters[0]} characters.",
            'max' => "The {$field} may not be greater than {$parameters[0]} characters.",
            'numeric' => "The {$field} must be a number.",
            'integer' => "The {$field} must be an integer.",
            'url' => "The {$field} format is invalid.",
            'alpha' => "The {$field} may only contain letters.",
            'in' => "The selected {$field} is invalid.",
            'confirmed' => "The {$field} confirmation does not match."
        ];
        
        return $messages[$rule] ?? "The {$field} field is invalid.";
    }
    
    /**
     * Create validator instance
     */
    public static function make($data, $rules, $customMessages = [])
    {
        return new static($data, $rules, $customMessages);
    }
    
    /**
     * Validate and return errors if any
     */
    public static function validateData($data, $rules, $customMessages = [])
    {
        $validator = new static($data, $rules, $customMessages);
        $validator->validate();
        
        return $validator->getErrors();
    }
}