<?php

namespace VK\Exceptions\Api;

use VK\Client\VKApiError;
use VK\Exceptions\VKApiException;

class VKApiMarketTooManyItemsInAlbumException extends VKApiException {
    /**
     * VKApiMarketTooManyItemsInAlbumException constructor.
     * @param VKApiError $error
     */
    public function __construct(VKApiError $error) {
        parent::__construct(1406, 'Too many items in album', $error);
    }
}
