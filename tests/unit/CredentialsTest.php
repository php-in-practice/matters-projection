<?php

namespace PhpInPractice\Matters;

/**
 * @coversDefaultClass PhpInPractice\Matters\Credentials
 * @covers ::<private>
 */
class CredentialsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @covers ::fromUsernameAndPassword
     * @covers ::username
     * @covers ::password
     */
    public function it_should_take_a_username_and_password()
    {
        $username = 'mike';
        $password = 'itworks';

        $credentials = Credentials::fromUsernameAndPassword($username, $password);

        $this->assertSame($username, $credentials->username());
        $this->assertSame($password, $credentials->password());
    }

    /**
     * @test
     * @covers ::fromUsernameAndPassword
     * @covers ::basicAuthentication
     */
    public function it_should_provide_a_basic_authentication_token()
    {
        $username = 'mike';
        $password = 'itworks';

        $credentials = Credentials::fromUsernameAndPassword($username, $password);

        $this->assertSame('bWlrZTppdHdvcmtz', $credentials->basicAuthentication());
    }
}
