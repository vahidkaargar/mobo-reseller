<?php

namespace App\Exceptions;

use Exception;

/**
 * An admin resolution the order's state does not allow. The message is safe to show.
 */
class OrderResolutionException extends Exception {}
