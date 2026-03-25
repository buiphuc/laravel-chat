<?php

namespace PhucBui\Chat\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhucBui\Chat\Contracts\Repositories\ChatAttachmentRepositoryInterface;
use PhucBui\Chat\Models\ChatAttachment;
use PhucBui\Chat\Models\ChatMessage;

class AttachmentService
{
    public function __construct(
        protected ChatAttachmentRepositoryInterface $attachmentRepository,
    ) {
    }

    /**
     * Upload and attach a file to a message.
     */
    public function upload(ChatMessage $message, UploadedFile $file): ChatAttachment
    {
        $disk = config('chat.attachments.disk', 'public');
        $path = config('chat.attachments.path', 'chat-attachments');

        $this->validateFile($file);

        $filePath = $file->store($path, $disk);

        return $this->attachmentRepository->create([
            'message_id' => $message->id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);
    }

    /**
     * Delete an attachment.
     */
    public function delete(ChatAttachment $attachment): bool
    {
        $disk = config('chat.attachments.disk', 'public');
        Storage::disk($disk)->delete($attachment->file_path);

        return $this->attachmentRepository->delete($attachment);
    }

    /**
     * Validate file against config constraints.
     */
    protected function validateFile(UploadedFile $file): void
    {
        $maxSize = config('chat.attachments.max_size', 10240); // KB
        $allowedTypes = config('chat.attachments.allowed_types', []);

        // Check file size (convert KB to bytes)
        if ($file->getSize() > $maxSize * 1024) {
            throw new \RuntimeException("File size exceeds maximum allowed size of {$maxSize}KB.");
        }

        // Check MIME type against allowed patterns
        if (!empty($allowedTypes)) {
            $mimeType = $file->getMimeType();
            $allowed = false;

            foreach ($allowedTypes as $pattern) {
                if (str_contains($pattern, '*')) {
                    $regex = str_replace(['/', '*'], ['\/', '.*'], $pattern);
                    if (preg_match("/^{$regex}$/", $mimeType)) {
                        $allowed = true;
                        break;
                    }
                } elseif ($mimeType === $pattern) {
                    $allowed = true;
                    break;
                }
            }

            if (!$allowed) {
                throw new \RuntimeException("File type {$mimeType} is not allowed.");
            }
        }
    }
}
