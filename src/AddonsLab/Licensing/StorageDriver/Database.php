<?php

namespace AddonsLab\Licensing\StorageDriver;

use AddonsLab\Licensing\Encoder;

class Database extends AbstractStorageDriver
{
    protected $db;

    protected $table = 'al_license';

    public function getLicenseInfoUrl($licenseKey)
    {
        return false; // we do not support license ping via database by default
    }


    public function setDb($db)
    {
        $this->db = $db;

        return $this;
    }

    public function isValid()
    {
        return is_object($this->db)
            && method_exists($this->db, 'query')
            && method_exists($this->db, 'fetchRow');
    }

    public function install()
    {
        $this->db->query('
            CREATE TABLE IF NOT EXISTS `' . $this->table . '` (
              `license_key` VARCHAR (100) NOT NULL,
              `license_data`	BLOB NOT NULL,
			  PRIMARY KEY (license_key)
            ) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;
        ');
    }

    protected function _getCachedData($cacheId)
    {
        try {
            $cachedData = $this->db->fetchRow('
                SELECT license_data FROM ' . $this->table . ' 
                WHERE license_key=\'' . addslashes($cacheId) . '\'
            ');
        } catch (\Exception $exception) {
            return false;
        }

        if (!$cachedData) {
            return false;
        }

        $encoder = new Encoder();

        return $encoder->decode($cachedData['license_data']);
    }

    protected function _setCachedData($licenseKey, array $data)
    {
        $encoder = new Encoder();
        $content = $encoder->encode($data);
        try {
            $this->db->query('
                REPLACE INTO ' . $this->table . ' (license_key, license_data)
                VALUES (\'' . addslashes($licenseKey) . '\', \'' . addslashes($content) . '\')
            ');
        } catch (\Exception $exception) {
            // ignore DB exceptions in licensing system
            return;
        }

    }

    public function setTable($table)
    {
        $this->table = $table;
    }
}
