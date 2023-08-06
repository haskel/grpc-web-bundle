<?php
declare(strict_types=1);

namespace Haskel\GrpcWebBundle\Routing;

use FilesystemIterator;
use Haskel\GrpcWebBundle\Attribute\Service;
use RecursiveCallbackFilterIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionMethod;
use SplFileInfo;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Routing\Loader\AnnotationClassLoader;
use Symfony\Component\Routing\Loader\AnnotationDirectoryLoader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class GrpcServiceRouteLoader extends AnnotationDirectoryLoader
{
    private bool $isLoaded = false;

    public function __construct(
        FileLocatorInterface $locator,
        AnnotationClassLoader $loader,
    ) {
        parent::__construct($locator, $loader);
    }

    public function load($path, string $type = null): RouteCollection
    {
        if (true === $this->isLoaded) {
            throw new \RuntimeException('Do not add the "grpc" loader twice');
        }

        $files = iterator_to_array(new RecursiveIteratorIterator(
            new RecursiveCallbackFilterIterator(
                new \RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS),
                fn (SplFileInfo $current) => !str_starts_with($current->getBasename(), '.')
            ),
            RecursiveIteratorIterator::LEAVES_ONLY
        ));
        usort($files, fn (SplFileInfo $a, SplFileInfo $b) => (string) $a > (string) $b ? 1 : -1);

        $routes = new RouteCollection();

        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            if (!$file->isFile() || !str_ends_with($file->getFilename(), '.php')) {
                continue;
            }

            if ($class = $this->findClass($file->getPathname())) {
                $reflectionClass = new ReflectionClass($class);
                if ($reflectionClass->isAbstract()) {
                    continue;
                }

                $servicePath = null;
                foreach ($reflectionClass->getAttributes(Service::class) as $attr) {
                    $arguments = $attr->getArguments();
                    $servicePath = $arguments[0] ?? null;
                }

                if ($servicePath === null) {
                    // todo: log warning
                    continue;
                }

                $methods = $reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);
                if (!$methods) {
                    continue;
                }

                foreach ($methods as $method) {
                    if (str_starts_with($method->getName(), '__')) { // skip magic methods
                        continue;
                    }

                    $name = str_replace("/", "", $servicePath) . '_' . $method->getName();

                    $route = new Route(
                        $servicePath . "/" . $method->getName(),
                        [
                            '_controller' => $class . '::' . $method->getName(),
                        ],
                        [],
                        [],
                        null,
                        [],
                        ['POST'],
                    );
                    $routes->add($name, $route);
                }
            }
        }

        $this->isLoaded = true;

        return $routes;
    }

    public function supports($resource, string $type = null): bool
    {
        return 'grpc' === $type;
    }
}
