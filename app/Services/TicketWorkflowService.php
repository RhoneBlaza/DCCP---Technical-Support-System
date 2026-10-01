<?php

namespace App\Services;

use App\Enums\ActivityType;
use App\Enums\MessageType;
use App\Enums\TicketStatusType;
use App\Enums\UserRole;
use App\Exceptions\WorkflowException;
use App\Models\Category;
use App\Models\Priority;
use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\TicketMessage;
use App\Models\TicketStatus;
use App\Models\User;
use App\Notifications\InternalNoteNotification;
use App\Notifications\NewTicketRequesterNotification;
use App\Notifications\NewTicketSupportNotification;
use App\Notifications\RequesterReplyNotification;
use App\Notifications\SlaOverdueNotification;
use App\Notifications\SupportReplyNotification;
use App\Notifications\TicketAssignedNotification;
use App\Notifications\TicketReopenedNotification;
use App\Notifications\TicketResolvedNotification;
use App\Notifications\TicketStatusChangedNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TicketWorkflowService
{
    public function __construct(
        protected TicketNumberGenerator $numberGenerator,
        protected AuditLogger $auditLogger,
        protected SlaCalculator $sla,
        protected AttachmentService $attachmentService,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Creation
    |--------------------------------------------------------------------------
    */

    public function create(User $actor, array $data, array $files = []): Ticket
    {
        return DB::transaction(function () use ($actor, $data, $files) {
            $priority = Priority::findOrFail($data['priority_id']);
            $openStatus = $this->requireStatus('open');

            $ticket = Ticket::create([
                'ticket_number' => $this->numberGenerator->next(),
                'requester_id' => $data['requester_id'],
                'created_by' => $actor->id,
                'department_id' => $data['department_id'],
                'category_id' => $data['category_id'],
                'priority_id' => $priority->id,
                'status_id' => $openStatus->id,
                'subject' => $data['subject'],
                'description' => $data['description'],
                'location' => $data['location'] ?? null,
                'device_type' => $data['device_type'] ?? null,
                'asset_number' => $data['asset_number'] ?? null,
                'contact_number' => $data['contact_number'] ?? null,
                'due_at' => now()->addHours($priority->sla_hours),
            ]);

            if (filled($files)) {
                $this->attachmentService->store($ticket, $files, $actor);
            }

            $this->track($ticket, ActivityType::Created, $actor,
                'Ticket created',
                null,
                ['ticket_number' => $ticket->ticket_number, 'subject' => $ticket->subject]);

            $this->auditLogger->log('ticket_created', $ticket,
                'Ticket '.$ticket->ticket_number.' created',
                null,
                $ticket->only(['ticket_number', 'requester_id', 'department_id', 'category_id', 'priority_id', 'subject']));

            $this->notifySupport($ticket, $actor, new NewTicketSupportNotification($ticket));
            $ticket->requester->notify(new NewTicketRequesterNotification($ticket));

            return $ticket;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Edits
    |--------------------------------------------------------------------------
    */

    /**
     * Apply an edit to a ticket's own fields.
     *
     * Status, priority and category have dedicated actions (and their own
     * policies) elsewhere in this service, so they are intentionally not
     * editable through this path. Only the descriptive fields are updated here,
     * which keeps the timeline and audit log honest.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $files
     */
    public function update(Ticket $ticket, User $actor, array $data, array $files = []): Ticket
    {
        return DB::transaction(function () use ($ticket, $actor, $data, $files) {
            $attributes = [
                'requester_id' => $data['requester_id'],
                'department_id' => $data['department_id'],
                'category_id' => $data['category_id'],
                'subject' => $data['subject'],
                'description' => $data['description'],
                'location' => $data['location'] ?? null,
                'device_type' => $data['device_type'] ?? null,
                'asset_number' => $data['asset_number'] ?? null,
                'contact_number' => $data['contact_number'] ?? null,
            ];

            $changed = [];

            foreach ($attributes as $key => $value) {
                if ($ticket->{$key} !== $value) {
                    $changed[$key] = ['from' => $ticket->{$key}, 'to' => $value];
                }
            }

            if ($changed !== []) {
                $ticket->fill($attributes)->save();

                $this->track($ticket, ActivityType::Updated, $actor,
                    'Ticket details updated',
                    null,
                    ['fields' => array_keys($changed)]);

                $this->auditLogger->log('ticket_updated', $ticket,
                    'Ticket '.$ticket->ticket_number.' details updated',
                    collect($changed)->map(fn ($change) => $change['from'])->all(),
                    collect($changed)->map(fn ($change) => $change['to'])->all());
            }

            if (filled($files)) {
                $this->attachmentService->store($ticket, $files, $actor);
            }

            if (isset($data['priority_id']) && (int) $data['priority_id'] !== $ticket->priority_id) {
                $this->changePriority($ticket, $actor, Priority::findOrFail((int) $data['priority_id']));
            }

            return $ticket;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Assignment
    |--------------------------------------------------------------------------
    */

    public function assign(Ticket $ticket, User $actor, User $assignee): void
    {
        $this->assertStaff($actor);

        if (! $assignee->is_active || ! $assignee->isStaff()) {
            throw WorkflowException::message('Tickets can only be assigned to an active support or administrator account.');
        }

        DB::transaction(function () use ($ticket, $actor, $assignee) {
            $wasUnassigned = $ticket->assigned_to === null;
            $oldStatus = $ticket->status;
            $ticket->assigned_to = $assignee->id;

            if ($wasUnassigned && in_array($oldStatus->key, ['open', 'reopened'], true)) {
                $this->applyStatus($ticket, $this->requireStatus('assigned'));
            }

            $ticket->save();

            $this->track($ticket, ActivityType::Assigned, $actor,
                'Assigned to '.$assignee->full_name,
                null,
                ['assigned_to' => $assignee->id, 'assigned_to_name' => $assignee->full_name]);

            $this->auditLogger->log('ticket_assigned', $ticket,
                'Ticket '.$ticket->ticket_number.' assigned to '.$assignee->full_name,
                ['assigned_to' => $oldStatus?->id],
                ['assigned_to' => $assignee->id]);

            $notification = new TicketAssignedNotification($ticket, $actor, $assignee);
            $assignee->notify($notification);
            $ticket->requester->notify($notification);
        });
    }

    public function unassign(Ticket $ticket, User $actor): void
    {
        $this->assertStaff($actor);

        if ($ticket->assigned_to === null) {
            throw WorkflowException::message('This ticket is not assigned.');
        }

        DB::transaction(function () use ($ticket, $actor) {
            $oldStatus = $ticket->status;
            $ticket->assigned_to = null;

            if (in_array($oldStatus->key, ['assigned', 'in_progress'], true)) {
                $this->applyStatus($ticket, $this->requireStatus('open'));
            }

            $ticket->save();

            $this->track($ticket, ActivityType::Unassigned, $actor,
                'Ticket unassigned',
                null,
                ['assigned_to' => null]);

            $this->auditLogger->log('ticket_unassigned', $ticket,
                'Ticket '.$ticket->ticket_number.' unassigned',
                ['assigned_to' => $ticket->getOriginal('assigned_to')],
                ['assigned_to' => null]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Manual status / priority / category changes
    |--------------------------------------------------------------------------
    */

    public function changeStatus(Ticket $ticket, User $actor, TicketStatus $status): void
    {
        $this->assertStaff($actor);

        $this->assertStatusChangeAllowed($ticket, $status, $actor);

        DB::transaction(function () use ($ticket, $actor, $status) {
            $old = $ticket->status;
            $this->applyStatus($ticket, $status);

            if ($status->type === TicketStatusType::Closed) {
                $ticket->closed_at = now();
                $ticket->save();
            }

            $this->track($ticket, ActivityType::StatusChanged, $actor,
                'Status changed from '.$old->name.' to '.$status->name,
                $old->key,
                ['status' => $status->key, 'status_name' => $status->name]);

            $this->auditLogger->log('ticket_status_changed', $ticket,
                'Ticket '.$ticket->ticket_number.' status changed from '.$old->name.' to '.$status->name,
                ['status_id' => $old->id, 'status' => $old->key],
                ['status_id' => $status->id, 'status' => $status->key]);

            $this->notifyStatusChange($ticket, $actor, $old, $status);
        });
    }

    public function changePriority(Ticket $ticket, User $actor, Priority $priority): void
    {
        $this->assertStaff($actor);

        DB::transaction(function () use ($ticket, $actor, $priority) {
            $old = $ticket->priority;

            if ($old->id === $priority->id) {
                return;
            }

            $shiftHours = $priority->sla_hours - $old->sla_hours;

            if ($shiftHours !== 0 && ! $this->sla->isPaused($ticket) && $ticket->due_at !== null) {
                $ticket->due_at = $ticket->due_at->addHours($shiftHours);
            }

            $ticket->priority_id = $priority->id;
            $ticket->save();

            $this->track($ticket, ActivityType::PriorityChanged, $actor,
                'Priority changed from '.$old->name.' to '.$priority->name,
                $old->key,
                ['priority' => $priority->key]);

            $this->auditLogger->log('ticket_priority_changed', $ticket,
                'Ticket '.$ticket->ticket_number.' priority changed from '.$old->name.' to '.$priority->name,
                ['priority_id' => $old->id, 'priority' => $old->key],
                ['priority_id' => $priority->id, 'priority' => $priority->key]);

            $this->notifyStatusChange($ticket, $actor, $old, $priority);
        });
    }

    public function changeCategory(Ticket $ticket, User $actor, Category $category): void
    {
        $this->assertStaff($actor);

        DB::transaction(function () use ($ticket, $actor, $category) {
            $old = $ticket->category;

            if ($old->id === $category->id) {
                return;
            }

            $ticket->category_id = $category->id;
            $ticket->save();

            $this->track($ticket, ActivityType::CategoryChanged, $actor,
                'Category changed from '.$old->name.' to '.$category->name,
                (string) $old->id,
                ['category_id' => $category->id, 'category_name' => $category->name]);

            $this->auditLogger->log('ticket_category_changed', $ticket,
                'Ticket '.$ticket->ticket_number.' category changed from '.$old->name.' to '.$category->name,
                ['category_id' => $old->id],
                ['category_id' => $category->id]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Replies & internal notes
    |--------------------------------------------------------------------------
    */

    public function reply(Ticket $ticket, User $actor, string $body, array $files = [], bool $internal = false): TicketMessage
    {
        if ($internal) {
            $this->assertStaff($actor);
        }

        return DB::transaction(function () use ($ticket, $actor, $body, $files, $internal) {
            if ($internal) {
                $message = $this->storeMessage($ticket, $actor, $body, MessageType::Internal, $files);

                $this->track($ticket, ActivityType::InternalNote, $actor,
                    'Internal note added',
                    null,
                    ['message_id' => $message->id],
                    true);

                $this->auditLogger->log('internal_note_added', $ticket,
                    'Internal note added to ticket '.$ticket->ticket_number);

                if ($ticket->assigned_to !== null && $ticket->assigned_to !== $actor->id) {
                    $assignee = $ticket->assignedUser;

                    if ($assignee && $assignee->is_active) {
                        $assignee->notify(new InternalNoteNotification($ticket, $message));
                    }
                }

                return $message;
            }

            $this->applyRequesterReplySideEffects($ticket, $actor);

            $message = $this->storeMessage($ticket, $actor, $body, MessageType::Public, $files);

            if ($actor->isStaff()) {
                $this->markFirstResponse($ticket);

                $this->track($ticket, ActivityType::SupportReply, $actor,
                    'Support replied',
                    null,
                    ['message_id' => $message->id]);

                $this->auditLogger->log('ticket_reply', $ticket,
                    'Support replied to ticket '.$ticket->ticket_number);

                $ticket->requester->notify(new SupportReplyNotification($ticket, $message));
            } else {
                $this->track($ticket, ActivityType::RequesterReply, $actor,
                    'Requester replied',
                    null,
                    ['message_id' => $message->id]);

                $this->auditLogger->log('ticket_requester_reply', $ticket,
                    'Requester replied to ticket '.$ticket->ticket_number);

                $this->notifyAssignable($ticket, $actor, new RequesterReplyNotification($ticket, $message));
            }

            return $message;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Resolve / close / reopen
    |--------------------------------------------------------------------------
    */

    public function resolve(Ticket $ticket, User $actor, string $resolutionMessage, array $files = []): TicketMessage
    {
        $this->assertStaff($actor);

        if ($ticket->is_resolved || $ticket->is_closed) {
            throw WorkflowException::message('This ticket is already resolved or closed.');
        }

        if (trim($resolutionMessage) === '') {
            throw WorkflowException::message('A resolution summary message is required.');
        }

        return DB::transaction(function () use ($ticket, $actor, $resolutionMessage, $files) {
            $message = $this->storeMessage($ticket, $actor, $resolutionMessage, MessageType::Public, $files);

            $old = $ticket->status;
            $this->applyStatus($ticket, $this->requireStatus('resolved'));
            $this->markFirstResponse($ticket);

            $this->track($ticket, ActivityType::Resolved, $actor,
                'Ticket marked as resolved',
                $old->key,
                ['status' => 'resolved']);

            $this->auditLogger->log('ticket_resolved', $ticket,
                'Ticket '.$ticket->ticket_number.' resolved');

            $ticket->requester->notify(new TicketResolvedNotification($ticket));

            return $message;
        });
    }

    public function close(Ticket $ticket, User $actor): void
    {
        $this->assertCloseAllowed($ticket, $actor);

        DB::transaction(function () use ($ticket, $actor) {
            $old = $ticket->status;
            $byRequester = $ticket->requester_id === $actor->id && ! $actor->isStaff();
            $closedStatus = $this->requireStatus('closed');

            $this->applyStatus($ticket, $closedStatus);
            $ticket->closed_at = now();
            $ticket->save();

            $description = $byRequester ? 'Ticket closed by requester' : 'Ticket closed';

            $this->track($ticket, ActivityType::Closed, $actor, $description, $old->key, ['status' => 'closed']);

            $this->auditLogger->log('ticket_closed', $ticket,
                'Ticket '.$ticket->ticket_number.' closed',
                ['status_id' => $old->id, 'status' => $old->key],
                ['status_id' => $closedStatus->id, 'status' => 'closed']);
        });
    }

    public function reopen(Ticket $ticket, User $actor, string $reason, array $files = []): TicketMessage
    {
        $this->assertReopenAllowed($ticket, $actor);

        if (trim($reason) === '') {
            throw WorkflowException::message('A reason explaining why the ticket is reopened is required.');
        }

        return DB::transaction(function () use ($ticket, $actor, $reason, $files) {
            $message = $this->storeMessage($ticket, $actor, $reason, MessageType::Public, $files);

            $old = $ticket->status;
            $this->applyStatus($ticket, $this->requireStatus('reopened'));
            $ticket->resolved_at = null;
            $ticket->closed_at = null;
            $ticket->due_at = now()->addHours($ticket->priority->sla_hours);
            $ticket->reopen_count = $ticket->reopen_count + 1;
            $ticket->save();

            $this->track($ticket, ActivityType::Reopened, $actor,
                'Ticket reopened',
                $old->key,
                ['status' => 'reopened']);

            $this->auditLogger->log('ticket_reopened', $ticket,
                'Ticket '.$ticket->ticket_number.' reopened');

            $this->notifyAssignable($ticket, $actor, new TicketReopenedNotification($ticket));

            return $message;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Scheduled tasks
    |--------------------------------------------------------------------------
    */

    /**
     * Send one SLA-overdue notification per ticket (uses overdue_notified_at).
     */
    public function notifyOverdue(): int
    {
        $tickets = Ticket::query()
            ->overdue()
            ->whereNull('overdue_notified_at')
            ->with(['status', 'assignedUser', 'requester'])
            ->limit(200)
            ->get();

        foreach ($tickets as $ticket) {
            DB::transaction(function () use ($ticket) {
                $ticket->update(['overdue_notified_at' => now()]);

                $recipients = $ticket->assigned_to !== null
                    ? collect([$ticket->assignedUser])
                    : $this->activeStaff();

                $notification = new SlaOverdueNotification($ticket);

                foreach ($recipients->filter(fn ($u) => $u && $u->is_active) as $recipient) {
                    $recipient->notify($notification);
                }

                foreach ($this->activeAdmins() as $admin) {
                    $admin->notify($notification);
                }
            });
        }

        return $tickets->count();
    }

    /**
     * Auto-close tickets resolved longer than auto_close_days (0 disables).
     */
    public function autoClose(): int
    {
        $days = (new SettingsService)->int('auto_close_days', 5);

        if ($days <= 0) {
            return 0;
        }

        $tickets = Ticket::query()
            ->whereHas('status', fn ($q) => $q->where('type', TicketStatusType::Resolved->value))
            ->where('resolved_at', '<', now()->subDays($days))
            ->limit(200)
            ->get();

        $systemActor = User::firstWhere('role', UserRole::Admin->value);

        foreach ($tickets as $ticket) {
            try {
                $this->close($ticket, $systemActor ?? $ticket->creator);
            } catch (\Throwable $e) {
                Log::warning('Auto-close failed for ticket '.$ticket->ticket_number, ['error' => $e->getMessage()]);
            }
        }

        return $tickets->count();
    }

    /*
    |--------------------------------------------------------------------------
    | Internals
    |--------------------------------------------------------------------------
    */

    protected function requireStatus(string $key): TicketStatus
    {
        $status = TicketStatus::byKey($key)->first();

        if ($status === null) {
            throw WorkflowException::message("The required system status \"{$key}\" is missing. Please re-run the reference data seeder.");
        }

        return $status;
    }

    protected function assertStaff(User $actor): void
    {
        if (! $actor->isStaff()) {
            throw WorkflowException::message('This action is only available to support staff.');
        }
    }

    protected function assertStatusChangeAllowed(Ticket $ticket, TicketStatus $status, User $actor): void
    {
        if ($ticket->is_closed) {
            throw WorkflowException::message('Closed tickets must be reopened before their status can be changed.');
        }

        if ($status->key === 'reopened') {
            throw WorkflowException::message('The "reopened" status is set automatically via the Reopen action.');
        }

        if ($status->key === 'assigned') {
            throw WorkflowException::message('The "assigned" status is set automatically by the Assign action.');
        }

        if ($status->key === 'in_progress' && $ticket->assigned_to === null) {
            throw WorkflowException::message('Assign a support staff member before moving this ticket to In Progress.');
        }

        if ($status->type === TicketStatusType::Resolved) {
            throw WorkflowException::message('Mark the ticket as resolved using the Resolve action instead.');
        }

        if ($status->type === TicketStatusType::Closed && ! $actor->isAdmin()) {
            if (! in_array($ticket->status->type, [TicketStatusType::Resolved], true)) {
                throw WorkflowException::message('Only resolved tickets may be closed directly. Administrators may close any ticket.');
            }
        }
    }

    protected function assertCloseAllowed(Ticket $ticket, User $actor): void
    {
        if ($ticket->is_closed) {
            throw WorkflowException::message('This ticket is already closed.');
        }

        $isRequestersOwnTicket = $ticket->requester_id === $actor->id;

        if (! $actor->isStaff() && ! $isRequestersOwnTicket) {
            throw WorkflowException::message('You can only close your own tickets.');
        }

        if (! $actor->isStaff() && $ticket->is_closed) {
            throw WorkflowException::message('Closed tickets cannot be closed again.');
        }

        if ($actor->isStaff() && ! $actor->isAdmin() && ! $ticket->is_resolved) {
            throw WorkflowException::message('Only resolved tickets may be closed by support staff. Administrators may close any ticket.');
        }
    }

    protected function assertReopenAllowed(Ticket $ticket, User $actor): void
    {
        if (! $ticket->is_resolved) {
            throw WorkflowException::message('Only resolved tickets can be reopened.');
        }

        if ($actor->isStaff()) {
            return;
        }

        if ($ticket->requester_id !== $actor->id) {
            throw WorkflowException::message('You can only reopen your own tickets.');
        }
    }

    /**
     * Apply the status side effects of a non-staff reply.
     *
     * A pending_user ticket only advances to in_progress when it already has an
     * assignee; an unowned ticket stays pending_user so that "in progress"
     * always implies a named owner.
     */
    protected function applyRequesterReplySideEffects(Ticket $ticket, User $actor): void
    {
        if ($actor->isStaff()) {
            return;
        }

        if ($ticket->is_closed) {
            throw WorkflowException::message('This ticket is closed. Use the Reopen action to bring it back into the queue.');
        }

        $status = $ticket->status;

        if ($status->key === 'pending_user' && $ticket->assigned_to !== null) {
            $this->applyStatus($ticket, $this->requireStatus('in_progress'));
            $this->track($ticket, ActivityType::StatusChanged, $actor,
                'Status changed from '.$status->name.' to '.$ticket->status->name,
                $status->key,
                ['status' => 'in_progress']);
        } elseif ($ticket->is_resolved) {
            $this->applyStatus($ticket, $this->requireStatus('reopened'));
            $ticket->resolved_at = null;
            $ticket->closed_at = null;
            $ticket->due_at = now()->addHours($ticket->priority->sla_hours);
            $ticket->reopen_count = $ticket->reopen_count + 1;
            $ticket->save();

            $this->track($ticket, ActivityType::Reopened, $actor,
                'Ticket reopened by requester reply',
                $status->key,
                ['status' => 'reopened']);
        }
    }

    /**
     * Apply a status change including SLA pause/resume bookkeeping.
     *
     * The cached `status` relation is dropped afterwards so that later reads
     * in the same request see the new status rather than the previous one.
     */
    protected function applyStatus(Ticket $ticket, TicketStatus $status): void
    {
        if ($ticket->status_id === $status->id) {
            return;
        }

        $old = $ticket->status;

        if ($old->pauses_sla && $ticket->sla_paused_at !== null) {
            $pausedSeconds = $ticket->sla_paused_at->diffInSeconds(now());

            if ($ticket->due_at !== null) {
                $ticket->due_at = $ticket->due_at->addSeconds($pausedSeconds);
            }

            $ticket->sla_paused_at = null;
        }

        if ($status->pauses_sla && $ticket->sla_paused_at === null) {
            $ticket->sla_paused_at = now();
        }

        $ticket->status_id = $status->id;
        $ticket->save();

        $ticket->unsetRelation('status');
    }

    protected function storeMessage(Ticket $ticket, User $actor, string $body, MessageType $type, array $files = []): TicketMessage
    {
        $message = TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $actor->id,
            'type' => $type->value,
            'body' => $body,
        ]);

        if (filled($files)) {
            $this->attachmentService->store($ticket, $files, $actor, $message);
        }

        return $message;
    }

    /**
     * Record the first public staff reply for first-response SLA metrics.
     */
    protected function markFirstResponse(Ticket $ticket): void
    {
        if ($ticket->first_response_at === null) {
            $ticket->first_response_at = now();
            $ticket->save();
        }
    }

    protected function track(
        Ticket $ticket,
        ActivityType $type,
        ?User $actor,
        string $description,
        ?string $oldValue = null,
        ?array $newValue = null,
        bool $internal = false
    ): TicketActivity {
        return TicketActivity::create([
            'ticket_id' => $ticket->id,
            'user_id' => $actor?->id,
            'type' => $type->value,
            'description' => $description,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'is_internal' => $internal,
            'created_at' => now(),
        ]);
    }

    /**
     * Notify all active support staff (respecting inactive + actor exclusion).
     */
    protected function notifySupport(Ticket $ticket, User $actor, $notification): void
    {
        $this->activeStaff()
            ->reject(fn ($u) => $u->id === $actor->id)
            ->each(fn ($u) => $u->notify($notification));
    }

    /**
     * Notify the assignee, or all active support staff when unassigned.
     */
    protected function notifyAssignable(Ticket $ticket, User $actor, $notification): void
    {
        if ($ticket->assigned_to !== null && $ticket->assigned_to !== $actor->id) {
            $assignee = $ticket->assignedUser;

            if ($assignee && $assignee->is_active) {
                $assignee->notify($notification);

                return;
            }
        }

        $this->notifySupport($ticket, $actor, $notification);
    }

    protected function notifyStatusChange(Ticket $ticket, User $actor, $from, $to): void
    {
        $notification = new TicketStatusChangedNotification($ticket, $from, $to);

        if ($ticket->requester_id !== $actor->id) {
            $ticket->requester->notify($notification);
        }

        if ($ticket->assigned_to !== null && $ticket->assigned_to !== $actor->id) {
            $assignee = $ticket->assignedUser;

            if ($assignee && $assignee->is_active) {
                $assignee->notify($notification);
            }
        }
    }

    public function activeStaff(): Collection
    {
        return User::query()
            ->whereIn('role', [UserRole::Support->value, UserRole::Admin->value])
            ->where('is_active', true)
            ->get();
    }

    public function activeAdmins(): Collection
    {
        return User::query()
            ->where('role', UserRole::Admin->value)
            ->where('is_active', true)
            ->get();
    }
}
