<?php

namespace TheFramework\App\TFWire\Traits;

/**
 * WithSearch — Live Search Trait
 * 
 * Livewire ❌ tidak punya search built-in.
 * TFWire  ✅ debounced live search + highlight + reset.
 * 
 * Usage:
 *   class UserTable extends Component {
 *       use WithSearch;
 *       protected array $searchable = ['name', 'email'];
 *       protected int $searchDebounce = 300;
 *   }
 * 
 *   View:
 *   <input tf-wire:model.debounce.300ms="search" placeholder="Search...">
 */
trait WithSearch
{
    public string $search = '';

    /** Columns that are searchable */
    protected array $searchable = [];

    /** Minimum characters before search triggers */
    protected int $searchMinLength = 1;

    /**
     * Hook: when search changes, reset page to 1
     */
    public function updatedSearch(string $value, $old): void
    {
        if (property_exists($this, 'page')) {
            $this->page = 1;
        }

        if (method_exists($this, 'clearComputedCache')) {
            $this->clearComputedCache();
        }
    }

    /**
     * Clear search
     */
    public function clearSearch(): void
    {
        $this->search = '';
        $this->updatedSearch('', $this->search);
    }

    /**
     * Check if currently searching
     */
    protected function isSearching(): bool
    {
        return strlen($this->search) >= $this->searchMinLength;
    }

    /**
     * Apply search to query builder
     */
    protected function applySearch($query, ?array $columns = null)
    {
        $columns = $columns ?? $this->searchable;

        if (!$this->isSearching() || empty($columns)) {
            return $query;
        }

        if (method_exists($query, 'where')) {
            $search = '%' . $this->search . '%';
            $first = true;

            foreach ($columns as $column) {
                if ($first) {
                    $query = $query->where($column, 'LIKE', $search);
                    $first = false;
                } else {
                    $query = $query->orWhere($column, 'LIKE', $search);
                }
            }
        }

        return $query;
    }

    /**
     * Highlight search term in text
     */
    protected function highlight(string $text): string
    {
        if (!$this->isSearching()) return htmlspecialchars($text);

        $escaped = htmlspecialchars($text);
        $term = htmlspecialchars($this->search);

        return str_ireplace(
            $term,
            '<mark class="tf-highlight">' . $term . '</mark>',
            $escaped
        );
    }
}
