@php
    use App\Http\Middleware\ProtectPublicForm;
@endphp

{{--
    Proteção anti-spam dos formulários abertos. O campo de texto é invisível e
    fora da ordem de tabulação: só um robô o preenche. O timestamp descarta
    envios instantâneos demais para terem sido digitados por uma pessoa.
--}}
<div aria-hidden="true" style="position:absolute;left:-9999px;top:-9999px;height:0;overflow:hidden">
    <label>
        Não preencha este campo
        <input type="text" name="{{ ProtectPublicForm::HONEYPOT_FIELD }}" value="" tabindex="-1" autocomplete="off">
    </label>
</div>
<input type="hidden" name="{{ ProtectPublicForm::TIMESTAMP_FIELD }}" value="{{ time() }}">
