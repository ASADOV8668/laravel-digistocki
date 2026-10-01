<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-lg bg-error px-5 py-2.5 text-center text-sm font-medium text-white transition hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-200']) }}>
    {{ $slot }}
</button>
