<?php

declare(strict_types=1);

namespace F4\Core\Phug;

use F4\Config;
use F4\Core\Phug\FileLocator;
use F4\Core\Phug\FileAdapter;
use F4\Core\Phug\Util;
use F4\Core\TemplateBasedRendererInterface;

use Phug\Renderer;
use Phug\Component\ComponentExtension;

class TemplateRenderer extends Renderer 
{

    public function __construct($options = null)
    {

        // workaround for undefined stdout
        if (!defined(constant_name: 'STDOUT')) {
            define(constant_name: 'STDOUT', value: fopen(filename: 'php://stdout', mode: 'wb'));
        }

        parent::__construct(options: [
            'debug'                 => Config::DEBUG_MODE,
            'exit_on_error'         => Config::DEBUG_MODE,
            'pretty'                => false,
            'locator_class_name'    => FileLocator::class,
            'adapter_class_name'    => FileAdapter::class,
            'cache_dir'             => (Config::DEBUG_MODE && !Config::TEMPLATE_CACHE_ENABLED) ? null : (Config::TEMPLATE_CACHE_PATH ?: sys_get_temp_dir()),
            'cache_lifetime'        => (Config::DEBUG_MODE && !Config::TEMPLATE_CACHE_ENABLED) ? null : Config::TEMPLATE_CACHE_LIFETIME,
            'paths'                 => Util::getPaths()
        ]);
        ComponentExtension::enable(renderer: $this);
    }

    public function renderTemplate(string $file, array $args = []): string
    {
        return $this->renderFile(path: $file, parameters: $args);
    }

}

