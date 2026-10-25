<?php

namespace App\Tests\Architecture;

use ApiPlatform\Metadata\ApiResource;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Vérifie les « Règles d'or » d'AGENTS.md sur le code source.
 * Ces tests tournent en CI : une règle contournée fait échouer le build.
 */
class SourceArchitectureTest extends TestCase
{
    private const SRC = __DIR__.'/../../src';
    private const TESTS = __DIR__.'/..';

    /** Seuls contrôleurs Symfony autorisés hors back-office : redirection de « / » et formulaire /login. */
    private const ALLOWED_CONTROLLERS = ['HomeController.php', 'SecurityController.php'];

    public function testNoManualJsonResponse(): void
    {
        foreach ($this->phpFiles(self::SRC) as $file) {
            $this->assertDoesNotMatchRegularExpression(
                '/JsonResponse|->json\(/',
                file_get_contents($file),
                sprintf('%s : pas de JSON à la main, passer par API Platform (règle d\'or 1).', $this->relative($file)),
            );
        }
    }

    public function testNoSymfonyControllerOutsideBackOffice(): void
    {
        foreach (glob(self::SRC.'/Controller/*.php') as $file) {
            $this->assertContains(
                basename($file),
                self::ALLOWED_CONTROLLERS,
                sprintf('%s : pas de contrôleur Symfony hors src/Controller/Admin (règles d\'or 1 et 2).', $this->relative($file)),
            );
        }
    }

    public function testAdminControllersUseEasyAdmin(): void
    {
        foreach ($this->classes('Controller/Admin') as $class) {
            $this->assertTrue(
                $class->isSubclassOf(AbstractCrudController::class) || $class->isSubclassOf(AbstractDashboardController::class),
                sprintf('%s doit étendre AbstractCrudController (règle d\'or 2).', $class->getName()),
            );
        }
    }

    public function testApiResourcesDeclareExplicitOperations(): void
    {
        foreach ($this->apiResourceClasses() as $class) {
            foreach ($class->getAttributes(ApiResource::class) as $attribute) {
                $this->assertNotNull(
                    $attribute->newInstance()->getOperations(),
                    sprintf('%s : #[ApiResource] doit lister ses operations (règle d\'or 1).', $class->getName()),
                );
            }
        }
    }

    public function testPasswordIsNeverInReadGroup(): void
    {
        // Test valide même s'il n'y a rien à vérifier (aucune entité/propriété concernée)
        $this->addToAssertionCount(1);

        foreach ([...$this->classes('Entity'), ...$this->classes('ApiResource')] as $class) {
            foreach ($class->getProperties() as $property) {
                if (!str_contains(strtolower($property->getName()), 'password')) {
                    continue;
                }
                foreach ($property->getAttributes(Groups::class) as $attribute) {
                    foreach ($attribute->newInstance()->getGroups() as $group) {
                        $this->assertStringEndsNotWith(
                            ':read',
                            $group,
                            sprintf('%s::$%s ne doit pas être dans un groupe :read.', $class->getName(), $property->getName()),
                        );
                    }
                }
            }
        }
    }

    public function testApiResourceEntitiesHaveCrudController(): void
    {
        // Test valide même s'il n'y a rien à vérifier (aucune entité/propriété concernée)
        $this->addToAssertionCount(1);

        $managed = [];
        foreach ($this->classes('Controller/Admin') as $class) {
            if ($class->isSubclassOf(AbstractCrudController::class) && !$class->isAbstract()) {
                $managed[] = $class->getMethod('getEntityFqcn')->invoke(null);
            }
        }

        foreach ($this->classes('Entity') as $class) {
            if ($class->getAttributes(ApiResource::class)) {
                $this->assertContains(
                    $class->getName(),
                    $managed,
                    sprintf('%s est exposée en API : créer son CrudController EasyAdmin et son entrée de menu.', $class->getName()),
                );
            }
        }
    }

    public function testStateAndServicesHaveUnitTests(): void
    {
        foreach (['State', 'Service'] as $dir) {
            foreach (glob(self::SRC.'/'.$dir.'/*.php') as $file) {
                $test = sprintf('%s/Unit/%s/%sTest.php', self::TESTS, $dir, basename($file, '.php'));
                $this->assertFileExists(
                    $test,
                    sprintf('%s n\'a pas de test unitaire (attendu : tests/Unit/%s/%sTest.php).', $this->relative($file), $dir, basename($file, '.php')),
                );
            }
        }
    }

    /** @return list<\ReflectionClass<object>> */
    private function apiResourceClasses(): array
    {
        return array_values(array_filter(
            [...$this->classes('Entity'), ...$this->classes('ApiResource')],
            fn (\ReflectionClass $class) => (bool) $class->getAttributes(ApiResource::class),
        ));
    }

    /** @return list<\ReflectionClass<object>> */
    private function classes(string $dir): array
    {
        $classes = [];
        foreach ($this->phpFiles(self::SRC.'/'.$dir) as $file) {
            $relative = substr(realpath($file), strlen(realpath(self::SRC)) + 1, -4);
            $name = 'App\\'.str_replace('/', '\\', $relative);
            if (class_exists($name)) {
                $classes[] = new \ReflectionClass($name);
            }
        }

        return $classes;
    }

    /** @return list<string> */
    private function phpFiles(string $dir): array
    {
        if (!is_dir($dir)) {
            return [];
        }
        $files = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)) as $file) {
            if ('php' === $file->getExtension()) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    private function relative(string $file): string
    {
        return 'src/'.substr(realpath($file), strlen(realpath(self::SRC)) + 1);
    }
}
