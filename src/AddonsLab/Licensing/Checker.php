<?php

namespace AddonsLab\Licensing;

use AddonsLab\Licensing\StorageDriver\AbstractStorageDriver;

class Checker
{
    protected $endpoint;
    protected $board_host;
    
    /**
     * @var \Closure
     */
    protected $remote_checker;
    
    /**
     * @var AbstractStorageDriver[]
     */
    protected $storageDrivers = array();

    public function __construct($storageDrivers)
    {
        /**
         * @var int $driverId
         * @var AbstractStorageDriver $storageDriver
         */
        foreach ($storageDrivers AS $driverId => $storageDriver) {
            if (
                ($storageDriver instanceof AbstractStorageDriver) === false
            ) {
                unset($storageDrivers[$driverId]);
                continue;
            }
            $file=__FILE__;

            if($storageDriver->isValid() === false) {
                unset($storageDrivers[$driverId]);
                continue;
            }
        }

        $this->storageDrivers = $storageDrivers;
    }
    
    public function getLicensePingUrl($licenseKey) {
        foreach ($this->storageDrivers AS $storageDriver) {
            $pingUrl=$storageDriver->getLicenseInfoUrl($licenseKey);
            if($pingUrl!==false) {
                return $pingUrl;
            }
        }
        
        return false;
    }

    /**
     * @param $licenseKey
     * @return LicenseData
     * Returns updated license data from server
     */
    public function forceLicenseUpdate($licenseKey)
    {
        // get the local version first
        $licenseData = $this->getLocalLicenseData($licenseKey);

        if ($licenseData === false) {
            // no local data ever existed, create a new one
            $licenseData = new LicenseData();
        }
        
        $queryData = array(
            'license_key' => $licenseKey,
            'server_ip' => isset($_SERVER['SERVER_ADDR'])? $_SERVER['SERVER_ADDR']:'127.0.0.1',
            'board_host' => $this->getBoardHost(),
            'ping_url'=>$this->getLicensePingUrl($licenseKey)
        );

        try {
            $jsonResponse = call_user_func(
                $this->remote_checker, 
                $this->endpoint . '?' . http_build_query($queryData),
                $queryData,
                $licenseData
            );
        } catch (\Exception $exception) {
            $licenseData->setServerError($exception->getMessage());
            return $licenseData;
        }

        $licenseData->setLastServerResponse((array)$jsonResponse);
        $licenseData->setLastError('');

        if ($licenseData->isValid()) {
            $licenseData->resetFailCount();
        } else {
            if ($licenseData->getLicenseErrorCode()) {
                $licenseData->setLastError($licenseData->getLicenseErrorMessage() . ' (error code: ' . $licenseData->getLicenseErrorCode() . ')');
            }
        }

        return $licenseData;
    }

    public function setLicenseData($licenseKey, LicenseData $licenseData)
    {
        foreach ($this->storageDrivers AS $storageDriver) {
            $storageDriver->setLocalData($licenseKey, $licenseData);
        }
    }

    public function getLocalLicenseData($licenseKey)
    {
        if(!$licenseKey) {
            return false;
        }
        
        /** @var AbstractStorageDriver $storageDriver */
        foreach ($this->storageDrivers AS $storageDriver) {
            $licenseData = $storageDriver->getLocalData($licenseKey);
            if (
                $licenseData !== false
                && $licenseData->checkDataIntegrity()
            ) {
                return $licenseData;
            }
        }

        return false;
    }

    /**
     * @return string
     */
    public function getEndpoint()
    {
        return $this->endpoint;
    }

    /**
     * @param string $endpoint
     */
    public function setEndpoint($endpoint)
    {
        $this->endpoint = $endpoint;
        
        return $this;
    }

    public function getBoardHost()
    {
        return $this->board_host;
    }

    public function setBoardHost($board_host)
    {
        $this->board_host = $board_host;
    }

    public function getRemoteChecker()
    {
        return $this->remote_checker;
    }

    public function setRemoteChecker($remote_checker)
    {
        $this->remote_checker = $remote_checker;
    }
}