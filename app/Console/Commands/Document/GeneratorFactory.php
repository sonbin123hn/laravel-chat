<?php
/**
 * Created by PhpStorm.
 * User: nguyenvo
 * Date: 2/19/21
 * Time: 3:09 PM
 */

namespace App\Console\Commands\Document;

use L5Swagger\Exceptions\L5SwaggerException;

class GeneratorFactory
{
    public function initialize()
    {
        $config = $this->getConfig();
        $paths = $config['paths'];
        $constants = $config['constants'] ?? [];
        $yamlCopyRequired = $config['generate_yaml_copy'] ?? false;

        return new Generator($paths, $constants, $yamlCopyRequired);
    }

    protected function getConfig()
    {
        $defaults = config('l5-swagger.defaults', []);
        $documentations = config('l5-swagger.documentations', []);

        if (! isset($documentations['default'])) {
            throw new L5SwaggerException('Documentation config not found');
        }

        return $this->mergeConfig($defaults, $documentations['default']);
    }

    protected function mergeConfig(array $defaults, array $config)
    {
        $merged = $defaults;

        foreach ($config as $key => &$value) {
            if (isset($defaults[$key])
                && $this->isAssociativeArray($defaults[$key])
                && $this->isAssociativeArray($value)
            ) {
                $merged[$key] = $this->mergeConfig($defaults[$key], $value);
                continue;
            }

            $merged[$key] = $value;
        }

        return $merged;
    }

    protected function isAssociativeArray($value): bool
    {
        return is_array($value) && count(array_filter(array_keys($value), 'is_string')) > 0;
    }
}