<?php

namespace MolnApps\ResetPassword\Contracts;

interface TokenFactory
{
	// @returns Token
	public function getNewToken();

	// @returns TokenValidator
	public function getTokenValidator($token, $expiration);
}