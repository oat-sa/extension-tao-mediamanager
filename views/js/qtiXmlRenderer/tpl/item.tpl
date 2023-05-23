<?xml version="1.0" encoding="UTF-8"?>
<div
    {{#each namespaces}}{{#if @key}}xmlns:{{@key}}="{{.}}"{{else}}xmlns="{{.}}"{{/if}} {{/each}}
    {{#if attributes}}{{{join attributes '=' ' ' '"'}}}{{/if}}>
    {{#if empty}}
        <div class="empty"{{#if dir}} dir="{{dir}}"{{/if}}></div>
    {{else}}
        {{{body}}}
    {{/if}}
</div>
