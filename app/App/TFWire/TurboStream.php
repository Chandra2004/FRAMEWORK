<?php

namespace TheFramework\App\TFWire;

use TheFramework\App\Http\View;

/**
 * ╔══════════════════════════════════════════════════════════════╗
 * ║  TurboStream Builder — Fluent DOM Manipulation API           ║
 * ║  Version: 1.0.0 | License: MIT                              ║
 * ║                                                              ║
 * ║  7 Core Actions + View Rendering + Conditionals +            ║
 * ║  Toast Notifications + Modal Control + Redirect +            ║
 * ║  Scroll + Bulk Operations + Merge + Chainable API            ║
 * ╚══════════════════════════════════════════════════════════════╝
 */
class TurboStream
{
    protected array $streams = [];

    // ╔══════════════════════════════════════════════════════════╗
    // ║  7 CORE ACTIONS (Turbo Stream Standard)                  ║
    // ╚══════════════════════════════════════════════════════════╝

    /** Append content at the END of target */
    public function append(string $target, string $content): self
    {
        $this->streams[] = $this->build('append', $target, $content);
        return $this;
    }

    /** Prepend content at the BEGINNING of target */
    public function prepend(string $target, string $content): self
    {
        $this->streams[] = $this->build('prepend', $target, $content);
        return $this;
    }

    /** Replace the entire target element (including its tag) */
    public function replace(string $target, string $content): self
    {
        $this->streams[] = $this->build('replace', $target, $content);
        return $this;
    }

    /** Update only the inner content of target */
    public function update(string $target, string $content): self
    {
        $this->streams[] = $this->build('update', $target, $content);
        return $this;
    }

    /** Remove target element from DOM */
    public function remove(string $target): self
    {
        $this->streams[] = "<turbo-stream action=\"remove\" target=\"{$target}\"></turbo-stream>";
        return $this;
    }

    /** Insert content BEFORE target element */
    public function before(string $target, string $content): self
    {
        $this->streams[] = $this->build('before', $target, $content);
        return $this;
    }

    /** Insert content AFTER target element */
    public function after(string $target, string $content): self
    {
        $this->streams[] = $this->build('after', $target, $content);
        return $this;
    }

    // ╔══════════════════════════════════════════════════════════╗
    // ║  VIEW-BASED ACTIONS (Render Blade views)                 ║
    // ╚══════════════════════════════════════════════════════════╝

    public function appendView(string $target, string $view, array $data = []): self
    {
        return $this->append($target, (string) View::render($view, $data));
    }

    public function prependView(string $target, string $view, array $data = []): self
    {
        return $this->prepend($target, (string) View::render($view, $data));
    }

    public function replaceView(string $target, string $view, array $data = []): self
    {
        return $this->replace($target, (string) View::render($view, $data));
    }

    public function updateView(string $target, string $view, array $data = []): self
    {
        return $this->update($target, (string) View::render($view, $data));
    }

    public function beforeView(string $target, string $view, array $data = []): self
    {
        return $this->before($target, (string) View::render($view, $data));
    }

    public function afterView(string $target, string $view, array $data = []): self
    {
        return $this->after($target, (string) View::render($view, $data));
    }

    // ╔══════════════════════════════════════════════════════════╗
    // ║  CONDITIONALS                                            ║
    // ╚══════════════════════════════════════════════════════════╝

    /** Add stream only if condition is true */
    public function when(bool $condition, callable $callback): self
    {
        if ($condition) $callback($this);
        return $this;
    }

    /** Add stream only if condition is false */
    public function unless(bool $condition, callable $callback): self
    {
        if (!$condition) $callback($this);
        return $this;
    }

    // ╔══════════════════════════════════════════════════════════╗
    // ║  TOAST / NOTIFICATION SHORTCUTS                          ║
    // ╚══════════════════════════════════════════════════════════╝

    public function success(string $msg, string $target = 'tf-notifications'): self
    {
        return $this->notification('success', $msg, $target);
    }

    public function error(string $msg, string $target = 'tf-notifications'): self
    {
        return $this->notification('error', $msg, $target);
    }

    public function warning(string $msg, string $target = 'tf-notifications'): self
    {
        return $this->notification('warning', $msg, $target);
    }

    public function info(string $msg, string $target = 'tf-notifications'): self
    {
        return $this->notification('info', $msg, $target);
    }

    /**
     * Render notifikasi menggunakan internal notification.blade.php
     * Memakai view bawaan framework (Tailwind + Flowbite + Lucide)
     */
    protected function notification(string $status, string $msg, string $target): self
    {
        $notifData = ['notification' => ['status' => $status, 'message' => $msg]];
        $html = (string) View::render('notification.notification', $notifData);
        return $this->append($target, $html);
    }

    // ╔══════════════════════════════════════════════════════════╗
    // ║  MODAL CONTROL                                           ║
    // ╚══════════════════════════════════════════════════════════╝

    /** Open a modal by replacing its content and adding show class */
    public function openModal(string $modalId, string $content): self
    {
        $html = "<div id=\"{$modalId}\" class=\"tf-modal tf-modal-open\" role=\"dialog\">"
              . "<div class=\"tf-modal-backdrop\" onclick=\"document.getElementById('{$modalId}').remove()\"></div>"
              . "<div class=\"tf-modal-content\">"
              . "<button class=\"tf-modal-close\" onclick=\"document.getElementById('{$modalId}').remove()\">&times;</button>"
              . $content
              . "</div></div>";
        return $this->append('tf-modal-container', $html);
    }

    /** Open a modal using a Blade view */
    public function openModalView(string $modalId, string $view, array $data = []): self
    {
        return $this->openModal($modalId, (string) View::render($view, $data));
    }

    /** Close a modal */
    public function closeModal(string $modalId): self
    {
        return $this->remove($modalId);
    }

    // ╔══════════════════════════════════════════════════════════╗
    // ║  REDIRECT (via Turbo)                                    ║
    // ╚══════════════════════════════════════════════════════════╝

    /** Redirect user using Turbo Drive */
    public function redirectTo(string $url): self
    {
        if (!headers_sent()) {
            header("Turbo-Location: {$url}");
        }
        return $this;
    }

    // ╔══════════════════════════════════════════════════════════╗
    // ║  SCROLL CONTROL                                          ║
    // ╚══════════════════════════════════════════════════════════╝

    /** Scroll to a specific element */
    public function scrollTo(string $target): self
    {
        $script = "<script>document.getElementById('{$target}')?.scrollIntoView({behavior:'smooth'})</script>";
        return $this->append('tf-scripts', $script);
    }

    /** Scroll to top of page */
    public function scrollToTop(): self
    {
        $script = "<script>window.scrollTo({top:0,behavior:'smooth'})</script>";
        return $this->append('tf-scripts', $script);
    }

    // ╔══════════════════════════════════════════════════════════╗
    // ║  BROWSER EVENTS                                          ║
    // ╚══════════════════════════════════════════════════════════╝

    /** Dispatch a custom browser event (for Alpine.js) */
    public function dispatch(string $event, array $data = []): self
    {
        $json = htmlspecialchars(json_encode($data, JSON_UNESCAPED_UNICODE));
        $script = "<script>document.dispatchEvent(new CustomEvent('{$event}',{detail:{$json}}))</script>";
        return $this->append('tf-scripts', $script);
    }

    /** Dispatch an event to a specific target element ID */
    public function dispatchTo(string $target, string $event, array $data = []): self
    {
        $json = htmlspecialchars(json_encode($data, JSON_UNESCAPED_UNICODE));
        $script = "<script>document.getElementById('{$target}')?.dispatchEvent(new CustomEvent('{$event}',{detail:{$json},bubbles:true}))</script>";
        return $this->append('tf-scripts', $script);
    }

    // ╔══════════════════════════════════════════════════════════╗
    // ║  BULK OPERATIONS                                         ║
    // ╚══════════════════════════════════════════════════════════╝

    /** Remove multiple targets at once */
    public function removeMany(array $targets): self
    {
        foreach ($targets as $target) {
            $this->remove($target);
        }
        return $this;
    }

    /** Apply same action to multiple targets */
    public function each(array $items, callable $callback): self
    {
        foreach ($items as $item) {
            $callback($this, $item);
        }
        return $this;
    }

    // ╔══════════════════════════════════════════════════════════╗
    // ║  BUILD & SEND                                            ║
    // ╚══════════════════════════════════════════════════════════╝

    protected function build(string $action, string $target, string $content): string
    {
        return "<turbo-stream action=\"{$action}\" target=\"{$target}\">"
             . "<template>{$content}</template>"
             . "</turbo-stream>";
    }

    public function buildRaw(string $action, string $target, string $content): string
    {
        return $this->build($action, $target, $content);
    }

    /** Get all streams as raw HTML string */
    public function toHtml(): string
    {
        return implode("\n", $this->streams);
    }

    /** Set Content-Type header and return HTML */
    public function render(): string
    {
        if (!headers_sent()) {
            header('Content-Type: text/vnd.turbo-stream.html; charset=utf-8');
        }
        return $this->toHtml();
    }

    /** Render + echo + exit (Controller shortcut) */
    public function send(): never
    {
        echo $this->render();
        exit;
    }

    // ╔══════════════════════════════════════════════════════════╗
    // ║  UTILITY                                                 ║
    // ╚══════════════════════════════════════════════════════════╝

    public function isEmpty(): bool { return empty($this->streams); }
    public function count(): int { return count($this->streams); }

    /** Merge another TurboStream's instructions */
    public function merge(TurboStream $other): self
    {
        $this->streams = array_merge($this->streams, $other->streams);
        return $this;
    }

    /** Clear all queued streams */
    public function clear(): self
    {
        $this->streams = [];
        return $this;
    }

    /** Tap into the builder for debugging */
    public function tap(callable $callback): self
    {
        $callback($this);
        return $this;
    }

    public function __toString(): string { return $this->toHtml(); }
}
