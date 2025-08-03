<?php

declare(strict_types=1);

namespace Impulse\Core\Console;

use Impulse\Core\Attributes\Renderer;
use Impulse\Core\Support\Config;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'renderer:configure',
    description: 'Initialise le projet Impulse avec un moteur de template',
    aliases: ['r:config', 'renderer:setup', 'renderer:config']
)]
class RendererConfigureCommand extends Command
{
    /**
     * @throws \JsonException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');
        $engines = $this->discoverRenderers();
        $options = array_map('ucfirst', array_keys($engines));

        $question = new ChoiceQuestion(
            '[<fg=cyan>Impulse</>] Quel moteur de template souhaitez-vous utiliser ?',
            array_values($options),
            0
        );
        $question->setErrorMessage('Le moteur %s est invalide.');

        $engine = $helper->ask($input, $output, $question);
        $engine = $engine === 'Aucun' ? '' : strtolower($engine);

        $questionPath = new Question(
            '[<fg=cyan>Impulse</>] Où se trouvent vos templates ? [<fg=yellow>views</>]',
            'views'
        );
        $templatePath = $helper->ask($input, $output, $questionPath);

        Config::load();
        Config::set('template_engine', $engine);
        Config::set('template_path', $templatePath);
        Config::save();

        $output->writeln("[<fg=cyan>Impulse</>] Fichier de configuration généré : <info>impulse.php</info>.");

        $bundle = $engines[$engine]['bundle'] ?? null;
        if ($bundle && $engine && isset($engines[$engine])) {
            return $this->addEngine($bundle, $output);
        }

        $output->writeln("[<fg=cyan>Impulse</>] 🎉 Impulse est prêt à être utilisé !");
        return Command::SUCCESS;
    }

    /**
     * @throws \JsonException
     */
    private function discoverRenderers(): array
    {
        $autoload = require getcwd() . '/vendor/autoload.php';
        $engines = [
            'aucun' => [
                'bundle' => null
            ]
        ];

        $composerFile = getcwd() . '/composer.json';
        if (!file_exists($composerFile)) {
            return $engines;
        }

        $composer = json_decode(file_get_contents($composerFile), true, 512, JSON_THROW_ON_ERROR);
        $psr4 = $composer['autoload']['psr-4'] ?? [];

        $paths = [
            __DIR__ . '/../Renderer' => 'Impulse\\Core\\Renderer\\'
        ];

        foreach ($psr4 as $namespace => $dir) {
            $rendererPath = rtrim($dir, '/') . '/Renderer';
            $fullPath = getcwd() . '/' . $rendererPath;

            if (is_dir($fullPath)) {
                $paths[$fullPath] = rtrim($namespace, '\\') . '\\Renderer\\';
            }
        }

        foreach ($paths as $path => $namespace) {
            $files = glob($path . '/*Renderer.php');
            foreach ($files as $file) {
                $className = $namespace . basename($file, '.php');
                $autoload->loadClass($className);

                if (!class_exists($className)) {
                    continue;
                }

                $reflection = new \ReflectionClass($className);
                foreach ($reflection->getAttributes(Renderer::class) as $attribute) {
                    /** @var Renderer $instance */
                    $instance = $attribute->newInstance();
                    if ($instance->name === 'html') {
                        continue;
                    }

                    $engines[$instance->name] = [
                        'bundle' => $instance->bundle
                    ];
                }
            }
        }

        return $engines;
    }

    /**
     * @throws \JsonException
     */
    private function addEngine(string $package, OutputInterface $output): int
    {
        if (!file_exists('composer.json')) {
            $output->writeln('[<fg=cyan>Impulse</>] <error>Erreur : composer.json introuvable à la racine du projet.</error>');
            return Command::FAILURE;
        }

        $composerData = json_decode(file_get_contents('composer.json'), true, 512, JSON_THROW_ON_ERROR);
        $package = explode(':', $package)[0];

        if (!isset($composerData['require'][$package])) {
            $output->writeln("[<fg=cyan>Impulse</>] 📦  Ajout de <info>$package</info> au fichier composer.json...");

            $process = new Process(['composer', 'require', $package]);
            $process->setTty(Process::isTtySupported());
            $process->run(fn($type, $buffer) => $output->write($buffer));

            if (!$process->isSuccessful()) {
                $output->writeln('[<fg=cyan>Impulse</>] <error>Erreur pendant l’installation du package Composer.</error>');
                return Command::FAILURE;
            }
        } else {
            $output->writeln("[<fg=cyan>Impulse</>] ✅  Le package <info>$package</info> est déjà présent.");
        }

        $output->writeln("[<fg=cyan>Impulse</>] 🎉  Impulse est prêt à être utilisé avec le moteur <info>$package</info> !");
        return Command::SUCCESS;
    }
}
