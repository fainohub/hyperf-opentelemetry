<?php

declare(strict_types=1);

namespace Hyperf\OpenTelemetry\Resource;

use Hyperf\Contract\ConfigInterface;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\SemConv\ResourceAttributes;

class ResourceFactory
{
    public static function create(ConfigInterface $config): ResourceInfo
    {
        $config = $config->get('opentelemetry.resource');

        return ResourceInfoFactory::merge(ResourceInfo::create(Attributes::create([
            ResourceAttributes::SERVICE_NAMESPACE => $config['service']['namespace'],
            ResourceAttributes::SERVICE_NAME => $config['service']['name'],
            ResourceAttributes::DEPLOYMENT_ENVIRONMENT => $config['environment'],
        ])), ResourceInfoFactory::defaultResource());
    }
}
