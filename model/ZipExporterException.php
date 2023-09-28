<?php

namespace oat\taoMediaManager\model;

use common_exception_UserReadableException as UserReadableException;
use LogicException;
class ZipExporterException extends LogicException implements UserReadableException
{
    private array $errors;

    public function __construct(array $errors)
    {
        $this->errors = $errors;
        $this->message = $this->getUserMessage();
    }

    public function getUserMessage(): string
    {
        return sprintf('Zip exporter exception: <br>%s', join('<br>', $this->errors));
    }
}
