<?php
declare(strict_types=1);

/**
 * Validación básica de formularios con mensajes en español.
 */
class Validator
{
    private static array $labels = [
        'nombre' => 'nombre',
        'email' => 'correo electrónico',
        'password' => 'contraseña',
        'password_confirm' => 'confirmación de contraseña',
        'telefono' => 'teléfono',
        'codigo' => 'código',
        'codigo_qr' => 'código QR',
        'num_serie' => 'número de serie',
        'modelo' => 'modelo',
        'direccion' => 'dirección',
        'latitud' => 'latitud',
        'longitud' => 'longitud',
        'capacidad' => 'capacidad',
        'energia_disp' => 'energía disponible',
        'nivel_bateria' => 'nivel de batería',
        'kilometraje' => 'kilometraje',
        'estacion_act_fk' => 'estación',
        'rol_id' => 'rol',
        'estado' => 'estado',
        'tipo' => 'tipo',
        'alias' => 'alias',
        'ultimos_digitos' => 'últimos 4 dígitos',
        'id_bicicleta' => 'bicicleta',
        'estacion_dest_fk' => 'estación de destino',
        'id_metodo_pago' => 'método de pago',
        'tipo_manten' => 'tipo de mantenimiento',
        'tecnico' => 'técnico',
        'fecha_inicio' => 'fecha de inicio',
        'fecha_fin' => 'fecha de fin',
        'costo' => 'costo',
        'tipo_operac' => 'tipo de operación',
        'cantidad_kwh' => 'cantidad de kWh',
        'costo_kwh' => 'costo por kWh',
        'descripcion' => 'descripción',
        'id_estacion_fk' => 'estación',
        'monto' => 'monto',
    ];

    public static function validate(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $ruleString) {
            $value = trim((string)($data[$field] ?? ''));
            $label = self::$labels[$field] ?? str_replace('_', ' ', $field);

            foreach (explode('|', $ruleString) as $rule) {
                [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);
                $valueForCheck = is_string($value) ? $value : '';

                $message = match ($name) {
                    'required' => $valueForCheck === '' ? "El campo {$label} es obligatorio." : null,
                    'email' => $valueForCheck !== '' && !filter_var($valueForCheck, FILTER_VALIDATE_EMAIL)
                        ? "El {$label} no tiene un formato válido." : null,
                    'min' => $valueForCheck !== '' && mb_strlen($valueForCheck) < (int)$param
                        ? "El campo {$label} debe tener al menos {$param} caracteres." : null,
                    'max' => $valueForCheck !== '' && mb_strlen($valueForCheck) > (int)$param
                        ? "El campo {$label} no debe superar {$param} caracteres." : null,
                    'numeric' => $valueForCheck !== '' && !is_numeric($valueForCheck)
                        ? "El campo {$label} debe ser numérico." : null,
                    'int' => $valueForCheck !== '' && filter_var($valueForCheck, FILTER_VALIDATE_INT) === false
                        ? "El campo {$label} debe ser un número entero." : null,
                    'date' => $valueForCheck !== '' && strtotime($valueForCheck) === false
                        ? "El campo {$label} no es una fecha válida." : null,
                    'in' => $valueForCheck !== '' && !in_array($valueForCheck, explode(',', (string)$param), true)
                        ? "El valor de {$label} no es válido." : null,
                    default => null,
                };

                if ($message !== null) {
                    $errors[$field] = $message;
                    break;
                }
            }
        }

        return $errors;
    }
}
