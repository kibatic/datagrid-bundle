<?php

namespace Kibatic\DatagridBundle;

use Kibatic\DatagridBundle\DependencyInjection\KibaticDatagridExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class KibaticDatagridBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new KibaticDatagridExtension();
    }
}
