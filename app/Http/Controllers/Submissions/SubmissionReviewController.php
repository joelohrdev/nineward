<?php

namespace App\Http\Controllers\Submissions;

use App\Actions\Submissions\ResolveSubmissionReview;
use App\Http\Controllers\Controller;
use App\Http\Requests\Submissions\StoreSubmissionReviewRequest;
use App\Models\Submission;
use App\Support\CurrentOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class SubmissionReviewController extends Controller
{
    /**
     * Decide whether a flagged registration is for a player already on file.
     */
    public function store(StoreSubmissionReviewRequest $request, Submission $submission, ResolveSubmissionReview $resolve, CurrentOrganization $currentOrganization): RedirectResponse
    {
        Gate::authorize('manageSettings', $currentOrganization->get());

        $resolve->handle($submission, $request->user(), $request->isSamePlayer());

        return to_route('submissions.show', $submission);
    }
}
