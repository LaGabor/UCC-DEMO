<?php

namespace App\Enums;

use Symfony\Component\HttpFoundation\Response;

enum ApiDomainStatus: int
{
    case NOT_FOUND = Response::HTTP_NOT_FOUND;
    case UNPROCESSABLE_ENTITY = Response::HTTP_UNPROCESSABLE_ENTITY;
    case INTERNAL_SERVER_ERROR = Response::HTTP_INTERNAL_SERVER_ERROR;
}
