/**
 * Máscaras de campos e autopreenchimento de endereço (ViaCEP).
 * Funciona sem dependências. Detecta os campos pelo atributo name
 * ou por data-mask="cnpj|cpf|phone|cep".
 */
(function () {
    'use strict';

    function onlyDigits(value) {
        return (value || '').replace(/\D/g, '');
    }

    var masks = {
        cnpj: function (v) {
            v = onlyDigits(v).slice(0, 14);
            return v
                .replace(/^(\d{2})(\d)/, '$1.$2')
                .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
                .replace(/\.(\d{3})(\d)/, '.$1/$2')
                .replace(/(\d{4})(\d)/, '$1-$2');
        },
        cpf: function (v) {
            v = onlyDigits(v).slice(0, 11);
            return v
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        },
        cep: function (v) {
            v = onlyDigits(v).slice(0, 8);
            return v.replace(/^(\d{5})(\d)/, '$1-$2');
        },
        phone: function (v) {
            v = onlyDigits(v).slice(0, 11);
            if (v.length <= 10) {
                return v
                    .replace(/^(\d{2})(\d)/, '($1) $2')
                    .replace(/(\d{4})(\d)/, '$1-$2');
            }
            return v
                .replace(/^(\d{2})(\d)/, '($1) $2')
                .replace(/(\d{5})(\d)/, '$1-$2');
        }
    };

    function maskTypeFor(input) {
        var explicit = input.getAttribute('data-mask');
        if (explicit && masks[explicit]) {
            return explicit;
        }
        var name = (input.getAttribute('name') || '').toLowerCase();
        if (name.indexOf('cnpj') !== -1) return 'cnpj';
        if (name.indexOf('cpf') !== -1) return 'cpf';
        if (name.indexOf('zip') !== -1 || name.indexOf('cep') !== -1) return 'cep';
        if (name.indexOf('whatsapp') !== -1 || name.indexOf('phone') !== -1 || name.indexOf('telefone') !== -1) return 'phone';
        return null;
    }

    function applyMask(input, type) {
        var apply = function () {
            var start = input.selectionStart;
            var before = input.value;
            input.value = masks[type](input.value);
            // mantém o cursor próximo ao fim em edições simples
            if (start !== null && input.value.length >= before.length) {
                try { input.setSelectionRange(input.value.length, input.value.length); } catch (e) {}
            }
        };
        input.addEventListener('input', apply);
        if (input.value) apply();
        var modes = { cnpj: 'numeric', cpf: 'numeric', cep: 'numeric', phone: 'tel' };
        if (!input.getAttribute('inputmode')) {
            input.setAttribute('inputmode', modes[type]);
        }
    }

    function fillByName(form, name, value) {
        if (!value) return;
        var field = form.querySelector('[name="' + name + '"]');
        if (field && !field.value) {
            field.value = value;
        }
    }

    function lookupCep(input) {
        var cep = onlyDigits(input.value);
        if (cep.length !== 8) return;
        var form = input.form;
        if (!form) return;

        input.classList.add('is-loading');
        fetch('https://viacep.com.br/ws/' + cep + '/json/')
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data || data.erro) return;
                fillByName(form, 'address', data.logradouro);
                fillByName(form, 'neighborhood', data.bairro);
                fillByName(form, 'city', data.localidade);
                fillByName(form, 'state', data.uf);
                // foca o número se estiver vazio
                var number = form.querySelector('[name="address_number"]');
                if (number && !number.value) number.focus();
            })
            .catch(function () { /* silencioso: usuário preenche manualmente */ })
            .finally(function () { input.classList.remove('is-loading'); });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var inputs = document.querySelectorAll('input');
        Array.prototype.forEach.call(inputs, function (input) {
            var type = maskTypeFor(input);
            if (!type) return;
            applyMask(input, type);
            if (type === 'cep') {
                input.addEventListener('blur', function () { lookupCep(input); });
            }
        });
    });
})();
