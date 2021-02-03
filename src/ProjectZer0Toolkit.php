<?php

declare(strict_types=1);

namespace ProjectZer0\Pz;

use Composer\Json\JsonFile;
use Composer\Package\CompletePackage;
use Composer\Repository\InstalledFilesystemRepository;
use Composer\Util\PackageSorter;
use ErrorException;
use Monolog\Logger;
use ProjectZer0\Pz\Config\Configuration;
use ProjectZer0\Pz\Console\Application;
use ProjectZer0\Pz\Module\PzModuleInterface;
use ProjectZer0\Pz\RPC\RpcCommandInterface;
use Psr\Log\LoggerInterface;
use Spiral\Goridge\Relay;
use Spiral\Goridge\RPC\RPC;
use Symfony\Bridge\Monolog\Handler\ConsoleHandler;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\Debug\TraceableEventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Yaml\Yaml;
use UnexpectedValueException;

/**
 * @author Aurimas Niekis <aurimas@niekis.lt>
 */
class ProjectZer0Toolkit
{
    public const VERSION = '1.0.0';

    private EventDispatcherInterface $eventDispatcher;
    private LoggerInterface $logger;
    private OutputInterface $output;

    private RPC $rpc;

    /** @var PzModuleInterface[] */
    private array $modules;

    private array $configuration;

    public function __construct(OutputInterface $output = null)
    {
        $this->modules         = [];
        $this->configuration   = [];
        $this->output          = $output ?? new ConsoleOutput();
        $this->logger          = new Logger('pz');
        $this->eventDispatcher = new TraceableEventDispatcher(
            new EventDispatcher(),
            new Stopwatch(),
            $this->logger
        );

        $rpcPort = getenv('PZ_PORT');
        if (false === $rpcPort) {
            throw new \LogicException('Missing "PZ_PORT" env variable');
        }

        $this->rpc = new RPC(Relay::create('tcp://host.docker.internal:' . $rpcPort));

        $consoleHandler = new ConsoleHandler($this->output);

        $this->eventDispatcher->addSubscriber($consoleHandler);
        $this->logger->pushHandler($consoleHandler);
    }

    public function run(): int
    {
        $app = new Application($this);

        return $app->run(null, $this->output);
    }

    public function boot(): void
    {
        $this->loadModules();

        foreach ($this->getModules() as $module) {
            $module->boot($this);
        }

        $this->loadConfiguration();
    }

    public function loadConfiguration(): void
    {
        $configFile = '/project/.pz.yaml';

        // Should not be the case as the shell script for pz should prevent launching without `.pz.yaml.` present
        if (false === file_exists($configFile)) {
            throw new ErrorException('Configuration file ".pz.yaml" is missing');
        }

        $yaml = Yaml::parseFile($configFile);

        $processor     = new Processor();
        $configuration = new Configuration($this->getModules());

        $this->configuration = $processor->processConfiguration($configuration, $yaml);
    }

    public function loadModules(): void
    {
        $jsonFile = new JsonFile('/project/vendor/composer/installed.json');
        $repo     = new InstalledFilesystemRepository($jsonFile);

        $sortedPackages = PackageSorter::sortPackages($repo->getPackages());
        foreach ($sortedPackages as $package) {
            if (false === ($package instanceof CompletePackage)) {
                continue;
            }

            if ('pz-module' === $package->getType()) {
                $this->registerModulePackage($package);
            }
        }
    }

    public function registerModulePackage(CompletePackage $package): void
    {
        $extra = $package->getExtra();

        if (empty($extra['pz_class'])) {
            throw new UnexpectedValueException(sprintf('Error while registering "%s" module. All "pz-module" packages should have a "pz_class" defined in their extra key to be usable.', $package->getPrettyName()));
        }

        $moduleClasses = is_array($extra['pz_class']) ? $extra['pz_class'] : [$extra['pz_class']];
        foreach ($moduleClasses as $moduleClass) {
            $module = new $moduleClass();

            if (false === ($module instanceof PzModuleInterface)) {
                throw new UnexpectedValueException(sprintf('Error while registering "%s" module. A "pz-module" package "pz_class" should implement PzModuleInterface', $package->getPrettyName()));
            }

            $this->addModule($module);
        }
    }

    public function addModule(PzModuleInterface $module): void
    {
        $this->modules[$module->getName()] = $module;

        if ($module instanceof EventSubscriberInterface) {
            $this->getEventDispatcher()->addSubscriber($module);
        }
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    /** @return PzModuleInterface[] */
    public function getModules(): array
    {
        return $this->modules;
    }

    public function sendRPCCommand(RpcCommandInterface $command): mixed
    {
        $payload = json_encode($command->getPayload(), JSON_THROW_ON_ERROR);

        return $this->rpc->call($command->getMethodName(), $payload);
    }

    public function getCurrentDirectory(): string
    {
        $cwd = getenv('PZ_PWD');

        // A fallback to current directory in docker
        if (false === $cwd) {
            return getcwd();
        }

        return $cwd;
    }
}
