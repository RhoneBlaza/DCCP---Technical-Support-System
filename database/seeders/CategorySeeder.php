<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Two-level category tree: parent => children (children are the leaves).
     */
    protected const CATEGORIES = [
        'Hardware' => ['Computer / PC', 'Laptop', 'Printer', 'Scanner', 'Projector', 'Other Hardware'],
        'Software' => ['Operating System', 'Application Installation', 'Software Licensing', 'Office Applications', 'Email / Office 365', 'Other Software'],
        'Network' => ['Internet Connection', 'Wi-Fi Issue', 'LAN / Network Devices', 'VPN Access', 'Other Network'],
        'Account' => ['User Account', 'Password Reset', 'Email Account', 'System Access', 'Other Account'],
        'Website/System' => ['Website Issue', 'Student Portal', 'Learning Management System', 'Online Services', 'Other Website/System'],
        'Other' => ['Other'],
    ];

    public function run(): void
    {
        $sort = 0;

        foreach (self::CATEGORIES as $parentName => $children) {
            $parent = Category::updateOrCreate(
                ['parent_id' => null, 'name' => $parentName],
                ['description' => 'Category group for '.$parentName.' issues.', 'is_active' => true, 'sort_order' => $sort]
            );

            $childSort = 0;
            foreach ($children as $childName) {
                Category::updateOrCreate(
                    ['parent_id' => $parent->id, 'name' => $childName],
                    ['description' => null, 'is_active' => true, 'sort_order' => $childSort]
                );
                $childSort++;
            }

            $sort++;
        }
    }
}
