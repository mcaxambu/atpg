@if ($errors->any())
    <div class="error-message">
        <strong>Revise os campos abaixo.</strong>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
