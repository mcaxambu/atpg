<?php

namespace App\Support\Radar;

/**
 * A API recusou a chave.
 *
 * Interrompe a varredura inteira de propósito: chave errada não é problema de
 * um item, e continuar gastaria uma chamada recusada por link coletado, toda
 * madrugada, sem nada na tela dizendo o motivo.
 */
class ChaveRecusada extends \RuntimeException {}
