<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Enums\MessageType;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Department;
use App\Models\Priority;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\TicketStatus;
use App\Models\User;
use App\Services\TicketNumberGenerator;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Sample demo data for local development and testing ONLY.
 *
 * WARNING: The demo accounts below are development defaults and must never
 * exist in a production environment.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new \RuntimeException('DemoSeeder refuses to run in a production environment.');
        }

        $admin = User::updateOrCreate(
            ['email' => 'admin@dccp-bangued.test'],
            [
                'first_name' => 'System',
                'last_name' => 'Administrator',
                'employee_id' => 'EMP-0001',
                'role' => UserRole::Admin,
                'password' => Hash::make('ChangeMe123!'),
                'is_active' => true,
                'account_status' => AccountStatus::Approved,
                'must_change_password' => false,
            ]
        );

        $support = User::updateOrCreate(
            ['email' => 'support@dccp-bangued.test'],
            [
                'first_name' => 'Technical',
                'last_name' => 'Support',
                'employee_id' => 'EMP-0002',
                'role' => UserRole::Support,
                'department_id' => Department::where('code', 'ICT')->value('id'),
                'password' => Hash::make('ChangeMe123!'),
                'is_active' => true,
                'account_status' => AccountStatus::Approved,
                'must_change_password' => false,
            ]
        );

        $juan = User::updateOrCreate(
            ['email' => 'juan@dccp-bangued.test'],
            [
                'first_name' => 'Juan',
                'last_name' => 'Dela Cruz',
                'employee_id' => 'EMP-0003',
                'role' => UserRole::Requester,
                'department_id' => Department::where('code', 'FIN')->value('id'),
                'password' => Hash::make('ChangeMe123!'),
                'is_active' => true,
                'account_status' => AccountStatus::Approved,
                'must_change_password' => false,
            ]
        );

        $this->seedSampleTickets($juan, $support);
    }

    protected function seedSampleTickets(User $requester, User $support): void
    {
        if (Ticket::exists()) {
            return;
        }

        $generator = new TicketNumberGenerator;
        $open = TicketStatus::byKey('open')->first();
        $inProgress = TicketStatus::byKey('in_progress')->first();
        $resolved = TicketStatus::byKey('resolved')->first();
        $closed = TicketStatus::byKey('closed')->first();
        $normal = Priority::byKey('normal')->first();
        $high = Priority::byKey('high')->first();
        $network = Category::where('name', 'Network')->first();
        $software = Category::where('name', 'Software')->first();
        $hardware = Category::where('name', 'Hardware')->first();
        $account = Category::where('name', 'Account')->first();

        $t1 = $this->createTicket($generator->next(), $requester, $support, $open, $normal,
            'Cannot connect to the office Wi-Fi',
            'Since this morning my laptop cannot connect to the office Wi-Fi network. It keeps asking for the password and then fails with "unable to connect".', $network,
            '3rd Floor, Finance Office');
        $this->message($t1, $requester, MessageType::Public, 'It started after I updated Windows this morning.');
        $this->message($t1, $support, MessageType::Public, 'We have restarted the access points on the 3rd floor. Could you try connecting again and let us know?');
        $this->message($t1, $support, MessageType::Internal, 'Probably the recent Windows update reset the network profile. Will re-add the profile on next visit.');

        $t2 = $this->createTicket($generator->next(), $requester, $support, $inProgress, $high,
            'Excel file becomes unresponsive on shared drive',
            'The monthly financial report xlsx on the shared drive freezes whenever I open it in Excel. It only happens on this workstation.',
            $software,
            '2nd Floor, Finance Office', 'Desktop', 'AST-2210', $support);
        $this->message($t2, $requester, MessageType::Public, 'The file is about 80 MB. It worked fine last month.');

        $t3 = $this->createTicket($generator->next(), $requester, $support, $resolved, $normal,
            'Printer shows "jam 0" without any paper jam',
            'The Canon printer near the Registrar office shows "Paper Jam 0" error message though there is no paper jam present.',
            $hardware,
            'Ground Floor, Registrar', 'Printer', 'AST-3311', null, now()->subDays(3));
        $this->message($t3, $support, MessageType::Public, 'We replaced the feed roller and cleared the error. The printer is working normally now.');
        $t3->update([
            'first_response_at' => now()->subDays(3)->addHours(2),
            'resolved_at' => now()->subDays(3)->addHours(6),
        ]);

        $t4 = $this->createTicket($generator->next(), $requester, $support, $closed, $normal,
            'Request for new office email account',
            'Newly hired teaching assistant needs an institutional email account for the Office of Academic Affairs.',
            $account,
            '1st Floor, Academic Affairs', null, null, null, now()->subDays(5));
        $t4->update([
            'resolved_at' => now()->subDays(5)->addHours(4),
            'closed_at' => now()->subDays(5)->addHours(5),
        ]);
        $this->message($t4, $support, MessageType::Public, 'Email account has been created and activated.');
    }

    protected function createTicket(
        string $number, User $requester, User $support, TicketStatus $status, Priority $priority,
        string $subject, string $description, ?Category $category, ?string $location = null,
        ?string $deviceType = null, ?string $assetNumber = null, ?User $assignee = null,
        ?CarbonInterface $createdAt = null
    ): Ticket {
        $createdAt ??= now();

        return Ticket::create([
            'ticket_number' => $number,
            'requester_id' => $requester->id,
            'created_by' => $requester->id,
            'department_id' => $requester->department_id,
            'category_id' => $category->id ?? Category::where('name', 'Other')->first()->id,
            'priority_id' => $priority->id,
            'status_id' => $status->id,
            'assigned_to' => $assignee?->id,
            'subject' => $subject,
            'description' => $description,
            'location' => $location,
            'device_type' => $deviceType,
            'asset_number' => $assetNumber,
            'contact_number' => $requester->contact_number,
            'due_at' => $createdAt->copy()->addHours($priority->sla_hours),
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    protected function message(Ticket $ticket, User $user, MessageType $type, string $body): void
    {
        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'type' => $type->value,
            'body' => $body,
        ]);
    }
}
