<div class="help-topic">
  <h2>{{ $t['judul'] }}</h2>
  @if(!empty($t['peran']))<div style="margin-bottom:6px">@foreach($t['peran'] as $p)<span class="badge b-navy">{{ \App\Models\User::ROLES[$p] ?? $p }}</span> @endforeach</div>@endif
  <p class="lead">{{ $t['apa'] }}</p>
  @if(!empty($t['gambar']) && file_exists(public_path('img/bantuan/'.$t['gambar'])))
    <figure><img src="{{ asset('img/bantuan/'.$t['gambar']) }}" alt="Tangkapan layar {{ $t['judul'] }}" loading="lazy"><figcaption>Tangkapan layar: {{ $t['judul'] }}</figcaption></figure>
  @endif
  @if(!empty($t['kapan']))<div class="q">Kapan dilaksanakan</div><p>{{ $t['kapan'] }}</p>@endif
  @if(!empty($t['langkah']))<div class="q">Langkah input data</div><ol>@foreach($t['langkah'] as $s)<li>{!! $s !!}</li>@endforeach</ol>@endif
  @if(!empty($t['isian']))<div class="q">Arti setiap isian</div>
    <table class="t">@foreach($t['isian'] as $f => $d)<tr><td>{{ $f }}</td><td>{{ $d }}</td></tr>@endforeach</table>@endif
  @if(!empty($t['tips']))<div class="q">Tips & aturan sistem</div><ul>@foreach($t['tips'] as $s)<li>{!! $s !!}</li>@endforeach</ul>@endif
</div>
