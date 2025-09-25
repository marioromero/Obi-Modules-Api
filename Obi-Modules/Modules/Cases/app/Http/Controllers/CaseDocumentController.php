<?php

namespace Modules\Cases\app\Http\Controllers;

use iio\libmergepdf\Merger;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Modules\Cases\Services\CasesDocsStorage;
use Modules\Core\app\Http\BaseApiController;
use RuntimeException;

class CaseDocumentController extends BaseApiController
{
    public function __construct(private CasesDocsStorage $storage)
    {
    }

    /**
     * Lista documentos de un caso
     */
    public function index(string $code, Request $request)
    {
        try {
            $recursive = $request->boolean('recursive', true);
            $onlyPdf = $request->boolean('only_pdf', true);

            $list = $this->storage->list($code, $recursive, $onlyPdf);

            // Filtros opcionales
            if ($type = $request->string('type')->toString()) {
                $list = array_values(array_filter($list, fn($i) => $i['type'] === strtoupper($type)));
            }
            if ($q = $request->string('q')->toString()) {
                $list = array_values(array_filter($list, fn($i) => stripos($i['filename'], $q) !== false));
            }

            return $this->success($list, 'Documentos del caso obtenidos correctamente', 200);

        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\Throwable $e) {
            return $this->error('Error interno al listar documentos', 500);
        }
    }

    /**
     * Obtiene metadatos de un documento específico
     */
    public function show(string $code, string $filename, Request $request)
    {
        try {
            $list = $this->storage->list($code, true, false);
            $document = collect($list)->firstWhere('filename', $filename);

            if (!$document) {
                return $this->error('Documento no encontrado', 404);
            }

            return $this->success($document, 'Documento encontrado', 200);

        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\Throwable $e) {
            return $this->error('Error interno al obtener documento', 500);
        }
    }

    /**
     * Descarga/visualiza un documento
     */
    public function download(string $code, Request $request)
    {
        try {
            $path = (string)$request->query('path', '');
            if ($path === '') {
                // retrocompatibilidad con ?filename=
                $filename = (string)$request->query('filename', '');
                if ($filename === '') return $this->error('Parámetro path o filename es requerido', 422);
                $path = $code . '/' . $filename;
            }

            $this->storage->sanitizeCaseCode($code);
            $path = $this->guardPath($code, $path);

            if (!Storage::disk('cases-docs')->exists($path)) {
                return $this->error('Archivo no existe', 404);
            }

            $disposition = $request->boolean('download') ? 'attachment' : 'inline';
            $name = basename($path);

            // STREAM del original (no toca el binario → firma intacta)
            return Storage::disk('cases-docs')->response($path, $name, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => $disposition . '; filename="' . $name . '"',
                'Cache-Control' => 'no-cache, must-revalidate',
            ]);

        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\Throwable $e) {
            return $this->error('Error interno al descargar documento', 500);
        }
    }

    /**
     * Sube un nuevo documento
     */
    public function store(string $code, Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|max:10240|mimes:pdf,doc,docx,jpg,jpeg,png', // 10MB max
                'filename' => 'sometimes|string|max:255',
                'type' => 'required|in:CONTRATO,MANDATO,DOC'
            ]);

            $file = $request->file('file');
            $type = strtoupper($request->string('type')->toString());
            $filename = $request->string('filename')->toString() ?: $file->getClientOriginalName();

            // Renombrar con prefijo
            $filename = $type . '_' . $filename;

            // Construir ruta relativa
            $relativePath = $code . '/' . $filename;

            // Verificar si ya existe y agregar timestamp si es necesario
            while (Storage::disk('cases-docs')->exists($relativePath)) {
                $base = pathinfo($filename, PATHINFO_FILENAME);
                $ext = pathinfo($filename, PATHINFO_EXTENSION);
                $filename = $base . '_' . time() . '.' . $ext;
                $relativePath = $code . '/' . $filename;
            }

            // Guardar archivo
            $content = file_get_contents($file->getRealPath());
            $this->storage->put($code, $relativePath, $content);

            return $this->success(['filename' => $filename], 'Documento subido correctamente', 201);

        } catch (ValidationException $e) {
            return $this->error('Datos inválidos: ' . $e->getMessage(), 422);
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\Throwable $e) {
            return $this->error('Error interno al subir documento', 500);
        }
    }

    /**
     * Elimina un documento
     */
    public function destroy(string $code, Request $request)
    {
        try {
            $filename = $request->string('filename')->toString();
            if (!$filename) {
                return $this->error('Parámetro filename es requerido', 422);
            }

            $relativePath = $code . '/' . $filename;

            $deleted = $this->storage->delete($code, $relativePath);

            if (!$deleted) {
                return $this->error('Documento no encontrado', 404);
            }

            return $this->success(null, 'Documento eliminado exitosamente', 200);

        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\Throwable $e) {
            return $this->error('Error interno al eliminar documento', 500);
        }
    }

    // Modules/Cases/app/Http/Controllers/CaseDocumentController.php
    public function checkSignature(string $code, \Illuminate\Http\Request $request)
    {
        $path = (string)$request->input('path');
        $this->storage->sanitizeCaseCode($code);
        $this->guardPath($code, $path);

        if (!\Illuminate\Support\Facades\Storage::disk('cases-docs')->exists($path)) {
            return response()->json(['message' => 'Archivo no existe'], 404);
        }

        // Lee hasta 1 MB (suficiente para detectar marcadores en la mayoría de PDFs)
        $stream = \Illuminate\Support\Facades\Storage::disk('cases-docs')->readStream($path);
        $buf = '';
        while (!feof($stream) && strlen($buf) < 1024 * 1024) {
            $buf .= fread($stream, 8192);
        }
        fclose($stream);

        $markers = [
            'has_Type_Sig' => (bool)preg_match('/\/Type\s*\/Sig/i', $buf),
            'has_ByteRange' => (bool)preg_match('/\/ByteRange\s*\[/i', $buf),
            'has_ACEPTA' => stripos($buf, 'ACEPTA') !== false,
        ];

        return response()->json([
            'likely_signed' => in_array(true, $markers, true),
            'markers' => $markers,
            'size' => \Illuminate\Support\Facades\Storage::disk('cases-docs')->size($path),
            'filename' => basename($path),
        ]);
    }

    private function guardPath(string $case, string $rel): string
    {
        // normaliza y quita barras iniciales
        $rel = str_replace('\\', '/', ltrim((string)$rel, '/'));

        // debe empezar por el prefijo del caso, p. ej. TR123/
        if (!str_starts_with($rel, $case . '/')) {
            abort(422, 'Ruta fuera del caso');
        }

        // prohíbe traversal
        if (str_contains($rel, '..')) {
            abort(422, 'Ruta inválida');
        }

        return $rel; // ruta segura, relativa al disk
    }

    public function preview(string $code, Request $request)
    {
        try {
            $path = (string)$request->query('path', '');
            if ($path === '') return $this->error('Parámetro path es requerido', 422);

            $this->storage->sanitizeCaseCode($code);
            $path = $this->guardPath($code, $path);

            if (!Storage::disk('cases-docs')->exists($path)) {
                return $this->error('Archivo no existe', 404);
            }

            // 1) ¿Nos dieron el comprobante explícito?
            $certHint = (string)$request->query('cert_path', '');
            $certPath = $certHint ? $this->guardPath($code, $certHint) : null;

            // 2) Si no, intenta heurística
            if (!$certPath) {
                $certPath = $this->findCertificateFor($path);
            }

            // 3) Si hay comprobante, concatena [comprobante + original]
            if ($certPath && Storage::disk('cases-docs')->exists($certPath)) {
                $merger = new Merger();
                $merger->addRaw(Storage::disk('cases-docs')->get($certPath));
                $merger->addRaw(Storage::disk('cases-docs')->get($path));
                $merged = $merger->merge();

                $name = pathinfo($path, PATHINFO_FILENAME) . '_preview.pdf';
                return response($merged, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="' . $name . '"',
                    'Cache-Control' => 'no-cache, must-revalidate',
                ]);
            }

            // 4) Sin comprobante: sirve el original (al menos firmado)
            $name = basename($path);
            return Storage::disk('cases-docs')->response($path, $name, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $name . '"',
                'Cache-Control' => 'no-cache, must-revalidate',
            ]);

        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\Throwable $e) {
            return $this->error('Error interno al previsualizar documento', 500);
        }
    }

    private function findCertificateFor(string $docPath): ?string
{
    $disk = Storage::disk('cases-docs');
    $dir  = trim(str_replace('\\','/', dirname($docPath)), '/');
    $base = pathinfo($docPath, PATHINFO_FILENAME);

    $candidates = [
        "{$dir}/{$base}-comprobante.pdf",
        "{$dir}/{$base}_comprobante.pdf",
        "{$dir}/{$base}-certificado.pdf",
        "{$dir}/{$base}_certificado.pdf",
        "{$dir}/{$base}-cert.pdf",
        "{$dir}/{$base}_cert.pdf",
        "{$dir}/certificados/{$base}.pdf",
        "{$dir}/cert/{$base}.pdf",
    ];
    foreach ($candidates as $c) if ($disk->exists($c)) return $c;

    // búsqueda amplia por palabras clave (primer match)
    foreach ($disk->files($dir) as $f) {
        if (preg_match('/(acepta|comprobante|certificad)/i', $f)) return $f;
    }
    return null;
}

}
