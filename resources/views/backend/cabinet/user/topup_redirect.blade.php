<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Переход к оплате…</title>
</head>
<body>
<p>Переходим на страницу оплаты банка…</p>
<form id="webpayment-form" method="POST" action="{{ $url }}" accept-charset="UTF-8">
    @foreach($fields as $name => $value)
        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
    @endforeach
    <noscript><button type="submit">Перейти к оплате</button></noscript>
</form>
<script>document.getElementById('webpayment-form').submit();</script>
</body>
</html>
