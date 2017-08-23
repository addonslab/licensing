<?php
namespace AddonsLab\Licensing\Exception;

use Throwable;

class TrialExpiredException extends \Exception
{
	public function __construct($message = "", $code = 0, Throwable $previous = null)
	{
		if($message=='') {
			$message= "The trial version of this product has expired. Please consider upgrading the product to the full version to continue using it.";
		}
		
		parent::__construct($message, $code, $previous);
	}
}