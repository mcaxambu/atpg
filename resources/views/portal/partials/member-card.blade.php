<article class="member-card">
    <div class="member-topline">
        <div class="avatar" style="background: {{ $member->avatar_color }}">{{ $member->avatar_initials }}</div>
        <div>
            <h3>{{ $member->name }}</h3>
            <p>{{ $member->role }}</p>
        </div>
    </div>
    <div class="meta-list">
        <span>{{ $member->company?->name ?? 'Empresa independente' }}</span>
        <span>{{ $member->experience_years }} anos de profissao</span>
        <span>{{ $member->city }}</span>
    </div>
    <div class="chip-list">
        @foreach ($member->specialties as $specialty)
            <span>{{ $specialty->name }}</span>
        @endforeach
    </div>
    <a class="secondary-button" href="{{ route('members.show', $member->slug) }}">Ver perfil</a>
</article>
