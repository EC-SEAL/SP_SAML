<?php

class sspmod_esmo_HttpSig_Error_ApiNotFound extends Exception
{
    public function __construct($microservice,$apiClass,$apiCall)
    {
        assert('is_string($microservice)');
        assert('is_string($apiClass)');
        assert('is_string($apiCall)');
        
        parent::__construct("Microservice '$microservice' api '$apiClass':'$apiCall' not found in metadata", 0);
        
        $this->initBacktrace($this);        
    }
}