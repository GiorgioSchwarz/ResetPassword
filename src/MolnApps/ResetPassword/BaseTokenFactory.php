<?php

namespace MolnApps\ResetPassword;

use \MolnApps\ResetPassword\Contracts\TokenFactory;

class BaseTokenFactory implements TokenFactory
{
	public function getNewToken()
	{
		return new BaseToken($this->getExpiration());
	}

	private function getExpiration()
	{
		return strtotime('+1 day');
	}

	public function getTokenValidator($token, $expiration)
	{
		return new BaseToken($token, $expiration);
	}
}