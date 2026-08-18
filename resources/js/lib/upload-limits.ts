export const TASK_ATTACHMENT_MAX_BYTES = 50 * 1024 * 1024;
export const TASK_ATTACHMENT_MAX_FILES = 10;
export const TASK_ATTACHMENT_ACCEPT =
    '.jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.csv,.ppt,.pptx,.zip,.txt,.rtf';

export const PROOF_IMAGE_MAX_BYTES = 20 * 1024 * 1024;
export const PROOF_VIDEO_MAX_BYTES = 100 * 1024 * 1024;
export const PROOF_DOCUMENT_MAX_BYTES = 50 * 1024 * 1024;
export const PROOF_ACCEPT =
    '.jpg,.jpeg,.png,.gif,.webp,.mp4,.mov,.webm,.pdf,.doc,.docx,.ai,.psd';

export const NOTIFICATION_SOUND_MAX_BYTES = 5 * 1024 * 1024;
export const NOTIFICATION_SOUND_ACCEPT = '.mp3,.wav,.ogg,audio/mpeg,audio/wav,audio/ogg';

export const DOCUMENTED_POST_MAX_BYTES = 150 * 1024 * 1024;

const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
const VIDEO_EXTENSIONS = ['mp4', 'mov', 'webm'];
const DOCUMENT_EXTENSIONS = ['pdf', 'doc', 'docx', 'ai', 'psd'];
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

export function sizeExceededMessage(filename: string, maxBytes: number): string {
    return `${filename} exceeds the maximum size of ${Math.round(maxBytes / (1024 * 1024))} MB.`;
}

export function combinedRequestExceededMessage(): string {
    return `The combined upload size exceeds the maximum request limit of ${Math.round(DOCUMENTED_POST_MAX_BYTES / (1024 * 1024))} MB.`;
}

function maxBytesForProofExtension(extension: string): number | null {
    if (IMAGE_EXTENSIONS.includes(extension)) {
        return PROOF_IMAGE_MAX_BYTES;
    }

    if (VIDEO_EXTENSIONS.includes(extension)) {
        return PROOF_VIDEO_MAX_BYTES;
    }

    if (DOCUMENT_EXTENSIONS.includes(extension)) {
        return PROOF_DOCUMENT_MAX_BYTES;
    }

    return null;
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

        if (file.size > TASK_ATTACHMENT_MAX_BYTES) {
            return sizeExceededMessage(file.name, TASK_ATTACHMENT_MAX_BYTES);
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
        const maxBytes = maxBytesForProofExtension(extension);

        if (maxBytes === null) {
            return `"${file.name}" is not an allowed proof file type.`;
        }

        if (file.size > maxBytes) {
            return sizeExceededMessage(file.name, maxBytes);
        }

        totalBytes += file.size;
    }

    if (totalBytes > DOCUMENTED_POST_MAX_BYTES) {
        return combinedRequestExceededMessage();
    }

    return null;
}

export function validateNotificationSound(file: File): string | null {
    const extension = fileExtension(file.name);

    if (!['mp3', 'wav', 'ogg'].includes(extension)) {
        return `"${file.name}" is not an allowed notification sound type.`;
    }

    if (file.size > NOTIFICATION_SOUND_MAX_BYTES) {
        return sizeExceededMessage(file.name, NOTIFICATION_SOUND_MAX_BYTES);
    }

    return null;
}
