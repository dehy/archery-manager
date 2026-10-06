<?php

declare(strict_types=1);

namespace App\Tests\Integration\Api;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * docs/api/openapi.yaml is the contract the mobile app's client is generated from: it must describe
 * exactly the routes, and the error codes, that the application really has.
 */
final class OpenApiSpecTest extends KernelTestCase
{
    private const string SPEC = '/docs/api/openapi.yaml';

    private const string SERVER_PREFIX = '/api/v1';

    private const array HTTP_METHODS = ['get', 'put', 'post', 'delete', 'options', 'head', 'patch', 'trace'];

    /** @var array<string, mixed> */
    private array $spec;

    private string $projectDir;

    #[\Override]
    protected function setUp(): void
    {
        self::bootKernel();
        $this->projectDir = (string) self::getContainer()->getParameter('kernel.project_dir');
        $spec = Yaml::parseFile($this->projectDir.self::SPEC);
        $this->assertIsArray($spec);
        $this->spec = $spec;
    }

    public function testItIsAnOpenApi31Document(): void
    {
        $this->assertStringStartsWith('3.1', $this->spec['openapi']);
        $this->assertNotEmpty($this->spec['info']['title']);
        $this->assertSame(self::SERVER_PREFIX, $this->spec['servers'][0]['url']);
        $this->assertArrayHasKey('bearerAuth', $this->spec['components']['securitySchemes']);
    }

    public function testEveryRouteOfTheApiIsDocumentedAndNothingElse(): void
    {
        $this->assertEqualsCanonicalizing($this->routedOperations(), $this->documentedOperations());
    }

    public function testEveryReferenceResolves(): void
    {
        $references = [];
        $this->collectReferences($this->spec, $references);

        $this->assertNotEmpty($references);
        foreach (array_unique($references) as $reference) {
            $this->assertStringStartsWith('#/', $reference);
            $node = $this->spec;
            foreach (explode('/', substr($reference, 2)) as $segment) {
                $this->assertIsArray($node);
                $this->assertArrayHasKey($segment, $node, \sprintf('Dangling reference %s', $reference));
                $node = $node[$segment];
            }
        }
    }

    public function testOperationsAreWellFormed(): void
    {
        $ids = [];
        foreach ($this->spec['paths'] as $path => $operations) {
            foreach (array_intersect_key($operations, array_flip(self::HTTP_METHODS)) as $method => $operation) {
                $label = strtoupper((string) $method).' '.$path;
                $this->assertArrayHasKey('operationId', $operation, $label);
                $ids[] = $operation['operationId'];
                $this->assertNotEmpty($operation['tags'] ?? [], $label.' has no tag');
                $this->assertNotEmpty($operation['summary'] ?? '', $label.' has no summary');
                $this->assertNotEmpty($operation['responses'], $label.' has no response');

                $isPublic = [] === ($operation['security'] ?? null);
                if (!$isPublic) {
                    $this->assertArrayHasKey('401', $operation['responses'], $label.' is authenticated but does not document 401');
                }
            }
        }

        $this->assertSame($ids, array_unique($ids), 'operationId must be unique');
    }

    public function testTheErrorCodeEnumIsExactlyWhatTheApplicationCanReturn(): void
    {
        $documented = $this->spec['components']['schemas']['Error']['properties']['error']['enum'];

        $this->assertEqualsCanonicalizing($this->errorCodesInCode(), $documented);
    }

    /**
     * @return list<string> "METHOD /path" of the routes under /api/v1
     */
    private function routedOperations(): array
    {
        $operations = [];
        $router = self::getContainer()->get(RouterInterface::class);
        foreach ($router->getRouteCollection() as $name => $route) {
            if (!str_starts_with($name, 'api_v1_')) {
                continue;
            }

            $this->assertStringStartsWith(self::SERVER_PREFIX, $route->getPath());
            foreach ($route->getMethods() as $method) {
                $operations[] = $method.' '.substr((string) $route->getPath(), \strlen(self::SERVER_PREFIX));
            }
        }

        return $operations;
    }

    /**
     * @return list<string> "METHOD /path" of the documented operations
     */
    private function documentedOperations(): array
    {
        $operations = [];
        foreach ($this->spec['paths'] as $path => $item) {
            foreach (array_keys(array_intersect_key($item, array_flip(self::HTTP_METHODS))) as $method) {
                $operations[] = strtoupper((string) $method).' '.$path;
            }
        }

        return $operations;
    }

    /**
     * @param array<array-key, mixed> $node
     * @param list<string>            $references
     */
    private function collectReferences(array $node, array &$references): void
    {
        foreach ($node as $key => $value) {
            if ('$ref' === $key && \is_string($value)) {
                $references[] = $value;
            } elseif (\is_array($value)) {
                $this->collectReferences($value, $references);
            }
        }
    }

    /**
     * The `error` codes that the API's error responses are built with, read from the source.
     *
     * @return list<string>
     */
    private function errorCodesInCode(): array
    {
        $codes = ['http_error']; // the fallback of ApiExceptionListener for unmapped statuses
        $files = [
            ...glob($this->projectDir.'/src/Security/Api/*.php') ?: [],
            ...glob($this->projectDir.'/src/Controller/Api/V1/*.php') ?: [],
            $this->projectDir.'/src/EventListener/ApiExceptionListener.php',
        ];

        foreach ($files as $file) {
            $source = (string) file_get_contents($file);
            preg_match_all("/ApiErrorResponse::create\\(\\s*'([a-z_]+)'/", $source, $created);
            preg_match_all("/Response::HTTP_[A-Z_]+ => '([a-z_]+)',/", $source, $mapped);
            $codes = [...$codes, ...$created[1], ...$mapped[1]];
        }

        return array_values(array_unique($codes));
    }
}
