<?php

namespace MolnApps\ResetPassword;

use \MolnApps\ResetPassword\Contracts\Token;
use \MolnApps\ResetPassword\Contracts\TokenValidator;

class BaseToken implements Token, TokenValidator
{
	private $cleanToken;
	private $token;

	public function __construct($expiration, $token = null)
	{
		$this->token = ($token) ?: $this->generateNewToken();
		$this->expiration = $expiration;
	}

	private function generateNewToken()
	{
		$this->cleanToken = hash('sha256', openssl_random_pseudo_bytes(64));

		return password_hash($this->cleanToken, PASSWORD_BCRYPT);
	}

	public function getToken()
	{
		return $this->token;
	}

	public function getCleanToken()
	{
		return $this->cleanToken;
	}

	public function getExpiration()
	{
		return $this->expiration;
	}

	public function validate($token)
	{
		return $this->isValid($token) && ! $this->isExpired();
	}

	private function isValid($token)
	{
		return password_verify($token, $this->getToken());
	}

	private function isExpired()
	{
		return $this->getExpiration() < time();
	}
}