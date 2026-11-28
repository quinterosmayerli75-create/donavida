<?php

namespace App\Support;

use App\Models\Donante;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Módulo 1 (Mayerli): reglas de validación compartidas por el registro y por el administrador,
 * para que los datos de donantes y hospitales se validen igual en todos lados.
 */
class Reglas
{
    // Solo letras (con tildes y ñ), espacios, apóstrofo, punto y guion
    public const LETRAS = 'regex:/^[\pL\s\'.-]+$/u';

    // Solo dígitos
    public const DIGITOS = 'regex:/^[0-9]+$/';

    // Mínimo 8 caracteres con minúscula, mayúscula, número y símbolo
    public const CLAVE_SEGURA = 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/';

    public static function mensajes(): array
    {
        return [
            'nombre.regex' => 'El nombre solo puede tener letras.',
            'apellidos.regex' => 'Los apellidos solo pueden tener letras.',
            'telefono.regex' => 'El teléfono solo puede tener números.',
            'telefono.between' => 'El teléfono debe tener entre 7 y 15 números.',
            'telefono_contacto.regex' => 'El teléfono del hospital solo puede tener números.',
            'whatsapp.regex' => 'El WhatsApp solo puede tener números.',
            'telefono_contacto.between' => 'El teléfono del hospital debe tener entre 7 y 15 números.',
            'whatsapp.between' => 'El WhatsApp debe tener entre 7 y 15 números.',
            'ci.between' => 'El carnet de identidad debe tener entre 5 y 12 números.',
            'nit.between' => 'El NIT debe tener entre 5 y 20 números.',
            'ci.regex' => 'El carnet de identidad solo puede tener números.',
            'nit.regex' => 'El NIT solo puede tener números.',
            'password.regex' => 'La contraseña debe tener al menos 8 caracteres, una mayúscula, una minúscula, un número y un símbolo.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'email.unique' => 'Ese correo ya está registrado.',
            'ci.unique' => 'Ese carnet ya está registrado.',
            'nit.unique' => 'Ese NIT ya está registrado.',
            'fecha_nacimiento.before_or_equal' => 'Debes tener al menos 18 años para registrarte como donante.',
            'fecha_nacimiento.after_or_equal' => 'Solo pueden donar personas de hasta 60 años.',
            'fecha_ultima_donacion.before_or_equal' => 'La fecha de tu última donación no puede ser futura.',
            'foto.image' => 'La foto debe ser una imagen (JPG o PNG).',
            'foto.max' => 'La foto no puede pesar más de 2 MB.',
            'carnet_donante.mimes' => 'El carnet de donante debe ser una imagen o un PDF.',
            'carnet_donante.max' => 'El carnet de donante no puede pesar más de 4 MB.',
        ];
    }

    public static function password(bool $obligatoria = true): array
    {
        return [$obligatoria ? 'required' : 'nullable', 'string', 'max:72', self::CLAVE_SEGURA, 'confirmed'];
    }

    public static function telefono(bool $obligatorio = true): array
    {
        return [$obligatorio ? 'required' : 'nullable', 'string', self::DIGITOS, 'between:7,15'];
    }

    /**
     * Datos del donante. $usuarioId y $donanteId se usan al editar para ignorar su propio correo y carnet.
     */
    public static function donante(?int $usuarioId = null, ?int $donanteId = null): array
    {
        // Edad permitida para donar: 18 a 60 años (Ley 1687, art. 19)
        $fechaMaximaNacimiento = Carbon::today()->subYears(Donante::EDAD_MINIMA)->toDateString();
        $fechaMinimaNacimiento = Carbon::today()->subYears(Donante::EDAD_MAXIMA + 1)->addDay()->toDateString();

        return [
            'nombre' => ['required', 'string', 'max:100', self::LETRAS],
            'apellidos' => ['required', 'string', 'max:100', self::LETRAS],
            'email' => ['required', 'email', 'max:150', Rule::unique('usuarios', 'email')->ignore($usuarioId)],
            'telefono' => self::telefono(),
            'ci' => ['required', 'string', 'between:5,12', self::DIGITOS, Rule::unique('donantes', 'ci')->ignore($donanteId)],
            'fecha_nacimiento' => ['required', 'date', 'before_or_equal:'.$fechaMaximaNacimiento, 'after_or_equal:'.$fechaMinimaNacimiento],
            'sexo' => ['required', 'in:F,M'],
            'tipo_sangre_id' => ['required', 'integer', 'exists:tipos_sangre,id'],
            'peso_kg' => ['nullable', 'numeric', 'min:30', 'max:250'],
            'direccion' => ['nullable', 'string', 'max:200'],
            'zona' => ['nullable', 'string', 'max:100'],
            'latitud' => ['nullable', 'numeric', 'between:-90,90'],
            'longitud' => ['nullable', 'numeric', 'between:-180,180'],
            'fecha_ultima_donacion' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }

    /**
     * Datos del hospital y de su responsable.
     */
    public static function hospital(?int $usuarioId = null, ?int $hospitalId = null): array
    {
        return [
            'nombre' => ['required', 'string', 'max:100', self::LETRAS],
            'email' => ['required', 'email', 'max:150', Rule::unique('usuarios', 'email')->ignore($usuarioId)],
            'telefono' => self::telefono(),
            'razon_social' => ['required', 'string', 'max:150'],
            'nit' => ['required', 'string', 'between:5,20', self::DIGITOS, Rule::unique('hospitales', 'nit')->ignore($hospitalId)],
            'direccion' => ['required', 'string', 'max:200'],
            'zona' => ['nullable', 'string', 'max:100'],
            'latitud' => ['required', 'numeric', 'between:-90,90'],
            'longitud' => ['required', 'numeric', 'between:-180,180'],
            'telefono_contacto' => ['required', 'string', self::DIGITOS, 'between:7,15'],
            'whatsapp' => ['nullable', 'string', self::DIGITOS, 'between:7,15'],
        ];
    }
}
