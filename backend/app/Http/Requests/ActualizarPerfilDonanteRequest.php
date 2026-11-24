<?php

namespace App\Http\Requests;

use App\Models\Donante;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Módulo 2: Donantes (Maya)
 * Reglas para PUT /api/donante/perfil
 *
 * Todos los campos son "sometimes": el donante puede enviar solo lo que quiere cambiar
 * (por ejemplo, solo la ubicación o solo la disponibilidad).
 * El CI no se puede cambiar: es su documento de identidad.
 */
class ActualizarPerfilDonanteRequest extends FormRequest
{
    public function authorize(): bool
    {
        // El permiso ya lo controla el middleware rol:donante en la ruta.
        return true;
    }

    public function rules(): array
    {
        // Misma regla de edad que en el registro: 18 a 60 años (Ley 1687, art. 19)
        $fechaMaximaNacimiento = Carbon::today()->subYears(Donante::EDAD_MINIMA)->toDateString();
        $fechaMinimaNacimiento = Carbon::today()->subYears(Donante::EDAD_MAXIMA + 1)->addDay()->toDateString();

        return [
            // Datos de la tabla usuarios
            'nombre' => ['sometimes', 'required', 'string', 'max:100'],
            'telefono' => ['sometimes', 'required', 'string', 'max:20'],

            // Datos de la tabla donantes
            'apellidos' => ['sometimes', 'required', 'string', 'max:100'],
            'tipo_sangre_id' => ['sometimes', 'required', 'integer', 'exists:tipos_sangre,id'],
            'fecha_nacimiento' => [
                'sometimes',
                'required',
                'date',
                'before_or_equal:' . $fechaMaximaNacimiento,
                'after_or_equal:' . $fechaMinimaNacimiento,
            ],
            'sexo' => ['sometimes', 'required', 'in:F,M'],
            'peso_kg' => ['sometimes', 'nullable', 'numeric', 'min:30', 'max:250'],
            'direccion' => ['sometimes', 'nullable', 'string', 'max:200'],
            'zona' => ['sometimes', 'nullable', 'string', 'max:100'],
            // La ubicación va en pareja: si envía la latitud, también debe enviar la longitud.
            // (Sin "sometimes", para que required_with se revise aunque falte uno de los dos.)
            'latitud' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitud'],
            'longitud' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitud'],
            'disponible' => ['sometimes', 'required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'fecha_nacimiento.before_or_equal' => 'Debes tener al menos 18 años para ser donante.',
            'fecha_nacimiento.after_or_equal' => 'Solo pueden donar personas de hasta 60 años.',
            'tipo_sangre_id.exists' => 'El tipo de sangre elegido no existe.',
            'latitud.required_with' => 'Falta la latitud de tu ubicación.',
            'longitud.required_with' => 'Falta la longitud de tu ubicación.',
        ];
    }
}
