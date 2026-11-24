<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><title>Confirmación de cita</title></head>
<body>
    <h1>Tu cita de donación está confirmada</h1>
    <p>Hola {{ $cita->donante->usuario->nombre }}, gracias por ser parte de DonaVida.</p>
    <p>Cita #{{ $cita->id }} en {{ ($cita->solicitud ?? $cita->campana)->hospital->razon_social }}.</p>
    <p>Horario: {{ $cita->fecha_hora->copy()->setTimezone('America/La_Paz')->format('d/m/Y H:i') }} (hora de Bolivia, UTC−04:00).</p>
    <p>Lugar: {{ $cita->campana?->lugar ?? $cita->solicitud->hospital->direccion }}.</p>
</body>
</html>
