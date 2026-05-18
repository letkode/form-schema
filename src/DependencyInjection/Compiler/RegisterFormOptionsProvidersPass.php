<?php

declare(strict_types=1);

namespace Letkode\FormSchema\DependencyInjection\Compiler;

use Letkode\FormSchema\Attribute\AsFormOptionsProvider;
use Letkode\FormSchema\Infrastructure\OptionsSource\OptionsEntitySource;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class RegisterFormOptionsProvidersPass implements CompilerPassInterface
{
    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(OptionsEntitySource::class)) {
            return;
        }

        $definition = $container->findDefinition(OptionsEntitySource::class);

        foreach ($container->getDefinitions() as $def) {
            $class = $def->getClass();
            if (null === $class || !class_exists($class)) {
                continue;
            }

            $reflection = new \ReflectionClass($class);
            $attributes = $reflection->getAttributes(AsFormOptionsProvider::class);

            foreach ($attributes as $attribute) {
                $instance = $attribute->newInstance();
                $definition->addMethodCall('registerProvider', [$class, $instance->methods]);
            }
        }
    }
}
