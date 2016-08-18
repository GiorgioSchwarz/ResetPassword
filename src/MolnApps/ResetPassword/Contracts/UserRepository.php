<?php

namespace MolnApps\ResetPassword\Contracts;

interface UserRepository
{
	public function accountExists($username);
	public function storePassword($username, $password);
}