<?php

namespace MolnApps\ResetPassword;

use \MolnApps\ResetPassword\Contracts\EventDispatcher;

class NullEventDispatcher implements EventDispatcher
{
	public function notify($event, array $context = [])
	{
		// Do nothing
	}
}