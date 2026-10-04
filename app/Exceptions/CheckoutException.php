<?php

namespace App\Exceptions;

use Exception;

/**
 * A checkout the user can fix (inactive account, empty cart, low balance).
 * The message is safe to show to the user.
 */
class CheckoutException extends Exception {}
