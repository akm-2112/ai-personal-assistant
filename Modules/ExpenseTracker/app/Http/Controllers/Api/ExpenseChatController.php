<?php

namespace Modules\ExpenseTracker\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\ExpenseTracker\Actions\HandleExpenseChatAction;
use Modules\ExpenseTracker\Http\Requests\SendExpenseChatRequest;

class ExpenseChatController extends Controller
{
    /**
     * Send a message to the expense chat agent via API.
     */
    public function send(
        SendExpenseChatRequest $request,
        HandleExpenseChatAction $handleExpenseChat,
    ): JsonResponse {
        $payload = $handleExpenseChat->handle(
            user: $request->user(),
            message: (string) $request->string('message'),
            conversationId: $request->string('conversation_id')->toString() ?: null,
        );

        return response()->json($payload);
    }
}
