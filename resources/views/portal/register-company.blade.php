@extends('layouts.registration')

@section('title', 'Cadastro de empresa')

@section('content')
@php
    $settings = $siteSettings ?? [];
    $siteName = $settings['site_name'] ?? 'Portal Associação Tech PG';
    $siteTagline = $settings['site_tagline'] ?? 'Portal Associativo';
    $logoPath = $settings['logo_path'] ?? null;
    $brandLogo = $logoPath ? asset('storage/' . $logoPath) : asset('images/atpg-logo.png');
@endphp

<section class="registration-card">
    <div class="registration-top">
        <aside class="registration-aside">
            <a class="brand" href="{{ route('home') }}">
                <span class="brand-logo">
                    <img src="{{ $brandLogo }}" alt="{{ $siteName }}">
                </span>
                <span><strong>{{ str($siteName)->replace('Portal ', '') }}</strong><small>{{ $siteTagline }}</small></span>
            </a>

            <div class="aside-copy">
                <h2>Associe sua empresa ao ecossistema tech de Ponta Grossa.</h2>
                <p>Recebemos os dados, conferimos as informações e encaminhamos a solicitação para avaliação da associação.</p>
                <div class="aside-metrics">
                    <div><strong>48</strong><span>membros</span></div>
                    <div><strong>22</strong><span>empresas</span></div>
                    <div><strong>15</strong><span>áreas</span></div>
                </div>
            </div>

            <div class="aside-note">
                Este cadastro não cria senha e não libera acesso administrativo. Ele abre uma solicitação pública para análise e aprovação.
            </div>
        </aside>

        <div class="registration-main">
            <div class="form-heading">
                <span class="eyebrow">Cadastro de empresa</span>
                <h1>Cadastre sua empresa</h1>
                <p>Preencha as informações principais. Após aprovação, a empresa poderá aparecer no portal da associação.</p>
            </div>

            <div class="trust-grid">
                <article><strong>Análise manual</strong><span>A equipe valida os dados antes de publicar.</span></article>
                <article><strong>Perfil público</strong><span>Empresa aparece no diretório após aprovação.</span></article>
                <article><strong>Sem senha</strong><span>Cadastro simples, aberto e sem área restrita.</span></article>
            </div>

            @if (session('registration_success'))
                <div class="registration-success">
                    <strong>Cadastro realizado com sucesso.</strong>
                    <p>Recebemos os dados da empresa. A associação fará a conferência antes de publicar no portal.</p>
                    <div class="form-actions">
                        <a class="primary-button" href="{{ route('home') }}">Voltar ao inicio</a>
                        <a class="secondary-button" href="{{ route('companies.index') }}">Ver empresas</a>
                    </div>
                </div>
            @else
                @include('portal.admin.partials.errors')

                <div class="form-steps">
                    <div><span>1</span> Dados institucionais</div>
                    <div><span>2</span> Endereço e contato</div>
                    <div><span>3</span> Análise da associação</div>
                </div>

                <form class="company-form" method="post" action="{{ route('companies.register.store') }}" enctype="multipart/form-data">
                    @csrf
                    @include('portal.partials.form-guard')
                    <div class="company-form-card">
                        <div class="form-section-title">
                            <strong>Dados da empresa</strong>
                            <span>Todos os campos são obrigatórios.</span>
                        </div>
                        <div class="form-grid">
                            <label>Nome fantasia<input name="name" value="{{ old('name') }}" placeholder="Nome público da empresa" autocomplete="organization" required></label>
                            <label>Razão social<input name="legal_name" value="{{ old('legal_name') }}" placeholder="Razão social completa" required></label>
                            <label>CNPJ<input name="cnpj" value="{{ old('cnpj') }}" placeholder="00.000.000/0000-00" inputmode="numeric" autocomplete="off" data-mask="cnpj" required></label>
                            <label>Segmento<input name="segment" value="{{ old('segment') }}" placeholder="Ex: Desenvolvimento de sistemas" required></label>
                            <label>Site <small>opcional</small><input name="site_url" value="{{ old('site_url') }}" placeholder="https://empresa.com.br" inputmode="url" autocomplete="url"></label>
                            <label>
                                Logo da empresa
                                <div class="logo-uploader">
                                    <div class="logo-preview" data-logo-preview>Logo</div>
                                    <input name="logo" type="file" accept=".jpg,.jpeg,.png,.webp,.svg,image/*" required data-logo-input>
                                </div>
                            </label>
                        </div>

                        <div class="form-section-title">
                            <strong>Endereço</strong>
                            <span>Digite o CEP para preencher rua, bairro, cidade e UF.</span>
                        </div>
                        <div class="form-grid">
                            <label>CEP<input name="zip_code" value="{{ old('zip_code') }}" placeholder="84000-000" inputmode="numeric" autocomplete="postal-code" data-mask="cep" data-cep-lookup required><small class="field-hint" data-cep-status></small></label>
                            <label>Cidade<input name="city" value="{{ old('city', 'Ponta Grossa') }}" placeholder="Ponta Grossa" autocomplete="address-level2" required></label>
                            <label>UF<input name="state" value="{{ old('state', 'PR') }}" maxlength="2" placeholder="PR" autocomplete="address-level1" data-mask="uf" required></label>
                            <label>Bairro<input name="neighborhood" value="{{ old('neighborhood') }}" placeholder="Centro" autocomplete="address-level3" required></label>
                            <label>Endereço<input name="address" value="{{ old('address') }}" placeholder="Rua, avenida ou alameda" autocomplete="address-line1" required></label>
                            <label>Numero<input name="address_number" value="{{ old('address_number') }}" placeholder="123 ou S/N" autocomplete="address-line2" required></label>
                        </div>

                        <div class="form-section-title">
                            <strong>Responsável pelo cadastro</strong>
                            <span>Usaremos estes dados para contato sobre a aprovação.</span>
                        </div>
                        <div class="form-grid">
                            <label>Nome do responsável<input name="contact_name" value="{{ old('contact_name') }}" placeholder="Nome completo" autocomplete="name" required></label>
                            <label>Cargo do responsável<input name="contact_role" value="{{ old('contact_role') }}" placeholder="Ex: Diretor, socio, gerente" required></label>
                            <label>E-mail de contato<input name="email" type="email" value="{{ old('email') }}" placeholder="contato@empresa.com.br" inputmode="email" autocomplete="email" required></label>
                            <label>WhatsApp<input name="whatsapp" value="{{ old('whatsapp') }}" placeholder="(42) 99999-9999" inputmode="tel" autocomplete="tel" data-mask="phone" required></label>
                        </div>

                        <label>Descrição da empresa<textarea name="description" rows="5" placeholder="Descreva serviços, atuação, diferenciais e público atendido" required>{{ old('description') }}</textarea></label>
                        <label class="consent-check">
                            <input type="checkbox" name="privacy_consent" value="1" required @checked(old('privacy_consent'))>
                            <span>Autorizo o uso dos dados enviados para análise da associação e, após aprovação, publicação do perfil da empresa no portal.</span>
                        </label>
                        <div class="form-actions">
                            <button class="primary-button" type="submit">Enviar cadastro para análise</button>
                            <a class="secondary-button" href="{{ route('home') }}">Cancelar</a>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    (() => {
        const onlyDigits = (value) => value.replace(/\D/g, '');

        const masks = {
            cnpj(value) {
                return onlyDigits(value)
                    .slice(0, 14)
                    .replace(/^(\d{2})(\d)/, '$1.$2')
                    .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
                    .replace(/\.(\d{3})(\d)/, '.$1/$2')
                    .replace(/(\d{4})(\d)/, '$1-$2');
            },
            cep(value) {
                return onlyDigits(value).slice(0, 8).replace(/^(\d{5})(\d)/, '$1-$2');
            },
            phone(value) {
                const digits = onlyDigits(value).slice(0, 11);

                if (digits.length <= 10) {
                    return digits
                        .replace(/^(\d{2})(\d)/, '($1) $2')
                        .replace(/(\d{4})(\d)/, '$1-$2');
                }

                return digits
                    .replace(/^(\d{2})(\d)/, '($1) $2')
                    .replace(/(\d{5})(\d)/, '$1-$2');
            },
            uf(value) {
                return value.replace(/[^a-zA-Z]/g, '').slice(0, 2).toUpperCase();
            },
        };

        document.querySelectorAll('[data-mask]').forEach((field) => {
            const mask = masks[field.dataset.mask];
            if (!mask) return;

            field.value = mask(field.value);
            field.addEventListener('input', () => {
                field.value = mask(field.value);
            });
        });

        const logoInput = document.querySelector('[data-logo-input]');
        const logoPreview = document.querySelector('[data-logo-preview]');

        if (logoInput && logoPreview) {
            logoInput.addEventListener('change', () => {
                const file = logoInput.files?.[0];
                if (!file || !file.type.startsWith('image/')) return;

                const reader = new FileReader();
                reader.onload = () => {
                    logoPreview.innerHTML = `<img src="${reader.result}" alt="Preview da logo">`;
                };
                reader.readAsDataURL(file);
            });
        }

        const cepField = document.querySelector('[data-cep-lookup]');
        const status = document.querySelector('[data-cep-status]');

        if (!cepField) return;

        const setStatus = (message, state = '') => {
            if (!status) return;
            status.textContent = message;
            status.className = `field-hint ${state}`.trim();
        };

        const fillAddress = (data) => {
            const fields = {
                address: data.logradouro,
                neighborhood: data.bairro,
                city: data.localidade,
                state: data.uf,
            };

            Object.entries(fields).forEach(([name, value]) => {
                const field = document.querySelector(`[name="${name}"]`);
                if (field && value) field.value = value;
            });
        };

        let lastCep = '';

        const lookupCep = async () => {
            const cep = onlyDigits(cepField.value);
            if (cep.length !== 8 || cep === lastCep) return;

            lastCep = cep;
            setStatus('Buscando endereço...');

            try {
                const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`, {
                    headers: { Accept: 'application/json' },
                });
                const data = await response.json();

                if (!response.ok || data.erro) {
                    setStatus('CEP não encontrado. Preencha o endereço manualmente.', 'is-error');
                    return;
                }

                fillAddress(data);
                setStatus('Endereço preenchido pelo CEP.', 'is-success');

                const numberField = document.querySelector('[name="address_number"]');
                if (numberField) numberField.focus();
            } catch (error) {
                setStatus('Não foi possível consultar o CEP agora.', 'is-error');
            }
        };

        cepField.addEventListener('input', lookupCep);
        cepField.addEventListener('blur', lookupCep);
    })();
</script>
@endpush
