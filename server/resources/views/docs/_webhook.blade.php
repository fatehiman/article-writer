{{-- Webhook instructions. Used on the docs page and in the "?" modal of the service form. --}}
<ol class="aw-doc-steps">
    <li>{!! __('ui.wh_step1') !!}</li>
    <li>{!! __('ui.wh_step2') !!}</li>
    <li>{!! __('ui.wh_step3') !!}</li>
    <li>{!! __('ui.wh_step4') !!}</li>
    <li>{!! __('ui.wh_step5') !!}</li>
</ol>

<h6 class="mt-3">{{ __('ui.wh_headers') }}</h6>
<table class="table table-sm aw-table small mb-3" dir="ltr">
    <tbody>
    <tr><td><code>Content-Type</code></td><td><code>application/json</code></td></tr>
    <tr><td><code>X-AW-Event</code></td><td><code>article.created</code></td></tr>
    <tr><td><code>X-AW-Delivery</code></td><td>{{ __('ui.wh_h_delivery') }}</td></tr>
    <tr><td><code>X-AW-Attempt</code></td><td>{{ __('ui.wh_h_attempt') }}</td></tr>
    <tr><td><code>X-AW-Timestamp</code></td><td>{{ __('ui.wh_h_timestamp') }}</td></tr>
    <tr><td><code>X-AW-Signature</code></td><td><code>t=&lt;timestamp&gt;,v1=&lt;hex&gt;</code> — HMAC-SHA256(<code>timestamp + "." + body</code>, secret)</td></tr>
    </tbody>
</table>

<h6>{{ __('ui.wh_body') }}</h6>
<pre class="aw-pre" dir="ltr">{{ $sample ?? \App\Http\Controllers\DocsController::sampleWebhook() }}</pre>

<h6 class="mt-3">{{ __('ui.wh_verify') }}</h6>
<pre class="aw-pre" dir="ltr">&lt;?php
$secret = 'whsec_...';                       // from the service page
$body   = file_get_contents('php://input');
$sig    = $_SERVER['HTTP_X_AW_SIGNATURE'] ?? '';
parse_str(str_replace(',', '&amp;', $sig), $p);  // t=..., v1=...
$ok = isset($p['t'], $p['v1'])
   &amp;&amp; abs(time() - (int) $p['t']) &lt; 600
   &amp;&amp; hash_equals(hash_hmac('sha256', $p['t'] . '.' . $body, $secret), $p['v1']);
if (!$ok) { http_response_code(401); exit; }

$event   = json_decode($body, true);
$article = $event['article'];
// Save $article['title'], $article['body_html_with_images'], $article['images'][...]['url'] ...
// Use $event['delivery']['id'] to ignore a delivery you already saved.
http_response_code(200);
echo 'ok';</pre>
