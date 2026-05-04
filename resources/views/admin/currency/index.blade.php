@extends('layouts.admin')

@section('module-meta')
    Display price = USD list price × <code>rate_per_usd</code>.
@endsection

@section('content')
<form method="post" action="{{ route('admin.currency.save') }}">
    @csrf
    <div class="table-wrap">
        <table class="data-table">
            <thead>
            <tr>
                <th>Code</th>
                <th>Label</th>
                <th>Symbol</th>
                <th>Rate per 1 USD</th>
                <th>Active</th>
            </tr>
            </thead>
            <tbody>
            @foreach($rates as $r)
                <tr>
                    <td><strong>{{ $r->code }}</strong></td>
                    <td>{{ $r->label }}</td>
                    <td>{{ $r->symbol }}</td>
                    <td>
                        <input class="input-inline" type="text" name="rate_per_usd[{{ $r->id }}]" value="{{ $r->rate_per_usd }}">
                    </td>
                    <td>
                        <input type="checkbox" name="is_active[{{ $r->id }}]" value="1" @checked($r->is_active)>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="form-actions">
        <button class="btn-admin btn-admin--primary" type="submit">Save rates</button>
    </div>
</form>
@endsection
