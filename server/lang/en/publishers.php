<?php

// Texts of the publish drivers. "<driver>.<field>" is the field label, "<field>_help" its help, "guide" the side box.
return [
    'opt_publish' => 'Publish now', 'opt_draft' => 'Draft', 'opt_pending' => 'Pending review', 'opt_private' => 'Private',
    'opt_summary' => 'Short (title + summary)', 'opt_full' => 'Full text',

    'webhook' => [
        'name' => 'Webhook (any app)',
        'url' => 'URL',
        'secret' => 'Secret for the signature',
        'secret_help' => 'Optional. When set, each request has an X-AW-Signature header.',
        'embed_images' => 'Put the image files in the request (base64)',
        'guide' => '<p>For your own software. We POST the same JSON as the service webhook to this URL.</p><p>Use this when you need more than one webhook for a service. See <b>API &amp; docs &gt; Webhook</b> for the format.</p>',
    ],
    'wordpress' => [
        'name' => 'WordPress',
        'site_url' => 'Site address',
        'site_url_help' => 'e.g. https://example.com',
        'username' => 'WordPress username',
        'app_password' => 'Application password',
        'app_password_help' => 'Not your normal password. Make one in WordPress: Users &gt; Profile &gt; Application Passwords.',
        'status' => 'Post status',
        'create_categories' => 'Create the category in WordPress if it does not exist',
        'guide' => '<ol><li>In WordPress, open <b>Users &gt; Profile</b>.</li><li>Under <b>Application Passwords</b>, type a name (e.g. "Article Writer") and click <b>Add</b>.</li><li>Copy the password here.</li></ol><p>We upload the images, set the first one as the featured image, put the others in the text, and set the category.</p><p>The site must allow the REST API (<code>/wp-json/</code>) and HTTPS.</p>',
    ],
    'telegram' => [
        'name' => 'Telegram',
        'bot_token' => 'Bot token',
        'bot_token_help' => 'From @BotFather, e.g. 123456:ABC-DEF...',
        'chat_id' => 'Channel or group',
        'chat_id_help' => '@channelname for public channels, or the numeric id (e.g. -1001234567890).',
        'mode' => 'What to send',
        'guide' => '<ol><li>Make a bot with <b>@BotFather</b> and copy its token.</li><li>Add the bot to your channel or group as an <b>admin</b> who can post.</li><li>Enter the channel name (@name) or the chat id.</li></ol><p>We send the cover image with the title and summary. With "Full text", the whole article follows in one or more messages.</p>',
    ],
    'instagram' => [
        'name' => 'Instagram',
        'ig_user_id' => 'Instagram business account ID',
        'access_token' => 'Access token (long-lived)',
        'caption_mode' => 'Caption',
        'graph_version' => 'Graph API version',
        'guide' => '<p>Needs an Instagram <b>Business or Creator</b> account that is linked to a Facebook page, and a Meta app with the <code>instagram_content_publish</code> permission.</p><p>Instagram needs <b>at least one image</b>. Use an Instagram image size (square 1080×1080 or portrait 1080×1350). 2 to 10 images are posted as a carousel.</p><p>The caption is the title, the summary (or the text) and hashtags from the keywords (max 2200 characters).</p>',
    ],
    'facebook' => [
        'name' => 'Facebook page',
        'page_id' => 'Page ID',
        'page_token' => 'Page access token',
        'page_token_help' => 'A long-lived Page token with pages_manage_posts.',
        'caption_mode' => 'Text',
        'graph_version' => 'Graph API version',
        'guide' => '<p>Posts to a Facebook <b>Page</b>. With images, the cover is posted as a photo with the text; without images, a text post.</p><p>Get a long-lived Page access token in the Meta developer tools (Graph API Explorer) with the <code>pages_manage_posts</code> permission.</p>',
    ],
    'linkedin' => [
        'name' => 'LinkedIn',
        'author_urn' => 'Author URN',
        'author_urn_help' => 'urn:li:person:xxxx (your profile) or urn:li:organization:xxxx (a company page).',
        'access_token' => 'Access token',
        'caption_mode' => 'Text',
        'api_version' => 'API version (YYYYMM)',
        'guide' => '<p>Posts to a LinkedIn profile or company page with the Posts API. The cover image is uploaded with the post.</p><p>Needs a LinkedIn app with <code>w_member_social</code> (profile) or <code>w_organization_social</code> (page). Tokens usually live 60 days; paste a new one when it expires.</p>',
    ],
];
