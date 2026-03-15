<?php

namespace TheFramework\App\TFWire\Traits;

/**
 * WithPagination — Smart Pagination Trait
 * 
 * Lebih powerful dari Livewire: support infinite scroll,
 * cursor pagination, dan load-more pattern.
 * 
 * Usage:
 *   class UserTable extends Component {
 *       use WithPagination;
 *       protected int $perPage = 10;
 *       protected string $paginationMode = 'pages'; // 'pages' | 'infinite' | 'load-more'
 *   }
 */
trait WithPagination
{
    public int $page = 1;
    public int $perPage = 15;

    /** Pagination mode: 'pages' (classic), 'infinite' (auto-scroll), 'load-more' (button) */
    protected string $paginationMode = 'pages';

    /** Cursor for cursor-based pagination (more efficient for large datasets) */
    protected ?string $cursor = null;

    /**
     * Go to specific page
     */
    public function gotoPage(int $page): void
    {
        $this->page = max(1, $page);
        $this->resetComputedOnPaginate();
    }

    public function nextPage(): void
    {
        $this->page++;
        $this->resetComputedOnPaginate();
    }

    public function previousPage(): void
    {
        $this->page = max(1, $this->page - 1);
        $this->resetComputedOnPaginate();
    }

    public function resetPage(): void
    {
        $this->page = 1;
        $this->cursor = null;
        $this->resetComputedOnPaginate();
    }

    /**
     * Set cursor for cursor pagination
     */
    public function setCursor(?string $cursor): void
    {
        $this->cursor = $cursor;
    }

    /**
     * Calculate database offset
     */
    protected function getOffset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }

    /**
     * Generate comprehensive pagination info
     */
    protected function getPaginationInfo(int $total): array
    {
        $lastPage = max(1, (int) ceil($total / $this->perPage));
        $this->page = min($this->page, $lastPage);

        return [
            'current_page' => $this->page,
            'per_page'     => $this->perPage,
            'total'        => $total,
            'last_page'    => $lastPage,
            'from'         => $total > 0 ? ($this->page - 1) * $this->perPage + 1 : 0,
            'to'           => min($this->page * $this->perPage, $total),
            'has_previous' => $this->page > 1,
            'has_next'     => $this->page < $lastPage,
            'mode'         => $this->paginationMode,
            'pages'        => $this->generatePageNumbers($this->page, $lastPage),
        ];
    }

    /**
     * Generate smart page numbers (with ellipsis)
     * e.g., [1, 2, 3, '...', 8, 9, 10] for page 2 of 10
     */
    protected function generatePageNumbers(int $current, int $last, int $window = 2): array
    {
        if ($last <= 7) return range(1, $last);

        $pages = [];
        $from = max(1, $current - $window);
        $to = min($last, $current + $window);

        if ($from > 1) {
            $pages[] = 1;
            if ($from > 2) $pages[] = '...';
        }

        for ($i = $from; $i <= $to; $i++) {
            $pages[] = $i;
        }

        if ($to < $last) {
            if ($to < $last - 1) $pages[] = '...';
            $pages[] = $last;
        }

        return $pages;
    }

    /**
     * Render pagination HTML (built-in, zero config)
     */
    protected function renderPagination(int $total): string
    {
        $info = $this->getPaginationInfo($total);

        if ($this->paginationMode === 'load-more') {
            return $this->renderLoadMore($info);
        }

        if ($this->paginationMode === 'infinite') {
            return $this->renderInfiniteScroll($info);
        }

        return $this->renderPageLinks($info);
    }

    protected function renderPageLinks(array $info): string
    {
        if ($info['last_page'] <= 1) return '';

        $html = '<nav class="tf-pagination" role="navigation">';
        $html .= '<span class="tf-page-info">Showing ' . $info['from'] . '-' . $info['to'] . ' of ' . $info['total'] . '</span>';
        $html .= '<div class="tf-page-links">';

        // Previous
        if ($info['has_previous']) {
            $html .= '<button tf-wire:click="gotoPage(' . ($info['current_page'] - 1) . ')" class="tf-page-btn">&laquo;</button>';
        }

        // Page numbers
        foreach ($info['pages'] as $page) {
            if ($page === '...') {
                $html .= '<span class="tf-page-dots">…</span>';
            } else {
                $active = $page === $info['current_page'] ? ' tf-page-active' : '';
                $html .= '<button tf-wire:click="gotoPage(' . $page . ')" class="tf-page-btn' . $active . '">' . $page . '</button>';
            }
        }

        // Next
        if ($info['has_next']) {
            $html .= '<button tf-wire:click="gotoPage(' . ($info['current_page'] + 1) . ')" class="tf-page-btn">&raquo;</button>';
        }

        $html .= '</div></nav>';
        return $html;
    }

    protected function renderLoadMore(array $info): string
    {
        if (!$info['has_next']) return '';
        return '<div class="tf-load-more">'
             . '<button tf-wire:click="nextPage" class="tf-load-more-btn">'
             . '<span tf-wire:loading tf-wire:target="nextPage" style="display:none">Loading...</span>'
             . '<span tf-wire:loading.remove tf-wire:target="nextPage">Load More (' . ($info['total'] - $info['to']) . ' remaining)</span>'
             . '</button></div>';
    }

    protected function renderInfiniteScroll(array $info): string
    {
        if (!$info['has_next']) return '';
        return '<div class="tf-infinite-trigger" '
             . 'x-data="{}" '
             . 'x-intersect="$el.closest(\'turbo-frame\') && TFWire.sendAction($el.closest(\'turbo-frame[data-controller=tfwire]\'), \'nextPage\')">'
             . '<div class="tf-wire-spinner" style="margin:1rem auto"></div>'
             . '</div>';
    }

    private function resetComputedOnPaginate(): void
    {
        if (method_exists($this, 'clearComputedCache')) {
            $this->clearComputedCache();
        }
    }
}
