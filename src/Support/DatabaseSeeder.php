<?php

declare(strict_types=1);

namespace App\Support;

use App\Domain\ValueObject\UserRole;
use App\Infrastructure\Repository\ProductRepository;
use App\Infrastructure\Repository\UserRepository;
use App\Infrastructure\Repository\VehicleRepository;
use App\Infrastructure\Security\PasswordHasher;

class DatabaseSeeder
{
    public function __construct(
        private UserRepository $users,
        private PasswordHasher $hasher,
        private VehicleRepository $vehicles,
        private ProductRepository $products
    ) {
    }

    public function seed(): void
    {
        $this->seedUsers();
        $this->seedVehicles();
        $this->seedProducts();
    }

    private function seedUsers(): void
    {
        $defaults = [
            ['login' => 'admin', 'name' => 'Super Admin', 'role' => UserRole::ADMIN, 'password' => 'admin123'],
            ['login' => 'manager', 'name' => 'Main Manager', 'role' => UserRole::MANAGER, 'password' => 'password'],
            ['login' => 'courier', 'name' => 'Lead Courier', 'role' => UserRole::COURIER, 'password' => 'password'],
        ];

        foreach ($defaults as $data) {
            if ($this->users->findByLogin($data['login'])) {
                continue;
            }

            $this->users->create([
                'login' => $data['login'],
                'password_hash' => $this->hasher->hash($data['password']),
                'name' => $data['name'],
                'role' => $data['role'],
                'created_at' => date(DATE_ATOM),
            ]);
        }
    }

    private function seedVehicles(): void
    {
        if (!empty($this->vehicles->findAll())) {
            return;
        }

        $this->vehicles->create([
            'brand' => 'Ford Transit',
            'license_plate' => 'A100AA',
            'max_weight' => 1500,
            'max_volume' => 18,
        ]);

        $this->vehicles->create([
            'brand' => 'Mercedes Sprinter',
            'license_plate' => 'B200BB',
            'max_weight' => 1800,
            'max_volume' => 20,
        ]);
    }

    private function seedProducts(): void
    {
        if (!empty($this->products->findAll())) {
            return;
        }

        $this->products->create([
            'name' => 'Электроника',
            'weight' => 1.2,
            'length' => 30,
            'width' => 20,
            'height' => 10,
        ]);

        $this->products->create([
            'name' => 'Бытовая техника',
            'weight' => 8.5,
            'length' => 50,
            'width' => 40,
            'height' => 15,
        ]);
    }
}
