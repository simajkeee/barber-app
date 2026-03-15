<?php

declare(strict_types=1);

namespace App\Reminder\Service;

final class MessageTemplateResolver
{
    /**
     * @param array<string, string> $variables
     */
    public function resolve(string $template, array $variables): string
    {
        $replacements = [];
        foreach ($variables as $key => $value) {
            $replacements['{'.$key.'}'] = $value;
        }

        return strtr($template, $replacements);
    }
}
