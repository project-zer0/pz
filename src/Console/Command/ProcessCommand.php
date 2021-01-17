<?php

declare(strict_types=1);

namespace ProjectZer0\Pz\Console\Command;

use ProjectZer0\Pz\Process\ProcessInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Aurimas Niekis <aurimas@niekis.lt>
 */
abstract class ProcessCommand extends PzCommand
{
    abstract public function getProcess(
        array $processArgs,
        InputInterface $input,
        OutputInterface $output
    ): ProcessInterface;

    public function run(InputInterface $input, OutputInterface $output): int
    {
        $cliArgs = $_SERVER['argv'];

        /**
         * Find the first our matched command name position and ignore the rest as it can be valid argument option in
         * Symfony console.
         */
        $commandNames = [$this->getName(), ...$this->getAliases()];
        $positions    = [];
        foreach ($commandNames as $commandName) {
            foreach ($cliArgs as $i => $cliArg) {
                if ($cliArg === $commandName) {
                    $positions[] = $i;
                }
            }
        }

        sort($positions);
        $first = (int) array_shift($positions);

        $processArguments = array_slice($cliArgs, $first + 1);
        $commandArguments = array_slice($cliArgs, 0, $first + 1);

        $input = new ArgvInput($commandArguments);

        // add the application arguments and options
        $this->mergeApplicationDefinition();

        $ignoreValidationErrors = \Closure::bind(fn (): bool => $this->ignoreValidationErrors, $this, Command::class)();

        // bind the input against the command specific arguments/options
        try {
            $input->bind($this->getDefinition());
        } catch (ExceptionInterface $e) {
            if (false === $ignoreValidationErrors) {
                throw $e;
            }
        }

        $this->initialize($input, $output);

        if ($input->isInteractive()) {
            $this->interact($input, $output);
        }

        // The command name argument is often omitted when a command is executed directly with its run() method.
        // It would fail the validation if we didn't make sure the command argument is present,
        // since it's required by the application.
        if ($input->hasArgument('command') && null === $input->getArgument('command')) {
            $input->setArgument('command', $this->getName());
        }

        $input->validate();

        return $this->getProcess($processArguments, $input, $output)->execute();
    }
}
