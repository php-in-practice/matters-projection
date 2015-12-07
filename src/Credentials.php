<?php

namespace PhpInPractice\Matters;

final class Credentials
{
    private $username;
    private $password;

    public static function fromUsernameAndPassword($username, $password)
    {
        return new static($username, $password);
    }

    public function username()
    {
        return $this->username;
    }

    public function password()
    {
        return $this->password;
    }

    public function basicAuthentication()
    {
        return base64_encode($this->username . ':' . $this->password);
    }

    private function __construct($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
    }
}
