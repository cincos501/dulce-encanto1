<?php

declare(strict_types=1);

namespace App\Jobs;

use App\AI\Media\IncomingMediaResolver;
use App\AI\Orchestrators\ConversationOrchestrator;
use App\DTO\ChatwootMessageDTO;
use App\Services\ChatwootService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ProcessIncomingMessageJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected array $payload,
        protected float $dispatchedAt = 0.0
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        ConversationOrchestrator $orchestrator,
        IncomingMediaResolver $mediaResolver,
        ChatwootService $chatwoot
    ): void {
        $message = ChatwootMessageDTO::fromWebhook($this->payload);
        $phone = $message->phone;

        Log::info('ProcessIncomingMessageJob executing', [
            'conversation_id' => $message->conversationId,
            'phone' => $phone,
            'dispatched_at' => $this->dispatchedAt,
        ]);

        // 1. Ignore non-incoming messages
        if ($message->messageType !== 'incoming') {
            Log::info('Job ignored non-incoming Chatwoot message', ['type' => $message->messageType]);

            return;
        }

        // 2. Debounce Check: If a newer message arrived for this phone after this job was dispatched, skip execution
        if ($this->dispatchedAt > 0 && ! app()->environment('testing')) {
            $latestTime = (float) (Redis::get("chatwoot_last_time:{$phone}") ?: 0);
            if ($latestTime > ($this->dispatchedAt + 0.0001)) {
                Log::info("ProcessIncomingMessageJob debounced for {$phone}. A newer message arrived, skipping redundant AI call.", [
                    'job_dispatched_at' => $this->dispatchedAt,
                    'latest_message_time' => $latestTime,
                ]);

                return;
            }
        }

        // 3. Consolidate buffered messages into a single combined prompt
        $combinedText = $message->text;
        if (! app()->environment('testing')) {
            $bufferKey = "chatwoot_buffer:{$phone}";
            $bufferedMsgs = Redis::lrange($bufferKey, 0, -1);
            Redis::del($bufferKey);

            if (! empty($bufferedMsgs)) {
                $combinedText = implode("\n", array_values(array_unique(array_filter($bufferedMsgs))));
            }
        }

        // 3b. Resolve buffered media (audio -> transcription, image/sticker/location -> text)
        $mediaRefs = [];
        if (! app()->environment('testing')) {
            $mediaKey = "chatwoot_media_buffer:{$phone}";
            foreach (Redis::lrange($mediaKey, 0, -1) as $raw) {
                $decoded = json_decode((string) $raw, true);
                if (is_array($decoded) && isset($decoded['kind'])) {
                    $mediaRefs[] = $decoded;
                }
            }
            Redis::del($mediaKey);
        }

        // Fallback (tests / Redis desactivado): usar los adjuntos del payload actual.
        if ($mediaRefs === [] && $message->hasMedia) {
            $mediaRefs = $message->attachmentRefs;
        }

        // Dedupe por URL / coordenadas.
        if ($mediaRefs !== []) {
            $seen = [];
            $unique = [];
            foreach ($mediaRefs as $ref) {
                $signature = ($ref['url'] ?? '').'|'.($ref['latitude'] ?? '').'|'.($ref['longitude'] ?? '');
                if (isset($seen[$signature])) {
                    continue;
                }
                $seen[$signature] = true;
                $unique[] = $ref;
            }
            $mediaRefs = $unique;

            $resolved = $mediaResolver->resolve($mediaRefs);

            if (! $resolved->isEmpty()) {
                $combinedText = trim($combinedText."\n\n".$resolved->promptText());
            }

            foreach ($resolved->privateNotes as $note) {
                try {
                    $chatwoot->sendPrivateNote($message->conversationId, $note);
                } catch (\Throwable $e) {
                    Log::warning('No se pudo enviar la nota interna de adjunto', ['error' => $e->getMessage()]);
                }
            }
        }

        // Salvaguarda: nunca enviar un turno vacío al LLM.
        if (trim($combinedText) === '') {
            $combinedText = '[El cliente envió un adjunto sin texto. Salúdalo y ofrécele ayuda con nuestros postres, tortas y pedidos.]';
        }

        $combinedMessage = new ChatwootMessageDTO(
            conversationId: $message->conversationId,
            phone: $message->phone,
            senderName: $message->senderName,
            text: trim($combinedText),
            messageType: $message->messageType,
            rawPayload: $message->rawPayload,
            attachmentRefs: $message->attachmentRefs,
            hasMedia: $message->hasMedia,
        );

        // 4. Delegate processing to the ConversationOrchestrator
        $orchestrator->handle($combinedMessage);
    }
}
