<?php

namespace Biigle\Modules\Geo\Exceptions;

use Exception;


class WebMapSourceException extends Exception
{
    protected $key;

    public function __construct($message, $key, $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->key = $key;
    }

    public function getMessageArray()
    {
        return [$this->key => $this->message];
    }
}