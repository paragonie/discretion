<?php
declare(strict_types=1);
namespace ParagonIE\Discretion\Tests\Struct;

use ParagonIE\Discretion\Data\HiddenString;
use ParagonIE\Discretion\Struct\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    /**
     * @covers User::checkPassword()
     * @covers User::setPassword()
     */
    public function testPassword()
    {
        $user = new User();
        $password = new HiddenString(\random_bytes(32));

        $user->setPassword($password);

        $this->assertTrue($user->checkPassword($password));
        $this->assertFalse($user->checkPassword(new HiddenString(\random_bytes(33))));
    }


}