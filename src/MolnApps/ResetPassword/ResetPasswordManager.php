<?php

namespace MolnApps\ResetPassword;

use \MolnApps\ResetPassword\Contracts\Repository;
use \MolnApps\ResetPassword\Contracts\TokenFactory;
use \MolnApps\ResetPassword\Contracts\EventDispatcher;

class ResetPasswordManager
{
	private $repository;
	private $tokenFactory;
	private $eventDispatcher;

	public function __construct(
		Repository $repository, 
		TokenFactory $tokenFactory, 
		EventDispatcher $eventDispatcher
	) {
		$this->repository = $repository;
		$this->tokenFactory = $tokenFactory;
		$this->eventDispatcher = $eventDispatcher;
	}

	public function createToken($username)
	{
		if ( ! $this->repository->accountExists($username)) {
			return;
		}

		$this->repository->deleteAllTokens($username);

		$token = $this->tokenFactory->getNewToken();

		$this->repository->storeToken([
			'username' => $username,
			'token' => $token->getToken(),
			'expiration' => $token->getExpiration(),
		]);

		$this->eventDispatcher->fireEvent('tokenWasCreated', [
			'username' => $username,
			'token' => $token->getCleanToken()
		]);
	}

	public function resetPassword($username, $cleanToken, $password)
	{
		if ( ! $this->repository->accountExists($username)) {
			return;
		}

		if ( ! $this->validateToken($username, $cleanToken)) {
			return;
		}
		
		$this->repository->storePassword($username, $password);

		$this->eventDispatcher->fireEvent('passwordWasReset', ['username' => $username]);

		$this->repository->deleteAllTokens($username);
	}

	private function validateToken($username, $cleanToken)
	{
		$rows = $this->repository->getAllTokens($username);

		foreach ($rows as $row) {
			$token = $this->tokenFactory->getTokenValidator($row['token'], $row['expiration']);
			
			if ($token->validate($cleanToken)) {
				return true;
			}
		}
	}
}