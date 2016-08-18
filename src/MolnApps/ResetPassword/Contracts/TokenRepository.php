<?php

namespace MolnApps\ResetPassword\Contracts;

interface TokenRepository
{
	public function getAllTokens($username);
	public function deleteAllTokens($username);
	public function storeToken(array $row);
}