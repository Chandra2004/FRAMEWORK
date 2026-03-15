<?php

namespace TheFramework\App\TFWire\Traits;

/**
 * WithSorting — Column Sorting Trait
 * 
 * Livewire ❌ tidak punya sorting built-in.
 * TFWire  ✅ sorting multi-column + direction + reset.
 * 
 * Usage:
 *   class UserTable extends Component {
 *       use WithSorting;
 *       protected array $sortable = ['name', 'email', 'created_at'];
 *   }
 * 
 *   View:
 *   <th tf-wire:click="sortBy('name')">
 *       Name {!! $this->sortIcon('name') !!}
 *   </th>
 */
trait WithSorting
{
    public string $sortField = '';
    public string $sortDirection = 'asc';

    /** Columns that are allowed to be sorted */
    protected array $sortable = [];

    /**
     * Sort by a specific field
     */
    public function sortBy(string $field): void
    {
        // Only allow whitelisted columns
        if (!empty($this->sortable) && !in_array($field, $this->sortable)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        // Reset to page 1 when sorting changes
        if (property_exists($this, 'page')) {
            $this->page = 1;
        }

        if (method_exists($this, 'clearComputedCache')) {
            $this->clearComputedCache();
        }
    }

    /**
     * Reset sorting
     */
    public function resetSort(): void
    {
        $this->sortField = '';
        $this->sortDirection = 'asc';
    }

    /**
     * Get sort icon HTML for a column
     */
    protected function sortIcon(string $field): string
    {
        if ($this->sortField !== $field) {
            return '<span class="tf-sort-icon tf-sort-none">⇅</span>';
        }

        return $this->sortDirection === 'asc'
            ? '<span class="tf-sort-icon tf-sort-asc">↑</span>'
            : '<span class="tf-sort-icon tf-sort-desc">↓</span>';
    }

    /**
     * Apply sort to a query builder (compatible with TheFramework & Laravel)
     */
    protected function applySorting($query)
    {
        if (!empty($this->sortField)) {
            if (method_exists($query, 'orderBy')) {
                return $query->orderBy($this->sortField, $this->sortDirection);
            }
        }
        return $query;
    }
}
