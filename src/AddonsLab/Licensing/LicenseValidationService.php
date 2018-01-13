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
    
    public function getLicenseChecker()
    {
        // check for existing license
        $checker = new Checker(
            $this->_callValidatorFunction('getDrivers')
        );

        $checker->setEndpoint(
            $this->_callValidatorFunction('getEndpoint')
        );

        return $checker;
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
        return $this->_callValidatorFunction('licenseReValidation', array($licenseKey, $logFailure));
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
        return $this->_callValidatorFunction('licenseLocalReValidation', array($licenseKey, $logFailure));
    }
    
    public function disableAddon($addonId, $licenseMessage)
    {
        return $this->_callValidatorFunction('disableAddon', array($addonId, $licenseMessage));
    }

    public function getInvalidLicenseMessage($addonName)
    {
        return $this->_callValidatorFunction('getInvalidLicenseMessage', array($addonName));
    }

    public function getExpiredTrialMessage($addonName)
    {
        return $this->_callValidatorFunction('getExpiredTrialMessage', array($addonName));
    }

    public function getLicenseEmptyMessage($addonName)
    {
        return $this->_callValidatorFunction('getLicenseEmptyMessage', array($addonName));
    }

    public function getBrandingMessage($addonName)
    {
        return $this->_callValidatorFunction('getBrandingMessage', array($addonName));
    }
    
    protected function _callValidatorFunction($name, array $arguments=array())
    {
        return call_user_func_array(array($this->validator_class, $name), $arguments);
    }
}