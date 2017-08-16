<?php

namespace AddonsLab\Licensing\StorageDriver;

use AddonsLab\Licensing\LicenseData;

abstract class AbstractStorageDriver {
	/**
	 * Initiates license data object from local data
	 * @param $license_key
	 * @return LicenseData|bool
	 */
	public function getLocalData($license_key)
	{
		$licenseData=new LicenseData();
		
		$data=$this->_getCachedData($license_key);
		
		if($data===false) {
			return false;
		}
		
		return $licenseData->initFromLocalStorage(array(
			'last_server_response'=>$data['last_server_response'],
			'fail_count'=>$data['fail_count'],
			'last_error'=>$data['last_error'],
			'data_hash'=>$data['data_hash']
		));
	}
	
	public function setLocalData($licenseKey, LicenseData $licenseData)
	{
		$this->_setCachedData($licenseKey, $licenseData->getForLocalStorage());
	}

    /**
     * @param $licenseKey
     * @return array
     * Should get and return unsterilized licensing data
     */
	protected abstract function _getCachedData($licenseKey);

    /**
     * @param $licenseKey
     * @param array $data
     * @return mixed
     * Should serialize and store licensing data
     */
    protected abstract function _setCachedData($licenseKey, array $data);

    /**
     * @param $licenseKey
     * @return string 
     * A URL where local license info can be accessed by outside servers. Return false if no ping support is needed
     */
    public abstract function getLicenseInfoUrl($licenseKey);

    /**
     * Will be called when setting up the licensing on customer's server, 
     * should run any tasks to setup the system, like creating directory, a table in the database etc.
     */
    public abstract function install();

    /**
     * @return bool
     * Should validate the syntax and data in local storage and return true if the data was not manually modified
     */
    public abstract function isValid();
}