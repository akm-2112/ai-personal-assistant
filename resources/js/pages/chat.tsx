import { FormEvent, useMemo, useState } from 'react';
import { Head } from '@inertiajs/react';
import { LoaderCircle, MessageSquareText, Plus, SendHorizontal } from 'lucide-react';
import { send as sendExpenseChat } from '@/actions/Modules/ExpenseTracker/Http/Controllers/Web/ExpenseChatController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { chat } from '@/routes';

type ChatRole = 'user' | 'assistant';

type ChatMessage = {
    id: string;
    role: ChatRole;
    content: string;
};

type ChatResponsePayload = {
    reply: string;
    conversation_id: string | null;
};

export default function Chat() {
    const [message, setMessage] = useState('');
    const [conversationId, setConversationId] = useState<string | null>(null);
    const [isSending, setIsSending] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [messages, setMessages] = useState<ChatMessage[]>([]);

    const canSend = useMemo(() => message.trim().length > 0 && !isSending, [isSending, message]);

    const createMessage = (role: ChatRole, content: string): ChatMessage => ({
        id: crypto.randomUUID(),
        role,
        content,
    });

    const sendMessage = async (event: FormEvent<HTMLFormElement>): Promise<void> => {
        event.preventDefault();

        const trimmedMessage = message.trim();

        if (trimmedMessage.length === 0 || isSending) {
            return;
        }

        setIsSending(true);
        setError(null);
        setMessages((previous) => [...previous, createMessage('user', trimmedMessage)]);
        setMessage('');

        try {
            const endpoint = sendExpenseChat();
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

            const response = await fetch(endpoint.url, {
                method: endpoint.method.toUpperCase(),
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    message: trimmedMessage,
                    conversation_id: conversationId,
                }),
            });

            if (!response.ok) {
                throw new Error('Unable to send chat message right now.');
            }

            const payload: ChatResponsePayload = await response.json();

            setConversationId(payload.conversation_id);
            setMessages((previous) => [...previous, createMessage('assistant', payload.reply)]);
        } catch {
            setError('Chat request failed. Please try again.');
        } finally {
            setIsSending(false);
        }
    };

    const startNewConversation = (): void => {
        setConversationId(null);
        setMessages([]);
        setError(null);
    };

    return (
        <>
            <Head title="Chat" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <Card className="border-sidebar-border/70">
                    <CardHeader>
                        <div className="flex items-center justify-between gap-3">
                            <CardTitle className="flex items-center gap-2 text-base">
                                <MessageSquareText className="size-5" />
                                Expense Assistant Chat
                            </CardTitle>
                            <Button type="button" variant="outline" size="sm" onClick={startNewConversation}>
                                <Plus className="mr-1 size-4" />
                                New Chat
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="max-h-[420px] space-y-2 overflow-y-auto rounded-lg border border-border bg-background p-3">
                            {messages.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    Ask things like: “How much did I spend this month?” or “What did I spend yesterday?”
                                </p>
                            ) : (
                                messages.map((entry) => (
                                    <div
                                        key={entry.id}
                                        className={[
                                            'max-w-[85%] rounded-lg px-3 py-2 text-sm',
                                            entry.role === 'user'
                                                ? 'ml-auto bg-primary text-primary-foreground'
                                                : 'bg-muted text-foreground',
                                        ].join(' ')}
                                    >
                                        {entry.content}
                                    </div>
                                ))
                            )}
                        </div>

                        <form onSubmit={sendMessage} className="space-y-2">
                            <div className="flex gap-2">
                                <Input
                                    value={message}
                                    onChange={(event) => setMessage(event.target.value)}
                                    placeholder="Add expense or ask about your spending..."
                                    disabled={isSending}
                                />
                                <Button type="submit" disabled={!canSend}>
                                    {isSending ? (
                                        <LoaderCircle className="size-4 animate-spin" />
                                    ) : (
                                        <SendHorizontal className="size-4" />
                                    )}
                                </Button>
                            </div>
                            <InputError message={error ?? undefined} />
                            {conversationId ? (
                                <p className="text-xs text-muted-foreground">Conversation: {conversationId}</p>
                            ) : null}
                        </form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

Chat.layout = {
    breadcrumbs: [
        {
            title: 'Chat',
            href: chat(),
        },
    ],
};
