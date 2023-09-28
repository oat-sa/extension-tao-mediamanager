<?php

namespace oat\taoMediaManager\model;

use common_exception_UserReadableException as UserReadableException;
use LogicException;

class ZipExporterFileErrorList extends LogicException implements UserReadableException
{
    private array $errors;

    public function __construct(array $errors)
    {
        $this->errors = $errors;
        $this->message = $this->getUserMessage();
    }

    public function getUserMessage(): string
    {
        return sprintf('Errors in zip file: <br>%s', implode('<br>', $this->errors));
    }
}
