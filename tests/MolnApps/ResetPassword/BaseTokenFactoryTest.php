<?php

namespace MolnApps\ResetPassword;

use \MolnApps\ResetPassword\Contracts\TokenFactory;
use \MolnApps\ResetPassword\Contracts\Token;
use \MolnApps\ResetPassword\Contracts\TokenValidator;

class BaseTokenFactoryTest extends \PHPUnit_Framework_TestCase
{
	/** @test */
	public function it_can_be_instantiated()
	{
		$tokenGenerator = new BaseTokenFactory;

		$this->assertNotNull($tokenGenerator);
		$this->assertInstanceOf(TokenFactory::class, $tokenGenerator);
	}

	/** @test */
	public function it_returns_a_new_token_instance_expiring_in_one_day()
	{
		$tokenGenerator = new BaseTokenFactory;

		$this->assertInstanceOf(Token::class, $tokenGenerator->getNewToken());
		$this->assertInstanceOf(BaseToken::class, $tokenGenerator->getNewToken());

		$this->assertEquals(strtotime('+1 day'), $tokenGenerator->getNewToken()->getExpiration());
	}

	/** @test */
	public function it_returns_a_token_validator_instance()
	{
		$tokenGenerator = new BaseTokenFactory;

		$token = $tokenGenerator->getNewToken();

		$tokenValidator = $tokenGenerator->getTokenValidator($token->getToken(), $token->getExpiration());

		$this->assertInstanceOf(TokenValidator::class, $tokenValidator);
	}
}