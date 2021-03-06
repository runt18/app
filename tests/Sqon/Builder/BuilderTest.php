<?php

namespace Test\Sqon\Builder;

use PHPUnit_Framework_TestCase as TestCase;
use Sqon\Builder\Builder;
use Sqon\Builder\Configuration;
use Sqon\Builder\ConfigurationInterface;
use Sqon\Sqon;
use Sqon\SqonInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Test\Sqon\Builder\Plugin\TestPlugin;
use Test\Sqon\Test\TempTrait;

/**
 * Verifies that the Sqon builder functions as intended.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 *
 * @covers \Sqon\Builder\Builder
 */
class BuilderTest extends TestCase
{
    use TempTrait;

    /**
     * The path to the Sqon.
     *
     * @var string
     */
    private $path;

    /**
     * The build configuration settings.
     *
     * @var array
     */
    private $settings;

    /**
     * Verify that the build configuration manager can be retrieved.
     */
    public function testRetrieveTheBuildConfigurationManager()
    {
        self::assertInstanceOf(
            ConfigurationInterface::class,
            $this->createBuilder()->getConfiguration(),
            'The build configuration manager was not returned.'
        );
    }

    /**
     * Verify that the Sqon event dispatcher can be retrieved.
     */
    public function testRetrieveSqonManagerEventDispatcher()
    {
        self::assertInstanceOf(
            EventDispatcherInterface::class,
            $this->createBuilder()->getEventDispatcher(),
            'The event dispatcher was not retrieved.'
        );
    }

    /**
     * Verify that the Sqon manager can be retrieved.
     */
    public function testRetrieveTheSqonManager()
    {
        self::assertInstanceOf(
            SqonInterface::class,
            $this->createBuilder()->getSqon(),
            'The Sqon manager was not returned.'
        );
    }

    /**
     * Verify that the plugins are registered.
     */
    public function testRegisterAvailablePlugins()
    {
        $this->settings['plugins'][] = [
            'class' => TestPlugin::class
        ];

        $builder = $this
            ->createBuilder()
            ->registerPlugins()
        ;

        self::assertSame(
            $builder->getConfiguration(),
            TestPlugin::$config,
            'The configuration manager was not provided to the plugin.'
        );

        self::assertSame(
            $builder->getEventDispatcher(),
            TestPlugin::$eventDispatcher,
            'The event dispatcher was not provided to the plugin.'
        );

        self::assertSame(
            $builder->getSqon(),
            TestPlugin::$sqon,
            'The Sqon manager was not provided to the plugin.'
        );
    }

    /**
     * Verify that the bootstrap script is set.
     */
    public function testSetThePhpBootstrapScriptForTheSqon()
    {
        file_put_contents(
            $this->settings['bootstrap'],
            '<?php echo "test\n"; __HALT_COMPILER();'
        );

        $this
            ->createBuilder()
            ->setBootstrap()
            ->commit()
        ;

        self::assertContains(
            'test',
            file_get_contents($this->path),
            'The PHP bootstrap script was not set.'
        );

        $this->settings['bootstrap'] = null;

        $this
            ->openBuilder()
            ->setBootstrap()
            ->commit()
        ;

        self::assertContains(
            $this->settings['shebang'],
            file_get_contents($this->path),
            'The shebang line was not set for the PHP bootstrap script.'
        );
    }

    /**
     * Verify that the compression mode is set.
     */
    public function testSetTheCompressionMode()
    {
        $builder = $this->createBuilder();

        self::assertSame(
            $builder,
            $builder->setCompression(),
            'The compression setter did not return a fluent interface.'
        );
    }

    /**
     * Verify that the main script is set.
     */
    public function testSetTheMainScriptForTheSqon()
    {
        // Set the main script for a new Sqon.
        $this
            ->createBuilder()
            ->setMain()
            ->commit()
        ;

        $sqon = Sqon::open($this->path);

        self::assertContains(
            $this->settings['main'],
            $sqon->getPath(Sqon::PRIMARY)->getContents(),
            'The main script was not set.'
        );

        $sqon = null;

        // Remove the main script for an existing Sqon.
        $this->settings['main'] = null;

        $this
            ->openBuilder()
            ->setMain()
            ->commit()
        ;

        $sqon = Sqon::open($this->path);

        self::assertFalse(
            $sqon->hasPath(Sqon::PRIMARY),
            'The primary script was not removed.'
        );
    }

    /**
     * Verify that the paths are set.
     */
    public function testSetThePathsInTheSqon()
    {
        $dir = $this->createTempDirectory();
        $file = $this->createTempFile();

        file_put_contents($dir . '/test.php', 'test');

        $this->settings['paths'] = [
            basename($dir),
            'test' => $dir,
            basename($file),
            'test.php' => $file
        ];

        $this
            ->createBuilder()
            ->setPaths()
            ->commit()
        ;

        $sqon = Sqon::open($this->path);

        self::assertTrue(
            $sqon->hasPath(basename($dir) . '/test.php'),
            'The directory was not set in the Sqon.'
        );

        self::assertTrue(
            $sqon->hasPath('test/test.php'),
            'The directory with an alternative path was not set.'
        );

        self::assertTrue(
            $sqon->hasPath(basename($file)),
            'The file was not set in the Sqon.'
        );

        self::assertTrue(
            $sqon->hasPath('test.php'),
            'The file with an alternative path was not set.'
        );
    }

    /**
     * Creates a new Sqon builder.
     */
    protected function setUp()
    {
        $this->path = $this->createTempFile();
        $this->settings = [
            'bootstrap' => $this->createTempFile(),
            'compression' => 'GZIP',
            'main' => 'path/to/main.php',
            'output' => basename($this->path),
            'paths' => [],
            'plugins' => [],
            'shebang' => '#!/usr/bin/env php'
        ];
    }

    /**
     * Deletes the temporary paths.
     */
    protected function tearDown()
    {
        $this->deleteTempPaths();
    }

    /**
     * Creates a new Sqon builder.
     *
     * @return Builder The builder.
     */
    private function createBuilder()
    {
        return Builder::create(
            new Configuration(
                sys_get_temp_dir(),
                ['sqon' => $this->settings]
            )
        );
    }

    /**
     * Create a new Sqon builder for an existing Sqon.
     *
     * @return Builder The builder.
     */
    private function openBuilder()
    {
        return Builder::open(
            $this->path,
            new Configuration(
                sys_get_temp_dir(),
                ['sqon' => $this->settings]
            )
        );
    }
}
