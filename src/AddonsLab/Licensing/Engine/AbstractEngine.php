<?php

namespace AddonsLab\Licensing\Engine;

use AddonsLab\Licensing\Checker;
use AddonsLab\Licensing\Exception\DataIntegrityException;
use AddonsLab\Licensing\Exception\LicenseFailedException;
use AddonsLab\Licensing\Exception\LicenseNotFoundException;
use AddonsLab\Licensing\Exception\TrialExpiredException;
use AddonsLab\Licensing\LicenseData;
use AddonsLab\Licensing\StorageDriver\AbstractStorageDriver;
use AddonsLab\Licensing\StorageDriver\Database;
use AddonsLab\Licensing\StorageDriver\File;

/**
 * Class Xf1
 * @package AddonsLab\Licensing\Engine
 * XenForo 1.x-related methods for license validation and rending license option
 */
abstract class AbstractEngine implements AbstractEngineInterface
{
    public static function getEndpoint() {
        return '';
    }

    public static function getLicenseChecker()
    {
        // check for existing license
        $checker = new Checker(
            static::getDrivers()
        );

        $checker->setEndpoint(static::getEndpoint());
        $checker->setBoardHost(static::getBoardDomain());

        return $checker;
    }

    public static function installDrivers()
    {
        foreach (static::getDrivers() AS $driver) {
            $driver->install();
        }
    }

    public static function getInvalidLicenseMessage($addonName)
    {
        return '<a style="color: red" href="https://customers.addonslab.com/" target="_blank">' . $addonName . ': invalid license detected.</a>';
    }

    public static function getExpiredTrialMessage($addonName)
    {
        return '<a style="color: red" href="https://customers.addonslab.com/" target="_blank">' . $addonName . ': your trial version is expired.</a>';
    }

    public static function getLicenseEmptyMessage($addonName)
    {
        return '<a style="color: red" href="https://customers.addonslab.com/submitticket.php" target="_blank">' . $addonName
            . ': please enter your license key in Admin Panel.</a>';
    }

    public static function getBrandingMessage($addonName)
    {
        return '<a class="concealed" href="https://customers.addonslab.com/marketplace.php" target="_blank">' . $addonName . '</a>';
    }

    /**
     * @param $licenseKey
     * @param bool $logFailure
     * @return \AddonsLab\Licensing\LicenseData
     * @throws DataIntegrityException
     * @throws LicenseFailedException Does full re-validation and throws exceptions in case of failure
     */
    public static function licenseLocalReValidation($licenseKey, $logFailure = true)
    {
        $checker = static::getLicenseChecker();

        $licenseData = $checker->getLocalLicenseData($licenseKey);

        if ($licenseData === false) {
            // should not happen
            throw new LicenseNotFoundException();
        }

        if (!$licenseData->checkDataIntegrity()) {
            // prevent usage, as license integrity could not be checked
            throw new DataIntegrityException();
        }

        if($licenseData->isExpiredTrial()) {
            throw  new TrialExpiredException();
        }

        if ($licenseData->isValid() === false && $logFailure === true) {
            $licenseData->increaseFailCount();
	        $checker->setLicenseData($licenseKey, $licenseData);
	        if ($licenseData->isFailed()) {
		        throw new LicenseFailedException();
	        }
        }

        return $licenseData;
    }

    /**
     * @param $licenseKey
     * @param bool $logFailure
     * @return \AddonsLab\Licensing\LicenseData
     * @throws DataIntegrityException
     * @throws LicenseFailedException Does full re-validation and throws exceptions in case of failure
     */
    public static function licenseReValidation($licenseKey, $logFailure = true)
    {
        $checker = static::getLicenseChecker();

        $licenseData = $checker->forceLicenseUpdate($licenseKey);

        if (!$licenseData->checkDataIntegrity()) {
            // prevent usage, as license integrity could not be checked
            throw new DataIntegrityException();
        }

        if ($licenseData->isValid() === false && $logFailure === true) {
            $licenseData->increaseFailCount();
        }

        $checker->setLicenseData($licenseKey, $licenseData);

        if ($licenseData->isFailed()) {
            throw new LicenseFailedException($licenseData->getFullStatusMessage());
        }

        return $licenseData;
    }
}
