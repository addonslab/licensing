<?php

namespace AddonsLab\Licensing\Engine;

interface AbstractEngineInterface
{
    public static function getDrivers();

    public static function getBoardDomain();

    public static function getEndpoint();

    public static function getLicenseChecker();

    public static function installDrivers();

    public static function getInvalidLicenseMessage($addonName);

    public static function getExpiredTrialMessage($addonName);

    public static function getLicenseEmptyMessage($addonName);

    public static function getBrandingMessage($addonName);

    public static function disableAddon($addonId, $licenseMessage);
}