<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    protected $model = Patient::class;

    public function definition(): array
    {
        $gender = fake()->randomElement(['M', 'F', 'otro']);

        return [
            'tenant_id' => Tenant::factory(),
            'code' => fake()->unique()->bothify('PAC-####'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'birth_date' => fake()->date(),
            'gender' => $gender,
            'dpi' => fake()->optional()->numerify('#############'),
            'nit' => fake()->optional()->numerify('########'),
            'phone' => fake()->optional()->numerify('########'),
            'email' => fake()->optional()->safeEmail(),
            'address' => fake()->optional()->address(),
            'insurance_company' => fake()->optional()->company(),
            'insurance_policy' => fake()->optional()->bothify('POL-#####'),
            'emergency_contact_name' => fake()->optional()->name(),
            'emergency_contact_phone' => fake()->optional()->numerify('########'),
            'blood_type' => fake()->optional()->randomElement(['A+','A-','B+','B-','O+','O-','AB+','AB-']),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}

