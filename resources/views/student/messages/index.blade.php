<x-dashboard-layout role="student" title="Chat rooms" description="Course group chat with your instructor and classmates.">
    @include('partials.chat-shell', [
        'rooms' => $rooms,
        'activeRoom' => $activeRoom,
        'chatBaseRoute' => $chatBaseRoute,
        'chatShowRoute' => $chatShowRoute,
        'chatSendRoute' => $chatSendRoute,
        'chatEditRoute' => 'student.messages.edit',
        'chatDeleteRoute' => 'student.messages.delete',
    ])
</x-dashboard-layout>
