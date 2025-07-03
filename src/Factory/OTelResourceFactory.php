<?php

declare(strict_types=1);

namespace FainoHub\Hyperf\OpenTelemetry\Factory;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\ContainerInterface;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\SemConv\ResourceAttributes;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class OTelResourceFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): ResourceInfo
    {
        $config = $container->get(ConfigInterface::class);

        return ResourceInfoFactory::defaultResource()->merge(ResourceInfo::create(Attributes::create(
            $config->get('open-telemetry.resource', [
                ResourceAttributes::SERVICE_NAME                => $config->get('app_name', 'hyperf'),
                ResourceAttributes::DEPLOYMENT_ENVIRONMENT_NAME => $config->get('app_env', 'production'),
            ])
        )));
    }
}
