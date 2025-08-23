<?php

namespace Modules\Cases\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class CasesDocsStorage
{

    private string $disk = 'cases-docs';

    /** Valida y normaliza el código (TR + dígitos). */
    public function sanitizeCaseCode(string $code): string
    {
        $code = trim($code);
        if (!preg_match('/^TR\d+$/', $code)) {
            throw new RuntimeException('Código de caso inválido.');
        }
        return $code;
    }

    /** Lista archivos del caso (por defecto recursivo). Solo PDFs si $onlyPdf=true. */
    public function list(string $caseCode, bool $recursive = true, bool $onlyPdf = true): array
    {
        $case = $this->sanitizeCaseCode($caseCode);

        $fs = Storage::disk($this->disk);
        $paths = $recursive ? $fs->allFiles($case) : $fs->files($case);

        $out = [];
        foreach ($paths as $relPath) {
            // evita leaks fuera del caso
            if (!Str::startsWith($relPath, $case . '/')) continue;

            $ext = strtolower(pathinfo($relPath, PATHINFO_EXTENSION));
            if ($onlyPdf && $ext !== 'pdf') continue;

            $name = pathinfo($relPath, PATHINFO_BASENAME);
            $size = $fs->size($relPath);
            $last = $fs->lastModified($relPath);

            $out[] = [
                'case_code' => $case,
                'path' => $relPath,          // relativo al disk
                'filename' => $name,
                'extension' => $ext,
                'size' => $size,
                'last_modified_iso' => gmdate('c', $last),
                // tipo heurístico básico (mejoraremos luego si quieres)
                'type' => $this->guessType($name),
            ];
        }
        return $out;
    }

    /** Entrega contenido binario (para controladores). */
    public function read(string $caseCode, string $relativePath): string
    {
        $case = $this->sanitizeCaseCode($caseCode);
        $this->guardPath($case, $relativePath);
        return Storage::disk($this->disk)->get($relativePath);
    }

    /** Escribe un archivo dentro del caso (por si necesitas subir). */
    public function put(string $caseCode, string $relativePath, string $contents): void
    {
        $case = $this->sanitizeCaseCode($caseCode);
        $this->guardPath($case, $relativePath);
        Storage::disk($this->disk)->put($relativePath, $contents);
    }

    /** Borra un archivo del caso. */
    public function delete(string $caseCode, string $relativePath): bool
    {
        $case = $this->sanitizeCaseCode($caseCode);
        $this->guardPath($case, $relativePath);
        return Storage::disk($this->disk)->delete($relativePath);
    }

    private function guardPath(string $case, string $rel): void
    {
        $rel = ltrim($rel, '/');
        if (!Str::startsWith($rel, $case . '/') || str_contains($rel, '..')) {
            throw new RuntimeException('Ruta fuera del caso.');
        }
    }

    private function guessType(string $filename): string
    {
        $n = mb_strtolower($filename, 'UTF-8');
        if (preg_match('/mandato|_ma2/', $n)) return 'MANDATO';
        if (preg_match('/contrat[ao]|contrato[_\-]de[_\-]servicio|_co2/', $n)) return 'CONTRATO';
        if (preg_match('/ifl|informe[_\-]?de[_\-]?liquidaci[oó]n|informe_a/', $n)) return 'INFORME_LIQUIDACION';
        if (str_contains($n, 'carta_prorroga')) return 'CARTA_PRORROGA';
        if (Str::startsWith($n, 'car-')) return 'CAR';
        return 'OTRO';
    }

}
