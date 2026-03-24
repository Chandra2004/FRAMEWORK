<?php

namespace TheFramework\Components;

use TheFramework\App\TFWire\Component;
use TheFramework\Models\User;

class Counter extends Component
{
    public string $name = 'default';
    public int $count = 0;

    public function mount(string $name = 'chandra'): void
    {
        $user = User::query()->where('id', '=', 1)->first();
        $this->name = $user->name;
        $this->count = $user->hitung;
    }

    protected function view(): string
    {
        return 'component.counter';
    }

    public function updatedName($value, $oldValue)
    {
        if ($value === $oldValue) {
            return;
        }
        User::query()->where('id', '=', 1)->update([
            'name' => $value
        ]);

        $this->flash('success', 'Nama berhasil diperbarui!');
    }


    public function increment()
    {
        if ($this->count >= 20) {
            $this->flash('warning', 'Limit hanya 20 saja');
            return;
        }

        $this->count++;
        User::query()->where('id', '=', 1)->update(['hitung' => $this->count]);
    }

    public function decrement()
    {
        if ($this->count <= 0) {
            $this->flash('warning', 'angka anda sudah minimal');
            return;
        }

        $this->count = max(0, $this->count - 1);
        User::query()->where('id', '=', 1)->update(['hitung' => $this->count]);
    }
}
