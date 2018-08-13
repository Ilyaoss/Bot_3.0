<?php

namespace VK\Exceptions\Api;

use VK\Client\VKApiError;
use VK\Exceptions\VKApiException;

class VKApiAuthParamCodeException extends VKApiException {
    /**
     * VKApiAuthParamCodeException constructor.
     * @param VKApiError $error
     */
    public function __construct(VKApiError $error) {
        parent::__construct(1110, 'Incorrect code', $error);
    }
}
