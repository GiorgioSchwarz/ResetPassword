<?php

namespace MolnApps\ResetPassword;

use \MolnApps\ResetPassword\Contracts\EventDispatcher;

class NullEventDispatcherTest extends \PHPUnit_Framework_TestCase
{
	/** @test */
	public function it_can_be_instantiated()
	{
		$eventDispatcher = new NullEventDispatcher;

		$this->assertNotNull($eventDispatcher);
		$this->assertInstanceOf(EventDispatcher::class, $eventDispatcher);
	}
}