<?php

declare(strict_types=1);

namespace UnicornFail\ArrayObject\Exception;

use Throwable;

class WeakReferenceException extends \RuntimeException
{
    public function __construct(?Throwable $previous = null)
    {
        $message = \sprintf('The object stored in the WeakReference is no longer available.');

        parent::__construct($message, 0, $previous);
    }
}
