<?php

namespace App\Services;

class FileCache
{
    private $cacheDir;

    public function __construct()
    {
        $this->cacheDir = sys_get_temp_dir() . '/iptv_cache';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
    }

    public function get($key)
    {
        $file = $this->getFilePath($key);
        if (!file_exists($file)) {
            return null;
        }

        $content = file_get_contents($file);
        $data = unserialize($content);

        if ($data['expires_at'] < time()) {
            unlink($file);
            return null;
        }

        return $data['payload'];
    }

    public function set($key, $payload, $ttlSeconds = 3600)
    {
        $data = [
            'created_at' => time(),
            'expires_at' => time() + $ttlSeconds,
            'payload' => $payload
        ];

        $file = $this->getFilePath($key);
        file_put_contents($file, serialize($data));
    }

    private function getFilePath($key)
    {
        return $this->cacheDir . '/' . md5($key) . '.cache';
    }

    public function clear()
    {
        $files = glob($this->cacheDir . '/*.cache');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}
