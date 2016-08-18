<?php

namespace MolnApps\ResetPassword;

use \MolnApps\ResetPassword\Contracts\UserRepository;
use \MolnApps\ResetPassword\Contracts\TokenRepository;
use \MolnApps\ResetPassword\Contracts\TokenFactory;
use \MolnApps\ResetPassword\Contracts\EventDispatcher;

class ResetPasswordManager
{
	private $userRepository;
	private $tokenRepository;
	private $tokenFactory;
	private $eventDispatcher;

	public function __construct(
		UserRepository $userRepository, 
		TokenRepository $tokenRepository,
		TokenFactory $tokenFactory, 
		EventDispatcher $eventDispatcher
	) {
		$this->userRepository = $userRepository;
		$this->tokenRepository = $tokenRepository;
		$this->tokenFactory = $tokenFactory;
		$this->eventDispatcher = $eventDispatcher;
	}

	public function createToken($username)
	{
		if ( ! $this->userRepository->accountExists($username)) {
			return false;
		}

		$this->tokenRepository->deleteAllTokens($username);

		$token = $this->tokenFactory->getNewToken();

		$this->tokenRepository->storeToken([
			'username' => $username,
			'token' => $token->getToken(),
			'expiration' => $token->getExpiration(),
		]);

		$this->eventDispatcher->fireEvent('tokenWasCreated', [
			'username' => $username,
			'token' => $token->getCleanToken()
		]);

		return true;
	}

	public function resetPassword($username, $cleanToken, $password)
	{
		if ( ! $this->userRepository->accountExists($username)) {
			return false;
		}

		if ( ! $this->validateToken($username, $cleanToken)) {
			return false;
		}
		
		$this->userRepository->storePassword($username, $password);

		$this->eventDispatcher->fireEvent('passwordWasReset', ['username' => $username]);

		$this->tokenRepository->deleteAllTokens($username);

		return true;
	}

	private function validateToken($username, $cleanToken)
	{
		$rows = $this->tokenRepository->getAllTokens($username);

		foreach ($rows as $row) {
			$token = $this->tokenFactory->getTokenValidator($row['token'], $row['expiration']);
			
			if ($token->validate($cleanToken)) {
				return true;
			}
		}
	}
}