<?php

namespace MolnApps\ResetPassword\Contracts;

interface TokenValidator
{
	public function validate($token);
}