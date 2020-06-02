<?xml version="1.0" encoding="UTF-8"?>
<div 
    xmlns="http://www.imsglobal.org/xsd/imsqti_v2p2"
    {{#if attributes}}{{{join attributes '=' ' ' '"'}}}{{/if}}>
    

    
    <div{{#if class}} class="{{class}}"{{/if}}>
        {{#if empty}}
            <div class="empty"></div>
        {{else}}
            {{{body}}}
        {{/if}}
    </div>
    
</div>
