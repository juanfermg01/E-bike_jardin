<?php
declare(strict_types=1);

class MapController
{
    public function index(): void
    {
        $db = Database::getInstance();
        $stations = $db->fetchAll('SELECT * FROM v_estaciones_mapa ORDER BY nombre');
        $totals = $db->fetch(
            "SELECT COUNT(*) estaciones,
                    COALESCE(SUM(bicis_disponibles),0) disponibles,
                    COALESCE(SUM(bicis_totales),0) flota
               FROM v_estaciones_mapa"
        );

        view('map/index', [
            'pageTitle' => 'Mapa y estaciones',
            'stations' => $stations,
            'totals' => $totals,
            'assets' => ['leaflet' => true, 'map' => true],
        ]);
    }

    public function apiEstaciones(): void
    {
        $db = Database::getInstance();
        $rows = $db->fetchAll(
            'SELECT id_estacion, codigo, nombre, direccion, latitud, longitud,
                    capacidad, energia_disp, estado, bicis_disponibles, bicis_totales
               FROM v_estaciones_mapa ORDER BY nombre'
        );

        $data = array_map(static fn(array $r): array => [
            'id' => (int)$r['id_estacion'],
            'codigo' => $r['codigo'],
            'nombre' => $r['nombre'],
            'direccion' => $r['direccion'],
            'latitud' => (float)$r['latitud'],
            'longitud' => (float)$r['longitud'],
            'capacidad' => (int)$r['capacidad'],
            'energia_disponible' => (float)$r['energia_disp'],
            'estado' => $r['estado'],
            'bicis_disponibles' => (int)$r['bicis_disponibles'],
            'bicis_totales' => (int)$r['bicis_totales'],
        ], $rows);

        json_response(['ok' => true, 'data' => $data, 'generado' => date('c')]);
    }

    public function apiElectrolineras(): void
    {
        $file = BASE_PATH . '/public/data/electrolineras.json';
        if (!is_file($file)) {
            json_response(['ok' => false, 'message' => 'No se encontró el archivo de puntos de carga.'], 404);
        }

        $items = json_decode((string)file_get_contents($file), true);
        if (!is_array($items)) {
            json_response(['ok' => false, 'message' => 'El archivo de puntos de carga no es válido.'], 500);
        }

        $items = array_values(array_filter($items, static function ($item) {
            return is_array($item) && isset($item['latitud'], $item['longitud'])
                && is_numeric($item['latitud']) && is_numeric($item['longitud']);
        }));

        json_response(['ok' => true, 'data' => $items, 'generado' => date('c')]);
    }
}
