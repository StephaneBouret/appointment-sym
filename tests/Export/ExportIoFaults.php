<?php
namespace App\Tests\Export {
    // Loaded only by phpunit.exports.xml; no production service or filesystem replacement.
    final class ExportIoFaults
    {
        public static string $mode = '';
        public static array $resources = [];
        public static int $csvWrites = 0;
        public static int $closes = 0;
        public static function reset(string $mode = ''): void
        {
            self::$mode=$mode; self::$resources=[]; self::$csvWrites=0; self::$closes=0;
        }
        public static function allClosed(): bool
        {
            foreach (self::$resources as $r) if (is_resource($r)) return false;
            return true;
        }
    }
}
namespace App\Service {
    use App\Tests\Export\ExportIoFaults as Fault;
    function fopen($path, $mode) {
        // Also admit the old stream during isolated counter-proofs.
        if (!in_array($path,['php://memory','php://output'],true)) throw new \LogicException('Unexpected export resource');
        if (Fault::$mode==='open') return false;
        return Fault::$resources[]=\fopen($path,$mode);
    }
    function fputcsv($stream, $fields, $separator=',', $enclosure='"', $escape='\\', $eol="\n") {
        ++Fault::$csvWrites;
        if (Fault::$mode==='csv_header' && Fault::$csvWrites===1) return false;
        if (Fault::$mode==='csv_row' && Fault::$csvWrites===2) return false;
        if (in_array(Fault::$mode,['interrupt','interrupt_cleanup'],true) && Fault::$csvWrites===3) throw new \RuntimeException('interruption synthétique');
        $n=\fputcsv($stream,$fields,$separator,$enclosure,$escape,$eol);
        return Fault::$mode==='csv_count' ? $n-1 : $n;
    }
    function fwrite($stream,$data) {
        if (Fault::$mode==='write') return false;
        if (Fault::$mode==='write_zero') return 0;
        if (Fault::$mode==='partial') return \fwrite($stream,substr($data,0,3));
        return \fwrite($stream,$data);
    }
    function fflush($stream) { return Fault::$mode==='flush' ? false : \fflush($stream); }
    function ftell($stream) { return Fault::$mode==='tell' ? false : \ftell($stream); }
    function rewind($stream) { return Fault::$mode==='rewind' ? false : \rewind($stream); }
    function stream_get_contents($stream) {
        if (Fault::$mode==='read') return false;
        $s=\stream_get_contents($stream);
        return Fault::$mode==='read_short' ? substr($s,0,4) : $s;
    }
    function fclose($stream) {
        ++Fault::$closes;
        if (Fault::$mode==='close_throw' && Fault::$closes===1) throw new \RuntimeException('fermeture synthétique');
        $closed=\fclose($stream);
        if (Fault::$mode==='interrupt_cleanup') throw new \RuntimeException('nettoyage synthétique');
        return Fault::$mode==='close' ? false : $closed;
    }
}
