import { Head } from '@inertiajs/react';
import { MessageSquareText } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { chat } from '@/routes';

export default function Chat() {
    return (
        <>
            <Head title="Chat" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <Card className="border-sidebar-border/70">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <MessageSquareText className="size-5" />
                            Assistant Chat
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="text-muted-foreground">
                        This page is ready for your module-aware chat UI.
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
