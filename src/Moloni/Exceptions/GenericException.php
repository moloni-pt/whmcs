<?php

namespace Moloni\Exceptions;

use Exception;

class GenericException extends Exception
{
    protected $data;
    protected $where;

    public function __construct($message, $data = [], $where = '')
    {
        $this->data = $data;
        $this->where = $where;

        parent::__construct($message);
    }

    public function getData(): array
    {
        return $this->data ?? [];
    }

    public function getWhere()
    {
        return $this->where ?? '';
    }

}
