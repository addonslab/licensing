<?php

namespace AddonsLab\Licensing\StorageDriver;

use AddonsLab\Licensing\Encoder;

class File extends AbstractStorageDriver
{
	protected $cacheDirectory;
	protected $cacheDirectoryUrl;

    public function getLicenseInfoUrl($licenseKey)
    {
        return $this->cacheDirectoryUrl. '/lic-' . md5($licenseKey) . '.bin';
    }


    public function setCacheDirectory($cacheDirectory)
	{
		$this->cacheDirectory = $cacheDirectory;
		
		return $this;
	}

    /**
     * @param mixed $cacheDirectoryUrl
     * @return File
     */
    public function setCacheDirectoryUrl($cacheDirectoryUrl)
    {
        $this->cacheDirectoryUrl = $cacheDirectoryUrl;
        return $this;
    }

	public function isValid()
	{
		if (!is_dir($this->cacheDirectory)) {
			mkdir($this->cacheDirectory, 0777, true);
		}

		return is_writable($this->cacheDirectory);
	}

	public function install()
	{
		// nothing to do to install this method
	}

	/**
	 * @param $licenseKey
	 * @return false|object
	 */
	protected function _getCachedData($licenseKey)
	{
		$fileName = $this->_getCachedFileName($licenseKey);

		if (!file_exists($fileName)) {
			return false;
		}

		$fileContent = file_get_contents($fileName);

		$encoder = new Encoder();

		return $encoder->decode($fileContent);
	}

	protected function _setCachedData($licenseKey, array $data)
	{
		$fileName = $this->_getCachedFileName($licenseKey);

		$encoder = new Encoder();

		$content = $encoder->encode($data);

		return file_put_contents($fileName, $content);
	}

	protected function _getCachedFileName($licenseKey)
	{
		return $this->cacheDirectory.'/lic-' . md5($licenseKey) . '.bin';
	}
}