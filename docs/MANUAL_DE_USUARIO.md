# Manual de usuario — E-Bike Jardín

Este manual explica cómo usar el sistema según el rol. Todas las acciones destructivas muestran una ventana de confirmación (SweetAlert2); si te equivocas, pulsa **Cancelar**.

## 1. Ingreso al sistema

1. Abre la dirección del proyecto (por ejemplo `http://localhost/ebike-jardin/public/`).
2. Pulsa **Iniciar sesión**.
3. Escribe tu correo y contraseña y pulsa **Entrar**.
4. Si no tienes cuenta, usa **Registrarme**. Las cuentas nuevas se crean con rol Cliente.

Cuentas de demostración:

| Rol | Correo | Contraseña |
|---|---|---|
| Administrador | `admin@ebikejardin.com` | `Admin123*` |
| Operador | `operador@ebikejardin.com` | `Operador123*` |
| Cliente | `cliente@ebikejardin.com` | `Cliente123*` |

Después de 5 intentos fallidos el sistema bloquea el ingreso durante 60 segundos.

## 2. El menú lateral

- **Principal**: Dashboard y Mapa y estaciones.
- **Operación**: Alquileres, Bicicletas, Estaciones y, para staff, Mantenimientos y Energía.
- **Administración**: Pagos; Reportes para staff; Usuarios solo para el administrador.
- Abajo aparecen tu nombre y el botón de **Cerrar sesión**.
- En pantallas pequeñas el menú se abre con el botón ☰.

## 3. Cliente

### 3.1 Panel principal

Muestra tu alquiler activo (si existe) con cronómetro, tus viajes completados, el total pagado, el tiempo en bicicleta, las bicicletas recomendadas, el mapa de estaciones y tus gráficas de viajes y gasto.

### 3.2 Alquilar una bicicleta

1. Pulsa **Alquilar una bicicleta** (o **Alquilar** en una recomendación).
2. Busca por código o modelo y filtra por estación si lo necesitas.
3. Selecciona una bicicleta. A la derecha verás el resumen con estación, tarifa y costo estimado.
4. Pulsa **Iniciar alquiler**.
5. Se abre el recibo con el cronómetro en vivo y el costo acumulado.

> Solo aparecen bicicletas disponibles, con estación asignada y batería suficiente. No puedes tener dos alquileres activos al mismo tiempo.

### 3.3 Finalizar el recorrido

1. En el recibo activo pulsa **Finalizar**.
2. Elige la **estación de destino** (puede ser la misma) y el **método de pago** (o deja “Pago simulado”).
3. Revisa el resumen: tiempo, tarifa, batería consumida, kilometraje y total.
4. Pulsa **Confirmar y pagar** y confirma en la ventana.
5. El recibo queda como **Completado** con el pago aprobado. Puedes usar **Imprimir recibo**.

### 3.4 Mis alquileres

Lista tus recorridos con filtros por estado. Desde **Ver** puedes consultar el detalle y, si sigue activo, finalizarlo.

### 3.5 Métodos de pago

1. Ve a **Métodos de pago → Agregar método**.
2. Elige el tipo, un alias y los últimos 4 dígitos.
3. Guarda. El sistema **no almacena el número completo** de la tarjeta.
4. Para dejar de usarlo, pulsa **Desactivar** (el historial se conserva).

### 3.6 Perfil

En **Mi perfil** puedes actualizar nombre, teléfono y correo, y cambiar la contraseña. La nueva contraseña debe tener al menos 8 caracteres y ser diferente a la actual.

## 4. Operador

Además de las funciones del cliente (puede alquilar para sí mismo), el operador gestiona la operación diaria:

### 4.1 Alquileres

- **Nuevo alquiler**: selecciona el cliente activo, la bicicleta y confirma.
- En la lista puede **Finalizar**, **Ver** o **Cancelar** un alquiler activo.
- Cancelar libera la bicicleta sin cobro y deja el alquiler en estado **Cancelado**.

### 4.2 Bicicletas

- Consulta la flota con filtros por código/modelo/serie, estado y estación.
- **Nueva bicicleta**: código QR, modelo, serie, estación, batería, kilometraje y fechas de mantenimiento.
- **Editar** permite actualizar estado, batería y ubicación.
- La eliminación está reservada al administrador y no se permite si hay alquileres o mantenimientos asociados.

### 4.3 Estaciones

- Consulta ocupación, energía y estado de cada estación.
- **Ver mapa** abre la ubicación.
- Crear, editar y eliminar estaciones es exclusivo del administrador.
- No se puede eliminar una estación con bicicletas asignadas.

### 4.4 Mantenimientos

1. Pulsa **Nuevo mantenimiento**.
2. Elige la bicicleta, el tipo, el técnico, la fecha de inicio y el costo.
3. Si dejas la fecha de fin vacía, la bicicleta queda **En mantenimiento**.
4. Al registrar la fecha de fin, la bicicleta vuelve a **Disponible** y se programa el próximo mantenimiento en 90 días.
5. No se puede registrar mantenimiento a bicicletas dadas de baja o en uso.

### 4.5 Energía

- Registra el consumo por estación: tipo de operación, kWh, costo por kWh, fecha y descripción.
- El **total** se calcula automáticamente.
- Usa los filtros para revisar por estación, tipo o rango de fechas.

### 4.6 Pagos y reportes

- **Pagos**: consulta todas las transacciones y su distribución por método.
- **Reportes**: elige el rango de fechas y revisa ingresos, alquileres, duración promedio, clientes, energía, mantenimiento y margen. Puedes **Imprimir** o **Exportar CSV**.

## 5. Administrador

Tiene todas las funciones del operador y además:

### 5.1 Usuarios

- **Nuevo usuario**: nombre, correo, teléfono, rol, estado y contraseña (mínimo 8 caracteres, con confirmación).
- Puedes filtrar por texto, rol y estado.
- **Editar**: cambia datos, rol, estado o contraseña (si la dejas vacía, se conserva).
- **Eliminar**: no se permite eliminarse a sí mismo ni borrar usuarios con alquileres.
- El administrador no puede cambiarse su propio rol ni desactivarse.

### 5.2 Estaciones

Crear, editar y eliminar estaciones. Al crear una estación se guardan sus coordenadas para el mapa.

### 5.3 Reembolsos

En **Pagos**, cada transacción aprobada muestra la opción de **Reembolsar**. El pago pasa a estado **Reembolsado** y queda registrado para auditoría. No se pueden reembolsar pagos ya reembolsados.

## 6. Mapa

1. Abre **Mapa y estaciones**.
2. Activa o desactiva las capas **Estaciones E-Bike** y **Puntos de carga**.
3. Usa el buscador para encontrar una estación o dirección.
4. Ajusta la **autonomía estimada** para filtrar puntos según tu batería.
5. Pulsa **Mi ubicación** para ver la estación más cercana y calcular la ruta.
6. Toca un marcador para ver el detalle y abrir la ruta.

## 7. Preguntas frecuentes

**¿Por qué no puedo alquilar?**
Puede que ya tengas un alquiler activo, que la bicicleta no esté disponible o que su batería sea menor al 10 %.

**¿Cómo se calcula el cobro?**
Por minuto: $150 COP. Se redondea hacia arriba y se cobra mínimo un minuto. El total aparece antes de confirmar.

**¿Qué pasa con la batería al terminar?**
Se descuenta según el tiempo de uso y el kilometraje aumenta; la bicicleta queda disponible en la estación de destino.

**No veo el menú Usuarios.**
Solo el administrador puede gestionar cuentas.

**¿Puedo recuperar un alquiler cancelado?**
No. Debes iniciar uno nuevo.

**¿Dónde se guardan los errores?**
En `storage/logs/app.log`. Si eres desarrollador, activa `APP_DEBUG=1` para ver el detalle en pantalla.
