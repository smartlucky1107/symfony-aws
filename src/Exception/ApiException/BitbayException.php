<?php

namespace App\Exception\ApiException;

use App\Exception\AppExceptionInterface;

class BitbayException extends \Exception implements BitbayErrorInterface, AppExceptionInterface
{
}
