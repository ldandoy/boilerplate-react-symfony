<?php

namespace App\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase as BaseApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

abstract class ApiTestCase extends BaseApiTestCase
{
    protected static ?bool $alwaysBootKernel = false;

    protected const PASSWORD = 'a-valid-password';

    protected Client $client;

    private static bool $schemaCreated = false;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        // Base de données vierge pour chaque test : schéma créé une fois, tables vidées ensuite
        $entityManager = $this->getEntityManager();
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();

        if (!self::$schemaCreated) {
            $schemaTool = new SchemaTool($entityManager);
            $schemaTool->dropSchema($metadata);
            $schemaTool->createSchema($metadata);
            self::$schemaCreated = true;
        } else {
            $connection = $entityManager->getConnection();
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
            foreach ($metadata as $classMetadata) {
                $connection->executeStatement('TRUNCATE TABLE '.$connection->quoteSingleIdentifier($classMetadata->getTableName()));
            }
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
        }

        // Évite que le login_throttling ne bloque des tests d'une exécution à l'autre
        static::getContainer()->get('cache.rate_limiter')->clear();
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    protected function createUser(string $email = 'user@example.com', bool $isVerified = true, array $roles = []): User
    {
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($hasher->hashPassword($user, self::PASSWORD));
        $user->setIsVerified($isVerified);
        $user->setRoles($roles);

        $entityManager = $this->getEntityManager();
        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }

    protected function findUser(string $email): ?User
    {
        $entityManager = $this->getEntityManager();
        $entityManager->clear();

        return $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
    }

    /**
     * @return array{token: string, refreshToken: string}
     */
    protected function login(string $email = 'user@example.com', string $password = self::PASSWORD): array
    {
        $response = $this->client->request('POST', '/api/login_check', [
            'json' => ['email' => $email, 'password' => $password],
        ]);

        $this->assertResponseIsSuccessful();

        return $response->toArray();
    }
}
