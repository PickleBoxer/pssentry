<?php

class PrestaShopException extends PrestaShopExceptionCore
{
    /**
     * Log the error to sentry
     */
    protected function logError()
    {
        if (function_exists('Sentry\captureException')) {
            Sentry\captureException($this);
        }
        parent::logError();
    }
}