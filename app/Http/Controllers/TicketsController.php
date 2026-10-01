<?php

namespace App\Http\Controllers;

use App\Exceptions\WorkflowException;
use App\Http\Requests\Tickets\ReplyRequest;
use App\Http\Requests\Tickets\StoreTicketRequest;
use App\Http\Requests\Tickets\UpdateTicketRequest;
use App\Models\Category;
use App\Models\Department;
use App\Models\Priority;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketStatus;
use App\Models\User;
use App\Services\AttachmentService;
use App\Services\AuditLogger;
use App\Services\TicketNumberGenerator;
use App\Services\TicketWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketsController extends Controller
{
    public function __construct(
        protected TicketWorkflowService $workflow,
        protected AuditLogger $audit,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Creation
    |--------------------------------------------------------------------------
    */

    public function create(): View
    {
        $this->authorize('create', Ticket::class);

        $categories = Category::active()->with('children')->roots()->orderBy('sort_order')->get();
        $departments = Department::active()->orderBy('name')->get();

        $prioritiesQuery = Priority::query();

        if (! auth()->user()->isStaff()) {
            $prioritiesQuery->requesterSelectable();
        }

        $priorities = $prioritiesQuery->ordered()->get();

        $requesters = [];
        if (auth()->user()->isStaff()) {
            $requesters = User::query()
                ->where('role', 'requester')
                ->where('is_active', true)
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();
        }

        return view('tickets.create', [
            'categories' => $categories,
            'departments' => $departments,
            'priorities' => $priorities,
            'requesters' => $requesters,
        ]);
    }

    public function previewNumber(): JsonResponse
    {
        $this->authorize('create', Ticket::class);

        return response()->json([
            'ticket_number' => app(TicketNumberGenerator::class)->preview(),
        ]);
    }

    public function store(StoreTicketRequest $request): RedirectResponse
    {
        $this->authorize('create', Ticket::class);

        $ticket = $this->workflow->create($request->user(), $request->safe()->toArray(), $request->file('attachments', []));

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', 'Ticket '.$ticket->ticket_number.' was created successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | Edit
    |--------------------------------------------------------------------------
    */

    public function edit(Ticket $ticket): View
    {
        $this->authorize('update', $ticket);

        $categories = Category::active()->with('children')->roots()->orderBy('sort_order')->get();
        $departments = Department::active()->orderBy('name')->get();

        $prioritiesQuery = Priority::query();

        if (! auth()->user()->isStaff()) {
            $prioritiesQuery->requesterSelectable();
        }

        $priorities = $prioritiesQuery->ordered()->get();

        $requesters = [];
        if (auth()->user()->isStaff()) {
            $requesters = User::query()
                ->where('role', 'requester')
                ->where('is_active', true)
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();
        }

        return view('tickets.edit', [
            'ticket' => $ticket,
            'categories' => $categories,
            'departments' => $departments,
            'priorities' => $priorities,
            'requesters' => $requesters,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(UpdateTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('update', $ticket);

        try {
            $this->workflow->update(
                $ticket,
                $request->user(),
                $request->safe()->except('attachments'),
                $request->file('attachments', [])
            );
        } catch (WorkflowException $e) {
            return back()->withInput()->withErrors(['subject' => $e->getMessage()]);
        }

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', 'Ticket '.$ticket->ticket_number.' was updated successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | Lists
    |--------------------------------------------------------------------------
    */

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Ticket::class);

        $tickets = $this->filteredQuery($request)
            ->with(['requester', 'status', 'priority', 'category', 'assignedUser'])
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('tickets.all', [
            'tickets' => $tickets,
            'filters' => $this->filters(),
        ]);
    }

    public function myTickets(Request $request): View
    {
        $this->authorize('viewAny', Ticket::class);

        $user = auth()->user();

        $tickets = $this->filteredQuery($request)
            ->when($user->isStaff(), fn ($q) => $q->where('assigned_to', $user->id))
            ->when(! $user->isStaff(), fn ($q) => $q->where('requester_id', $user->id))
            ->with(['requester', 'status', 'priority', 'category', 'assignedUser'])
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('tickets.my-tickets', [
            'tickets' => $tickets,
            'filters' => $this->filters(),
        ]);
    }

    public function queue(Request $request): View
    {
        $this->authorize('viewAny', Ticket::class);

        $tickets = $this->filteredQuery($request)
            ->unresolved()
            ->with(['requester', 'status', 'priority', 'category', 'assignedUser'])
            ->orderByRaw('CASE WHEN assigned_to IS NULL THEN 0 ELSE 1 END')
            ->orderBy('due_at')
            ->paginate(25)
            ->withQueryString();

        return view('tickets.queue', [
            'tickets' => $tickets,
            'filters' => $this->filters(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Single ticket
    |--------------------------------------------------------------------------
    */

    public function show(Ticket $ticket): View
    {
        $this->authorize('view', $ticket);

        $ticket->load([
            'requester',
            'creator',
            'department',
            'category',
            'priority',
            'status',
            'assignedUser',
            'messages' => fn ($q) => $q->with(['user', 'attachments']),
            'activities' => fn ($q) => $q->with('user')
                ->when(! auth()->user()->isStaff(), fn ($query) => $query->where('is_internal', false)),
        ]);

        $messages = $ticket->messages->map(function ($message) {
            if (! auth()->user()->isStaff() && $message->is_internal) {
                return null;
            }

            return $message;
        })->filter();

        $statusChoices = TicketStatus::query()
            ->active()
            ->ordered()
            ->whereNotIn('key', ['resolved', 'reopened', 'assigned'])
            ->where(fn ($q) => auth()->user()->isAdmin()
                ? $q
                : $q->where('key', '!=', 'closed'))
            ->get();

        $staff = User::query()
            ->whereIn('role', ['support', 'admin'])
            ->where('is_active', true)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $priorities = Priority::active()->ordered()->get();
        $categories = Category::active()->with('children')->roots()->orderBy('sort_order')->get();

        return view('tickets.show', compact(
            'ticket', 'messages', 'statusChoices', 'staff', 'priorities', 'categories'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | Thread actions
    |--------------------------------------------------------------------------
    */

    public function reply(ReplyRequest $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('reply', $ticket);

        try {
            $this->workflow->reply(
                $ticket,
                $request->user(),
                $request->string('body'),
                $request->file('attachments', [])
            );
        } catch (WorkflowException $e) {
            return back()->withInput()->withErrors(['body' => $e->getMessage()]);
        }

        return back()->with('status', 'Your reply has been posted.');
    }

    public function internalNote(ReplyRequest $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('view', $ticket);

        if (! auth()->user()->isStaff()) {
            abort(403);
        }

        try {
            $this->workflow->reply(
                $ticket,
                $request->user(),
                $request->string('body'),
                $request->file('attachments', []), internal: true
            );
        } catch (WorkflowException $e) {
            return back()->withInput()->withErrors(['body' => $e->getMessage()]);
        }

        return back()->with('status', 'Internal note added.');
    }

    /*
    |--------------------------------------------------------------------------
    | Workflow
    |--------------------------------------------------------------------------
    */

    public function assign(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('assign', $ticket);

        $assignee = User::findOrFail($request->integer('assignee_id'));

        try {
            $this->workflow->assign($ticket, $request->user(), $assignee);
        } catch (WorkflowException $e) {
            return back()->withErrors(['assignee_id' => $e->getMessage()]);
        }

        return back()->with('status', 'Ticket assigned to '.$assignee->full_name.'.');
    }

    public function unassign(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('assign', $ticket);

        $this->workflow->unassign($ticket, $request->user());

        return back()->with('status', 'Ticket unassigned.');
    }

    public function changeStatus(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('manage', $ticket);

        $status = TicketStatus::active()->findOrFail($request->integer('status_id'));

        try {
            $this->workflow->changeStatus($ticket, $request->user(), $status);
        } catch (WorkflowException $e) {
            return back()->withErrors(['status_id' => $e->getMessage()]);
        }

        return back()->with('status', 'Status updated.');
    }

    public function changePriority(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('manage', $ticket);

        $priority = Priority::active()->findOrFail($request->integer('priority_id'));

        $this->workflow->changePriority($ticket, $request->user(), $priority);

        return back()->with('status', 'Priority updated.');
    }

    public function changeCategory(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('manage', $ticket);

        $category = Category::active()->findOrFail($request->integer('category_id'));

        $this->workflow->changeCategory($ticket, $request->user(), $category);

        return back()->with('status', 'Category updated.');
    }

    public function resolve(ReplyRequest $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('manage', $ticket);

        if (trim($request->string('body')) === '') {
            return back()->withInput()->withErrors(['body' => 'A resolution summary message is required.']);
        }

        try {
            $this->workflow->resolve(
                $ticket,
                $request->user(),
                $request->string('body'),
                $request->file('attachments', [])
            );
        } catch (WorkflowException $e) {
            return back()->withInput()->withErrors(['body' => $e->getMessage()]);
        }

        return back()->with('status', 'Ticket marked as resolved.');
    }

    public function close(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('manage', $ticket);

        try {
            $this->workflow->close($ticket, $request->user());
        } catch (WorkflowException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return back()->with('status', 'Ticket closed.');
    }

    public function reopen(ReplyRequest $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('reopen', $ticket);

        if (trim($request->string('body')) === '') {
            return back()->withInput()->withErrors(['body' => 'A reason explaining why the ticket is reopened is required.']);
        }

        try {
            $this->workflow->reopen(
                $ticket,
                $request->user(),
                $request->string('body'),
                $request->file('attachments', [])
            );
        } catch (WorkflowException $e) {
            return back()->withInput()->withErrors(['body' => $e->getMessage()]);
        }

        return back()->with('status', 'Ticket reopened.');
    }

    /*
    |--------------------------------------------------------------------------
    | Downloads
    |--------------------------------------------------------------------------
    */

    public function download(Ticket $ticket, TicketAttachment $attachment, AttachmentService $attachments): StreamedResponse
    {
        $this->authorize('view', $ticket);

        abort_unless($attachment->ticket_id === $ticket->id, 404);
        abort_unless($attachments->isVisibleTo($attachment, auth()->user()), 403);

        $this->audit->log(
            'attachment_downloaded',
            $ticket,
            'Attachment downloaded: '.$attachment->original_name
        );

        return $attachments->download($attachment);
    }

    /*
    |--------------------------------------------------------------------------
    | Shared helpers
    |--------------------------------------------------------------------------
    */

    protected function filteredQuery(Request $request)
    {
        return Ticket::query()
            ->visibleTo(auth()->user())
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = trim($request->string('q'));

                $query->where(function ($query) use ($term) {
                    $query
                        ->whereLike('ticket_number', $term)
                        ->orWhereLike('subject', $term)
                        ->orWhereLike('description', $term)
                        ->orWhereHas('requester', function ($query) use ($term) {
                            $query->whereLike('first_name', $term)
                                ->orWhereLike('last_name', $term)
                                ->orWhereLike('email', $term);
                        });
                });
            })
            ->when($request->filled('status'), fn ($query) => $query
                ->whereHas('status', fn ($q) => $q->where('key', $request->query('status'))))
            ->when($request->filled('priority'), fn ($query) => $query
                ->whereHas('priority', fn ($q) => $q->where('key', $request->query('priority'))))
            ->when($request->filled('category'), fn ($query) => $query
                ->where('category_id', $request->integer('category')))
            ->when($request->filled('department'), fn ($query) => $query
                ->where('department_id', $request->integer('department')))
            ->when($request->filled('start_date') || $request->filled('end_date'), function ($query) use ($request) {
                $start = $request->filled('start_date') ? $request->input('start_date') : null;
                $end = $request->filled('end_date') ? $request->input('end_date') : null;

                // A manually typed range can arrive reversed; order the ends so
                // the filter still returns the intended span. ISO dates compare
                // correctly as strings.
                if ($start && $end && $start > $end) {
                    [$start, $end] = [$end, $start];
                }

                $query
                    ->when($start, fn ($query) => $query->whereDate('created_at', '>=', $start))
                    ->when($end, fn ($query) => $query->whereDate('created_at', '<=', $end));
            });
    }

    protected function filters(): array
    {
        return [
            'statuses' => TicketStatus::active()->ordered()->get(),
            'priorities' => Priority::active()->ordered()->get(),
            'categories' => Category::active()->orderBy('name')->get(),
            'departments' => Department::active()->orderBy('name')->get(),
        ];
    }
}
