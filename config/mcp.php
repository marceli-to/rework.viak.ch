<?php

// laravel/mcp: only the OAuth part is configured here ([[17-mcp]]). Dynamic
// client registration accepts claude.ai's callback addresses and nothing else.
// ChatGPT's (https://chatgpt.com, https://platform.openai.com) would go here
// too if VIAK ever connects it (#65).
return [
	'redirect_domains' => [
		'https://claude.ai',
		'https://claude.com',
	],

	'custom_schemes' => [],

	'authorization_server' => null,
];
