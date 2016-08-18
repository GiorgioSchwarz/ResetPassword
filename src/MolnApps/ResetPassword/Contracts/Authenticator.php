<?php

namespace MolnApps\ResetPassword\Contracts;

interface Authenticator
{
	public function authenticate($username, $password);
}