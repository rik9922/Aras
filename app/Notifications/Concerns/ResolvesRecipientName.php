<?php

namespace App\Notifications\Concerns;

trait ResolvesRecipientName
{
    protected function recipientFirstName(object $notifiable): string
    {
        $firstName = trim((string) ($notifiable->first_name ?? ''));

        if ($firstName !== '') {
            return $firstName;
        }

        $name = trim((string) ($notifiable->name ?? ''));

        if ($name === '') {
            return 'there';
        }

        return strtok($name, ' ') ?: $name;
    }
}
