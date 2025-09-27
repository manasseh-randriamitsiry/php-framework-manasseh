<?php

namespace App;

class Mail
{
    protected $to = [];
    protected $from = '';
    protected $fromName = '';
    protected $subject = '';
    protected $body = '';
    protected $isHtml = true;
    protected $attachments = [];
    protected $headers = [];
    protected $config = [];
    
    public function __construct()
    {
        $this->config = [
            'driver' => Environment::get('MAIL_DRIVER', 'mail'),
            'host' => Environment::get('MAIL_HOST', 'localhost'),
            'port' => Environment::get('MAIL_PORT', 587),
            'username' => Environment::get('MAIL_USERNAME', ''),
            'password' => Environment::get('MAIL_PASSWORD', ''),
            'encryption' => Environment::get('MAIL_ENCRYPTION', 'tls'),
            'from_address' => Environment::get('MAIL_FROM_ADDRESS', 'noreply@localhost'),
            'from_name' => Environment::get('MAIL_FROM_NAME', 'Application')
        ];
        
        $this->from = $this->config['from_address'];
        $this->fromName = $this->config['from_name'];
    }
    
    /**
     * Set recipient
     */
    public function to($email, $name = '')
    {
        if (is_array($email)) {
            foreach ($email as $addr => $recipientName) {
                $this->to[] = [
                    'email' => is_numeric($addr) ? $recipientName : $addr,
                    'name' => is_numeric($addr) ? '' : $recipientName
                ];
            }
        } else {
            $this->to[] = ['email' => $email, 'name' => $name];
        }
        
        return $this;
    }
    
    /**
     * Set sender
     */
    public function from($email, $name = '')
    {
        $this->from = $email;
        $this->fromName = $name;
        return $this;
    }
    
    /**
     * Set subject
     */
    public function subject($subject)
    {
        $this->subject = $subject;
        return $this;
    }
    
    /**
     * Set HTML body
     */
    public function html($body)
    {
        $this->body = $body;
        $this->isHtml = true;
        return $this;
    }
    
    /**
     * Set plain text body
     */
    public function text($body)
    {
        $this->body = $body;
        $this->isHtml = false;
        return $this;
    }
    
    /**
     * Load view as email body
     */
    public function view($viewName, $data = [])
    {
        try {
            ob_start();
            extract($data);
            include __DIR__ . "/../views/emails/{$viewName}.php";
            $this->body = ob_get_clean();
            $this->isHtml = true;
            
        } catch (\Exception $e) {
            Logger::error("Failed to load email view", [
                'view' => $viewName,
                'error' => $e->getMessage()
            ]);
            throw new \Exception("Email view not found: {$viewName}");
        }
        
        return $this;
    }
    
    /**
     * Add attachment
     */
    public function attach($filePath, $name = '')
    {
        if (!file_exists($filePath)) {
            throw new \Exception("Attachment file not found: {$filePath}");
        }
        
        $this->attachments[] = [
            'path' => $filePath,
            'name' => $name ?: basename($filePath),
            'type' => mime_content_type($filePath)
        ];
        
        return $this;
    }
    
    /**
     * Add custom header
     */
    public function header($name, $value)
    {
        $this->headers[$name] = $value;
        return $this;
    }
    
    /**
     * Send email
     */
    public function send()
    {
        try {
            $this->validateEmail();
            
            switch ($this->config['driver']) {
                case 'smtp':
                    return $this->sendViaSmtp();
                case 'sendmail':
                    return $this->sendViaSendmail();
                case 'mail':
                default:
                    return $this->sendViaMail();
            }
            
        } catch (\Exception $e) {
            Logger::error("Email sending failed", [
                'to' => $this->to,
                'subject' => $this->subject,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Validate email data
     */
    protected function validateEmail()
    {
        if (empty($this->to)) {
            throw new \Exception("No recipients specified");
        }
        
        if (empty($this->subject)) {
            throw new \Exception("Subject is required");
        }
        
        if (empty($this->body)) {
            throw new \Exception("Email body is required");
        }
        
        if (!filter_var($this->from, FILTER_VALIDATE_EMAIL)) {
            throw new \Exception("Invalid sender email address");
        }
        
        foreach ($this->to as $recipient) {
            if (!filter_var($recipient['email'], FILTER_VALIDATE_EMAIL)) {
                throw new \Exception("Invalid recipient email: {$recipient['email']}");
            }
        }
    }
    
    /**
     * Send via PHP's mail() function
     */
    protected function sendViaMail()
    {
        $headers = $this->buildHeaders();
        $success = true;
        
        foreach ($this->to as $recipient) {
            $to = $recipient['name'] ? "{$recipient['name']} <{$recipient['email']}>" : $recipient['email'];
            
            if (!mail($to, $this->subject, $this->body, $headers)) {
                $success = false;
                Logger::warning("Failed to send email via mail()", [
                    'to' => $recipient['email']
                ]);
            }
        }
        
        if ($success) {
            Logger::info("Email sent successfully via mail()", [
                'to' => array_column($this->to, 'email'),
                'subject' => $this->subject
            ]);
        }
        
        return ['success' => $success];
    }
    
    /**
     * Send via SMTP
     */
    protected function sendViaSmtp()
    {
        if (!function_exists('fsockopen')) {
            throw new \Exception("fsockopen function is required for SMTP");
        }
        
        $socket = $this->connectToSmtp();
        
        try {
            $this->smtpCommand($socket, "EHLO " . $this->config['host']);
            
            // Start TLS if required
            if ($this->config['encryption'] === 'tls') {
                $this->smtpCommand($socket, "STARTTLS");
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                $this->smtpCommand($socket, "EHLO " . $this->config['host']);
            }
            
            // Authenticate
            if (!empty($this->config['username'])) {
                $this->smtpAuth($socket);
            }
            
            // Send email
            $this->smtpCommand($socket, "MAIL FROM: <{$this->from}>");
            
            foreach ($this->to as $recipient) {
                $this->smtpCommand($socket, "RCPT TO: <{$recipient['email']}>");
            }
            
            $this->smtpCommand($socket, "DATA");
            
            $message = $this->buildMessage();
            fwrite($socket, $message . "\r\n.\r\n");
            $this->readSmtpResponse($socket);
            
            $this->smtpCommand($socket, "QUIT");
            fclose($socket);
            
            Logger::info("Email sent successfully via SMTP", [
                'to' => array_column($this->to, 'email'),
                'subject' => $this->subject
            ]);
            
            return ['success' => true];
            
        } catch (\Exception $e) {
            if (is_resource($socket)) {
                fclose($socket);
            }
            throw $e;
        }
    }
    
    /**
     * Connect to SMTP server
     */
    protected function connectToSmtp()
    {
        $host = $this->config['host'];
        $port = $this->config['port'];
        
        if ($this->config['encryption'] === 'ssl') {
            $host = 'ssl://' . $host;
        }
        
        $socket = fsockopen($host, $port, $errno, $errstr, 30);
        
        if (!$socket) {
            throw new \Exception("Could not connect to SMTP server: {$errstr} ({$errno})");
        }
        
        $this->readSmtpResponse($socket);
        
        return $socket;
    }
    
    /**
     * Send SMTP command
     */
    protected function smtpCommand($socket, $command)
    {
        fwrite($socket, $command . "\r\n");
        return $this->readSmtpResponse($socket);
    }
    
    /**
     * Read SMTP response
     */
    protected function readSmtpResponse($socket)
    {
        $response = fgets($socket, 512);
        $code = substr($response, 0, 3);
        
        if ($code >= 400) {
            throw new \Exception("SMTP Error: {$response}");
        }
        
        return $response;
    }
    
    /**
     * SMTP authentication
     */
    protected function smtpAuth($socket)
    {
        $this->smtpCommand($socket, "AUTH LOGIN");
        $this->smtpCommand($socket, base64_encode($this->config['username']));
        $this->smtpCommand($socket, base64_encode($this->config['password']));
    }
    
    /**
     * Send via sendmail
     */
    protected function sendViaSendmail()
    {
        $sendmailPath = '/usr/sbin/sendmail -t -i';
        $pipe = popen($sendmailPath, 'w');
        
        if (!$pipe) {
            throw new \Exception("Could not open sendmail pipe");
        }
        
        $message = $this->buildMessage();
        fwrite($pipe, $message);
        $result = pclose($pipe);
        
        $success = ($result === 0);
        
        if ($success) {
            Logger::info("Email sent successfully via sendmail", [
                'to' => array_column($this->to, 'email'),
                'subject' => $this->subject
            ]);
        }
        
        return ['success' => $success];
    }
    
    /**
     * Build email headers
     */
    protected function buildHeaders()
    {
        $headers = [];
        
        $from = $this->fromName ? "{$this->fromName} <{$this->from}>" : $this->from;
        $headers[] = "From: {$from}";
        $headers[] = "Reply-To: {$this->from}";
        $headers[] = "X-Mailer: PHP Framework Mailer";
        
        if ($this->isHtml) {
            $headers[] = "MIME-Version: 1.0";
            $headers[] = "Content-Type: text/html; charset=UTF-8";
        } else {
            $headers[] = "Content-Type: text/plain; charset=UTF-8";
        }
        
        foreach ($this->headers as $name => $value) {
            $headers[] = "{$name}: {$value}";
        }
        
        return implode("\r\n", $headers);
    }
    
    /**
     * Build complete email message
     */
    protected function buildMessage()
    {
        $message = [];
        
        foreach ($this->to as $recipient) {
            $to = $recipient['name'] ? "{$recipient['name']} <{$recipient['email']}>" : $recipient['email'];
            $message[] = "To: {$to}";
        }
        
        $from = $this->fromName ? "{$this->fromName} <{$this->from}>" : $this->from;
        $message[] = "From: {$from}";
        $message[] = "Subject: {$this->subject}";
        $message[] = "Date: " . date('r');
        $message[] = "Message-ID: <" . uniqid() . "@{$_SERVER['HTTP_HOST']}>>";
        
        if ($this->isHtml) {
            $message[] = "MIME-Version: 1.0";
            $message[] = "Content-Type: text/html; charset=UTF-8";
        } else {
            $message[] = "Content-Type: text/plain; charset=UTF-8";
        }
        
        foreach ($this->headers as $name => $value) {
            $message[] = "{$name}: {$value}";
        }
        
        $message[] = "";
        $message[] = $this->body;
        
        return implode("\r\n", $message);
    }
    
    /**
     * Create new mail instance
     */
    public static function create()
    {
        return new static();
    }
    
    /**
     * Send quick email
     */
    public static function quick($to, $subject, $body, $isHtml = true)
    {
        return self::create()
            ->to($to)
            ->subject($subject)
            ->html($body)
            ->send();
    }
}