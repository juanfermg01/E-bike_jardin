<?php
declare(strict_types=1);

class HomeController
{
    public function index(): void
    {
        $db = Database::getInstance();
        $stats = [
            'bicis' => (int)$db->scalar("SELECT COUNT(*) FROM bicicletas WHERE estado = 'disponible'"),
            'estaciones' => (int)$db->scalar("SELECT COUNT(*) FROM estaciones WHERE estado = 'operativa'"),
            'alquileres' => (int)$db->scalar("SELECT COUNT(*) FROM alquileres WHERE estado = 'completado'"),
            'usuarios' => (int)$db->scalar("SELECT COUNT(*) FROM usuarios WHERE estado = 'activo'"),
        ];

        view('home', [
            'pageTitle' => 'Bienvenido',
            'stats' => $stats,
            'assets' => ['leaflet' => true, 'map' => true],
        ], 'layouts/guest');
    }
}
