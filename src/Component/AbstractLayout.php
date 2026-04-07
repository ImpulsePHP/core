<?php

declare(strict_types=1);

namespace Impulse\Core\Component;

abstract class AbstractLayout extends AbstractComponent
{
    public function isScopedStyle(): bool
    {
        return false;
    }

    /**
     * Retourne une chaîne à préfixer devant le titre de la page (ou null).
     */
    public function titlePrefix(): ?string
    {
        return null;
    }

    /**
     * Retourne une chaîne à suffixer après le titre de la page (ou null).
     */
    public function titleSuffix(): ?string
    {
        return null;
    }
}
