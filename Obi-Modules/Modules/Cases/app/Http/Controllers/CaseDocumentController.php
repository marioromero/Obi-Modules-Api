<?php

namespace Modules\Cases\app\Http\Controllers;

use iio\libmergepdf\Merger;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Modules\Cases\Services\CasesDocsStorage;
use Modules\Core\app\Http\BaseApiController;
use RuntimeException;
use Imagick;

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
            $onlyPdf = $request->boolean('only_pdf', false);

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
        $tStart = microtime(true);
        $log = Log::channel('documents');

        try {
            $path = (string) $request->query('path', '');
            if ($path === '') {
                // retrocompatibilidad con ?filename=
                $filename = (string) $request->query('filename', '');
                if ($filename === '') {
                    return $this->error('Parámetro path o filename es requerido', 422);
                }
                $path = $code . '/' . $filename;
            }

            $this->storage->sanitizeCaseCode($code);
            $path = $this->guardPath($code, $path);

            $disk = Storage::disk('cases-docs');

            $tExistsStart = microtime(true);
            $exists = $disk->exists($path);
            $existsMs = (microtime(true) - $tExistsStart) * 1000;

            if ($existsMs > 1000) {
                $log->warning('documents.exists.slow', [
                    'case_code' => $code,
                    'path' => $path,
                    'exists_ms' => round($existsMs, 2),
                    'timestamp' => date('c'),
                ]);
            }

            if (!$exists) {
                return $this->error('Archivo no existe', 404);
            }

            $size = $disk->size($path);
            $disposition = $request->boolean('download') ? 'attachment' : 'inline';
            $name = basename($path);

            // TTFB del lado app: validación + exists() + size() + setup de response.
            // No incluye tiempo de stream físico (lo maneja LiteSpeed).
            $ttfbMs = (microtime(true) - $tStart) * 1000;

            $stream = $disk->readStream($path);

            $response = response()->stream(function () use ($stream, $tStart, $ttfbMs, $code, $path, $size, $log) {
                // Primer byte físico emitido por PHP (cuando Laravel invoca este callback).
                $firstByteMs = (microtime(true) - $tStart) * 1000;

                while (!feof($stream)) {
                    echo fread($stream, 8192);
                }
                fclose($stream);

                $totalMs = (microtime(true) - $tStart) * 1000;

                if ($ttfbMs > 2000) {
                    $log->warning('documents.download.slow_ttfb', [
                        'case_code' => $code,
                        'path' => $path,
                        'ttfb_ms' => round($ttfbMs, 2),
                        'first_byte_ms' => round($firstByteMs, 2),
                        'total_ms' => round($totalMs, 2),
                        'size_bytes' => $size,
                        'timestamp' => date('c'),
                    ]);
                }
            }, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => $disposition . '; filename="' . $name . '"',
                'Cache-Control' => 'no-cache, must-revalidate',
                'Content-Length' => (string) $size,
                'X-TTFB-ms' => (string) round($ttfbMs, 2),
                'X-Document-Case' => $code,
            ]);

            return $response;
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\Throwable $e) {
            return $this->error('Error interno al descargar documento', 500);
        }
    }

    /**
     * Sube un nuevo documento
     */
    public function store(Request $request, string $code)
    {
        try {
            $this->storage->sanitizeCaseCode($code);

            $request->validate([
                'file' => 'required|file|max:20480|mimes:pdf,doc,docx,jpg,jpeg,png,xls,xlsx',
                'filename' => 'sometimes|string|max:255',
                'type' => 'required|in:CONTRATO,MANDATO,DOC'
            ]);

            $file = $request->file('file');
            $type = strtoupper($request->string('type')->toString());
            $filename = $request->string('filename')->toString() ?: $file->getClientOriginalName();

            $filename = $this->sanitizeDocumentFilename($filename);

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
     * Sube un nuevo documento desde base64
     */
    public function storeBase64(Request $request, string $code)
    {
        try {
            $this->storage->sanitizeCaseCode($code);

            $request->validate([
                'base64' => 'required|string',
                'filename' => 'required|string|max:255',
                'type' => 'required|in:CONTRATO,MANDATO,DOC'
            ]);

            $base64 = $request->string('base64')->toString();
            $type = strtoupper($request->string('type')->toString());
            $filename = $this->sanitizeDocumentFilename($request->string('filename')->toString());

            // Decodificar base64
            $content = base64_decode($base64);
            if ($content === false) {
                return $this->error('Base64 inválido', 422);
            }

            // Convertir a PDF usando Imagick si está disponible, sino asumir que ya es PDF
            if (class_exists('Imagick')) {
                $imagick = new Imagick();
                $imagick->readImageBlob($content);
                $imagick->setImageFormat('pdf');
                $pdfContent = $imagick->getImagesBlob();
            } else {
                $pdfContent = $content;
            }

            // Renombrar con prefijo
            $filename = $type . '_' . $filename . '.pdf';

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
            $this->storage->put($code, $relativePath, $pdfContent);

            return $this->success(['filename' => $filename], 'Documento subido correctamente', 201);
        } catch (ValidationException $e) {
            return $this->error('Datos inválidos: ' . $e->getMessage(), 422);
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\Throwable $e) {
            return $this->error('Error interno al subir documento: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Elimina un documento
     */
    public function destroy(Request $request, string $code)
    {
        try {
            $this->storage->sanitizeCaseCode($code);

            $path = (string) $request->query('path', '');
            if ($path === '') {
                // retrocompatibilidad con ?filename=
                $filename = (string) $request->query('filename', '');
                if ($filename === '') {
                    return $this->error('Parámetro path o filename es requerido', 422);
                }
                $path = $code . '/' . $filename;
            }

            $path = $this->guardPath($code, $path);

            if (!Storage::disk('cases-docs')->exists($path)) {
                return $this->error('Documento no encontrado', 404);
            }

            $deleted = $this->storage->delete($code, $path);

            if (!$deleted) {
                return $this->error('Error al eliminar documento', 500);
            }

            return $this->success(null, 'Documento eliminado exitosamente', 200);
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\Throwable $e) {
            return $this->error('Error interno al eliminar documento', 500);
        }
    }

    public function checkSignature(string $code, Request $request)
    {
        $path = (string) $request->input('path');
        $this->storage->sanitizeCaseCode($code);
        $this->guardPath($code, $path);

        if (!Storage::disk('cases-docs')->exists($path)) {
            return response()->json(['message' => 'Archivo no existe'], 404);
        }

        $stream = Storage::disk('cases-docs')->readStream($path);
        $buf = '';
        while (!feof($stream) && strlen($buf) < 1024 * 1024) {
            $buf .= fread($stream, 8192);
        }
        fclose($stream);

        $markers = [
            'has_Type_Sig' => (bool) preg_match('/\/Type\s*\/Sig/i', $buf),
            'has_ByteRange' => (bool) preg_match('/\/ByteRange\s*\[/i', $buf),
            'has_ACEPTA' => stripos($buf, 'ACEPTA') !== false,
        ];

        return response()->json([
            'likely_signed' => in_array(true, $markers, true),
            'markers' => $markers,
            'size' => Storage::disk('cases-docs')->size($path),
            'filename' => basename($path),
        ]);
    }

    public function preview(string $code, Request $request)
    {
        try {
            $path = (string) $request->query('path', '');
            if ($path === '') {
                return $this->error('Parámetro path es requerido', 422);
            }

            $this->storage->sanitizeCaseCode($code);
            $path = $this->guardPath($code, $path);

            if (!Storage::disk('cases-docs')->exists($path)) {
                return $this->error('Archivo no existe', 404);
            }

            $certHint = (string) $request->query('cert_path', '');
            $certPath = $certHint ? $this->guardPath($code, $certHint) : null;

            if (!$certPath) {
                $certPath = $this->findCertificateFor($path);
            }

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

    private function guardPath(string $case, string $rel): string
    {
        $rel = str_replace('\\', '/', ltrim((string) $rel, '/'));

        if (!str_starts_with($rel, $case . '/')) {
            abort(422, 'Ruta fuera del caso');
        }

        if (str_contains($rel, '..')) {
            abort(422, 'Ruta inválida');
        }

        return $rel;
    }

    private function findCertificateFor(string $docPath): ?string
    {
        $disk = Storage::disk('cases-docs');
        $dir = trim(str_replace('\\', '/', dirname($docPath)), '/');
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

        foreach ($candidates as $c) {
            if ($disk->exists($c)) {
                return $c;
            }
        }

        foreach ($disk->files($dir) as $f) {
            if (preg_match('/(acepta|comprobante|certificad)/i', $f)) {
                return $f;
            }
        }

        return null;
    }

    private function sanitizeDocumentFilename(string $filename): string
    {
        $filename = trim($filename);
        $filename = str_replace('\\', '/', $filename);
        $filename = basename($filename);

        $info = pathinfo($filename);
        $name = $info['filename'] ?? '';
        $ext = $info['extension'] ?? '';

        $name = preg_replace('/\s+/', ' ', $name);
        $name = preg_replace('/\.+/', '.', $name);
        $name = trim($name, " ._\t\n\r\0\x0B");
        $ext = trim($ext, " ._\t\n\r\0\x0B");

        if ($name === '') {
            $name = 'documento';
        }

        if ($ext !== '') {
            return $name . '.' . $ext;
        }

        return $name;
    }

    public function checkMandatoContrato(string $code)
    {
        try {
            $list = $this->storage->list($code, true, false);

            $hasMandato  = false;
            $hasContrato = false;

            foreach ($list as $doc) {
                $type = $doc['type'] ?? 'OTRO';
                if ($type === 'MANDATO')  $hasMandato  = true;
                if ($type === 'CONTRATO') $hasContrato = true;
            }

            return $this->success([
                'mandato'  => $hasMandato,
                'contrato' => $hasContrato,
            ], 'Verificacion de documentos completada', 200);
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\Throwable $e) {
            return $this->error('Error interno al verificar documentos', 500);
        }
    }
}
