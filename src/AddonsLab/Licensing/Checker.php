<?php

namespace AddonsLab\Licensing;

use AddonsLab\Licensing\StorageDriver\AbstractStorageDriver;

class Checker
{
    protected $endpoint;
    
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
            'server_ip' => $_SERVER['SERVER_ADDR'],
            'board_host' => parse_url(\XenForo_Application::getOptions()->get('boardUrl'), PHP_URL_HOST),
            'ping_url'=>$this->getLicensePingUrl($licenseKey)
        );

        $client = \XenForo_Helper_Http::getClient(
            $this->endpoint . '?' . http_build_query($queryData)
        );

        try {
            $apiResponse = $client->request();
        } catch (\Exception $ex) {
            $licenseData->setServerError($ex->getMessage());

            return $licenseData;
        }

        if ($apiResponse->getStatus() !== 200) {
            // do not increase failed count so we don't disable the product because of our server moving or unavailable
            $licenseData->setServerError($apiResponse->getStatus() . ' ' . $apiResponse->getMessage());
            return $licenseData;
        }

        $jsonResponse = @json_decode($apiResponse->getBody());

        if (!$jsonResponse) {
            $licenseData->increaseFailCount();
            $licenseData->setLastError('Failed to decode license data - ' . substr($apiResponse->getBody(), 0, 100) . '...');
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
}