<?php

namespace App\Console\Commands;

use App\Models\VerificationRequest;
use App\Services\SettingsService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('purge:expired-id-images')]
#[Description('Delete ID document files older than the id_image_retention_days setting. The ID number and decision record are kept.')]
class PurgeExpiredIdImages extends Command
{
    public function handle(): int
    {
        $days = (new SettingsService)->int('id_image_retention_days', 0);

        if ($days <= 0) {
            $this->info('id_image_retention_days is 0 (keep forever). No ID documents were deleted.');

            return self::SUCCESS;
        }

        $cutoff = now()->subDays($days);

        $requests = VerificationRequest::query()
            ->whereNotNull('id_image_path')
            ->where('id_image_path', '!=', '')
            ->where(function ($q) use ($cutoff) {
                $q->whereNull('reviewed_at')->where('submitted_at', '<', $cutoff)
                    ->orWhereNotNull('reviewed_at')->where('reviewed_at', '<', $cutoff);
            })
            ->get();

        $disk = Storage::disk('private');
        $count = 0;

        foreach ($requests as $request) {
            $disk->delete($request->id_image_path);
            $request->forceFill(['id_image_path' => null])->save();
            $count++;
        }

        $this->info('Deleted {'.$count.'} expired ID document file(s). The ID numbers and decision records were kept.');

        return self::SUCCESS;
    }
}
