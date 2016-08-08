<?php

namespace MolnApps\ResetPassword\Contracts;

interface Token
{
	public function getToken();
	public function getCleanToken();
	public function getExpiration();
}