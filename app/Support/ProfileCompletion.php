<?php

namespace App\Support;

use App\Models\JornadaLaboral;
use App\Models\User;

/**
 * Decide si un usuario debe completar su perfil antes de usar el sistema. Lo exige
 * VerificarPermisoDeCompletarPerfil en cada petición web.
 *
 * Hay dos motivos:
 * - la bandera de incorporación (perfil.editar) que recibe una cuenta nueva al entrar con
 *   Microsoft (NewUserOnboardingService);
 * - que falte algún dato que el formulario de perfil pide. Así también quedan obligados los
 *   usuarios creados por un administrador, importados o anteriores a la bandera.
 */
final class ProfileCompletion
{
    /**
     * perfil.editar is the current onboarding flag. The second permission keeps
     * compatibility with users provisioned by the original Microsoft login.
     */
    public const PERMISSIONS = [
        'perfil.editar',
        'cambiar-datos-personales',
    ];

    /** Datos del empleado que exige EditPerfilDocente al completar el perfil, con su etiqueta. */
    public const CAMPOS_EMPLEADO = [
        'numero_empleado' => 'número de empleado',
        'celular' => 'celular',
        'sexo' => 'sexo',
        'categoria_id' => 'categoría',
        'centro_facultad_id' => 'facultad o centro',
        'departamento_academico_id' => 'unidad académica',
    ];

    /** Datos del estudiante que exige EditPerfilEstudiante, con su etiqueta. */
    public const CAMPOS_ESTUDIANTE = [
        'nombre' => 'nombres',
        'apellido' => 'apellidos',
        'sexo' => 'sexo',
        'centro_facultad_id' => 'facultad o centro',
        'carrera_id' => 'carrera',
        'cuenta' => 'número de cuenta',
    ];

    public static function isRequired(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return self::hasPendingFlag($user) || self::missingFields($user) !== [];
    }

    /** Cuenta nueva que todavía no termina su incorporación. */
    public static function hasPendingFlag(User $user): bool
    {
        return $user->permissions()
            ->whereIn('permissions.name', self::PERMISSIONS)
            ->exists();
    }

    /**
     * Etiquetas de lo que le falta al perfil. Un usuario sin empleado ni estudiante no tiene
     * perfil que completar todavía: EditPerfil le crea el de empleado.
     *
     * @return list<string>
     */
    public static function missingFields(User $user): array
    {
        if ($empleado = $user->empleado) {
            $faltan = self::vacios($empleado, self::CAMPOS_EMPLEADO);

            // Solo se exige si el catálogo tiene jornadas activas; sin ellas no habría qué elegir.
            if (blank($empleado->jornada_laboral) && JornadaLaboral::query()->where('activo', true)->exists()) {
                $faltan[] = 'jornada laboral';
            }

            if (! $empleado->firma()->exists()) {
                $faltan[] = 'firma';
            }

            return $faltan;
        }

        if ($estudiante = $user->estudiante) {
            return self::vacios($estudiante, self::CAMPOS_ESTUDIANTE);
        }

        return ['perfil de empleado o estudiante'];
    }

    public static function clear(User $user): void
    {
        $permissions = $user->permissions()
            ->whereIn('permissions.name', self::PERMISSIONS)
            ->get();

        foreach ($permissions as $permission) {
            $user->revokePermissionTo($permission);
        }
    }

    /** @return list<string> */
    private static function vacios(object $modelo, array $campos): array
    {
        $faltan = [];

        foreach ($campos as $campo => $etiqueta) {
            if (blank($modelo->{$campo})) {
                $faltan[] = $etiqueta;
            }
        }

        return $faltan;
    }
}
