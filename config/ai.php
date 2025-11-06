<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default AI Handler
    |--------------------------------------------------------------------------
    |
    | This option controls the default AI handler (provider and model) that
    | will be used by the application, in the format 'provider:model'.
    |
    | e.g., 'gemini:gemini-2.5-flash-lite', 'openai:gpt-4o-mini'
    */

    'default_handler' => env('AI_DEFAULT_HANDLER', 'gemini:gemini-2.5-flash-lite'),

    /*
    |--------------------------------------------------------------------------
    | Default Files for AI Context
    |--------------------------------------------------------------------------
    |
    | Here you may specify default files from `storage/app/public` that should
    | always be included in the context of AI generation requests. These paths
    | are relative to `storage/app/public`.
    |
    */
    'default_files' => array_filter(explode(',', env('AI_DEFAULT_FILES', ''))),

    /*
    |--------------------------------------------------------------------------
    | Default AI Profile
    |--------------------------------------------------------------------------
    | Here you may specify the default AI profile to be used
    | throughout the application, loaded from storage/app/public/json/profiles. 
    | This profile can define specific settings or behaviors for AI interactions.
    |
    */

    'default_profile' => env('AI_DEFAULT_PROFILE', 'assistant'),

    /*
    |--------------------------------------------------------------------------
    | Task-Specific AI Configuration
    |--------------------------------------------------------------------------
    |
    | Here you can define specific providers and models for different tasks.
    | If a task is not defined here or the value is null, it will fall back
    | to the `default_handler`.
    |
    | Supported tasks: 'chat', 'explanation', 'summarize', 'suggest'
    |
    */
    'tasks' => [
        'chat' => env('AI_CHAT_HANDLER'),
        'explanation' => env('AI_EXPLANATION_HANDLER'),
        'summarize' => env('AI_SUMMARIZE_HANDLER'),
        'suggest' => env('AI_SUGGEST_HANDLER'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Predefined Prompts
    |--------------------------------------------------------------------------
    | Here you may define some predefined prompts that can be used
    | throughout the application to standardize common AI requests.
    |
    */
    'prompts' => [
        'system_prompt' => env(
            'AI_SYSTEM_PROMPT',
            <<<PROMPT
            You are a helpful assistant specialized in web development, graphic design, 
            and related software for teams. Provide clear, concise, and accurate information
            to assist users with their queries in these domains. 
            PROMPT
        ),
        'explanation' => <<<PROMPT
            Leg kort (in niet al te veel woorden), en in zo eenvoudig mogelijke bewoordingen, 
            voor een leek (de lezer aan wie je dit uitlegt), uit wat :input betekent - in zover 
            relevant, met betrekking to web development, grafische vormgeving of aanverwante software 
            voor teams (je hoeft dit verder niet te benoemen)
            PROMPT,
        'summarize' => <<<PROMPT
            Geef een korte samenvatting (in niet al te veel woorden, maximaal enkele regels) van de volgende tekst 
            alsof ik het aan iemand vertel over mijn tekst : :input
            PROMPT,
        'suggest' => <<<PROMPT
            You are an assistant that suggests relevant next prompts for a user in a chat conversation.
            Based on the provided chat history and context, suggest up to 3 short, relevant follow-up questions or prompts.
            The suggestions should be things the user might want to ask next.
            The suggestions should be in the same language as the last user message in the history.
            Return a JSON object with a single key "suggestions" which is an array of strings. For example: {"suggestions": ["What is a closure?", "Explain promises.", "How do I use flexbox?"]}
            If you have no suggestions, the "suggestions" array should be empty.
            If the chat history is empty, provide some general conversation starters related to web development, graphic design, or team software.
            Do not add any other text, just the JSON object.
            Chat History:
            ---
            :history
            ---
            PROMPT,
    ],
];
