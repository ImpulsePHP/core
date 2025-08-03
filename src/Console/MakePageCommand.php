<?php

declare(strict_types=1);

namespace Impulse\Core\Console;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

#[AsCommand(
    name: 'make:page',
    description: 'Crée une page personnalisé',
    aliases: [
        'm:page',
        'p:make',
    ]
)]
class MakePageCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');
        $question = new Question('[<fg=cyan>Impulse</>] Nom de la page (ex: ContactPage) : ');
        $name = $helper->ask($input, $output, $question);

        if (!$name) {
            $output->writeln('[<fg=cyan>Impulse</>] <error>Nom invalide.</error>');
            return Command::FAILURE;
        }

        $question = new Question('[<fg=cyan>Impulse</>] URL de la page (ex: /contact) : ');
        $path = $helper->ask($input, $output, $question);

        $path = str_starts_with(strtolower($path), '/') ? strtolower($path) : '/' . strtolower($path);
        $className = str_ends_with(strtolower($name), 'page') ? ucfirst($name) : ucfirst($name) . 'Page';
        $filePath = getcwd() . "/src/Page/{$className}.php";

        if (file_exists($filePath)) {
            $output->writeln("[<fg=cyan>Impulse</>] <error>Le fichier $className existe déjà.</error>");
            return Command::FAILURE;
        }

        $namespace = 'App\\Page';
        $code = <<<PHP
        <?php
        
        declare(strict_types=1);
        
        namespace $namespace;
        
        use Impulse\Core\Component\AbstractPage;
        use Impulse\\Core\\Attribute\\PageProperty;
        
        #[PageProperty(
            route: '$path',
            name: '$className'
        )]
        final class $className extends AbstractPage
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
        $output->writeln("[<fg=cyan>Impulse</>] 🎉 La page <info>$className</info> créée avec succès : <info>src/Page/{$className}.php</info>");

        return Command::SUCCESS;
    }
}
