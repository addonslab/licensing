<?php

namespace AddonsLab\Licensing;

use AddonsLab\Licensing\StorageDriver\AbstractStorageDriver;

class LicenseData
{
    /**
     * @var array
     * Decoded server response from json format as an array
     * Should not be updated if the remote request is failed
     */
    protected $last_server_response;

    protected $fail_count;

    protected $last_error;

    protected $data_hash;

    /**
     * @return bool
     * True if the license could not be verified after 5 tries
     */
    public function isFailed()
    {
        return $this->fail_count >= 5;
    }

    public function isExpiredTrial()
    {
        if (
            $this->last_server_response
            && !empty($this->last_server_response['IsTrial'])
            && !empty($this->last_server_response['ExpirationDateTimestamp'])
            && $this->last_server_response['ExpirationDateTimestamp'] < time()
        ) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     * Returns true if server response has IsValid key in it
     */
    public function isValid()
    {
        return
            $this->last_server_response
            && !empty($this->last_server_response['IsValid']);
    }

    public function hasBranding()
    {
        return $this->last_server_response && !empty($this->last_server_response['HasBranding']);
    }

    public function getLicenseErrorCode()
    {
        if ($this->last_server_response && !empty($this->last_server_response['error_code'])) {
            return $this->last_server_response['error_code'];
        }

        return false;
    }

    public function getLicenseErrorMessage()
    {
        if ($this->last_server_response && !empty($this->last_server_response['error_message'])) {
            return $this->last_server_response['error_message'];
        }

        return false;
    }

    public function initFromLocalStorage(array $localStorageData)
    {
        $this->last_server_response = $localStorageData['last_server_response'];
        $this->fail_count = $localStorageData['fail_count'];
        $this->data_hash = $localStorageData['data_hash'];
        $this->last_error = $localStorageData['last_error'];

        return $this;
    }

    /**
     * @return string
     * Textual information about the license, to show in admin panel and license pages.
     */
    public function getFullStatusMessage()
    {
        $messages = array();
        if ($this->last_server_response) {
            if (!empty($this->last_server_response['RegistrationDate'])) {
                $messages[] = 'Registration Date: ' . $this->last_server_response['RegistrationDate'];
            }
            if (!empty($this->last_server_response['ExpirationDate'])) {
                $messages[] = 'Expiration Date: ' . $this->last_server_response['ExpirationDate'];
            }

            if ($this->isValid()) {
                if (!empty($this->last_server_response['IsTrial'])) {
                    $messages[] = 'Trial Version';
                } else {
                    $messages[] = 'Valid License';
                }
            } else {
                $messages[] = 'Invalid License';
            }

            if (array_key_exists('HasBranding', $this->last_server_response)) {
                if (empty($this->last_server_response['HasBranding'])) {
                    $messages[] = 'Branding Removed';
                } else {
                    $messages[] = 'Visible Branding';
                }
            }

            if (!empty($this->last_server_response['DomainList'])) {
                $messages[] = 'Domain: ' . $this->last_server_response['DomainList'];
            }

            if (!empty($this->last_server_response['IpList'])) {
                $messages[] = 'IP: ' . $this->last_server_response['IpList'];
            }
        }

        if ($this->getLicenseErrorCode()) {
            $messages[] = $this->getLicenseErrorMessage() . ' (' . $this->getLicenseErrorCode() . ')';
        } else {
            if (!empty($this->last_error)) {
                $messages[] = 'Last check error: ' . $this->last_error;
            }
        }

        if ($this->fail_count) {
            $messages[] = 'Check failure count: ' . $this->fail_count . ' out of 5';
        }

        return implode(', ', $messages);
    }

    public function getForLocalStorage()
    {
        return array(
            'last_server_response' => $this->last_server_response,
            'fail_count' => $this->fail_count,
            'last_error' => $this->last_error,
            'data_hash' => $this->data_hash,
        );
    }

    public function increaseFailCount()
    {
        $this->fail_count++;
        $this->data_hash = $this->_generateDataHash();
    }

    public function resetFailCount()
    {
        $this->fail_count = 0;
        $this->data_hash = $this->_generateDataHash();
    }

    /**
     * @return array
     */
    public function getLastServerResponse()
    {
        return $this->last_server_response;
    }

    /**
     * @return mixed
     */
    public function getFailCount()
    {
        return $this->fail_count;
    }

    /**
     * @return mixed
     */
    public function getLastError()
    {
        return $this->last_error;
    }

    /**
     * @param array $last_server_response
     */
    public function setLastServerResponse(array $last_server_response)
    {
        $this->last_server_response = $last_server_response;

        $this->data_hash = $this->_generateDataHash();
    }

    public function setLastError($lastError)
    {
        $this->last_error = $lastError;
    }

    public function setServerError($statusCode)
    {
        $this->setLastError('Server error - ' . $statusCode);
    }

    public function hasServerError()
    {
        return strpos($this->last_error, 'Server error - ') === 0;
    }

    /**
     * @return bool
     * Should ensure the license data was not modified locally
     */
    public function checkDataIntegrity()
    {
        if ($this->data_hash === $this->_generateDataHash()) {
            return true;
        }

        // reset the server response as it is not valid anymore
        $this->last_server_response = false;

        // update the hash
        $this->_generateDataHash();

        return false;
    }

    protected function _generateDataHash()
    {
        return md5(json_encode($this->last_server_response) . $this->fail_count . '6^scTEnByH0H\'|1nr1GR~NpPSK=qTl/E:UqHsY&&Jq,<O*6Vocx?$uHs%QH$UCa');
    }
}