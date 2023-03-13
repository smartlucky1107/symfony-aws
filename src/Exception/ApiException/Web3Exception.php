<?php

namespace App\Exception\ApiException;

use App\Exception\AppExceptionInterface;

class Web3Exception extends \Exception implements Web3ErrorInterface, AppExceptionInterface
{
}
