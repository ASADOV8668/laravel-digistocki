@foreach ($listings as $listing)
    <x-listing-card :listing="$listing" />
@endforeach
