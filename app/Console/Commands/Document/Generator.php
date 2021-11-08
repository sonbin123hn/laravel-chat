<?php


namespace App\Console\Commands\Document;

use Symfony\Component\Yaml\Yaml;
use Illuminate\Support\Facades\File;
use L5Swagger\Exceptions\L5SwaggerException;
use Symfony\Component\Yaml\Dumper as YamlDumper;

class Generator
{
    /**
     * @var string
     */
    protected $annotationDir;

    /**
     * @var array
     */
    protected $originDocs;

    /**
     * @var string
     */
    protected $docDir;

    /**
     * @var string
     */
    protected $docsFile;

    /**
     * @var string
     */
    protected $yamlDocsFile;

    /**
     * @var array
     */
    protected $apiDocs = [];

    /**
     * @var array
     */
    protected $excludedDirs;

    /**
     * @var array
     */
    protected $constants;

    /**
     * @var bool
     */
    protected $yamlCopyRequired;

    /**
     * @var string
     */
    protected $basePath;

    public function __construct(
        array $paths,
        array $constants,
        bool $yamlCopyRequired
    ) {
        $this->annotationDir = $paths['annotations'][0];
        $this->docDir = explode('/', $paths['docs']);
        array_pop($this->docDir);
        $this->docDir = implode('/', $this->docDir);
        $this->excludedDirs = $paths['excludes'];
        $this->basePath = $paths['base'];
        $this->constants = $constants;
        $this->yamlCopyRequired = $yamlCopyRequired;
    }

    public function generate($version = null)
    {
        $this->scanOriginalDocs($version);

        if ($version) {
            $this->docsFile = $this->docDir.DIRECTORY_SEPARATOR.$version.DIRECTORY_SEPARATOR.($paths['docs_json'] ?? 'api-docs.json');
            $this->yamlDocsFile = $this->docDir.DIRECTORY_SEPARATOR.$version.DIRECTORY_SEPARATOR.($paths['docs_yaml'] ?? 'api-docs.yaml');
            return $this->createDoc($version)
                ->saveJson()
                ->makeYamlCopy($version);
        }

        foreach ($this->originDocs as $dir) {
            if (!file_exists($dir)) {
                $this->docsFile = $this->docDir.DIRECTORY_SEPARATOR.lcfirst($dir).DIRECTORY_SEPARATOR.($paths['docs_json'] ?? 'api-docs.json');
                $this->yamlDocsFile = $this->docDir.DIRECTORY_SEPARATOR.lcfirst($dir).DIRECTORY_SEPARATOR.($paths['docs_yaml'] ?? 'api-docs.yaml');
                $this->createDocs($dir)
                    ->saveJson()
                    ->makeYamlCopy($dir);
            }
        }

        return;
    }

    /**
     * Check directory structure and permissions.
     * @var string $dir
     * @throws L5SwaggerException
     *
     * @return Generator
     */
    protected function prepareDirectory($dir = null): self
    {
        if (File::exists($this->docDir . DIRECTORY_SEPARATOR . $dir) && ! is_writable($this->docDir . DIRECTORY_SEPARATOR . $dir)) {
            throw new L5SwaggerException('Documentation storage directory is not writable');
        }

        if (! File::exists($this->docDir . DIRECTORY_SEPARATOR . $dir)) {
            File::makeDirectory($this->docDir . DIRECTORY_SEPARATOR . $dir, 0755, true);
        }

        if (! File::exists($this->docDir . DIRECTORY_SEPARATOR . $dir)) {
            throw new L5SwaggerException('Documentation storage directory could not be created');
        }

        return $this;
    }

    /**
     * Scan directory and prepare directory
     *
     * @param null $version
     * @return $this
     * @throws L5SwaggerException
     */
    protected function scanOriginalDocs($version = null)
    {
        if ($version) {
            $this->originDocs = scandir($this->annotationDir . DIRECTORY_SEPARATOR . $version);
            $this->prepareDirectory($version);

            return $this;
        }

        $this->originDocs = scandir($this->annotationDir);

        foreach ($this->originDocs as $dir) {
            if (!file_exists($dir)) {
                $this->prepareDirectory(lcfirst($dir));
            }
        }

        return $this;
    }

    /**
     * Scan dir and create json
     *
     * @param null $dir
     * @return $this
     */
    protected function createDocs($dir = null)
    {
        $originDocs = scandir($this->annotationDir.DIRECTORY_SEPARATOR.$dir);
        $path = $this->annotationDir . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR;

        foreach ($originDocs as $child) {
            if (is_file($path . $child)) {
                $this->apiDocs = array_merge_recursive(
                    $this->apiDocs,
                    json_decode(file_get_contents($path . $child), true)
                );
            }
        }
        $this->apiDocs['servers'][0]['url'] = $this->constants['L5_SWAGGER_CONST_HOST'];
        $this->apiDocs['servers'][0]['description'] = str_replace(
            'v1',
            $this->constants['L5_SWAGGER_VERSION'],
            $this->apiDocs['servers'][0]['description']
        );

        return $this;
    }

    /**
     * Create json
     */
    protected function createDoc($dir = null)
    {
        $path = $this->annotationDir . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR;

        foreach ($this->originDocs as $child) {
            if (is_file($path . $child)) {
                $this->apiDocs = array_merge_recursive(
                    $this->apiDocs,
                    json_decode(file_get_contents($path . $child), true)
                );
            }
        }

        return $this;
    }

    /**
     * Save documentation as yaml file.
     */
    protected function makeYamlCopy($dir = null): void
    {
        if ($this->yamlCopyRequired) {
            $yamlDocs = (new YamlDumper(2))->dump(
                json_decode(file_get_contents($this->docsFile), true),
                20,
                0,
                Yaml::DUMP_OBJECT_AS_MAP ^ Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE
            );

            file_put_contents(
                $this->yamlDocsFile,
                $yamlDocs
            );
        }
    }

    /**
     * Save json file
     *
     * @return $this
     */
    protected function saveJson()
    {
        file_put_contents(
            $this->docsFile,
            stripslashes(json_encode($this->apiDocs, JSON_PRETTY_PRINT))
        );

        $this->apiDocs = [];

        return $this;
    }
}