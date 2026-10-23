<?php

namespace App\Http\Controllers\Submissions;

use App\Enums\PaymentStatus;
use App\Enums\SubmissionStatus;
use App\Forms\SubmissionAnswers;
use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\Payment;
use App\Models\Submission;
use App\Support\CurrentOrganization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SubmissionController extends Controller
{
    public function __construct(private CurrentOrganization $currentOrganization) {}

    public function index(Request $request): Response
    {
        Gate::authorize('manageSettings', $this->currentOrganization->get());

        $status = $request->enum('status', SubmissionStatus::class);
        $formId = $request->integer('form') ?: null;
        $onlyUnpaid = $request->boolean('unpaid');

        $submissions = Submission::query()
            ->with(['form:id,title', 'payment', 'player' => fn ($query) => $query->select('id', 'first_name', 'last_name', 'deleted_at'), 'guardian:id,first_name,last_name'])
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->when($formId !== null, fn ($query) => $query->where('form_id', $formId))
            ->when($onlyUnpaid, fn ($query) => $query->whereHas('payment', fn ($payments) => $payments->whereIn('status', [
                PaymentStatus::Pending, PaymentStatus::Failed, PaymentStatus::Expired,
            ])))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Submission $submission): array => [
                'id' => $submission->id,
                'playerName' => $submission->player?->fullName(),
                'guardianName' => $submission->guardian?->fullName(),
                'formTitle' => $submission->form->title,
                'status' => $submission->status->value,
                'statusLabel' => $submission->status->label(),
                'submittedAt' => $submission->created_at?->toIso8601String(),
                'payment' => $this->paymentSummary($submission->payment),
            ]);

        return Inertia::render('submissions/index', [
            'submissions' => $submissions,
            'filters' => ['status' => $status?->value, 'form' => $formId, 'unpaid' => $onlyUnpaid],
            'formOptions' => Form::query()->orderBy('title')->orderBy('id')->get(['id', 'title'])
                ->map(fn (Form $form): array => ['id' => $form->id, 'title' => $form->title])->all(),
            'needsReviewCount' => Submission::query()->where('status', SubmissionStatus::NeedsReview)->count(),
        ]);
    }

    public function show(Submission $submission): Response
    {
        Gate::authorize('manageSettings', $this->currentOrganization->get());

        $submission->load(['form:id,title', 'formVersion', 'player', 'guardian', 'possibleDuplicate.guardians', 'reviewer:id,name', 'payment']);
        $stripeAccountId = $this->currentOrganization->get()?->stripeConnectAccount?->stripe_account_id;

        return Inertia::render('submissions/show', [
            'submission' => [
                'id' => $submission->id,
                'formTitle' => $submission->form->title,
                'formVersion' => $submission->formVersion->version,
                'status' => $submission->status->value,
                'statusLabel' => $submission->status->label(),
                'reviewReason' => $submission->review_reason,
                'submittedAt' => $submission->created_at?->toIso8601String(),
                'reviewedBy' => $submission->reviewer?->name,
                'reviewedAt' => $submission->reviewed_at?->toIso8601String(),
                'player' => $submission->player === null ? null : [
                    'id' => $submission->player->id,
                    'name' => $submission->player->fullName(),
                    'isRemoved' => $submission->player->trashed(),
                ],
                'guardianName' => $submission->guardian?->fullName(),
                'possibleDuplicate' => $submission->possibleDuplicate === null ? null : [
                    'id' => $submission->possibleDuplicate->id,
                    'name' => $submission->possibleDuplicate->fullName(),
                    'dateOfBirth' => $submission->possibleDuplicate->date_of_birth->toDateString(),
                    'guardians' => $submission->possibleDuplicate->guardians->map->fullName()->all(),
                ],
            ],
            'answers' => (new SubmissionAnswers($submission->formVersion, $submission->data))->readable(),
            'payment' => $submission->payment === null ? null : [
                ...$this->paymentSummary($submission->payment),
                'refundedCents' => $submission->payment->refunded_cents,
                'disputeStatus' => $submission->payment->dispute_status,
                'paidAt' => $submission->payment->paid_at?->toIso8601String(),
                // Refunds and disputes are handled in the organization's own Stripe Dashboard.
                'stripeUrl' => $stripeAccountId !== null && $submission->payment->stripe_payment_intent_id !== null
                    ? "https://dashboard.stripe.com/{$stripeAccountId}/payments/{$submission->payment->stripe_payment_intent_id}"
                    : null,
                'payUrl' => $submission->payment->status->isOutstanding() && $this->currentOrganization->get() !== null
                    ? $submission->payment->payUrl($this->currentOrganization->get())
                    : null,
            ],
        ]);
    }

    /**
     * @return array{amountCents: int, status: string, statusLabel: string}|null
     */
    private function paymentSummary(?Payment $payment): ?array
    {
        return $payment === null ? null : [
            'amountCents' => $payment->amount_cents,
            'status' => $payment->status->value,
            'statusLabel' => $payment->status->label(),
        ];
    }
}
