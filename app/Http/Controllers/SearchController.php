<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function index(Request $request): View
    {
        $term = trim($request->string('q'));

        if ($term === '') {
            return view('search.results', ['term' => '', 'tickets' => collect()]);
        }

        $tickets = Ticket::query()
            ->visibleTo(auth()->user())
            ->with(['requester', 'status', 'priority'])
            ->where(function ($query) use ($term) {
                $query
                    ->whereLike('ticket_number', $term)
                    ->orWhereLike('subject', $term)
                    ->orWhereLike('description', $term)
                    ->orWhereLike('asset_number', $term)
                    ->orWhereHas('requester', function ($query) use ($term) {
                        $query->whereLike('first_name', $term)
                            ->orWhereLike('last_name', $term)
                            ->orWhereLike('email', $term);
                    });
            })
            ->when(is_numeric($term), fn ($query) => $query->orWhere('id', (int) $term))
            ->latest()
            ->limit(50)
            ->get();

        return view('search.results', ['term' => $term, 'tickets' => $tickets]);
    }
}
