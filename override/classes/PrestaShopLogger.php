<?php

class PrestaShopLogger extends PrestaShopLoggerCore
{
    public static function addLog($message, $severity = 1, $errorCode = null, $objectType = null, $objectId = null, $allowDuplicate = false, $idEmployee = null)
    {
        if ($severity > 1 && function_exists('Sentry\captureException')) {
            Sentry\withScope(function (Sentry\State\Scope $scope) use ($message, $severity, $errorCode, $objectType, $objectId): void {
                $scope->setContext('log', [
                    'severity' => $severity,
                    'errorCode' => $errorCode,
                    'objectType' => $objectType,
                    'objectId' => $objectId,
                ]);

                Sentry\captureException(new Exception($message));
            });
        }
        return parent::addLog($message, $severity, $errorCode, $objectType, $objectId, $allowDuplicate, $idEmployee);
    }
}