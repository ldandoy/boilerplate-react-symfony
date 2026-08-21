<?php

namespace App\Tests\Unit\State;

use App\Entity\User;
use App\State\MeProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class MeProviderTest extends TestCase
{
    private function createUserWithId(int $id, string $email): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setIsVerified(true);

        $reflection = new \ReflectionProperty(User::class, 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($user, $id);

        return $user;
    }

    public function testUnauthenticatedThrowsUnauthorizedException(): void
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);

        $provider = new MeProvider($security);

        $this->expectException(UnauthorizedHttpException::class);

        $provider->provide($this->createStub(\ApiPlatform\Metadata\Operation::class));
    }

    public function testAuthenticatedReturnsPopulatedMeDto(): void
    {
        $user = $this->createUserWithId(3, 'user@example.com');

        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn($user);

        $provider = new MeProvider($security);
        $me = $provider->provide($this->createStub(\ApiPlatform\Metadata\Operation::class));

        $this->assertSame(3, $me->id);
        $this->assertSame('user@example.com', $me->email);
        $this->assertSame(['ROLE_USER'], $me->roles);
        $this->assertTrue($me->isVerified);
    }
}
