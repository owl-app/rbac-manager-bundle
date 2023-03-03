<?php

namespace Owl\Bundle\RbacManagerBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Container;

final class OwlRbacManagerBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(DoctrineOrmMappingsPass::createXmlMappingDriver(
            [$this->getConfigFilesPath() => $this->getModelNamespace()],
            ['owl_rbac_manager.entity_manager'],
            false
        ));
    }

    protected function getConfigFilesPath(): string
    {
        return sprintf(
            '%s/Resources/config/doctrine/%s',
            $this->getPath(),
            strtolower($this->getDoctrineMappingDirectory())
        );
    }

    protected function getDoctrineMappingDirectory(): string
    {
        return 'model';
    }

    protected function getBundlePrefix(): string
    {
        return Container::underscore(substr((string) strrchr(static::class, '\\'), 1, -6));
    }

    protected function getModelNamespace(): string
    {
        return 'Owl\Component\Rbac\Model';
    }
}
