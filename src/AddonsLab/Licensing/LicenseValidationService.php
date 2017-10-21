<?php

namespace AddonsLab\Licensing;
use AddonsLab\Licensing\Exception\DataIntegrityException;
use AddonsLab\Licensing\Exception\LicenseFailedException;

/**
 * Class LicenseValidationService
 * @package AddonsLab\Licensing
 * A wrapper service for static calls
 */
class LicenseValidationService
{
    protected $validator_class;

    public function __construct($validator_class)
    {
        $this->validator_class = $validator_class;
    }

    /**
     * @param $licenseKey
     * @param bool $logFailure
     * @return \AddonsLab\Licensing\LicenseData
     * @throws DataIntegrityException
     * @throws LicenseFailedException Does full re-validation and throws exceptions in case of failure
     */
    public function licenseReValidation($licenseKey, $logFailure = true)
    {
        return call_user_func(array($this->validator_class, 'licenseReValidation'), $licenseKey, $logFailure);
    }

    /**
     * @param $licenseKey
     * @param bool $logFailure
     * @return \AddonsLab\Licensing\LicenseData
     * @throws DataIntegrityException
     * @throws LicenseFailedException Does full re-validation and throws exceptions in case of failure
     */
    public function licenseLocalReValidation($licenseKey, $logFailure = true)
    {
        return call_user_func(array($this->validator_class, 'licenseLocalReValidation'), $licenseKey, $licenseKey);
    }
    
    public function disableAddon($addonId, $licenseMessage)
    {
        return call_user_func(array($this->validator_class, 'disableAddon'), $addonId, $licenseMessage);
    }
}