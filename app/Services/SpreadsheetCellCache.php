<?php

namespace App\Services;

use DateInterval;
use DateTimeImmutable;
use PDO;
use PDOStatement;
use Psr\SimpleCache\CacheInterface;

/** A private, short-lived SQLite cell cache for large spreadsheet exports. */
class SpreadsheetCellCache implements CacheInterface
{
    private PDO $database;
    private PDOStatement $read;
    private PDOStatement $write;
    private PDOStatement $remove;
    private string $path;

    public function __construct()
    {
        $directory = storage_path('framework/cache');
        if (!is_dir($directory)) mkdir($directory, 0755, true);
        $this->path = tempnam($directory, 'excel-cells-');
        chmod($this->path, 0600);
        $path = $this->path;
        register_shutdown_function(static function () use ($path) {
            if (is_file($path)) @unlink($path);
        });
        $this->database = new PDO('sqlite:'.$this->path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $this->database->exec('PRAGMA journal_mode=OFF; PRAGMA synchronous=OFF; PRAGMA cache_size=-4096; CREATE TABLE cells (key TEXT PRIMARY KEY, value BLOB NOT NULL, expires INTEGER) WITHOUT ROWID');
        // The cache is disposable and private to this instance. One transaction
        // avoids a disk sync per cell while keeping PHP memory bounded.
        $this->database->beginTransaction();
        $this->read = $this->database->prepare('SELECT value FROM cells WHERE key = ? AND (expires IS NULL OR expires > ?)');
        $this->write = $this->database->prepare('INSERT OR REPLACE INTO cells (key,value,expires) VALUES (?,?,?)');
        $this->remove = $this->database->prepare('DELETE FROM cells WHERE key = ?');
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->read->execute([$key, time()]);
        $value = $this->read->fetchColumn();
        $this->read->closeCursor();
        return $value === false ? $default : unserialize($value);
    }

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $expires = $ttl instanceof DateInterval ? (new DateTimeImmutable())->add($ttl)->getTimestamp() : ($ttl === null ? null : time() + $ttl);
        return $this->write->execute([$key, serialize($value), $expires]);
    }

    public function delete(string $key): bool
    {
        return $this->remove->execute([$key]);
    }

    public function clear(): bool
    {
        $this->database->exec('DELETE FROM cells');
        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        foreach ($keys as $key) yield $key => $this->get($key, $default);
    }

    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        foreach ($values as $key => $value) $this->set($key, $value, $ttl);
        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) $this->delete($key);
        return true;
    }

    public function has(string $key): bool
    {
        $missing = new \stdClass();
        return $this->get($key, $missing) !== $missing;
    }

    public function __destruct()
    {
        if (isset($this->database)) {
            unset($this->read, $this->write, $this->remove, $this->database);
        }
        if (isset($this->path)) @unlink($this->path);
    }
}
