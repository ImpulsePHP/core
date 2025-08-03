<?php

declare(strict_types=1);

namespace Impulse\Core\Console;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

#[AsCommand(
    name: 'make:component',
    description: 'Crée un composant personnalisé',
    aliases: [
        'm:component',
        'c:make',
    ]
)]
class MakeComponentCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');
        $question = new Question('[<fg=cyan>Impulse</>] Nom du composant (ex: NavbarComponent) : ');
        $name = $helper->ask($input, $output, $question);

        if (!$name) {
            $output->writeln('[<fg=cyan>Impulse</>] <error>Nom invalide.</error>');
            return Command::FAILURE;
        }

        $className = str_ends_with(strtolower($name), 'component') ? ucfirst($name) : ucfirst($name) . 'Component';
        $filePath = getcwd() . "/src/Component/{$className}.php";

        if (file_exists($filePath)) {
            $output->writeln("[<fg=cyan>Impulse</>] <error>Le fichier $className existe déjà.</error>");
            return Command::FAILURE;
        }

        $namespace = 'App\\Component';
        $code = <<<PHP
        <?php
        
        declare(strict_types=1);
        
        namespace $namespace;
        
        use Impulse\Core\Component\AbstractComponent;

        final class $className extends AbstractComponent
        {
            public function setup(): void
            {
                // ...
            }
        
            public function template(): string
            {
                return <<<HTML
                    <!-- Votre template HTML ici -->
                HTML;
            }
        }
        
        PHP;

        file_put_contents($filePath, $code);
        $output->writeln("[<fg=cyan>Impulse</>] 🎉 Le composant <info>$className</info> créé avec succès : <info>src/Component/{$className}.php</info>");

        return Command::SUCCESS;
    }
}
