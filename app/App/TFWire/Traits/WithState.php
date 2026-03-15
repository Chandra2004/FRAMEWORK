<?php

namespace TheFramework\App\TFWire\Traits;

/**
 * WithState — Advanced State Persistence
 * 
 * Livewire ❌ tidak bisa persist state across page refresh.
 * TFWire  ✅ persist di localStorage, sessionStorage, atau URL.
 * 
 * Usage:
 *   class DashboardFilter extends Component {
 *       use WithState;
 * 
 *       public string $dateRange = '7d';
 *       public string $category = 'all';
 * 
 *       // Properties yang dipersist di browser
 *       protected array $persist = ['dateRange', 'category'];
 *       protected string $persistDriver = 'local'; // 'local' | 'session'
 *   }
 */
trait WithState
{
    /** Properties to persist across page navigations */
    protected array $persist = [];

    /** Storage driver: 'local' (localStorage) or 'session' (sessionStorage) */
    protected string $persistDriver = 'local';

    /**
     * Get the persist config for the JS engine
     * This is called during render to pass config to frontend
     */
    protected function getPersistConfig(): array
    {
        if (empty($this->persist)) return [];

        $data = [];
        foreach ($this->persist as $prop) {
            if (property_exists($this, $prop)) {
                $data[$prop] = $this->{$prop};
            }
        }

        return [
            'driver'     => $this->persistDriver,
            'key'        => 'tfwire:' . $this->id,
            'properties' => $data,
        ];
    }

    /**
     * Apply persisted state from browser (called during hydrate)
     */
    public function applyPersistedState(array $data): void
    {
        foreach ($data as $key => $value) {
            if (in_array($key, $this->persist) && property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }
}
