<?php

namespace MolnApps\ResetPassword\Contracts;

interface Repository
{
	public function accountExists($username);
	public function storePassword($username, $password);
	
	public function getAllTokens($username);
	public function deleteAllTokens($username);
	public function storeToken(array $row);
}