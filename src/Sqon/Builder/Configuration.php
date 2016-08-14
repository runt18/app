<?php

namespace Sqon\Builder;

use InvalidArgumentException;
use RuntimeException;
use Sqon\Builder\Exception\ConfigurationException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Manages the build configuration settings for a Sqon builder.
 *
 * ```php
 * [
 *     'sqon' => [
 *         'bootstrap' => 'path/to/script.php',
 *         'compression' => 'GZIP',
 *         'main' => 'path/to/main.php',
 *         'output' => 'example.sqon',
 *         'paths' => [
 *             'path/to/a',
 *             'path/to/b',
 *             'path/to/c'
 *         ],
 *         'plugins' => [
 *             'path/to/plugin/a.php',
 *             'path/to/plugin/b.php',
 *             'path/to/plugin/c.php',
 *         ],
 *         'shebang' => '#!/usr/bin/env php'
 *     ]
 * ]
 * ```
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * The default build configuration settings.
     *
     * @var array
     */
    private static $default = [
        'bootstrap' => null,
        'compression' => 'NONE',
        'main' => null,
        'output' => 'project.sqon',
        'paths' => [],
        'plugins' => [],
        'shebang' => null
    ];

    /**
     * The base directory path.
     *
     * @var string
     */
    private $directory;

    /**
     * The build configuration settings.
     *
     * @var array
     */
    private $settings;

    /**
     * Initializes the new build configuration manager.
     *
     * @param string $directory The base directory path.
     * @param array  $settings  The build configuration settings.
     */
    public function __construct($directory, array $settings)
    {
        $this->directory = $directory;
        $this->settings = $this->setDefaults($settings);
    }

    /**
     * {@inheritdoc}
     */
    public function getBootstrap()
    {
        if (null === $this->settings['sqon']['bootstrap']) {
            return null;
        }

        $contents = file_get_contents($this->settings['sqon']['bootstrap']);

        if (false === $contents) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException(
                sprintf(
                    'The PHP bootstrap script "%s" could not be read.',
                    $this->settings['sqon']['bootstrap']
                )
            );
            // @codeCoverageIgnoreEnd
        }

        return $contents;
    }

    /**
     * {@inheritdoc}
     */
    public function getCompression()
    {
        return $this->settings['sqon']['compression'];
    }

    /**
     * {@inheritdoc}
     */
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * {@inheritdoc}
     */
    public function getMain()
    {
        return $this->settings['sqon']['main'];
    }

    /**
     * {@inheritdoc}
     */
    public function getOutput()
    {
        return $this->settings['sqon']['output'];
    }

    /**
     * {@inheritdoc}
     */
    public function getPaths()
    {
        return $this->settings['sqon']['paths'];
    }

    /**
     * {@inheritdoc}
     */
    public function getShebang()
    {
        return $this->settings['sqon']['shebang'];
    }

    /**
     * {@inheritdoc}
     */
    public function registerPlugins(EventDispatcherInterface $dispatcher)
    {
        foreach ($this->settings['sqon']['plugins'] as $plugin) {
            $this->registerPlugin($dispatcher, $plugin);
        }
    }

    /**
     * Sets the default Sqon build settings.
     *
     * This method will set any default setting that is missing from the user
     * provided configuration settings. Settings that are references to items
     * such as class constants will also be resolved.
     *
     * @param array $settings The build configuration settings.
     *
     * @return array The build configuration settings.
     *
     * @throws InvalidArgumentException If a setting is invalid.
     */
    private function setDefaults(array $settings)
    {
        if (isset($settings['sqon'])) {
            $settings['sqon'] = array_merge(self::$default, $settings['sqon']);
        } else {
            $settings = ['sqon' => self::$default];
        }

        $constant = '\Sqon\Sqon::' . $settings['sqon']['compression'];

        if (!defined($constant)) {
            // @codeCoverageIgnoreStart
            throw new InvalidArgumentException(
                sprintf(
                    'The compression mode "%s" is not valid.',
                    $settings['sqon']['compression']
                )
            );
            // @codeCoverageIgnoreEnd
        }

        $settings['sqon']['compression'] = constant($constant);

        return $settings;
    }

    /**
     * Registers an individual plugin with the event dispatcher.
     *
     * @param EventDispatcherInterface $dispatcher The event dispatcher.
     * @param string                   $path       The path to the plugin.
     *
     * @throws ConfigurationException If the plugin could not be registered.
     */
    private function registerPlugin(
        EventDispatcherInterface $dispatcher,
        $path
    ) {
        if (!is_file($path)) {
            // @codeCoverageIgnoreStart
            throw new ConfigurationException(
                "The plugin \"$path\" does not exist."
            );
            // @codeCoverageIgnoreEnd
        }

        $callback = require $path;

        if (!is_callable($callback)) {
            // @codeCoverageIgnoreStart
            throw new ConfigurationException(
                "The plugin \"$path\" did not return a callback."
            );
            // @codeCoverageIgnoreEnd
        }

        $callback($dispatcher);
    }
}
