<?php

namespace Sqon\Console;

use Sqon\Console\Command\CreateCommand;
use Sqon\Console\Command\EditCommand;
use Sqon\Console\Command\ExtractCommand;
use Sqon\Console\Command\VerifyCommand;
use Sqon\Console\Helper\VerboseHelper;
use Symfony\Component\Console\Application as Base;

/**
 * Manages the handling of command line input and output.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class Application extends Base
{
    /**
     * The build date of the Sqon application.
     *
     * @var string
     */
    const DATE = '(repo)';

    /**
     * The version number of the Sqon application.
     *
     * @var string
     */
    const VERSION = '(repo)';

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct('Sqon', self::VERSION);
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function getLongVersion()
    {
        if ('(repo)' === self::VERSION) {
            return sprintf(
                '<info>%s</info> <fg=blue>(repo)</fg=blue>',
                $this->getName()
            );
        }

        return sprintf(
            '<info>%s</info> version <comment>%s</comment> <fg=blue>(%s)</fg=blue>',
            $this->getName(),
            self::VERSION,
            self::DATE
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultCommands()
    {
        return array_merge(
            parent::getDefaultCommands(),
            [
                new CreateCommand(),
                new EditCommand(),
                new ExtractCommand(),
                new VerifyCommand()
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultHelperSet()
    {
        $set = parent::getDefaultHelperSet();

        $set->set(new VerboseHelper());

        return $set;
    }
}
