<?xml version="1.0" encoding="UTF-8"?>
<div 
    {{#each namespaces}}{{#if @key}}xmlns:{{@key}}="{{.}}"{{else}}xmlns="{{.}}"{{/if}} {{/each}}
    {{#if attributes}}{{{join attributes '=' ' ' '"'}}}{{/if}}>
    <div{{#if class}} class="{{class}}"{{/if}}>
        {{#if empty}}
            <div class="empty"></div>
        {{else}}
            {{{body}}}
        {{/if}}
    </div>
    
</div>
