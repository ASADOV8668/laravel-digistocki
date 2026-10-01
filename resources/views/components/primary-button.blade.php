<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-lg bg-primary px-5 py-2.5 text-center text-sm font-medium text-white transition hover:bg-primary-600 focus:outline-none focus:ring-4 focus:ring-primary/30 focus:ring-offset-2']) }}>
    {{ $slot }}
</button>
