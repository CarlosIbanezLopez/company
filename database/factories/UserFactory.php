<?php

namespace Database\Factories;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Contraseña (ya cifrada) que comparten los usuarios generados.
     */
    protected static ?string $password;

    /**
     * Estado por defecto: cuenta activa, sin rol y con el correo verificado.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'telefono' => null,
            'activo' => true,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Correo sin verificar.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Cuenta deshabilitada por el administrador.
     */
    public function inactivo(): static
    {
        return $this->state(fn (array $attributes) => [
            'activo' => false,
        ]);
    }

    /**
     * Asigna un rol existente por su nombre (p. ej. "Responsable de Noticias").
     */
    public function conRol(string $rol): static
    {
        return $this->state(fn (array $attributes) => [
            'rol_id' => Rol::query()->where('nombre', $rol)->value('id'),
        ]);
    }
}
