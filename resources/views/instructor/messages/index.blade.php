<x-dashboard-layout role="instructor" title="Chat rooms" description="Group chat rooms per course with your enrolled students.">
    @include('partials.chat-shell', [
        'rooms' => $rooms,
        'activeRoom' => $activeRoom,
        'chatBaseRoute' => $chatBaseRoute,
        'chatShowRoute' => $chatShowRoute,
        'chatSendRoute' => $chatSendRoute,
        'chatDeleteRoute' => 'instructor.messages.delete',
    ])
</x-dashboard-layout>
