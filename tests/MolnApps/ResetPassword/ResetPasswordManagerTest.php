<?php

namespace MolnApps\ResetPassword;

use \MolnApps\ResetPassword\Contracts\Repository;
use \MolnApps\ResetPassword\Contracts\TokenFactory;
use \MolnApps\ResetPassword\Contracts\Token;
use \MolnApps\ResetPassword\Contracts\TokenValidator;
use \MolnApps\ResetPassword\Contracts\EventDispatcher;

class ResetPasswordManagerTest extends \PHPUnit_Framework_TestCase
{
	private $repository;
	private $tokenGenerator;
	private $eventDispatcher;

	/** @test */
	public function it_can_be_instantiated()
	{
		$repository = $this->createMock(Repository::class);
		$tokenGenerator = $this->createMock(TokenFactory::class);
		$eventDispatcher = $this->createMock(EventDispatcher::class);

		$manager = new ResetPasswordManager($repository, $tokenGenerator, $eventDispatcher);

		$this->assertNotNull($manager);
	}

	/** @test */
	public function it_will_create_a_new_token_and_fire_event_if_account_exists()
	{
		$manager = $this->createResetPasswordManager();

		$this->shouldDeleteAccountTokens('john.doe@example.com');
		$this->shouldStoreToken('john.doe@example.com', 'abc123', strtotime('+1 day'));
		$this->shouldFireEvent('tokenWasCreated', [
			'username' => 'john.doe@example.com', 
			'token' => 'abc'
		]);

		$manager->createToken('john.doe@example.com');
	}

	/** @test */
	public function it_wont_create_a_new_token_and_fire_event_if_account_not_exists()
	{
		$manager = $this->createResetPasswordManager();

		$this->shouldNotStoreToken();
		$this->shouldNotFireEvent('tokenWasCreated');

		$manager->createToken('jane.doe@example.com');
	}

	/** @test */
	public function it_will_reset_password_and_fire_event_and_destroy_token_if_account_exists_and_token_is_valid()
	{
		$manager = $this->createResetPasswordManager();

		$this->shouldStoreAccountPassword('john.doe@example.com', 'New_Password_123');
		$this->shouldFireEvent('passwordWasReset', ['username' => 'john.doe@example.com']);
		$this->shouldDeleteAccountTokens('john.doe@example.com');

		$manager->resetPassword('john.doe@example.com', 'abc123', 'New_Password_123');
	}

	/** @test */
	public function it_wont_reset_password_and_fire_event_if_account_does_not_exists()
	{
		$manager = $this->createResetPasswordManager();

		$this->shouldNotStoreAccountPassword();
		$this->shouldNotFireEvent('passwordWasReset');

		$manager->resetPassword('jane.doe@example.com', 'abc123', 'New_Password_123');
	}

	/** @test */
	public function it_wont_reset_password_and_fire_event_if_account_exists_but_token_is_not_valid()
	{
		$manager = $this->createResetPasswordManager();

		$this->shouldNotStoreAccountPassword();
		$this->shouldNotFireEvent('passwordWasReset');

		$manager->resetPassword('john.doe@example.com', 'foobar', 'New_Password_123');
	}

	/** @test */
	public function it_wont_reset_password_and_fire_event_if_account_exists_but_token_is_expired()
	{
		$manager = $this->createResetPasswordManager();

		$this->shouldNotStoreAccountPassword();
		$this->shouldNotFireEvent('passwordWasReset');

		$manager->resetPassword('john.doe@example.com', 'def456', 'New_Password_123');
	}

	// ! Factory method

	private function createResetPasswordManager()
	{
		$this->repository = $this->createRepositoryStub();
		$this->tokenGenerator = $this->createTokenFactoryStub();
		$this->eventDispatcher = $this->createEventDispatcherStub();

		return new ResetPasswordManager($this->repository, $this->tokenGenerator, $this->eventDispatcher);
	}

	// ! Prophecy methods

	private function shouldStoreToken($username, $token, $expiration)
	{
		$this->repository
			->expects($this->once())
			->method('storeToken')
			->with([
				'username' => $username, 
				'token' => $token, 
				'expiration' => $expiration,
			]);
	}

	private function shouldNotStoreToken()
	{
		$this->repository
			->expects($this->never())
			->method('storeToken');
	}

	private function shouldStoreAccountPassword($username, $password)
	{
		$this->repository
			->expects($this->once())
			->method('storePassword')
			->with($username, $password);
	}

	private function shouldDeleteAccountTokens($username)
	{
		$this->repository
			->expects($this->once())
			->method('deleteAllTokens')
			->with($username);
	}

	private function shouldNotStoreAccountPassword()
	{
		$this->repository
			->expects($this->never())
			->method('storePassword');
	}

	private function shouldFireEvent($event, $context = [])
	{
		$this->eventDispatcher
			->expects($this->once())
			->method('fireEvent')
			->with($event, $context);
	}

	private function shouldNotFireEvent($event)
	{
		$this->eventDispatcher
			->expects($this->never())
			->method('fireEvent')
			->with($event);
	}

	// ! Stub factory methods

	private function createRepositoryStub()
	{
		$repository = $this->createMock(Repository::class);

		$map = [
			['john.doe@example.com', true],
			['jane.doe@example.com', false],
		];

		$repository
			->method('accountExists')
			->will($this->returnValueMap($map));

		$map = [
			['john.doe@example.com', [$this->getExpiredTokenRow(), $this->getValidTokenRow()]],
		];

		$repository
			->method('getAllTokens')
			->will($this->returnValueMap($map));

		return $repository;
	}

	private function getExpiredTokenRow()
	{
		return [
			'username' => 'john.doe@example.com', 
			'token' => 'def456', 
			'expiration' => strtotime('-3 days'),
		];
	}

	private function getValidTokenRow()
	{
		$token = $this->createTokenStub();

		return [
			'username' => 'john.doe@example.com', 
			'token' => $token->getToken(), 
			'expiration' => $token->getExpiration(),
		];
	}

	private function createTokenStub()
	{
		$token = $this->createMock(Token::class);
		
		$token->method('getToken')->willReturn('abc123');
		$token->method('getCleanToken')->willReturn('abc');
		$token->method('getExpiration')->willReturn(strtotime('+1 day'));

		return $token;
	}

	private function createTokenValidatorStub()
	{
		$tokenValidator = $this->createMock(TokenValidator::class);
		
		$map = [
			['abc123', true],
			[$this->any(), false],
		];
		
		$tokenValidator
			->method('validate')
			->will($this->returnValueMap($map));

		return $tokenValidator;
	}

	private function createTokenFactoryStub(Token $token = null, TokenValidator $tokenValidator = null)
	{
		$token = ($token) ?: $this->createTokenStub();
		$tokenValidator = ($tokenValidator) ?: $this->createTokenValidatorStub();

		$tokenGenerator = $this->createMock(TokenFactory::class);
		$tokenGenerator->method('getNewToken')->willReturn($token);

		$tokenGenerator->method('getTokenValidator')->willReturn($tokenValidator);

		return $tokenGenerator;
	}

	private function createEventDispatcherStub()
	{
		$eventDispatcher = $this->createMock(EventDispatcher::class);
		return $eventDispatcher;
	}
}