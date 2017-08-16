<?php
namespace AddonsLab\Licensing\Exception;

use Throwable;

class LicenseNotFoundException extends \Exception
{
	public function __construct($message = "", $code = 0, Throwable $previous = null)
	{
		if($message=='') {
			$message= "Local license information is not found. Please re-save license in Admin Panel.";
		}
		
		parent::__construct($message, $code, $previous);
	}
}