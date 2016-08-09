<?php

namespace MolnApps\ResetPassword;

use \MolnApps\ResetPassword\Contracts\EventDispatcher;

class NullEventDispatcher implements EventDispatcher
{
	public function fireEvent($event, array $context = [])
	{
		// Do nothing
	}
}