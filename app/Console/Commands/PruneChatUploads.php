<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Constants\ChatAttachments;
use App\Services\ChatAttachmentService;
use Illuminate\Console\Command;

/**
 * Remove chat attachments that were uploaded but never sent: the temporary
 * area keeps a member's file for ChatAttachments::TEMPORARY_TTL_HOURS.
 * Scheduled hourly in routes/console.php.
 */
class PruneChatUploads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chat:prune-uploads';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete chat uploads that were never sent with a message';

    /**
     * Execute the console command.
     */
    public function handle(ChatAttachmentService $attachments): int
    {
        $removed = $attachments->prune();

        $this->info(sprintf('Removed %d unsent chat upload%s older than %d hours.', $removed, $removed === 1 ? '' : 's', ChatAttachments::TEMPORARY_TTL_HOURS));

        return self::SUCCESS;
    }
}
