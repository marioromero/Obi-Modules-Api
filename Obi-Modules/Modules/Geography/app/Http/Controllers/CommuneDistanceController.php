<?php

namespace Modules\Geography\app\Http\Controllers;
use Modules\Core\app\Http\BaseApiController;

use Illuminate\Http\Request;
use Modules\Geography\Models\CommuneDistance;

class CommuneDistanceController extends BaseApiController
{
    /**
     * Obtener la distancia en km entre dos comunas
     *
     * @param int $originId ID de la comuna de origen
     * @param int $destinationId ID de la comuna de destino
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDistance($originId, $destinationId)
    {
        $communeDistance = CommuneDistance::on('geography_db')
            ->where('origin_id', $originId)
            ->where('destination_id', $destinationId)
            ->first();

        if (!$communeDistance) {
            return $this->error('No se encontró la distancia entre las comunas seleccionadas', 404);
        }

        return $this->success([
            'origin_id' => $communeDistance->origin_id,
            'destination_id' => $communeDistance->destination_id,
            'distance_km' => $communeDistance->distance_km,
        ], 'Distancia obtenida correctamente');
    }
}
