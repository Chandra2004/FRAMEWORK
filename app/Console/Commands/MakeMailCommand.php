<?php

namespace TheFramework\Console\Commands;

use TheFramework\Console\BaseCommand;

class MakeMailCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'make:mail';
    }

    public function getDescription(): string
    {
        return 'Buat class Mail baru untuk pengiriman email';
    }

    public function handle(array $args): void
    {
        $name = $args[0] ?? null;

        if (!$name) {
            $name = $this->ask("Nama mail class (contoh: WelcomeMail)");
        }

        if (!$name) {
            $this->error("Nama mail wajib diisi.");
            return;
        }

        if (!str_ends_with($name, 'Mail')) {
            $name .= 'Mail';
        }

        $dir = BASE_PATH . '/app/Mail';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $filePath = "{$dir}/{$name}.php";

        if (file_exists($filePath)) {
            $this->error("File sudah ada: app/Mail/{$name}.php");
            return;
        }

        $viewName = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', str_replace('Mail', '', $name)));

        $content = <<<PHP
<?php

namespace TheFramework\Mail;

class {$name}
{
    protected string \$subject = '';
    protected string \$view = 'emails.{$viewName}';
    protected array \$data = [];

    public function __construct(array \$data = [])
    {
        \$this->data = \$data;
        \$this->subject = '{$name}';
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        return \$this;
    }

    /**
     * Get email subject.
     */
    public function getSubject(): string
    {
        return \$this->subject;
    }

    /**
     * Set email subject.
     */
    public function subject(string \$subject): self
    {
        \$this->subject = \$subject;
        return \$this;
    }

    /**
     * Get the view name.
     */
    public function getView(): string
    {
        return \$this->view;
    }

    /**
     * Get the data for the view.
     */
    public function getData(): array
    {
        return \$this->data;
    }
}

PHP;

        file_put_contents($filePath, $content);

        $this->success("Mail dibuat: app/Mail/{$name}.php");
        $this->comment("Buat juga view: resources/views/emails/{$viewName}.blade.php");
    }
}
