<?php

declare(strict_types=1);

namespace Framework;

class TemplateEngine
{
    private array $globalTemplateData = [];

    public function __construct(private string $basePath)
    {
    }

    public function render(string $template, array $data = []) //: void
    {
        extract($data, EXTR_SKIP);
        extract($this->globalTemplateData, EXTR_SKIP);
        ob_start(); // Turn on output buffering. The content won't be sent unless we say it directly or limit 4096 would be violated

        include $this->resolve($template);
        $output = ob_get_contents(); // Return the content of the output buffer
        ob_end_clean(); // Clear ouput buffer and turn off output buffering

        return $output;
    }

    public function resolve(string $path)
    {
        return "{$this->basePath}/$path";
    }

    public function addGlobal(string $key, mixed $value)
    {
        $this->globalTemplateData[$key] = $value;
    }
}
