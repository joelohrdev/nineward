<?php

namespace App\Http\Controllers\Submissions;

use App\Enums\SubmissionStatus;
use App\Http\Controllers\Controller;
use App\Models\Submission;
use App\Support\CurrentOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DismissedSubmissionController extends Controller
{
    /**
     * Set a registration aside, for example spam or a test entry. The player
     * it created stays in the list until removed there.
     */
    public function store(Request $request, Submission $submission, CurrentOrganization $currentOrganization): RedirectResponse
    {
        Gate::authorize('manageSettings', $currentOrganization->get());

        $submission->forceFill([
            'status' => SubmissionStatus::Dismissed,
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()?->id,
        ])->save();

        return to_route('submissions.index');
    }
}
