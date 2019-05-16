<?php
namespace AddonsLab\Licensing\Exception;

use Throwable;

class LicenseFailedException extends \Exception
{
	public function __construct($message = "", $code = 0, Throwable $previous = null)
	{
	    if($message=='') {
			$message= "Your license seems to be invalid. If you have moved your website to another server/domain, make sure to reissue your license and retry the installation.";
		}

		parent::__construct($message, $code, $previous);
	}
}
