<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    protected const DEPARTMENTS = [
        ['Administration', 'ADM'],
        ['Finance', 'FIN'],
        ['Human Resources', 'HR'],
        ['Academic Affairs', 'ACAD'],
        ['Registrar', 'REG'],
        ['Library', 'LIB'],
        ['Faculty', 'FAC'],
        ['Student Services', 'SS'],
        ['ICT / Technical Support', 'ICT'],
    ];

    public function run(): void
    {
        foreach (self::DEPARTMENTS as [$name, $code]) {
            Department::updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'is_active' => true]
            );
        }
    }
}
