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
