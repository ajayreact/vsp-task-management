<?php

namespace App\Modules\TaskManagement\Services;

use App\Modules\TaskManagement\Enums\ContractCountry;
use App\Modules\TaskManagement\Models\Contract;
use App\Modules\TaskManagement\Models\ContractVersion;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class ContractPdfService
{
    /**
     * @return array{content: string, filename: string}
     */
    public function render(Contract $contract, ContractVersion $version, bool $includeSignaturePlaceholders = true, ?array $clientSignature = null): array
    {
        $contract->loadMissing(['company', 'createdBy']);
        $snapshot = $version->snapshot;
        $snapshot['document_logo'] = $this->documentLogoDataUri($version);

        $html = view('contracts.pdf.agreement', [
            'contract' => $contract,
            'version' => $version,
            'snapshot' => $snapshot,
            'country' => $contract->country,
            'includeSignaturePlaceholders' => $includeSignaturePlaceholders,
            'clientSignature' => $clientSignature,
        ])->render();

        $pdf = Pdf::loadHTML($html)->setPaper('a4');

        $filename = sprintf('%s - %s.pdf', $contract->contract_number, $contract->company->name);

        return [
            'content' => $pdf->output(),
            'filename' => $this->sanitizeFilename($filename),
        ];
    }

    public function downloadResponse(Contract $contract, ContractVersion $version, bool $signed = false): Response
    {
        $signature = $signed
            ? $contract->signatures()->latest('signed_at')->first()?->only([
                'signer_name', 'authorized_person', 'signature_type', 'signature_data', 'signed_at',
            ])
            : null;

        $rendered = $this->render(
            $contract,
            $version,
            includeSignaturePlaceholders: ! $signed,
            clientSignature: $signature,
        );

        return response($rendered['content'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$rendered['filename'].'"',
        ]);
    }

    public function inlineResponse(Contract $contract, ContractVersion $version): Response
    {
        $rendered = $this->render($contract, $version);

        return response($rendered['content'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$rendered['filename'].'"',
        ]);
    }

    protected function sanitizeFilename(string $filename): string
    {
        return preg_replace('/[^\w\s\-.()]/u', '', str_replace(['/', '\\'], '-', $filename)) ?: 'contract.pdf';
    }

    protected function documentLogoDataUri(ContractVersion $version): string
    {
        $media = $version->getFirstMedia('document_logo');

        if ($media !== null && is_file($media->getPath())) {
            return 'data:'.$media->mime_type.';base64,'.base64_encode((string) file_get_contents($media->getPath()));
        }

        $logo = (string) ($version->snapshot['document_logo'] ?? '');

        return str_starts_with($logo, 'data:image') ? $logo : '';
    }
}
