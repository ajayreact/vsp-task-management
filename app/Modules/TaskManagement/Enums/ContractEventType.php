<?php

namespace App\Modules\TaskManagement\Enums;

enum ContractEventType: string
{
    case Created = 'created';
    case Edited = 'edited';
    case VersionCreated = 'version_created';
    case PdfGenerated = 'pdf_generated';
    case Sent = 'sent';
    case SigningLinkGenerated = 'signing_link_generated';
    case Viewed = 'viewed';
    case Signed = 'signed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Downloaded = 'downloaded';
    case StoredInDocuments = 'stored_in_documents';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Draft created',
            self::Edited => 'Contract edited',
            self::VersionCreated => 'New version created',
            self::PdfGenerated => 'PDF generated',
            self::Sent => 'Sent to client',
            self::SigningLinkGenerated => 'Signing link generated',
            self::Viewed => 'Client viewed contract',
            self::Signed => 'Client signed contract',
            self::Rejected => 'Contract rejected',
            self::Cancelled => 'Contract cancelled',
            self::Downloaded => 'PDF downloaded',
            self::StoredInDocuments => 'Stored in Operations Documents',
        };
    }
}
