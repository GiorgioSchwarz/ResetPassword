<?php

namespace MolnApps\ResetPassword;

use \MolnApps\ResetPassword\Contracts\Token;
use \MolnApps\ResetPassword\Contracts\TokenValidator;

class BaseTokenTest extends \PHPUnit_Framework_TestCase
{
	/** @test */
	public function it_must_be_instantiated_with_expiration_timestamp()
	{
		$token = new BaseToken(strtotime('+1 day'));

		$this->assertNotNull($token);
		$this->assertInstanceOf(Token::class, $token);
		$this->assertInstanceOf(TokenValidator::class, $token);
	}

	/** @test */
	public function it_creates_a_new_token_encrypted_with_bcrypt()
	{
		$token = new BaseToken(strtotime('+1 day'));

		$this->assertBcrypt($token->getToken());
	}

	/** @test */
	public function it_returns_unencrypted_token()
	{
		$token = new BaseToken(strtotime('+1 day'));

		$this->assertNotNull($token->getCleanToken());
		$this->assertNotEquals($token->getCleanToken(), $token->getToken());
	}

	/** @test */
	public function it_creates_an_expiration_unix_timestamp()
	{
		$token = new BaseToken(strtotime('+1 day'));

		$this->assertEquals(strtotime('+1 day'), $token->getExpiration());
	}

	/** @test */
	public function it_can_be_instantiated_with_stored_token_and_expiration_timestamp()
	{
		$storedToken = password_hash('foobar', PASSWORD_BCRYPT);
		$storedExpiration = strtotime('+7 days');

		$token = new BaseToken($storedExpiration, $storedToken);

		$this->assertEquals($storedToken, $token->getToken());
		$this->assertEquals($storedExpiration, $token->getExpiration());
		$this->assertNull($token->getCleanToken());
	}

	/** @test */
	public function it_validates_stored_token()
	{
		$storedToken = password_hash('foobar', PASSWORD_BCRYPT);
		$storedExpiration = strtotime('+7 days');

		$token = new BaseToken($storedExpiration, $storedToken);

		$this->assertTrue($token->validate('foobar'));
	}

	/** @test */
	public function it_wont_validate_wrong_stored_token()
	{
		$storedToken = password_hash('foobar', PASSWORD_BCRYPT);
		$storedExpiration = strtotime('+7 days');

		$token = new BaseToken($storedExpiration, $storedToken);

		$this->assertFalse($token->validate('barbaz'));
	}

	/** @test */
	public function it_wont_validate_expired_token()
	{
		$storedToken = password_hash('foobar', PASSWORD_BCRYPT);
		$storedExpiration = strtotime('-7 days');

		$token = new BaseToken($storedExpiration, $storedToken);

		$this->assertFalse($token->validate('foobar'));
	}

	private function assertBcrypt($token)
	{
		$this->assertEquals('$2y$', substr($token, 0, 4));
	}
}