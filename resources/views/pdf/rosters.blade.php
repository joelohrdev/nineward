<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 36px 40px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #1C2430; }
        .sheet { page-break-after: always; }
        .sheet:last-child { page-break-after: auto; }
        .org { font-size: 9px; letter-spacing: 1px; text-transform: uppercase; color: #5A6472; }
        h1 { font-family: 'DejaVu Sans Condensed', sans-serif; font-weight: bold; font-size: 26px; text-transform: uppercase; margin: 4px 0 2px; }
        .meta { color: #5A6472; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 8px; letter-spacing: 1px; text-transform: uppercase; color: #5A6472; border-bottom: 2px solid #1C2430; padding: 4px 5px; }
        td { border-bottom: 1px solid #C9D3DC; padding: 6px 5px; vertical-align: top; }
        .jersey { width: 30px; font-family: 'DejaVu Sans Condensed', sans-serif; font-weight: bold; font-size: 14px; text-align: center; }
        .name { font-weight: bold; }
        .muted { color: #5A6472; }
        .rule { border-top: 3px solid #1C2430; margin-bottom: 8px; }
        .footer { margin-top: 14px; font-size: 8px; color: #5A6472; }
    </style>
</head>
<body>
@foreach ($sheets as $sheet)
    <div class="sheet">
        <div class="rule"></div>
        <div class="org">{{ $organization }}</div>
        <h1>{{ $sheet['team'] }}</h1>
        <div class="meta">
            {{ $sheet['division'] }} · {{ $sheet['season'] }} · {{ $sheet['coach'] ? 'Head coach '.$sheet['coach'] : 'No head coach' }} · {{ count($sheet['players']) }} players
        </div>
        <table>
            <thead>
                <tr>
                    <th class="jersey">#</th>
                    <th>Player</th>
                    <th>Born</th>
                    <th>Age</th>
                    <th>Pos</th>
                    <th>B/T</th>
                    @if ($includeContacts)
                        <th>Parents and guardians</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse ($sheet['players'] as $player)
                    <tr>
                        <td class="jersey">{{ $player['jersey'] }}</td>
                        <td class="name">{{ $player['name'] }}@if ($player['captain']) <span class="muted">(C)</span>@endif</td>
                        <td>{{ $player['born'] }}</td>
                        <td>{{ $player['age'] }}</td>
                        <td>{{ $player['position'] }}</td>
                        <td>{{ $player['batsThrows'] }}</td>
                        @if ($includeContacts)
                            <td class="muted">{{ $player['guardians'] }}</td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ $includeContacts ? 7 : 6 }}" class="muted">No players on this team yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="footer">Printed {{ $printedAt }}. Contains minors' personal information. Keep it private.</div>
    </div>
@endforeach
</body>
</html>
