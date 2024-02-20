<?php

declare(strict_types=1);

namespace App\OpenApi;

use ApiPlatform\Core\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\Core\OpenApi\Model;
use ApiPlatform\Core\OpenApi\OpenApi;
use Doctrine\DBAL\Types\Type;

final class OpenApiFactory implements OpenApiFactoryInterface
{
    private OpenApiFactoryInterface $decorated;

    public function __construct(OpenApiFactoryInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    /**
     * @param Type[] $context
     *
     * @return OpenApi
     */
    public function __invoke(array $context = []): OpenApi
    {
        $openApi = $this->decorated->__invoke($context);

        $paths = $openApi->getPaths();
        $paths = $paths->getPaths();

        $filteredPaths = new Model\Paths();

        foreach ($paths as $path => $pathItem) {
            // If a prefix is configured on API Platform's routes, it must appear here.
            if ('/weathers/{id}' === $path) {
                continue;
            }
            $filteredPaths->addPath((string) $path, $pathItem);
        }

        return $openApi->withPaths($filteredPaths);
    }
}
