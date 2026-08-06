<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Formato de Personas Convencidas</title>
  <style>
    @page { margin:7mm 8mm; }
    * { box-sizing:border-box; }
    html, body { margin:0; padding:0; font-family:DejaVu Sans, sans-serif; color:#121212; font-size:8px; }
    .sheet { page-break-after:always; }
    .sheet:last-child { page-break-after:auto; }
    .header { height:42px; margin-bottom:4px; color:#fff; background:#7d1727; border-radius:3px; }
    .header-table { width:100%; border-collapse:collapse; }
    .header-table td { padding:5px 8px; vertical-align:middle; }
    .header-mark { width:22%; font-size:10px; font-weight:bold; letter-spacing:.8px; }
    .header-title { width:48%; text-align:center; font-size:19px; font-weight:bold; }
    .header-state { width:30%; }
    .state-box { padding:5px 7px; color:#111; background:#fff; border-radius:3px; }
    .label { display:block; margin-bottom:2px; color:#555; font-size:6px; font-weight:bold; text-transform:uppercase; }
    .value { display:block; min-height:13px; overflow:hidden; white-space:nowrap; font-size:8px; font-weight:bold; }
    .person { position:relative; height:64px; margin-bottom:3px; padding-left:28px; page-break-inside:avoid; }
    .number { position:absolute; top:0; bottom:0; left:0; width:24px; color:#fff; background:#8b1e2d; border:1px solid #6f1220; border-radius:3px; text-align:center; font-size:13px; font-weight:bold; line-height:62px; }
    .row { width:100%; border-collapse:collapse; table-layout:fixed; }
    .row td { height:30px; padding:2px 5px 1px; vertical-align:top; border:1px solid #2f2f2f; }
    .top-row { margin-bottom:2px; }
    .name { width:44%; }
    .phone { width:18%; }
    .elector { width:38%; }
    .address { width:69%; }
    .section { width:12%; }
    .flags { width:19%; padding-top:5px !important; white-space:nowrap; }
    .flag { display:inline-block; margin-right:6px; font-size:8px; font-weight:bold; }
    .dot { display:inline-block; width:8px; height:8px; margin-right:2px; vertical-align:-1px; border:1px solid #111; border-radius:50%; }
    .dot.checked { background:#111; }
    .mov-number { display:inline-block; min-width:29px; margin-left:1px; padding:0 2px 1px; border-bottom:1px solid #111; }
    .footer { margin-top:2px; color:#555; text-align:right; font-size:6px; }
  </style>
</head>
<body>
@php
  $hojas = $afiliados->chunk(10)->values();
@endphp
@foreach($hojas as $hojaIndex => $hoja)
  @php
    $hoja = $hoja->values();
  @endphp
  <section class="sheet">
    <div class="header">
      <table class="header-table">
        <tr>
          <td class="header-mark">REGISTRO TERRITORIAL</td>
          <td class="header-title">Formato de Personas Convencidas</td>
          <td class="header-state"><div class="state-box"><span class="label">Estado</span><span class="value">Michoacán</span></div></td>
        </tr>
      </table>
    </div>

    @for($slot = 0; $slot < 10; $slot++)
      @php
        $afiliado = $hoja->get($slot);
        $numero = $numeroInicial + ($hojaIndex * 10) + $slot;
        $nombreCompleto = $afiliado ? collect([$afiliado->nombre, $afiliado->apellido_paterno, $afiliado->apellido_materno])->filter()->implode(' ') : '';
        $direccion = $afiliado ? collect([
          $afiliado->calle,
          $afiliado->numero_ext ? 'N.º ext. '.$afiliado->numero_ext : null,
          $afiliado->numero_int ? 'Int. '.$afiliado->numero_int : null,
          $afiliado->colonia,
          $afiliado->cp ? 'C.P. '.$afiliado->cp : null,
          $afiliado->localidad,
          $afiliado->municipio,
        ])->filter()->implode(' · ') : '';
      @endphp
      <div class="person">
        <div class="number">{{ $afiliado ? $numero : '' }}</div>
        <table class="row top-row"><tr>
          <td class="name"><span class="label">Nombre(s) / paterno / materno</span><span class="value">{{ $nombreCompleto }}</span></td>
          <td class="phone"><span class="label">Teléfono / celular</span><span class="value">{{ optional($afiliado)->telefono }}</span></td>
          <td class="elector"><span class="label">Clave de elector</span><span class="value">{{ optional($afiliado)->clave_elector }}</span></td>
        </tr></table>
        <table class="row"><tr>
          <td class="address"><span class="label">Calle / N.º int-ext / colonia / C.P. / localidad / municipio</span><span class="value">{{ $direccion }}</span></td>
          <td class="section"><span class="label">Sección electoral</span><span class="value">{{ optional($afiliado)->seccion }}</span></td>
          <td class="flags">
            <span class="flag"><span class="dot {{ optional($afiliado)->tipo_vinculo === 'dv' ? 'checked' : '' }}"></span>DV</span>
            <span class="flag"><span class="dot {{ optional($afiliado)->tipo_vinculo === 'comite' ? 'checked' : '' }}"></span>Comité</span>
            <span class="flag"><span class="dot {{ optional($afiliado)->tipo_vinculo === 'mov' ? 'checked' : '' }}"></span>MOV #<span class="mov-number">{{ optional($afiliado)->numero_mov }}</span></span>
          </td>
        </tr></table>
      </div>
    @endfor

    <div class="footer">Página {{ $hojaIndex + 1 }} de {{ $hojas->count() }} del PDF · Página {{ $paginaListado }} del listado · Hasta {{ $perPage }} registros seleccionados</div>
  </section>
@endforeach
</body>
</html>
