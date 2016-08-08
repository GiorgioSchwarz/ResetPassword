<?php

namespace MolnApps\ResetPassword\Contracts;

interface EventDispatcher
{
	public function fireEvent($event, array $context = []);
}