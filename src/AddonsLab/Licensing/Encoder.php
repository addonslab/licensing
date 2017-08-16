<?php

namespace AddonsLab\Licensing;

class Encoder
{
	public function encode(array $data)
	{
		$data = json_encode($data);

		$packed = unpack('H*', $data);

		return $packed[1];
	}

	/**
	 * @param $hex
	 * @return false|object
	 */
	public function decode($hex)
	{
		$text = pack('H*', $hex);

		if (!$text) {
			return false;
		}

		$data = @json_decode($text, true);

		if (!is_array($data)) {
			return false;
		}

		return $data;
	}
}