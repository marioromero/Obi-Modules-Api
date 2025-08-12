<?php

namespace Modules\Customers\app\Http\Controllers;
use Modules\Core\app\Http\BaseApiController;
use Modules\Customers\app\Http\Requests\UpdateCustomerRequest;
use Modules\Customers\app\Resources\CustomerResource;
use Illuminate\Http\Request;
use Modules\Customers\app\Http\Requests\StoreCustomerRequest;
use Modules\Customers\Models\Customer;
use App\Http\Controllers\Controller;
use Modules\Customers\Models\CustomerDetail;

class CustomerController extends BaseApiController
{
 
    public function index()
    {
        // 1) Trae todos los registros y todos sus atributos visibles
        $customers = Customer::all();

        // 2) Devuelve la data sin pasarla por el Resource
        return $this->success($customers, 'Listado de clientes');
    }
    public function show(int $id)
    {
        $customer = CustomerDetail::findOrFail($id);    // ← lee la vista

        return $this->success(
            $customer,
            'Cliente obtenido correctamente'
        );
    }

    public function store(StoreCustomerRequest $request)   // ← Form Request
    {
        // El FormRequest ya hizo la validación y devuelve solo campos permitidos
        $customer = Customer::create($request->validated());

        return $this->success($customer, 'Cliente creado correctamente', 201);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        // `validated()` trae sólo los campos realmente enviados
        $customer->fill($request->validated())->save();

        return $this->success($customer, 'Cliente actualizado correctamente');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        return $this->success(null,'Cliente eliminado exitosamente',200);
    }

    public function search(Request $request)
    {
        $dni   = $request->query('dni');
        $email = $request->query('email');

        $customer = Customer::when($dni,   fn($q) => $q->where('dni',   $dni))
                            ->when($email, fn($q) => $q->orWhere('email', $email))
                            ->first();

        return $customer
            ? $this->success($customer, 'Cliente encontrado')
            : $this->success(null, 'No existe', 204);
    }

    public function customersByName(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:1|max:100',
        ]);

        // Normalizamos a minúsculas el término
        $term = mb_strtolower($request->query('q'), 'UTF-8');

        $results = Customer::query()
            ->select(['id'])
            ->selectRaw("CONCAT(name, ' ', lastname) AS text")
            ->whereRaw(
                "LOWER(CONCAT(name, ' ', lastname)) LIKE ?",
                ["%{$term}%"]
            )
            ->orderBy('name')
            ->limit(15)
            ->get();

        return $this->success($results, 'Clientes encontrados');
    }

    public function customersByDni(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:1|max:20',
        ]);

        // 1) Le quitamos los puntos y espacios al término
        $raw  = $request->query('q');
        $term = str_replace(['.', ' '], '', trim($raw));
        // ahora "20.008.648-1" → "20008648-1"

        // 2) Buscamos en la BD (que almacena e.g. "20008648-1")
        $results = Customer::query()
            ->select(['id'])
            ->selectRaw("CONCAT(dni, ' – ', name, ' ', lastname) AS text")
            ->where('dni', 'LIKE', "%{$term}%")
            ->orderBy('dni')
            ->limit(15)
            ->get();

        return $this->success($results, 'Clientes encontrados por DNI');
    }

    public function getTagsByDniUser(Request $request)
    {
        /* 1) Validar */
        $request->validate([
            'dni' => 'required|string',
        ]);

        // Normalizar DNI (sin puntos ni espacios)
        $dni = preg_replace('/[.\s]/', '', (string) $request->query('dni'));

        /* 2) Traer solo lo necesario */
        $customer = Customer::query()
            ->select(['tags', 'comments'])
            ->where('dni', $dni)
            ->first();

        if (! $customer) {
            return $this->success(null, 'No existe', 204);
        }

        // Helper: si parece JSON ({ o [) decodifica; si no, deja el string
        $maybeJson = function ($value) {
            if (!is_string($value)) return $value;
            $t = trim($value);
            if ($t === '' || strtolower($t) === 'null') return null;
            $first = $t[0] ?? '';
            if ($first !== '{' && $first !== '[') {
                return $value; // texto plano
            }
            $decoded = json_decode($t, true);
            return (json_last_error() === JSON_ERROR_NONE) ? $decoded : $value;
        };

        $tags     = $maybeJson($customer->tags);      // si tags es JSON, array; si es texto, string
        $comments = $maybeJson($customer->comments);  // LONGTEXT: te lo devuelve como string

        return $this->success(
            ['tags' => $tags, 'comments' => $comments],
            'Tags y comentarios obtenidos'
        );
    }

    public function findByDni(string $dni)
        {
            // Normaliza: quita puntos y espacios para comparar con lo almacenado
            $dni = str_replace(['.', ' '], '', trim($dni));

            $customer = Customer::where('dni', $dni)->first();

            return $customer
                ? $this->success($customer, 'Cliente encontrado')
                : $this->success(null, 'No existe', 204);
        }

    public function verifyExistingCustomer(Request $request)
    {
        $raw = (string) ($request->query('dni') ?? $request->input('dni') ?? '');
        $dni = str_replace(['.', ' '], '', trim($raw));
        if ($dni !== '' && str_contains($dni, '-')) {
            [$num, $dv] = explode('-', $dni, 2);
            $dni = preg_replace('/\D/', '', $num) . '-' . strtoupper($dv);
        }

        $customer = $dni === '' ? null : Customer::query()->where('dni', $dni)->first();

        if ($customer) {
            $fullName = trim(($customer->name ?? '') . ' ' . ($customer->lastname ?? ''));
            return $this->success(1, "El RUT del cliente ya se encuentra registrado a nombre de: {$fullName}.", 200);
        }

        return $this->success(0, '', 200);
    }

    public function showCustomerByDni(Request $request)
    {
        $request->validate([
            'dni' => 'required|string|min:2|max:20',
        ]);

        // Normalizar: sin puntos/espacios, DV en minúscula y con guion
        $normalize = function (string $rut): ?string {
            $rut = str_replace(['.', ' '], '', trim($rut));

            if ($rut === '') {
                return null;
            }

            if (str_contains($rut, '-')) {
                [$num, $dv] = explode('-', $rut, 2);
            } else {
                // si viene sin guion, último char es el DV
                $num = substr($rut, 0, -1);
                $dv  = substr($rut, -1);
            }

            $num = preg_replace('/\D+/', '', $num ?? '');
            $dv  = strtolower($dv ?? '');

            if ($num === '' || $dv === '') {
                return null;
            }

            return $num . '-' . $dv;
        };

        $normalized = $normalize($request->query('dni'));
        if (!$normalized) {
            return $this->success(null, 'DNI inválido', 422); // conserva tu helper/contrato
        }

        // Búsqueda EXACTA por DNI normalizado
        $customer = Customer::query()
            ->where('dni', $normalized)
            ->first();

        if (!$customer) {
            return $this->success(null, 'No existe', 204);
        }

        // Devuelve el objeto completo (según atributos visibles del modelo)
        return $this->success($customer, 'Cliente encontrado', 200);
    }

}

