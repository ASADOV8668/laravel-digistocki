@props([
    'name' => 'date',
    'value' => '',
    'required' => false,
    'defaultToday' => true,
])

@php
    $englishDigits = static fn (string $input): string => strtr($input, [
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
    ]);
    $parts = preg_split('/[\/-]/', $englishDigits((string) $value)) ?: [];
    $todayParts = explode('/', \App\Support\PersianDate::format(now()));
    $initialYear = filled($parts[0] ?? null) ? $parts[0] : ($defaultToday ? ($todayParts[0] ?? '') : '');
    $initialMonth = filled($parts[1] ?? null) ? ltrim($parts[1], '0') : ($defaultToday ? ltrim($todayParts[1] ?? '', '0') : '');
    $initialDay = filled($parts[2] ?? null) ? ltrim($parts[2], '0') : ($defaultToday ? ltrim($todayParts[2] ?? '', '0') : '');
    $year = old($name.'_year', $initialYear);
    $month = old($name.'_month', $initialMonth);
    $day = old($name.'_day', $initialDay);
    $currentYear = (int) ($todayParts[0] ?? 1405);
    $idPrefix = str_replace('_', '-', $name);
    $months = [
        1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد', 4 => 'تیر',
        5 => 'مرداد', 6 => 'شهریور', 7 => 'مهر', 8 => 'آبان',
        9 => 'آذر', 10 => 'دی', 11 => 'بهمن', 12 => 'اسفند',
    ];
@endphp

<div class="grid grid-cols-3 gap-2" dir="rtl">
    <div>
        <label for="{{ $idPrefix }}-day" class="sr-only">روز</label>
        <select id="{{ $idPrefix }}-day" name="{{ $name }}_day" @required($required) class="{{ $attributes->get('class', 'public-select') }}">
            <option value="">روز</option>
            @for ($option = 1; $option <= 31; $option++)
                <option value="{{ $option }}" @selected((string) $day === (string) $option)>{{ \App\Support\PersianNumber::digits($option) }}</option>
            @endfor
        </select>
    </div>
    <div>
        <label for="{{ $idPrefix }}-month" class="sr-only">ماه</label>
        <select id="{{ $idPrefix }}-month" name="{{ $name }}_month" @required($required) class="{{ $attributes->get('class', 'public-select') }}">
            <option value="">ماه</option>
            @foreach ($months as $option => $label)
                <option value="{{ $option }}" @selected((string) $month === (string) $option)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="{{ $idPrefix }}-year" class="sr-only">سال</label>
        <select id="{{ $idPrefix }}-year" name="{{ $name }}_year" @required($required) class="{{ $attributes->get('class', 'public-select') }}">
            <option value="">سال</option>
            @for ($option = 1350; $option <= $currentYear; $option++)
                <option value="{{ $option }}" @selected((string) $year === (string) $option)>{{ \App\Support\PersianNumber::digits($option) }}</option>
            @endfor
        </select>
    </div>
</div>
