@foreach($panels as $panel)
    @if(($panel['title'] ?? '') !== '' || ($panel['body'] ?? '') !== '')
        <details class="home-about__panel">
            @if(($panel['title'] ?? '') !== '')
                <summary class="home-about__summary">{{ $panel['title'] }}</summary>
            @endif
            @if(($panel['body'] ?? '') !== '')
                <div class="home-about__body">{!! $panel['body'] !!}</div>
            @endif
        </details>
    @endif
@endforeach
