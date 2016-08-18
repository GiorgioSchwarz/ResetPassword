<?php

namespace MolnApps\ResetPassword;

use \MolnApps\ResetPassword\Contracts\UserRepository;
use \MolnApps\ResetPassword\Contracts\TokenRepository;
use \MolnApps\ResetPassword\Contracts\TokenFactory;
use \MolnApps\ResetPassword\Contracts\Token;
use \MolnApps\ResetPassword\Contracts\TokenValidator;
use \MolnApps\ResetPassword\Contracts\EventDispatcher;

class ResetPasswordManagerTest extends \PHPUnit_Framework_TestCase
{
	private $userRepository;
	private $tokenRepository;
	private $tokenGenerator;
	private $eventDispatcher;

	/** @test */
	public function it_can_be_instantiated()
	{
		$manager = $this->createResetPasswordManager();

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

		$result = $manager->createToken('john.doe@example.com');

		$this->assertTrue($result);
	}

	/** @test */
	public function it_wont_create_a_new_token_and_fire_event_if_account_not_exists()
	{
		$manager = $this->createResetPasswordManager();

		$this->shouldNotStoreToken();
		$this->shouldNotFireEvent('tokenWasCreated');

		$result = $manager->createToken('jane.doe@example.com');

		$this->assertFalse($result);
	}

	/** @test */
	public function it_will_reset_password_and_fire_event_and_destroy_token_if_account_exists_and_token_is_valid()
	{
		$manager = $this->createResetPasswordManager();

		$this->shouldStoreAccountPassword('john.doe@example.com', 'New_Password_123');
		$this->shouldFireEvent('passwordWasReset', ['username' => 'john.doe@example.com']);
		$this->shouldDeleteAccountTokens('john.doe@example.com');

		$result = $manager->resetPassword('john.doe@example.com', 'abc123', 'New_Password_123');

		$this->assertTrue($result);
	}

	/** @test */
	public function it_wont_reset_password_and_fire_event_if_account_does_not_exists()
	{
		$manager = $this->createResetPasswordManager();

		$this->shouldNotStoreAccountPassword();
		$this->shouldNotFireEvent('passwordWasReset');

		$result = $manager->resetPassword('jane.doe@example.com', 'abc123', 'New_Password_123');

		$this->assertFalse($result);
	}

	/** @test */
	public function it_wont_reset_password_and_fire_event_if_account_exists_but_token_is_not_valid()
	{
		$manager = $this->createResetPasswordManager();

		$this->shouldNotStoreAccountPassword();
		$this->shouldNotFireEvent('passwordWasReset');

		$result = $manager->resetPassword('john.doe@example.com', 'foobar', 'New_Password_123');

		$this->assertFalse($result);
	}

	/** @test */
	public function it_wont_reset_password_and_fire_event_if_account_exists_but_token_is_expired()
	{
		$manager = $this->createResetPasswordManager();

		$this->shouldNotStoreAccountPassword();
		$this->shouldNotFireEvent('passwordWasReset');

		$result = $manager->resetPassword('john.doe@example.com', 'def456', 'New_Password_123');

		$this->assertFalse($result);
	}

	// ! Factory method

	private function createResetPasswordManager()
	{
		$this->userRepository = $this->createUserRepositoryStub();
		$this->tokenRepository = $this->createTokenRepositoryStub();
		$this->tokenGenerator = $this->createTokenFactoryStub();
		$this->eventDispatcher = $this->createEventDispatcherStub();

		return new ResetPasswordManager($this->userRepository, $this->tokenRepository, $this->tokenGenerator, $this->eventDispatcher);
	}

	// ! Prophecy methods

	private function shouldStoreToken($username, $token, $expiration)
	{
		$this->tokenRepository
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
		$this->tokenRepository
			->expects($this->never())
			->method('storeToken');
	}

	private function shouldStoreAccountPassword($username, $password)
	{
		$this->userRepository
			->expects($this->once())
			->method('storePassword')
			->with($username, $password);
	}

	private function shouldDeleteAccountTokens($username)
	{
		$this->tokenRepository
			->expects($this->once())
			->method('deleteAllTokens')
			->with($username);
	}

	private function shouldNotStoreAccountPassword()
	{
		$this->userRepository
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

	private function createTokenRepositoryStub()
	{
		$tokenRepository = $this->createMock(TokenRepository::class);

		$map = [
			['john.doe@example.com', [$this->getExpiredTokenRow(), $this->getValidTokenRow()]],
		];

		$tokenRepository
			->method('getAllTokens')
			->will($this->returnValueMap($map));

		return $tokenRepository;
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