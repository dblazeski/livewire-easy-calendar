<!DOCTYPE html>
<html lang="en" class="antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Documentation') - Livewire Calendar</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700;800&family=JetBrains+Mono:wght@400;500&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('docs-assets/docs.css') }}">

    @livewireStyles
    @stack('styles')
</head>
<body class="bg-docs-bg font-sans text-docs-text" x-data="{ sidebarOpen: false }">

    <div class="docs-stripe fixed left-0 right-0 top-0 z-50"></div>

    <div
        x-show="sidebarOpen"
        x-cloak
        x-transition:enter="transition-opacity duration-200 ease-out"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity duration-150 ease-in"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="sidebarOpen = false"
        class="fixed inset-0 z-30 bg-black/40 lg:hidden"
    ></div>

    <aside
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        class="fixed inset-y-0 left-0 z-40 w-72 bg-docs-sidebar pt-[3px] transition-transform duration-200 ease-out lg:translate-x-0"
    >
        @include('docs._nav')
    </aside>

    <button
        @click="sidebarOpen = !sidebarOpen"
        class="fixed left-4 top-5 z-50 flex size-10 items-center justify-center rounded-lg bg-docs-sidebar text-docs-nav shadow-lg lg:hidden"
        aria-label="Toggle navigation"
        type="button"
    >
        <svg x-show="!sidebarOpen" xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
        </svg>
        <svg x-show="sidebarOpen" x-cloak xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
    </button>

    <main class="docs-content min-h-dvh lg:ml-72">
        <div class="mx-auto max-w-4xl px-6 pb-16 pt-20 lg:px-12 lg:pt-12">
            @yield('content')
        </div>
    </main>

    @livewireScripts
    @stack('scripts')
</body>
</html>
