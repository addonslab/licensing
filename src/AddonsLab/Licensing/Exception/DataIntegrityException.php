<?php
namespace AddonsLab\Licensing\Exception;

class DataIntegrityException extends \Exception
{
	public function __construct($message = "", $code = 0, \Throwable $previous = null)
	{
		if ($message == '') {
			$message = "Unfortunately we could not validate your license. Please contact add-on provider if you think this is an error.";
		}

		parent::__construct($message, $code, $previous);
	}
}