<?php

namespace Database\Seeders;

use App\Models\Priority;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = config('tsts.default_settings');

        foreach ($defaults as $key => $config) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => is_array($config['value']) ? json_encode($config['value']) : $config['value'], 'type' => $config['type']]
            );
        }

        $this->setDefaultPriority();
    }

    /**
     * Point default_priority_id at the "normal" priority.
     */
    protected function setDefaultPriority(): void
    {
        $normal = Priority::byKey('normal')->first();

        if ($normal !== null) {
            Setting::updateOrCreate(
                ['key' => 'default_priority_id'],
                ['value' => $normal->id, 'type' => 'integer']
            );
        }
    }
}
