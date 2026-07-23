<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChatwootWebhookRequest extends FormRequest
{
    /**
     * Determine if the webhook caller is authorized.
     */
    public function authorize(): bool
    {
        $secret = config('chatwoot.webhook_secret');
        if ($secret) {
            // Verify custom header or token query param
            return $this->header('X-Chatwoot-Webhook-Secret') === $secret
                || $this->query('token') === $secret;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'event' => 'required|string',
            'content' => 'nullable|string',
            'message_type' => 'required|string',
            'conversation' => 'required|array',
            'conversation.id' => 'required|integer',
            'conversation.contact_inbox' => 'required|array',
            'conversation.contact_inbox.source_id' => 'required|string',
            'sender' => 'required|array',
        ];
    }
}
