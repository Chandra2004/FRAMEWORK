<?php

namespace TheFramework\Components;

use TheFramework\App\TFWire\Component;

class CounterLabel extends Component
{
    public string $text = '';
    public int $val = 0;

    /**
     * Parameter mount() harus sesuai dengan yang dikirim dari tfwire() call
     */
    public function mount(string $text = '', int $val = 0): void
    {
        $this->text = $text;
        $this->val = $val;
    }

    protected function view(): string
    {
        return 'component.counter-label';
    }
}
