@props([
    'name' => 'date',
    'value' => '',
    'required' => false,
    'defaultToday' => true,
])

@php
    $englishDigits = static fn(string $input): string => strtr($input, [
        '۰' => '0',
        '۱' => '1',
        '۲' => '2',
        '۳' => '3',
        '۴' => '4',
        '۵' => '5',
        '۶' => '6',
        '۷' => '7',
        '۸' => '8',
        '۹' => '9',
        '٠' => '0',
        '١' => '1',
        '٢' => '2',
        '٣' => '3',
        '٤' => '4',
        '٥' => '5',
        '٦' => '6',
        '٧' => '7',
        '٨' => '8',
        '٩' => '9',
    ]);
    $parts = preg_split('/[\/-]/', $englishDigits((string) $value)) ?: [];
    $todayParts = explode('/', \App\Support\PersianDate::format(now()));
    $todayYear = (int) ($todayParts[0] ?? 1405);
    $todayMonth = (int) ($todayParts[1] ?? 1);
    $todayDay = (int) ($todayParts[2] ?? 1);
    $initialYear = filled($parts[0] ?? null) ? (int) $parts[0] : ($defaultToday ? $todayYear : '');
    $initialMonth = filled($parts[1] ?? null)
        ? ltrim($parts[1], '0')
        : ($defaultToday
            ? ltrim($todayParts[1] ?? '', '0')
            : '');
    $initialDay = filled($parts[2] ?? null)
        ? ltrim($parts[2], '0')
        : ($defaultToday
            ? ltrim($todayParts[2] ?? '', '0')
            : '');
    $initialYearNumber = $initialYear === '' ? null : (int) $initialYear;
    $initialMonthNumber = $initialMonth === '' ? null : (int) $initialMonth;
    $initialDayNumber = $initialDay === '' ? null : (int) $initialDay;
    $initialDateIsSelectable = $initialYear === '' || (
        $initialYearNumber >= $todayYear
        && $initialYearNumber <= $todayYear + 10
        && ($initialYearNumber > $todayYear
            || $initialMonthNumber > $todayMonth
            || ($initialMonthNumber === $todayMonth && $initialDayNumber >= $todayDay))
    );
    if (! $initialDateIsSelectable && $defaultToday) {
        $initialYear = $todayYear;
        $initialMonth = $todayMonth;
        $initialDay = $todayDay;
    }
    $year = old($name . '_year', $initialYear);
    $month = old($name . '_month', $initialMonth);
    $day = old($name . '_day', $initialDay);
    $currentYear = $todayYear;
    $idPrefix = str_replace('_', '-', $name);
    $months = [
        1 => 'فروردین',
        2 => 'اردیبهشت',
        3 => 'خرداد',
        4 => 'تیر',
        5 => 'مرداد',
        6 => 'شهریور',
        7 => 'مهر',
        8 => 'آبان',
        9 => 'آذر',
        10 => 'دی',
        11 => 'بهمن',
        12 => 'اسفند',
    ];
@endphp

<div class="grid grid-cols-3 gap-2" dir="rtl"
    x-data="{
        year: @js((string) $year),
        month: @js((string) $month),
        day: @js((string) $day),
        todayYear: {{ $todayYear }},
        todayMonth: {{ $todayMonth }},
        todayDay: {{ $todayDay }},
        isCurrentYear() { return Number(this.year) === this.todayYear; },
        isMonthDisabled(month) { return this.isCurrentYear() && Number(month) < this.todayMonth; },
        isDayDisabled(day) { return this.isCurrentYear() && Number(this.month) === this.todayMonth && Number(day) < this.todayDay; },
        normalize() {
            if (this.isCurrentYear() && Number(this.month) < this.todayMonth) this.month = String(this.todayMonth);
            if (this.isCurrentYear() && Number(this.month) === this.todayMonth && Number(this.day) < this.todayDay) this.day = String(this.todayDay);
        }
    }">
    <div>
        <label for="{{ $idPrefix }}-day" class="sr-only">روز</label>
        <select id="{{ $idPrefix }}-day" name="{{ $name }}_day" x-model="day" @change="normalize()" @required($required)
            class="{{ $attributes->get('class', 'public-select') }}">
            <option value="">روز</option>
            @for ($option = 1; $option <= 31; $option++)
                <option value="{{ $option }}" :disabled="isDayDisabled({{ $option }})" @selected((string) $day === (string) $option)>
                    {{ \App\Support\PersianNumber::digits($option) }}</option>
            @endfor
        </select>
    </div>
    <div>
        <label for="{{ $idPrefix }}-month" class="sr-only">ماه</label>
        <select id="{{ $idPrefix }}-month" name="{{ $name }}_month" x-model="month" @change="normalize()" @required($required)
            class="{{ $attributes->get('class', 'public-select') }}">
            <option value="">ماه</option>
            @foreach ($months as $option => $label)
                <option value="{{ $option }}" :disabled="isMonthDisabled({{ $option }})" @selected((string) $month === (string) $option)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="{{ $idPrefix }}-year" class="sr-only">سال</label>
        <select id="{{ $idPrefix }}-year" name="{{ $name }}_year" x-model="year" @change="normalize()" @required($required)
            class="{{ $attributes->get('class', 'public-select') }}">
            <option value="">سال</option>
            @for ($option = $currentYear; $option <= $currentYear + 10; $option++)
                <option value="{{ $option }}" @selected((string) $year === (string) $option)>
                    {{ \App\Support\PersianNumber::digits($option) }}</option>
            @endfor
        </select>
    </div>
</div>
