<?php

namespace oat\taoMediaManager\model\export\service;

use common_exception_UserReadableException as UserReadableException;
use LogicException;

class MediaReferencesNotFoundException extends LogicException implements UserReadableException
{
    private array $mediaReferences;

    public function __construct(array $errors)
    {
        $this->mediaReferences = $errors;
        $this->message = $this->getUserMessage();
    }

    public function getUserMessage(): string
    {
        return sprintf('Media references to %s could not be found.', implode(', ', $this->mediaReferences));
    }
}
