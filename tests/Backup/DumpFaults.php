<?php
namespace App\Tests\Backup {
    final class DumpFaults
    {
        public static string $root;
        public static string $mode;
        public static string $sql;
        public static array $trace = [];
        public static array $temporaries = [];
        public const DATA = "-- synthetic only, échantillon\nINSERT INTO example VALUES (1, 'été');\n";
        public const EXISTING = 'preexisting synthetic file: never overwrite';

        public static function guard(string $path): void
        {
            $path = str_replace('\\', '/', $path);
            if ($path !== self::$root && !str_starts_with($path, self::$root.'/')) {
                throw new \LogicException('Test refused a path outside its isolated directory');
            }
            if (str_contains($path, '/../')) throw new \LogicException('Traversal refused');
        }

        public static function operation(string $name): string
        {
            self::$trace[] = $name;
            if (self::$mode === $name.'_throw' || ($name === 'process' && self::$mode === 'process_cleanup_throw')) throw new \RuntimeException('synthetic-password synthetic-user synthetic-db');
            return self::$mode;
        }
    }
}
namespace Symfony\Component\Process {
    use App\Tests\Backup\DumpFaults as F;
    // This class is defined before Composer is loaded in worker.php. No shell API exists here.
    final class Process
    {
        public static function fromShellCommandline(string $command): self
        {
            F::operation('process_construct');
            if (!str_contains($command, '--defaults-extra-file=') || !str_contains($command, '--no-create-info')) {
                throw new \LogicException('Dump options changed');
            }
            return new self();
        }
        public function setTimeout(int $timeout): void { if ($timeout !== 3600) throw new \LogicException('Timeout changed'); }
        public function run(): int
        {
            F::operation('process');
            F::guard(F::$sql);
            \file_put_contents(F::$sql, F::$mode === 'empty_sql' ? '' : F::DATA);
            return F::$mode === 'process_false' ? 1 : 0;
        }
        public function isSuccessful(): bool { return F::$mode !== 'process_false'; }
        public function getErrorOutput(): string { return 'synthetic-password synthetic-user synthetic-db'; }
    }
}
namespace App\Command {
    use App\Tests\Backup\DumpFaults as F;
    function sprintf($format, ...$args) {
        $value = \sprintf($format, ...$args);
        if (str_ends_with($value, '.sql')) {
            $value = F::$root.'/synthetic_20000101_000000.sql';
            F::$sql = $value;
            if (F::$mode === 'sql_collision') \file_put_contents($value, F::EXISTING);
            if (F::$mode === 'archive_collision') \file_put_contents($value.'.gz', F::EXISTING);
            if (F::$mode === 'sql_directory') \mkdir($value);
        }
        return $value;
    }
    function sys_get_temp_dir() { return F::$root; }
    function is_dir($path) { F::guard($path); return \is_dir($path); }
    function mkdir($path, $permissions, $recursive) {
        F::guard($path); F::operation('mkdir');
        return F::$mode === 'mkdir_false' ? false : \mkdir($path, $permissions, $recursive);
    }
    function file_exists($path) { F::guard($path); return \file_exists($path); }
    function is_link($path) { F::guard($path); return \is_link($path); }
    function tempnam($directory, $prefix) {
        F::guard($directory);
        $kind = $prefix === 'mysqldump_' ? 'options_temp' : 'archive_temp';
        F::operation($kind);
        if (F::$mode === $kind.'_false') return false;
        if ($kind === 'archive_temp' && F::$mode === 'temp_fallback') {
            $directory = F::$root.'/fallback'; \mkdir($directory);
        }
        $path = str_replace('\\', '/', \tempnam($directory, $prefix));
        F::$temporaries[$path] = $kind;
        return $path;
    }
    function chmod($path, $permissions) {
        F::guard($path); F::operation('chmod');
        return F::$mode === 'chmod_false' ? false : \chmod($path, $permissions);
    }
    function fopen($path, $mode) {
        F::guard($path); F::operation('reserve');
        if (F::$mode === 'reserve_race') \file_put_contents($path, F::EXISTING);
        return F::$mode === 'reserve_false' ? false : @\fopen($path, $mode);
    }
    function fclose($handle) {
        F::operation('close');
        $result = \fclose($handle);
        return F::$mode === 'close_false' ? false : $result;
    }
    function file_put_contents($path, $data) {
        F::guard($path);
        $kind = (F::$temporaries[$path] ?? '') === 'options_temp' ? 'options_write' : 'archive_write';
        $mode = F::operation($kind);
        if ($mode === $kind.'_false') return false;
        if ($mode === $kind.'_zero') return 0;
        if ($mode === $kind.'_partial') return \file_put_contents($path, substr((string)$data, 0, 4));
        if ($mode === 'corrupt_write' && $kind === 'archive_write') {
            \file_put_contents($path, str_repeat('x', strlen($data)));
            return strlen($data);
        }
        return \file_put_contents($path, $data);
    }
    function file_get_contents($path) {
        F::guard($path);
        $kind = str_ends_with($path, '.sql') ? 'read' : 'verify_read';
        F::operation($kind);
        if (F::$mode === $kind.'_false') return false;
        return \file_get_contents($path);
    }
    function gzencode($data, $level) {
        F::operation('compress');
        if (F::$mode === 'compress_false') return false;
        if (F::$mode === 'corrupt_compress') return 'invalid gzip';
        return \gzencode($data, $level);
    }
    function gzdecode($data) {
        F::operation('decode');
        return F::$mode === 'decode_false' ? false : @\gzdecode($data);
    }
    function function_exists($name) {
        return F::$mode === 'no_zlib' || (F::$mode === 'no_decode' && $name === 'gzdecode') ? false : \function_exists($name);
    }
    function link($source, $destination) {
        F::guard($source); F::guard($destination); F::operation('finalize');
        if (\file_exists($destination)) throw new \LogicException('Incomplete final archive exposed');
        if (F::$mode === 'finalize_race') \file_put_contents($destination, F::EXISTING);
        return F::$mode === 'finalize_false' ? false : @\link($source, $destination);
    }
    function unlink($path) {
        F::guard($path);
        $kind = str_ends_with($path, '.sql') ? 'sql_unlink' : ((F::$temporaries[$path] ?? '') === 'options_temp' ? 'options_unlink' : 'archive_unlink');
        F::operation($kind);
        if ($kind === 'options_unlink' && F::$mode === 'process_cleanup_throw') throw new \RuntimeException('synthetic-password');
        return F::$mode === $kind.'_false' ? false : \unlink($path);
    }
}
