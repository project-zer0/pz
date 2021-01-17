<?php

declare(strict_types=1);

namespace ProjectZer0\Pz\Console;

use ProjectZer0\Pz\Console\Command\PzCommand;
use ProjectZer0\Pz\Module\PzModuleCommandProviderInterface;
use ProjectZer0\Pz\ProjectZer0Toolkit;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\HelpCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

/**
 * @author Aurimas Niekis <aurimas@niekis.lt>
 */
class Application extends BaseApplication
{
    private ProjectZer0Toolkit $toolkit;
    private bool               $commandsRegistered = false;
    private array              $registrationErrors = [];

    public function __construct(ProjectZer0Toolkit $toolkit)
    {
        $this->toolkit = $toolkit;

        parent::__construct('Project Zer0 Toolkit', ProjectZer0Toolkit::VERSION);
    }

    /**
     * Runs the current application.
     *
     * @return int 0 if everything went fine, or an error code
     *
     * @throws Throwable
     */
    public function doRun(InputInterface $input, OutputInterface $output): int
    {
        $this->registerCommands();

        if ($this->registrationErrors) {
            $this->renderRegistrationErrors($input, $output);
        }

        $this->setDispatcher($this->toolkit->getEventDispatcher());

        return parent::doRun($input, $output);
    }

    /**
     * {@inheritdoc}
     */
    protected function doRunCommand(Command $command, InputInterface $input, OutputInterface $output): int
    {
        if ($this->registrationErrors) {
            $this->renderRegistrationErrors($input, $output);
            $this->registrationErrors = [];
        }

        if ($command instanceof PzCommand) {
            $command->setToolkit($this->toolkit);
        }

        return parent::doRunCommand($command, $input, $output);
    }

    /**
     * {@inheritdoc}
     */
    public function find(string $name): Command
    {
        $this->registerCommands();

        return parent::find($name);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $name): Command
    {
        $this->registerCommands();

        $command = parent::get($name);

        if ('help' !== $name && $command instanceof HelpCommand) {
            $wantedCommand = $this->get($name);

            if ($wantedCommand instanceof PzCommand && $wantedCommand->doesIgnoreHelp()) {
                return $wantedCommand;
            }
        }

        return $command;
    }

    /**
     * {@inheritdoc}
     */
    public function all(string $namespace = null): array
    {
        $this->registerCommands();

        return parent::all($namespace);
    }

    public function add(Command $command): ?Command
    {
        $this->registerCommands();

        return parent::add($command);
    }

    public function registerCommands(): void
    {
        if ($this->commandsRegistered) {
            return;
        }

        $this->commandsRegistered = true;

        $this->toolkit->boot();

        foreach ($this->toolkit->getModules() as $module) {
            if ($module instanceof PzModuleCommandProviderInterface) {
                try {
                    $module->registerCommands($this);
                } catch (Throwable $e) {
                    $this->registrationErrors[] = $e;
                }
            }
        }
    }

    private function renderRegistrationErrors(InputInterface $input, OutputInterface $output): void
    {
        if ($output instanceof ConsoleOutputInterface) {
            $output = $output->getErrorOutput();
        }

        (new SymfonyStyle($input, $output))->warning('Some commands could not be registered:');

        foreach ($this->registrationErrors as $error) {
            $this->doRenderThrowable($error, $output);
        }
    }
}
