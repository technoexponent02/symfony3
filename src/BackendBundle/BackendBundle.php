<?php

namespace BackendBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use BackendBundle\DependencyInjection\CompilerPass\ConnectionCompilerPass;

class BackendBundle extends Bundle
{
	public function build(ContainerBuilder $container)
	{
	    parent::build($container);
	    $container->addCompilerPass(new ConnectionCompilerPass());
	}
}
