<?php

namespace App;

class FileUpload
{
    protected $allowedTypes = [];
    protected $maxSize = 2097152; // 2MB default
    protected $uploadPath = 'uploads/';
    protected $errors = [];
    
    public function __construct($config = [])
    {
        $this->allowedTypes = $config['allowed_types'] ?? explode(',', Environment::get('ALLOWED_FILE_TYPES', 'jpg,jpeg,png,gif,pdf'));
        $this->maxSize = ($config['max_size'] ?? Environment::get('MAX_UPLOAD_SIZE', 2048)) * 1024; // Convert KB to bytes
        $this->uploadPath = $config['upload_path'] ?? 'uploads/';
        
        $this->ensureUploadDirectory();
    }
    
    /**
     * Handle single file upload
     */
    public function upload($fieldName, $customFilename = null)
    {
        try {
            if (!isset($_FILES[$fieldName])) {
                throw new \Exception("No file uploaded for field: {$fieldName}");
            }
            
            $file = $_FILES[$fieldName];
            
            // Validate file
            $this->validateFile($file);
            
            if (!empty($this->errors)) {
                throw new \Exception("File validation failed: " . implode(', ', $this->errors));
            }
            
            // Generate safe filename
            $filename = $this->generateFilename($file, $customFilename);
            $destination = $this->uploadPath . $filename;
            
            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                throw new \Exception("Failed to move uploaded file to destination");
            }
            
            // Log successful upload
            Logger::info("File uploaded successfully", [
                'original_name' => $file['name'],
                'saved_as' => $filename,
                'size' => $file['size'],
                'type' => $file['type']
            ]);
            
            return [
                'success' => true,
                'filename' => $filename,
                'original_name' => $file['name'],
                'path' => $destination,
                'size' => $file['size'],
                'type' => $file['type']
            ];
            
        } catch (\Exception $e) {
            // Log error
            Logger::error("File upload failed", [
                'field' => $fieldName,
                'error' => $e->getMessage(),
                'file_info' => $_FILES[$fieldName] ?? 'No file data'
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'errors' => $this->errors
            ];
        }
    }
    
    /**
     * Handle multiple file uploads
     */
    public function uploadMultiple($fieldName, $customFilenames = [])
    {
        $results = [];
        
        if (!isset($_FILES[$fieldName]) || !is_array($_FILES[$fieldName]['name'])) {
            return [
                'success' => false,
                'error' => "No files uploaded or invalid field structure for: {$fieldName}"
            ];
        }
        
        $fileCount = count($_FILES[$fieldName]['name']);
        
        for ($i = 0; $i < $fileCount; $i++) {
            // Reconstruct file array for single file processing
            $file = [
                'name' => $_FILES[$fieldName]['name'][$i],
                'type' => $_FILES[$fieldName]['type'][$i],
                'tmp_name' => $_FILES[$fieldName]['tmp_name'][$i],
                'error' => $_FILES[$fieldName]['error'][$i],
                'size' => $_FILES[$fieldName]['size'][$i]
            ];
            
            // Temporarily set the file for validation
            $tempFieldName = $fieldName . '_temp_' . $i;
            $_FILES[$tempFieldName] = $file;
            
            $customFilename = $customFilenames[$i] ?? null;
            $result = $this->upload($tempFieldName, $customFilename);
            
            // Clean up temporary file entry
            unset($_FILES[$tempFieldName]);
            
            $results[] = $result;
        }
        
        return $results;
    }
    
    /**
     * Validate uploaded file
     */
    protected function validateFile($file)
    {
        $this->errors = [];
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->addFileError($file['error']);
            return;
        }
        
        // Check file size
        if ($file['size'] > $this->maxSize) {
            $maxSizeMB = round($this->maxSize / 1048576, 2);
            $this->errors[] = "File size exceeds maximum allowed size of {$maxSizeMB}MB";
        }
        
        // Check file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedTypes)) {
            $allowedString = implode(', ', $this->allowedTypes);
            $this->errors[] = "File type '{$extension}' not allowed. Allowed types: {$allowedString}";
        }
        
        // Check MIME type
        if (!$this->validateMimeType($file, $extension)) {
            $this->errors[] = "File MIME type does not match extension";
        }
        
        // Check if file is actually uploaded
        if (!is_uploaded_file($file['tmp_name'])) {
            $this->errors[] = "File was not uploaded through HTTP POST";
        }
        
        // Additional security checks
        $this->performSecurityChecks($file);
    }
    
    /**
     * Add file upload error message
     */
    protected function addFileError($errorCode)
    {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        
        $this->errors[] = $errors[$errorCode] ?? 'Unknown upload error';
    }
    
    /**
     * Validate MIME type matches extension
     */
    protected function validateMimeType($file, $extension)
    {
        $allowedMimeTypes = [
            'jpg' => ['image/jpeg', 'image/pjpeg'],
            'jpeg' => ['image/jpeg', 'image/pjpeg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'xls' => ['application/vnd.ms-excel'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'txt' => ['text/plain'],
            'csv' => ['text/csv', 'application/csv']
        ];
        
        if (!isset($allowedMimeTypes[$extension])) {
            return false;
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        return in_array($detectedMimeType, $allowedMimeTypes[$extension]);
    }
    
    /**
     * Perform additional security checks
     */
    protected function performSecurityChecks($file)
    {
        // Check for PHP files disguised as other types
        $content = file_get_contents($file['tmp_name'], false, null, 0, 1024);
        if (strpos($content, '<?php') !== false || strpos($content, '<?=') !== false) {
            $this->errors[] = "File contains PHP code and is not allowed";
        }
        
        // Check for executable file headers
        $dangerousHeaders = [
            "\x4D\x5A", // PE executable
            "\x7F\x45\x4C\x46", // ELF executable
            "\xFE\xED\xFA", // Mach-O executable
        ];
        
        foreach ($dangerousHeaders as $header) {
            if (strpos($content, $header) === 0) {
                $this->errors[] = "File appears to be an executable and is not allowed";
                break;
            }
        }
        
        // Check filename for dangerous characters
        $filename = $file['name'];
        if (preg_match('/[<>:"\/\\|?*]/', $filename)) {
            $this->errors[] = "Filename contains invalid characters";
        }
        
        // Check for null bytes
        if (strpos($filename, "\0") !== false) {
            $this->errors[] = "Filename contains null bytes";
        }
    }
    
    /**
     * Generate safe filename
     */
    protected function generateFilename($file, $customFilename = null)
    {
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if ($customFilename) {
            // Sanitize custom filename
            $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $customFilename);
            $filename = trim($filename, '.');
            
            // Ensure it has the correct extension
            if (pathinfo($filename, PATHINFO_EXTENSION) !== $extension) {
                $filename .= '.' . $extension;
            }
        } else {
            // Generate unique filename
            $filename = uniqid('upload_', true) . '.' . $extension;
        }
        
        // Ensure filename is unique
        $counter = 1;
        $originalFilename = $filename;
        while (file_exists($this->uploadPath . $filename)) {
            $pathInfo = pathinfo($originalFilename);
            $filename = $pathInfo['filename'] . '_' . $counter . '.' . $pathInfo['extension'];
            $counter++;
        }
        
        return $filename;
    }
    
    /**
     * Ensure upload directory exists
     */
    protected function ensureUploadDirectory()
    {
        if (!is_dir($this->uploadPath)) {
            if (!mkdir($this->uploadPath, 0755, true)) {
                throw new \Exception("Failed to create upload directory: {$this->uploadPath}");
            }
        }
        
        if (!is_writable($this->uploadPath)) {
            throw new \Exception("Upload directory is not writable: {$this->uploadPath}");
        }
        
        // Create .htaccess to prevent execution of uploaded files
        $htaccessPath = $this->uploadPath . '.htaccess';
        if (!file_exists($htaccessPath)) {
            $htaccessContent = "# Prevent execution of uploaded files\n";
            $htaccessContent .= "php_flag engine off\n";
            $htaccessContent .= "RemoveHandler .php .phtml .php3 .php4 .php5 .php6\n";
            $htaccessContent .= "RemoveType .php .phtml .php3 .php4 .php5 .php6\n";
            
            file_put_contents($htaccessPath, $htaccessContent);
        }
    }
    
    /**
     * Delete uploaded file
     */
    public function delete($filename)
    {
        try {
            $filepath = $this->uploadPath . $filename;
            
            if (!file_exists($filepath)) {
                throw new \Exception("File not found: {$filename}");
            }
            
            if (!unlink($filepath)) {
                throw new \Exception("Failed to delete file: {$filename}");
            }
            
            Logger::info("File deleted successfully", ['filename' => $filename]);
            
            return ['success' => true, 'message' => 'File deleted successfully'];
            
        } catch (\Exception $e) {
            Logger::error("File deletion failed", [
                'filename' => $filename,
                'error' => $e->getMessage()
            ]);
            
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get file information
     */
    public function getFileInfo($filename)
    {
        $filepath = $this->uploadPath . $filename;
        
        if (!file_exists($filepath)) {
            return null;
        }
        
        return [
            'filename' => $filename,
            'path' => $filepath,
            'size' => filesize($filepath),
            'type' => mime_content_type($filepath),
            'created' => filectime($filepath),
            'modified' => filemtime($filepath)
        ];
    }
    
    /**
     * Clean up old files
     */
    public function cleanupOldFiles($days = 30)
    {
        $cutoffTime = time() - ($days * 24 * 60 * 60);
        $deletedCount = 0;
        
        $files = glob($this->uploadPath . '*');
        
        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $cutoffTime) {
                if (unlink($file)) {
                    $deletedCount++;
                    Logger::info("Old file cleaned up", ['file' => basename($file)]);
                }
            }
        }
        
        return $deletedCount;
    }
    
    /**
     * Get upload statistics
     */
    public function getStats()
    {
        $files = glob($this->uploadPath . '*');
        $totalSize = 0;
        $fileCount = 0;
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $totalSize += filesize($file);
                $fileCount++;
            }
        }
        
        return [
            'total_files' => $fileCount,
            'total_size' => $totalSize,
            'upload_path' => $this->uploadPath,
            'max_size' => $this->maxSize,
            'allowed_types' => $this->allowedTypes
        ];
    }
    
    /**
     * Get validation errors
     */
    public function getErrors()
    {
        return $this->errors;
    }
}