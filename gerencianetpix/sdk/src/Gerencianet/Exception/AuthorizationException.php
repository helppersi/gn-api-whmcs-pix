<?php

namespace Gerencianet\Exception;

use Exception;

class AuthorizationException extends Exception
{
    private $status;
    private $reason;
    private $body;

    public function __construct($status, $reason, $body = null)
    {
        $this->status = $status;
        $this->reason = $reason;
        $this->body = $body;
        
        parent::__construct($reason, $status);
    }

    public function __toString()
    {
        // Extract message from API response Body
        $bodyMsg = json_decode($this->body)->mensagem;

        return "Authorization Error $this->status: $this->message - $bodyMsg\n";
    }
}
