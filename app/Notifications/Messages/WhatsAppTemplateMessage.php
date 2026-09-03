<?php

namespace App\Notifications\Messages;

class WhatsAppTemplateMessage
{
    protected string $language;

    /** @var array<int, string> */
    protected array $parameters = [];

    protected string $event;

    protected string $departamento = 'General';

    public function __construct(protected string $template)
    {
        $this->language = config('services.whatsapp.default_language');
    }

    public static function create(string $template): static
    {
        return new static($template);
    }

    public function language(string $language): static
    {
        $this->language = $language;

        return $this;
    }

    /**
     * @param  array<int, string>  $parameters
     */
    public function parameters(array $parameters): static
    {
        $this->parameters = $parameters;

        return $this;
    }

    public function event(string $event): static
    {
        $this->event = $event;

        return $this;
    }

    /**
     * The department/inbox this message should be sent from, e.g.
     * "Suscripción" or "Cobranzas". Defaults to "General".
     */
    public function department(string $departamento): static
    {
        $this->departamento = $departamento;

        return $this;
    }

    public function getDepartment(): string
    {
        return $this->departamento;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * @return array<int, string>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getEvent(): string
    {
        return $this->event ?? $this->template;
    }
}
