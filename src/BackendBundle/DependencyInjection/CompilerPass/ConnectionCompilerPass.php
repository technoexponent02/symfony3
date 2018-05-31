<?php
namespace BackendBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class ConnectionCompilerPass implements CompilerPassInterface
{
	/**
	 * {@inheritDoc}
	 */
	public function process(ContainerBuilder $container)
	{
	    $connection = $container
	    ->getDefinition(sprintf('doctrine.dbal.%s_connection', 'company'))
	    ->addMethodCall('setSession', [
	        new Reference('session'),
	        $container->getParameter('database_host'),
	        $container->getParameter('database_name'),
	        $container->getParameter('database_user'),
	        $container->getParameter('database_password'),
	    ]);
	}
}