<?php

namespace App\Modules\TaskManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Enums\ContractEventType;
use App\Modules\TaskManagement\Enums\ContractStatus;
use App\Modules\TaskManagement\Exceptions\DeliverableShareException;
use App\Modules\TaskManagement\Http\Requests\ContractSignRequest;
use App\Modules\TaskManagement\Models\ContractShareLink;
use App\Modules\TaskManagement\Services\ContractEventLogger;
use App\Modules\TaskManagement\Services\ContractPdfService;
use App\Modules\TaskManagement\Services\ContractService;
use App\Modules\TaskManagement\Services\ContractShareLinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class ContractShareController extends Controller
{
    public function __construct(
        protected ContractShareLinkService $shareLinks,
        protected ContractService $contracts,
        protected ContractPdfService $pdf,
        protected ContractEventLogger $events,
    ) {}

    public function show(string $token): SymfonyResponse
    {
        return $this->handleShareRequest(
            fn () => $this->renderShow($this->shareLinks->resolveByToken($token)),
            ['identifier_type' => 'legacy_token', 'token_suffix' => substr($token, -8)],
        );
    }

    public function showShort(string $shortCode): SymfonyResponse
    {
        return $this->handleShareRequest(
            fn () => $this->renderShow($this->shareLinks->resolveByShortCode($shortCode)),
            ['identifier_type' => 'short_code', 'short_code' => $shortCode],
        );
    }

    public function sign(ContractSignRequest $request, string $token): SymfonyResponse
    {
        return $this->handleShareRequest(
            fn () => $this->processSign($this->shareLinks->resolveByToken($token), $request),
            ['identifier_type' => 'legacy_token', 'token_suffix' => substr($token, -8)],
        );
    }

    public function signShort(ContractSignRequest $request, string $shortCode): SymfonyResponse
    {
        return $this->handleShareRequest(
            fn () => $this->processSign($this->shareLinks->resolveByShortCode($shortCode), $request),
            ['identifier_type' => 'short_code', 'short_code' => $shortCode],
        );
    }

    public function pdf(string $token): SymfonyResponse
    {
        return $this->handleShareRequest(
            fn () => $this->renderPdf($this->shareLinks->resolveByToken($token)),
            ['identifier_type' => 'legacy_token', 'token_suffix' => substr($token, -8)],
        );
    }

    public function pdfShort(string $shortCode): SymfonyResponse
    {
        return $this->handleShareRequest(
            fn () => $this->renderPdf($this->shareLinks->resolveByShortCode($shortCode)),
            ['identifier_type' => 'short_code', 'short_code' => $shortCode],
        );
    }

    protected function renderShow(ContractShareLink $link): InertiaResponse
    {
        $contract = $link->contract;
        $this->shareLinks->markViewed($link);

        if (in_array($contract->status, [ContractStatus::SentToClient, ContractStatus::Generated], true)) {
            $contract->update(['status' => ContractStatus::Viewed]);
            $this->events->log($contract, ContractEventType::Viewed);
        }

        $contract->loadMissing(['company', 'currentVersion']);
        $snapshot = $contract->currentVersion?->snapshot ?? [];
        $provider = $snapshot['provider'] ?? config('contracts.provider');
        $client = $snapshot['client'] ?? [];
        $signed = $contract->status === ContractStatus::Signed;

        return Inertia::render('TaskManagement/contract-share/show', [
            'provider_name' => $provider['name'] ?? config('app.name'),
            'contract' => [
                'title' => $contract->title,
                'contract_number' => $contract->contract_number,
                'effective_date' => $contract->effective_date->toDateString(),
                'client_name' => $client['name'] ?? $contract->company->name,
                'status' => $contract->status->value,
                'status_label' => $contract->status->label(),
                'signed' => $signed,
            ],
            'snapshot' => $snapshot,
            'pdf_url' => $link->publicPdfUrl(),
            'sign_url' => $link->publicSignUrl(),
            'can_sign' => ! $signed && ! $link->isExpired() && ! $link->isRevoked(),
        ]);
    }

    protected function processSign(ContractShareLink $link, ContractSignRequest $request): RedirectResponse
    {
        $contract = $link->contract;

        if ($contract->status === ContractStatus::Signed) {
            return back()->with('error', 'This contract has already been signed.');
        }

        $this->contracts->signByClient($contract, $request->validated(), $request);

        $actor = User::query()->find($contract->created_by_user_id);

        if ($actor !== null) {
            if ($contract->currentVersion?->getFirstMedia('pdf') === null) {
                $this->contracts->generatePdf($contract, $actor);
            }

            $this->contracts->generatePdf($contract, $actor, signed: true);

            if ($contract->original_document_id === null) {
                $this->contracts->storeInOperationsDocuments($contract, $actor, signed: false);
            }

            $this->contracts->storeInOperationsDocuments($contract, $actor, signed: true);
        }

        return back()->with('success', 'Thank you. Your signature has been recorded.');
    }

    protected function renderPdf(ContractShareLink $link): SymfonyResponse
    {
        $contract = $link->contract;
        $contract->loadMissing(['company', 'currentVersion']);
        $version = $contract->currentVersion ?? abort(404);

        $signed = $contract->status === ContractStatus::Signed;
        $media = $version->getFirstMedia($signed ? 'signed_pdf' : 'pdf');

        if ($media !== null && is_file($media->getPath())) {
            return $media->toInlineResponse(request());
        }

        return $this->pdf->inlineResponse($contract, $version);
    }

    /**
     * @param  callable(): SymfonyResponse|InertiaResponse|RedirectResponse  $callback
     * @param  array<string, mixed>  $context
     */
    protected function handleShareRequest(callable $callback, array $context = []): SymfonyResponse|JsonResponse
    {
        try {
            $response = $callback();

            return $response instanceof SymfonyResponse
                ? $response
                : $response->toResponse(request());
        } catch (DeliverableShareException $exception) {
            return Inertia::render('TaskManagement/share/error', [
                'title' => $exception->title,
                'message' => $exception->getMessage(),
            ])->toResponse(request())->setStatusCode($exception->statusCode);
        } catch (Throwable $exception) {
            report($exception);

            return Inertia::render('TaskManagement/share/error', [
                'title' => 'Something went wrong',
                'message' => 'We could not open this contract link. Please contact the sender.',
            ])->toResponse(request())->setStatusCode(500);
        }
    }
}
