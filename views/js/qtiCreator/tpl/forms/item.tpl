{{#if languagesList}}
    <div class="panel">
        <label for="xml:lang">
            {{__ "Language"}}
        </label>
        <span class="icon-help tooltipstered" data-tooltip="~ .tooltip-content:first" data-tooltip-theme="info"></span>
        <span class="tooltip-content">
            {{__ "Define item language."}}
        </span>
        <select name="xml:lang" class="select2" data-has-search="false">
            {{#each languagesList}}
                <option value="{{@key}}"{{#equal @key ../xml:lang}} selected="selected"{{/equal}}>{{this}}</option>
            {{/each}}
        </select>
    </div>
{{/if}}