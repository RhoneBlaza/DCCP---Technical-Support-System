<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentService
{
    /**
     * The exact extension => MIME mapping for the hard-coded allowlist. The
     * applications validates BOTH the extension and the real MIME type.
     */
    public const MIME_MAP = [
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'pdf' => ['application/pdf'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'txt' => ['text/plain'],
    ];

    public const PROFILE_PHOTO_ALLOWED = ['jpg', 'jpeg', 'png'];

    protected const PROFILE_PHOTO_MAX_KB = 2048;

    /**
     * Extensions that may be enabled in settings (subset of the hard allowlist).
     */
    public function allowedExtensions(): array
    {
        return array_keys(self::MIME_MAP);
    }

    /**
     * MIME types allowed for a collection of extensions.
     */
    public function mimeTypesFor(array $extensions): array
    {
        $mimes = [];

        foreach ($extensions as $extension) {
            $extension = strtolower($extension);

            if (isset(self::MIME_MAP[$extension])) {
                array_push($mimes, ...self::MIME_MAP[$extension]);
            }
        }

        return array_values(array_unique($mimes));
    }

    /**
     * Store uploaded files against a ticket (optionally on a message).
     *
     * @return Collection<int, TicketAttachment>
     */
    public function store(Ticket $ticket, array $files, User $uploader, ?TicketMessage $message = null)
    {
        $attachments = collect();

        foreach ($files as $file) {
            $extension = strtolower($file->getClientOriginalExtension());
            $storedName = Str::random(40).'.'.$extension;
            $path = $file->storeAs('attachments/'.$ticket->id, $storedName, 'private');

            $attachments->push(TicketAttachment::create([
                'ticket_id' => $ticket->id,
                'message_id' => $message?->id,
                'uploaded_by' => $uploader->id,
                'original_name' => $this->sanitizeOriginalName($file->getClientOriginalName()),
                'stored_name' => $storedName,
                'disk' => 'private',
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]));
        }

        return $attachments;
    }

    /**
     * Stream a stored attachment through an authorized response.
     */
    public function download(TicketAttachment $attachment, bool $inline = false): StreamedResponse
    {
        $disk = Storage::disk($attachment->disk);

        return $disk->download(
            $attachment->path,
            $attachment->original_name,
            [
                'Content-Type' => $attachment->mime_type,
                'Content-Disposition' => ($inline ? 'inline' : 'attachment').'; filename="'.$attachment->original_name.'"',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    /**
     * Whether inline preview may be offered for this attachment.
     */
    public function canInline(TicketAttachment $attachment): bool
    {
        return in_array($attachment->mime_type, ['image/jpeg', 'image/png'], true);
    }

    /**
     * A requester may only see/download non-internal attachments on their own tickets.
     */
    public function isVisibleTo(TicketAttachment $attachment, User $user): bool
    {
        if ($user->role->isStaff()) {
            return true;
        }

        if ($attachment->ticket->requester_id !== $user->id) {
            return false;
        }

        return ! $attachment->is_internal;
    }

    /**
     * Remove the physical file belonging to an attachment.
     */
    public function deleteFile(TicketAttachment $attachment): void
    {
        Storage::disk($attachment->disk)->delete($attachment->path);
    }

    /**
     * Sanitize an uploaded original filename for safe display/storage in the DB.
     */
    public function sanitizeOriginalName(string $name): string
    {
        $name = basename($name);
        $name = preg_replace('/[\x00-\x1F\x7F\s]+/u', '_', $name);
        $name = Str::limit($name, 200, '');

        return $name !== '' ? $name : 'file';
    }

    /**
     * Validation rules for a single upload group.
     */
    public function validationRules(): array
    {
        $settings = new SettingsService;
        $maxKb = $settings->int('max_attachment_kb', 5120);
        $maxFiles = $settings->int('max_attachments_per_message', 5);
        $extensions = $settings->array('allowed_attachment_extensions', $this->allowedExtensions());
        $extensions = array_intersect($extensions, $this->allowedExtensions());

        return [
            'attachments' => ['nullable', 'array', 'max:'.$maxFiles],
            'attachments.*' => [
                'file',
                'mimes:'.implode(',', $extensions),
                'mimetypes:'.implode(',', $this->mimeTypesFor($extensions)),
                'max:'.$maxKb,
            ],
        ];
    }

    /**
     * Validate that the desired extension list is a subset of the hard allowlist.
     */
    public function validateSettingsExtensions(array $extensions): bool
    {
        return count(array_diff($extensions, $this->allowedExtensions())) === 0;
    }
}
