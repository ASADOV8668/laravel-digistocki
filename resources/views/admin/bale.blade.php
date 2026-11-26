<x-admin-layout title="اتصال ربات بله">
    <section class="admin-card max-w-lg space-y-4 p-5">
        <h2 class="text-lg font-bold">حساب بله</h2>

        @if (session('status'))
            <p role="status">{{ session('status') }}</p>
        @endif

        @if ($connected)
            <p>حساب بله به حساب مدیریت شما متصل است.</p>
            <form method="POST" action="{{ route('admin.bale.destroy') }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-lg bg-red-700 px-4 py-2 font-bold text-white">قطع اتصال</button>
            </form>
        @else
            <p>در گفت‌وگوی خصوصی با ربات دیجی استوکی، دستور <bdi dir="ltr">/start</bdi> را بفرستید و کد دریافتی را وارد کنید.</p>
            <form method="POST" action="{{ route('admin.bale.store') }}" class="space-y-3">
                @csrf
                <label for="bale-code" class="block font-bold">کد اتصال</label>
                <input id="bale-code" name="code" dir="ltr" required maxlength="32" autocomplete="off"
                       spellcheck="false" class="block w-full rounded-lg border-slate-300">
                @error('code')
                    <p role="alert" class="text-red-700">{{ $message }}</p>
                @enderror
                <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 font-bold text-white">اتصال حساب</button>
            </form>
        @endif
    </section>
</x-admin-layout>
