<?php

namespace MolnApps\ResetPassword;

use \MolnApps\ResetPassword\Contracts\UserRepository;
use \MolnApps\ResetPassword\Contracts\Authenticator;
use \MolnApps\ResetPassword\Contracts\EventDispatcher;

class ChangePasswordManager
{
	private $userRepository;
	private $authenticator;
	private $eventDispatcher;

	public function __construct(
		UserRepository $userRepository, 
		Authenticator $authenticator,
		EventDispatcher $eventDispatcher
	) {
		$this->userRepository = $userRepository;
		$this->authenticator = $authenticator;
		$this->eventDispatcher = $eventDispatcher;
	}

	public function changePassword($username, $oldPassword, $newPassword)
	{
		if ( ! $this->userRepository->accountExists($username)) {
			return false;
		}

		if ( ! $this->authenticator->authenticate($username, $oldPassword)) {
			return false;
		}

		$this->userRepository->storePassword($username, $newPassword);
		
		$this->eventDispatcher->fireEvent('passwordWasChanged', ['username' => $username]);
		
		return true;
	}
}