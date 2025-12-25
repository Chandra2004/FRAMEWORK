<?php

namespace TheFramework\App;

abstract class Job
{
    /**
     * Data yang dibawa oleh Job
     */
    protected $data;

    public function __construct($data = [])
    {
        $this->data = $data;
    }

    /**
     * Logic utama yang akan dijalankan oleh Job
     */
    abstract public function handle();

    /**
     * Helper untuk mendapatkan data
     */
    protected function getData($key = null, $default = null)
    {
        if ($key === null)
            return $this->data;
        return $this->data[$key] ?? $default;
    }
}
