<?php

namespace MolnApps\ResetPassword;

use \MolnApps\ResetPassword\Contracts\UserRepository;
use \MolnApps\ResetPassword\Contracts\Authenticator;
use \MolnApps\ResetPassword\Contracts\EventDispatcher;

class ChangePasswordManagerTest extends \PHPUnit_Framework_TestCase
{
	private $userRepository;
	private $authenticator;
	private $eventDispatcher;

	/** @test */
	public function it_can_be_instantiated()
	{
		$manager = $this->createChangePasswordManager();

		$this->assertNotNull($manager);
	}

	/** @test */
	public function it_returns_false_if_account_does_not_exists()
	{
		$manager = $this->createChangePasswordManager();

		$this->assertFalse($manager->changePassword('jane.doe@example.com', 'WrongPassword', 'NewPassword'));
	}

	/** @test */
	public function it_returns_false_if_old_password_does_not_match()
	{
		$manager = $this->createChangePasswordManager();

		$this->assertFalse($manager->changePassword('john.doe@example.com', 'WrongPassword', 'NewPassword'));
	}

	/** @test */
	public function it_returns_true_if_old_password_matches()
	{
		$manager = $this->createChangePasswordManager();

		$this->shouldStoreNewPassword('john.doe@example.com', 'NewPassword');
		$this->shouldFireEvent('passwordWasChanged', ['username' => 'john.doe@example.com']);

		$this->assertTrue($manager->changePassword('john.doe@example.com', 'ValidPassword', 'NewPassword'));
	}

	private function shouldStoreNewPassword($username, $newPassword)
	{
		$this->userRepository->expects($this->once())->method('storePassword')->with($username, $newPassword);
	}

	private function shouldFireEvent($event, array $context)
	{
		$this->eventDispatcher->expects($this->once())->method('fireEvent')->with($event, $context);
	}

	private function createChangePasswordManager()
	{
		$this->userRepository = $this->createUserRepositoryStub();
		$this->authenticator = $this->createAuthenticatorStub();
		$this->eventDispatcher = $this->createEventDispatcherStub();

		return new ChangePasswordManager($this->userRepository, $this->authenticator, $this->eventDispatcher);
	}

	private function createUserRepositoryStub()
	{
		$repository = $this->createMock(UserRepository::class);

		$map = [
			['john.doe@example.com', true],
			['jane.doe@example.com', false],
		];

		$repository
			->method('accountExists')
			->will($this->returnValueMap($map));

		return $repository;
	}

	private function createAuthenticatorStub()
	{
		$authenticator = $this->createMock(Authenticator::class);

		$map = [
			['john.doe@example.com', 'ValidPassword', true],
			['john.doe@example.com', 'WrongPassword', false],
		];

		$authenticator
			->method('authenticate')
			->will($this->returnValueMap($map));

		return $authenticator;
	}

	private function createEventDispatcherStub()
	{
		$eventDispatcher = $this->createMock(EventDispatcher::class);
		return $eventDispatcher;
	}
}