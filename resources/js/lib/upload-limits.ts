export const MAX_FILE_BYTES = 600 * 1024 * 1024;
export const MAX_FILE_MESSAGE = 'File size cannot exceed 600 MB.';

export const TASK_ATTACHMENT_MAX_BYTES = MAX_FILE_BYTES;
export const TASK_ATTACHMENT_MAX_FILES = 10;
export const TASK_ATTACHMENT_ACCEPT =
    '.jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.csv,.ppt,.pptx,.zip,.txt,.rtf';

export const PROOF_IMAGE_MAX_BYTES = MAX_FILE_BYTES;
export const PROOF_VIDEO_MAX_BYTES = MAX_FILE_BYTES;
export const PROOF_DOCUMENT_MAX_BYTES = MAX_FILE_BYTES;
export const PROOF_ACCEPT =
    '.jpg,.jpeg,.png,.gif,.webp,.mp4,.mov,.webm,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.ai,.psd';

export const LOGO_MAX_BYTES = MAX_FILE_BYTES;
export const LOGO_ACCEPT = '.jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp';

export const DOCUMENT_MAX_BYTES = MAX_FILE_BYTES;
export const DOCUMENT_ACCEPT = '.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.ai,.psd';

export const CONTENT_ATTACHMENT_MAX_BYTES = MAX_FILE_BYTES;
export const CONTENT_ATTACHMENT_ACCEPT = PROOF_ACCEPT;

export const NOTIFICATION_SOUND_MAX_BYTES = MAX_FILE_BYTES;
export const NOTIFICATION_SOUND_ACCEPT = '.mp3,.wav,.ogg,audio/mpeg,audio/wav,audio/ogg';

export const DOCUMENTED_POST_MAX_BYTES = MAX_FILE_BYTES;

const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
const VIDEO_EXTENSIONS = ['mp4', 'mov', 'webm'];
const DOCUMENT_EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'ai', 'psd'];
const TASK_ATTACHMENT_EXTENSIONS = [
    ...IMAGE_EXTENSIONS,
    'pdf',
    'doc',
    'docx',
    'xls',
    'xlsx',
    'csv',
    'ppt',
    'pptx',
    'zip',
    'txt',
    'rtf',
];

export function fileExtension(filename: string): string {
    const parts = filename.toLowerCase().split('.');

    return parts.length > 1 ? (parts.at(-1) ?? '') : '';
}

export function sizeExceededMessage(_filename?: string): string {
    return MAX_FILE_MESSAGE;
}

export function combinedRequestExceededMessage(): string {
    return MAX_FILE_MESSAGE;
}

function fileTooLarge(file: File): boolean {
    return file.size > MAX_FILE_BYTES;
}

export function validateTaskAttachments(files: File[]): string | null {
    if (files.length === 0) {
        return null;
    }

    if (files.length > TASK_ATTACHMENT_MAX_FILES) {
        return `You can attach at most ${TASK_ATTACHMENT_MAX_FILES} files at a time.`;
    }

    let totalBytes = 0;

    for (const file of files) {
        const extension = fileExtension(file.name);

        if (!TASK_ATTACHMENT_EXTENSIONS.includes(extension)) {
            return `"${file.name}" is not an allowed working file type.`;
        }

        if (fileTooLarge(file)) {
            return sizeExceededMessage(file.name);
        }

        totalBytes += file.size;
    }

    if (totalBytes > DOCUMENTED_POST_MAX_BYTES) {
        return combinedRequestExceededMessage();
    }

    return null;
}

export function validateProofFiles(files: File[]): string | null {
    if (files.length === 0) {
        return null;
    }

    let totalBytes = 0;

    for (const file of files) {
        const extension = fileExtension(file.name);
        const allowed = [...IMAGE_EXTENSIONS, ...VIDEO_EXTENSIONS, ...DOCUMENT_EXTENSIONS];

        if (!allowed.includes(extension)) {
            return `"${file.name}" is not an allowed proof file type.`;
        }

        if (fileTooLarge(file)) {
            return sizeExceededMessage(file.name);
        }

        totalBytes += file.size;
    }

    if (totalBytes > DOCUMENTED_POST_MAX_BYTES) {
        return combinedRequestExceededMessage();
    }

    return null;
}

export function validateLogoFile(file: File): string | null {
    const extension = fileExtension(file.name);

    if (!IMAGE_EXTENSIONS.includes(extension)) {
        return `"${file.name}" is not an allowed logo file type.`;
    }

    if (fileTooLarge(file)) {
        return sizeExceededMessage(file.name);
    }

    return null;
}

export function validateDocumentFile(file: File): string | null {
    const extension = fileExtension(file.name);

    if (!DOCUMENT_EXTENSIONS.includes(extension)) {
        return `"${file.name}" is not an allowed document file type.`;
    }

    if (fileTooLarge(file)) {
        return sizeExceededMessage(file.name);
    }

    return null;
}

export function validateContentAttachments(files: File[]): string | null {
    return validateProofFiles(files);
}

export function validateNotificationSound(file: File): string | null {
    const extension = fileExtension(file.name);

    if (!['mp3', 'wav', 'ogg'].includes(extension)) {
        return `"${file.name}" is not an allowed notification sound type.`;
    }

    if (fileTooLarge(file)) {
        return sizeExceededMessage(file.name);
    }

    return null;
}
