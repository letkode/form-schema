<?php

declare(strict_types=1);

namespace Letkode\FormSchema;

use Letkode\FormSchema\DependencyInjection\Compiler\RegisterFormOptionsProvidersPass;
use Letkode\FormSchema\Domain\Contract\FieldTypeInterface;
use Letkode\FormSchema\Domain\Contract\FormRenderInterface;
use Letkode\FormSchema\Domain\Contract\GroupRenderInterface;
use Letkode\FormSchema\Domain\Contract\OptionsSourceInterface;
use Letkode\FormSchema\Domain\Contract\SectionRenderInterface;
use Letkode\FormSchema\Infrastructure\Cache\CachedFormSchemaResolver;
use Letkode\FormSchema\Infrastructure\Cache\DoctrineCacheInvalidationSubscriber;
use Letkode\FormSchema\Infrastructure\Doctrine\TableNameSubscriber;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class LetkodeFormSchemaBundle extends AbstractBundle
{
    #[\Override]
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->scalarNode('default_locale')->defaultValue('es')->end()
                ->arrayNode('available_locales')
                    ->scalarPrototype()->end()
                    ->defaultValue(['es'])
                ->end()
                ->scalarNode('entity_namespace')->defaultValue('App\\Entity')->end()
                ->scalarNode('table_prefix')->defaultValue('')->end()
                ->arrayNode('table_names')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('form')->defaultNull()->end()
                        ->scalarNode('form_section')->defaultNull()->end()
                        ->scalarNode('form_group')->defaultNull()->end()
                        ->scalarNode('form_field')->defaultNull()->end()
                        ->scalarNode('form_option_general')->defaultNull()->end()
                        ->scalarNode('form_option_general_value')->defaultNull()->end()
                    ->end()
                ->end()
                ->arrayNode('cache')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')->defaultFalse()->end()
                        ->scalarNode('pool')->defaultValue('cache.app')->end()
                        ->integerNode('ttl')->defaultValue(3600)->end()
                        ->scalarNode('key_prefix')->defaultValue('fsb')->end()
                        ->booleanNode('auto_invalidate')->defaultTrue()->end()
                    ->end()
                ->end()
                ->arrayNode('disabled_field_types')->scalarPrototype()->end()->defaultValue([])->end()
                ->arrayNode('disabled_options_sources')->scalarPrototype()->end()->defaultValue([])->end()
                ->arrayNode('disabled_form_renders')->scalarPrototype()->end()->defaultValue([])->end()
                ->arrayNode('disabled_section_renders')->scalarPrototype()->end()->defaultValue([])->end()
                ->arrayNode('disabled_group_renders')->scalarPrototype()->end()->defaultValue([])->end()
            ->end()
        ;
    }

    #[\Override]
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import(__DIR__ . '/Resources/config/services.yaml');

        $builder->setParameter('letkode_form_schema.default_locale', $config['default_locale']);
        $builder->setParameter('letkode_form_schema.available_locales', $config['available_locales']);
        $builder->setParameter('letkode_form_schema.entity_namespace', $config['entity_namespace']);
        $builder->setParameter('letkode_form_schema.table_prefix', $config['table_prefix']);
        $builder->setParameter('letkode_form_schema.table_names', $config['table_names']);
        $builder->setParameter('letkode_form_schema.cache.enabled', $config['cache']['enabled']);
        $builder->setParameter('letkode_form_schema.cache.pool', $config['cache']['pool']);
        $builder->setParameter('letkode_form_schema.cache.ttl', $config['cache']['ttl']);
        $builder->setParameter('letkode_form_schema.cache.key_prefix', $config['cache']['key_prefix']);
        $builder->setParameter('letkode_form_schema.cache.auto_invalidate', $config['cache']['auto_invalidate']);
        $builder->setParameter('letkode_form_schema.disabled_field_types', $config['disabled_field_types']);
        $builder->setParameter('letkode_form_schema.disabled_options_sources', $config['disabled_options_sources']);
        $builder->setParameter('letkode_form_schema.disabled_form_renders', $config['disabled_form_renders']);
        $builder->setParameter('letkode_form_schema.disabled_section_renders', $config['disabled_section_renders']);
        $builder->setParameter('letkode_form_schema.disabled_group_renders', $config['disabled_group_renders']);

        $container->services()
            ->set(TableNameSubscriber::class)
                ->args([$config['table_prefix'], $config['table_names']])
                ->tag('doctrine.event_listener', ['event' => 'loadClassMetadata']);

        if ($config['cache']['enabled']) {
            $container->services()
                ->set(CachedFormSchemaResolver::class)
                    ->decorate('Letkode\FormSchema\Domain\Contract\FormSchemaResolverInterface')
                    ->args([
                        service('.inner'),
                        service($config['cache']['pool']),
                        $config['cache']['ttl'],
                        $config['cache']['key_prefix'],
                    ])
                    ->public();

            if ($config['cache']['auto_invalidate']) {
                $container->services()
                    ->set(DoctrineCacheInvalidationSubscriber::class)
                        ->args([service('Letkode\FormSchema\Infrastructure\Cache\FormSchemaCacheInvalidator')])
                        ->tag('doctrine.event_listener', ['event' => 'postUpdate'])
                        ->tag('doctrine.event_listener', ['event' => 'postPersist'])
                        ->tag('doctrine.event_listener', ['event' => 'postRemove']);
            }
        }
    }

    #[\Override]
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new RegisterFormOptionsProvidersPass());

        $container->registerForAutoconfiguration(FieldTypeInterface::class)
            ->addTag('form_schema.field_type');

        $container->registerForAutoconfiguration(OptionsSourceInterface::class)
            ->addTag('form_schema.options_source');

        $container->registerForAutoconfiguration(FormRenderInterface::class)
            ->addTag('form_schema.form_render');

        $container->registerForAutoconfiguration(SectionRenderInterface::class)
            ->addTag('form_schema.section_render');

        $container->registerForAutoconfiguration(GroupRenderInterface::class)
            ->addTag('form_schema.group_render');
    }
}
