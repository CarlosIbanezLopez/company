<!DOCTYPE html>
<html lang="es">
<body style="font-family: Arial, sans-serif; color: #111;">
    <h2 style="margin:0 0 12px">Nueva consulta desde el sitio web</h2>
    <table cellpadding="6" style="border-collapse: collapse; border: 1px solid #ccc;">
        <tr><td><strong>Nombre</strong></td><td>{{ $consulta->nombre }}</td></tr>
        <tr><td><strong>Correo</strong></td><td>{{ $consulta->correo }}</td></tr>
        <tr><td><strong>Teléfono</strong></td><td>{{ $consulta->telefono ?? '—' }}</td></tr>
        <tr><td><strong>Categoría</strong></td><td>{{ $consulta->categoria?->nombre ?? 'General' }}</td></tr>
        <tr><td><strong>Fecha</strong></td><td>{{ $consulta->created_at?->format('d/m/Y H:i') }}</td></tr>
    </table>
    <p style="white-space: pre-line; border-left: 4px solid #1e3a8a; padding-left: 12px;">{{ $consulta->mensaje }}</p>
    @isset($enlacePanel)
        <p>
            <a href="{{ $enlacePanel }}" style="display: inline-block; background: #1e3a8a; color: #fff; padding: 10px 16px; text-decoration: none;">Ver la consulta en el panel de administración</a>
        </p>
    @endisset
    <p style="color:#666; font-size: 12px;">Puede responder directamente a este correo. La consulta también quedó registrada en el panel de administración.</p>
</body>
</html>
